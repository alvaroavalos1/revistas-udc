<?php
require_once '../config/session.php';
require_once '../config/db.php';
assert($pdo instanceof PDO);
require_once '../config/r2.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$mensaje = '';
$error   = '';

if (isset($_GET['estado']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $estado = $_GET['estado'];
    if (in_array($estado, ['borrador', 'publicada', 'archivada'])) {
        $stmt = $pdo->prepare('UPDATE revistas SET estado = ?, publicada_en = ? WHERE id = ?');
        $stmt->execute([$estado, $estado === 'publicada' ? date('Y-m-d H:i:s') : null, $id]);
    }
    header('Location: revistas.php');
    exit;
}

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $pdo->prepare('DELETE FROM revistas WHERE id = ?')->execute([$id]);
    header('Location: revistas.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $categoria   = (int)$_POST['categoria_id'];
    $estado      = $_POST['estado'];

    $portada_url = null;
    if (!empty($_FILES['portada']['name'])) {
        $ext = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));
        $key = 'portadas/portada_' . uniqid() . '.' . $ext;
        $portada_url = upload_to_r2($_FILES['portada']['tmp_name'], $key, $_FILES['portada']['type']);
    }

    $pdf_url = null;
    if (!empty($_FILES['pdf']['name'])) {
        $key = 'pdfs/revista_' . uniqid() . '.pdf';
        $pdf_url = upload_to_r2($_FILES['pdf']['tmp_name'], $key, 'application/pdf');
    }

    if ($titulo && $categoria) {
        $stmt = $pdo->prepare('INSERT INTO revistas (categoria_id, subida_por, titulo, descripcion, portada_url, pdf_url, estado, publicada_en) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$categoria, $_SESSION['usuario_id'], $titulo, $descripcion, $portada_url, $pdf_url, $estado, $estado === 'publicada' ? date('Y-m-d H:i:s') : null]);
        $mensaje = 'Revista guardada correctamente';
    } else {
        $error = 'El título y la categoría son obligatorios';
    }
}

$categorias = $pdo->query('SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre_es')->fetchAll(PDO::FETCH_ASSOC);
$revistas   = $pdo->query('
    SELECT r.*, c.nombre_es AS categoria, u.nombre AS autor
    FROM revistas r
    JOIN categorias c ON r.categoria_id = c.id
    JOIN usuarios u ON r.subida_por = u.id
    ORDER BY r.creado_en DESC
')->fetchAll(PDO::FETCH_ASSOC);

$con_ingles = $pdo->query('SELECT revista_id FROM revistas_en')->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Revistas — Panel UDC</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background: #f4f6fa; display: flex; min-height: 100vh; }
    .sidebar { width: 220px; background: #003B7A; display: flex; flex-direction: column; flex-shrink: 0; min-height: 100vh; }
    .sb-logo { padding: 22px 18px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .sb-logo-brand { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
    .sb-dot { width: 8px; height: 8px; border-radius: 50%; background: #F5C518; flex-shrink: 0; }
    .sb-logo h2 { color: #fff; font-size: 15px; font-weight: 500; }
    .sb-logo p  { color: rgba(255,255,255,0.4); font-size: 11px; margin-top: 2px; padding-left: 16px; }
    .sb-section { padding: 16px 18px 4px; font-size: 10px; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.06em; }
    .sb-link { display: flex; align-items: center; gap: 10px; padding: 10px 18px; font-size: 13px; color: rgba(255,255,255,0.65); text-decoration: none; border-left: 3px solid transparent; }
    .sb-link:hover { background: rgba(255,255,255,0.07); color: #fff; }
    .sb-link.active { background: rgba(245,197,24,0.12); color: #F5C518; border-left-color: #F5C518; }
    .sb-link i { font-size: 17px; }
    .sb-bottom { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding: 10px 0; }
    .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .topbar { background: #fff; border-bottom: 3px solid #F5C518; padding: 0 24px; height: 54px; display: flex; align-items: center; justify-content: space-between; }
    .topbar h1 { font-size: 16px; color: #003B7A; font-weight: 500; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .btn-preview { display: flex; align-items: center; gap: 6px; padding: 7px 14px; background: #EBF3FB; border: 1px solid #b8d4ef; border-radius: 8px; font-size: 13px; color: #003B7A; text-decoration: none; font-weight: 500; }
    .btn-preview:hover { background: #d6e8f7; }
    .user-info { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #555; }
    .avatar { width: 34px; height: 34px; border-radius: 50%; background: #003B7A; color: #F5C518; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; }
    .badge-rol { background: #FEF9E7; color: #003B7A; border: 1px solid #F5C518; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 500; }
    .content { padding: 24px; flex: 1; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
    .top-bar h2 { font-size: 14px; font-weight: 500; color: #1a202c; }
    .btn-nueva { display: flex; align-items: center; gap: 6px; padding: 9px 18px; background: #003B7A; color: #fff; border: none; border-radius: 8px; font-size: 13px; cursor: pointer; font-weight: 500; }
    .btn-nueva:hover { background: #00306a; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 100; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal { background: #fff; border-radius: 14px; padding: 28px; width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; border-top: 4px solid #F5C518; }
    .modal h2 { font-size: 16px; font-weight: 500; margin-bottom: 20px; color: #003B7A; }
    label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; margin-top: 14px; }
    input[type=text], textarea, select { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; font-family: Arial, sans-serif; }
    input[type=text]:focus, textarea:focus, select:focus { border-color: #003B7A; }
    textarea { resize: vertical; min-height: 80px; }
    input[type=file] { font-size: 13px; }
    .modal-footer { display: flex; gap: 10px; margin-top: 22px; justify-content: flex-end; }
    .btn-cancel { padding: 9px 18px; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; cursor: pointer; font-size: 13px; color: #555; }
    .btn-save { padding: 9px 20px; background: #003B7A; color: #fff; border: none; border-radius: 8px; font-size: 13px; cursor: pointer; font-weight: 500; }
    .btn-save:hover { background: #00306a; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
    .card { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; transition: box-shadow 0.15s; }
    .card:hover { box-shadow: 0 4px 16px rgba(0,59,122,0.1); }
    .card-img { width: 100%; height: 130px; background: #EBF3FB; display: flex; align-items: center; justify-content: center; font-size: 40px; overflow: hidden; }
    .card-img img { width: 100%; height: 100%; object-fit: cover; }
    .card-body { padding: 12px; }
    .card-title { font-size: 13px; font-weight: 500; color: #1a202c; margin-bottom: 3px; }
    .card-cat { font-size: 11px; color: #aaa; margin-bottom: 8px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 500; margin-bottom: 8px; }
    .badge-pub { background: #EAF3DE; color: #3B6D11; }
    .badge-dra { background: #FEF9E7; color: #856d00; }
    .badge-arc { background: #f4f6fa; color: #888; }
    .badge-en { background: #EBF3FB; color: #003B7A; font-size: 10px; padding: 2px 6px; border-radius: 20px; margin-left: 4px; }
    .card-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .btn-xs { padding: 4px 9px; border-radius: 6px; font-size: 11px; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; color: #333; text-decoration: none; }
    .btn-xs:hover { background: #f4f6fa; }
    .btn-xs.primary { background: #003B7A; color: #fff; border-color: #003B7A; }
    .btn-xs.primary:hover { background: #00306a; }
    .btn-xs.gold { background: #F5C518; color: #003B7A; border-color: #F5C518; font-weight: 500; }
    .btn-xs.danger { border-color: #fca5a5; color: #b91c1c; }
    .btn-xs.danger:hover { background: #fee2e2; }
    .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; border-left: 3px solid; }
    .alert-ok  { background: #EAF3DE; color: #3B6D11; border-color: #F5C518; }
    .alert-err { background: #FEF2F2; color: #B91C1C; border-color: #B91C1C; }
    .empty { text-align: center; padding: 60px; color: #aaa; font-size: 14px; }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-brand"><div class="sb-dot"></div><h2>Revistas UDC</h2></div>
    <p>Panel de administración</p>
  </div>
  <div class="sb-section">Navegación</div>
  <a class="sb-link" href="dashboard.php"><i class="ti ti-home" aria-hidden="true"></i> Dashboard</a>
  <a class="sb-link active" href="revistas.php"><i class="ti ti-file-text" aria-hidden="true"></i> Revistas</a>
  <a class="sb-link" href="revistas_en.php"><i class="ti ti-world" aria-hidden="true"></i> Versión inglés</a>
  <a class="sb-link" href="categorias.php"><i class="ti ti-tag" aria-hidden="true"></i> Categorías</a>
  <?php if ($_SESSION['rol'] === 'admin'): ?>
  <a class="sb-link" href="accesos.php"><i class="ti ti-shield" aria-hidden="true"></i> Registro IP</a>
  <a class="sb-link" href="usuarios.php"><i class="ti ti-users" aria-hidden="true"></i> Usuarios</a>
  <?php endif; ?>
  <div class="sb-bottom">
    <a class="sb-link" href="../public/logout.php"><i class="ti ti-logout" aria-hidden="true"></i> Cerrar sesión</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <h1>Revistas</h1>
    <div class="topbar-right">
      <a class="btn-preview" href="../public/index.php" target="_blank"><i class="ti ti-eye" aria-hidden="true"></i> Ver sitio público</a>
      <div class="user-info">
        <span><?= htmlspecialchars($_SESSION['nombre']) ?></span>
        <span class="badge-rol"><?= $_SESSION['rol'] ?></span>
        <div class="avatar"><?= strtoupper(substr($_SESSION['nombre'], 0, 2)) ?></div>
      </div>
    </div>
  </div>

  <div class="content">
    <?php if ($mensaje): ?><div class="alert alert-ok"><?= $mensaje ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-err"><?= $error ?></div><?php endif; ?>

    <div class="top-bar">
      <h2>Todas las revistas (<?= count($revistas) ?>)</h2>
      <button class="btn-nueva" onclick="document.getElementById('modal').classList.add('open')">
        <i class="ti ti-plus" aria-hidden="true"></i> Nueva revista
      </button>
    </div>

    <?php if (empty($revistas)): ?>
      <div class="empty">📭 No hay revistas todavía.</div>
    <?php else: ?>
    <div class="grid">
      <?php foreach ($revistas as $r):
        $badge    = match($r['estado']) { 'publicada' => 'badge-pub', 'borrador' => 'badge-dra', default => 'badge-arc' };
        $tiene_en = in_array($r['id'], $con_ingles);
      ?>
      <div class="card">
        <div class="card-img">
          <?php if ($r['portada_url']): ?>
            <img src="<?= htmlspecialchars(url_asset($r['portada_url'])) ?>" alt="Portada">
          <?php else: ?>
            📄
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="card-title">
            <?= htmlspecialchars($r['titulo']) ?>
            <?php if ($tiene_en): ?><span class="badge-en">🇺🇸 EN</span><?php endif; ?>
          </div>
          <div class="card-cat"><?= htmlspecialchars($r['categoria']) ?> · <?= htmlspecialchars($r['autor']) ?></div>
          <span class="badge <?= $badge ?>"><?= $r['estado'] ?></span>
          <div class="card-actions">
            <?php if ($r['estado'] !== 'publicada'): ?>
              <a class="btn-xs primary" href="?id=<?= $r['id'] ?>&estado=publicada">Publicar</a>
            <?php endif; ?>
            <?php if ($r['estado'] !== 'borrador'): ?>
              <a class="btn-xs" href="?id=<?= $r['id'] ?>&estado=borrador">Borrador</a>
            <?php endif; ?>
            <?php if (!$tiene_en): ?>
              <a class="btn-xs gold" href="revistas_en.php">+ EN</a>
            <?php endif; ?>
            <?php if ($r['pdf_url']): ?>
              <a class="btn-xs" href="<?= htmlspecialchars(url_asset($r['pdf_url'])) ?>" target="_blank">Ver PDF</a>
            <?php endif; ?>
            <a class="btn-xs" href="editar_revista.php?id=<?= $r['id'] ?>"><i class="ti ti-edit" style="font-size:11px"></i> Editar</a>
            <a class="btn-xs danger" href="?eliminar=<?= $r['id'] ?>" onclick="return confirm('¿Eliminar esta revista?')">Eliminar</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="modal-overlay" id="modal">
  <div class="modal">
    <h2>Nueva revista</h2>
    <form method="POST" enctype="multipart/form-data">
      <label>Título *</label>
      <input type="text" name="titulo" placeholder="Título de la revista" required>
      <label>Descripción</label>
      <textarea name="descripcion" placeholder="Breve descripción del contenido..."></textarea>
      <label>Categoría *</label>
      <select name="categoria_id" required>
        <option value="">Selecciona una categoría</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_es']) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Portada (imagen)</label>
      <input type="file" name="portada" accept="image/*">
      <label>Archivo PDF</label>
      <input type="file" name="pdf" accept="application/pdf">
      <label>Estado</label>
      <select name="estado">
        <option value="borrador">Borrador</option>
        <option value="publicada">Publicar ahora</option>
      </select>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="document.getElementById('modal').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn-save">Guardar revista</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>