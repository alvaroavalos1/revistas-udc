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

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $pdo->prepare('DELETE FROM revistas_en WHERE id = ?')->execute([$id]);
    header('Location: revistas_en.php');
    exit;
}

if (isset($_GET['estado']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $estado = $_GET['estado'];
    if (in_array($estado, ['borrador', 'publicada', 'archivada'])) {
        $stmt = $pdo->prepare('UPDATE revistas_en SET estado = ?, publicada_en = ? WHERE id = ?');
        $stmt->execute([$estado, $estado === 'publicada' ? date('Y-m-d H:i:s') : null, $id]);
    }
    header('Location: revistas_en.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $revista_id  = (int)$_POST['revista_id'];
    $titulo      = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $estado      = $_POST['estado'];

    $existe = $pdo->prepare('SELECT id FROM revistas_en WHERE revista_id = ?');
    $existe->execute([$revista_id]);

    if ($existe->fetch()) {
        $error = 'Esta revista ya tiene una versión en inglés. Elimínala primero para reemplazarla.';
    } else {
        $portada_url = null;
        if (!empty($_FILES['portada']['name'])) {
            $ext = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));
            $key = 'portadas/portada_en_' . uniqid() . '.' . $ext;
            $url = upload_to_r2($_FILES['portada']['tmp_name'], $key, $_FILES['portada']['type']);
            $portada_url = $url ?: 'data:' . $_FILES['portada']['type'] . ';base64,' . base64_encode(file_get_contents($_FILES['portada']['tmp_name']));
        }

        $pdf_url  = null;
        $pdf_blob = null;
        if (!empty($_FILES['pdf']['name'])) {
            $key     = 'pdfs/revista_en_' . uniqid() . '.pdf';
            $pdf_url = upload_to_r2($_FILES['pdf']['tmp_name'], $key, 'application/pdf');
            if (!$pdf_url) {
                $pdf_blob = file_get_contents($_FILES['pdf']['tmp_name']);
            }
        }

        if ($titulo && $revista_id) {
            $stmt = $pdo->prepare('INSERT INTO revistas_en (revista_id, subida_por, titulo, descripcion, portada_url, pdf_url, pdf_blob, estado, publicada_en) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $revista_id, $_SESSION['usuario_id'], $titulo, $descripcion,
                $portada_url, $pdf_url, $pdf_blob, $estado,
                $estado === 'publicada' ? date('Y-m-d H:i:s') : null
            ]);
            if ($pdf_blob !== null) {
                $pdo->prepare('UPDATE revistas_en SET pdf_url = ? WHERE revista_id = ?')
                    ->execute(['public/ver_pdf.php?id=' . $revista_id . '&lang=en', $revista_id]);
            }
            $mensaje = 'Versión en inglés guardada correctamente';
        } else {
            $error = 'El título y la revista son obligatorios';
        }
    }
}

$revistas_es = $pdo->query('
    SELECT r.id, r.titulo, c.nombre_es AS categoria
    FROM revistas r
    JOIN categorias c ON r.categoria_id = c.id
    ORDER BY r.titulo ASC
')->fetchAll(PDO::FETCH_ASSOC);

$con_ingles = $pdo->query('SELECT revista_id FROM revistas_en')->fetchAll(PDO::FETCH_COLUMN);

$versiones = $pdo->query('
    SELECT re.*, r.titulo AS titulo_es, c.nombre_es AS categoria, u.nombre AS autor
    FROM revistas_en re
    JOIN revistas r ON re.revista_id = r.id
    JOIN categorias c ON r.categoria_id = c.id
    JOIN usuarios u ON re.subida_por = u.id
    ORDER BY re.creado_en DESC
')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Versión inglés — Panel UDC</title>
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
    .content { padding: 24px; flex: 1; display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start; }
    .tabla-wrap { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; }
    .tabla-header { padding: 16px 20px; border-bottom: 0.5px solid #e2e8f0; font-size: 14px; font-weight: 500; color: #003B7A; display: flex; align-items: center; gap: 8px; }
    .tabla-header-bar { width: 3px; height: 18px; background: #F5C518; border-radius: 2px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #003B7A; color: rgba(255,255,255,0.8); padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 500; }
    td { padding: 11px 14px; font-size: 13px; color: #333; border-bottom: 1px solid #f4f6fa; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f9fbff; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge-pub { background: #EAF3DE; color: #3B6D11; }
    .badge-dra { background: #FEF9E7; color: #856d00; }
    .badge-arc { background: #f4f6fa; color: #888; }
    .btn-xs { padding: 4px 9px; border-radius: 6px; font-size: 11px; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; color: #333; text-decoration: none; display: inline-block; margin-right: 4px; }
    .btn-xs:hover { background: #f4f6fa; }
    .btn-xs.primary { background: #003B7A; color: #fff; border-color: #003B7A; }
    .btn-xs.danger { border-color: #fca5a5; color: #b91c1c; }
    .btn-xs.danger:hover { background: #fee2e2; }
    .form-card { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; position: sticky; top: 24px; }
    .form-card-header { background: #003B7A; padding: 16px 20px; display: flex; align-items: center; gap: 10px; }
    .form-card-header h2 { color: #fff; font-size: 14px; font-weight: 500; }
    .form-card-header span { background: #F5C518; color: #003B7A; font-size: 10px; font-weight: 500; padding: 2px 8px; border-radius: 20px; }
    .form-card-body { padding: 20px; }
    label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; margin-top: 14px; }
    input[type=text], textarea, select { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; font-family: Arial, sans-serif; }
    input[type=text]:focus, textarea:focus, select:focus { border-color: #003B7A; }
    textarea { resize: vertical; min-height: 80px; }
    input[type=file] { font-size: 13px; }
    .btn-submit { width: 100%; padding: 11px; background: #003B7A; color: #fff; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; margin-top: 16px; font-weight: 500; }
    .btn-submit:hover { background: #00306a; }
    .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; border-left: 3px solid; }
    .alert-ok  { background: #EAF3DE; color: #3B6D11; border-color: #F5C518; }
    .alert-err { background: #FEF2F2; color: #B91C1C; border-color: #B91C1C; }
    .empty { text-align: center; padding: 40px; color: #aaa; font-size: 14px; }
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
  <a class="sb-link" href="revistas.php"><i class="ti ti-file-text" aria-hidden="true"></i> Revistas</a>
  <a class="sb-link active" href="revistas_en.php"><i class="ti ti-world" aria-hidden="true"></i> Versión inglés</a>
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
    <h1>Versión en inglés</h1>
    <div class="topbar-right">
      <a class="btn-preview" href="../public/index.php?lang=en" target="_blank"><i class="ti ti-eye" aria-hidden="true"></i> Ver en inglés</a>
      <div class="user-info">
        <span><?= htmlspecialchars($_SESSION['nombre']) ?></span>
        <span class="badge-rol"><?= $_SESSION['rol'] ?></span>
        <div class="avatar"><?= strtoupper(substr($_SESSION['nombre'], 0, 2)) ?></div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="tabla-wrap">
      <div class="tabla-header">
        <div class="tabla-header-bar"></div>
        Versiones en inglés (<?= count($versiones) ?>)
      </div>
      <?php if (empty($versiones)): ?>
        <div class="empty">📭 No hay versiones en inglés todavía.</div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Título en inglés</th>
            <th>Revista original</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($versiones as $v):
            $badge = match($v['estado']) { 'publicada' => 'badge-pub', 'borrador' => 'badge-dra', default => 'badge-arc' };
          ?>
          <tr>
            <td><?= htmlspecialchars($v['titulo']) ?></td>
            <td>
              <div><?= htmlspecialchars($v['titulo_es']) ?></div>
              <div style="font-size:11px;color:#aaa;"><?= htmlspecialchars($v['categoria']) ?></div>
            </td>
            <td><span class="badge <?= $badge ?>"><?= $v['estado'] ?></span></td>
            <td>
              <?php if ($v['estado'] !== 'publicada'): ?>
                <a class="btn-xs primary" href="?id=<?= $v['id'] ?>&estado=publicada">Publicar</a>
              <?php endif; ?>
              <?php if ($v['estado'] !== 'borrador'): ?>
                <a class="btn-xs" href="?id=<?= $v['id'] ?>&estado=borrador">Borrador</a>
              <?php endif; ?>
              <?php if ($v['pdf_url']): ?>
                <a class="btn-xs" href="<?= htmlspecialchars(url_asset($v['pdf_url'])) ?>" target="_blank">Ver PDF</a>
              <?php endif; ?>
              <a class="btn-xs danger" href="?eliminar=<?= $v['id'] ?>" onclick="return confirm('¿Eliminar esta versión en inglés?')">Eliminar</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <div class="form-card">
      <div class="form-card-header">
        <h2>Nueva versión en inglés</h2>
        <span>🇺🇸 English</span>
      </div>
      <div class="form-card-body">
        <?php if ($mensaje): ?><div class="alert alert-ok"><?= $mensaje ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-err"><?= $error ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
          <label>Revista original (español) *</label>
          <select name="revista_id" required>
            <option value="">Selecciona la revista en español</option>
            <?php foreach ($revistas_es as $r): ?>
              <option value="<?= $r['id'] ?>" <?= in_array($r['id'], $con_ingles) ? 'disabled' : '' ?>>
                <?= htmlspecialchars($r['titulo']) ?> — <?= htmlspecialchars($r['categoria']) ?>
                <?= in_array($r['id'], $con_ingles) ? '(ya tiene versión)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <label>Title in English *</label>
          <input type="text" name="titulo" placeholder="Magazine title in English" required>
          <label>Description in English</label>
          <textarea name="descripcion" placeholder="Brief description in English..."></textarea>
          <label>Cover image (English version)</label>
          <input type="file" name="portada" accept="image/*">
          <label>PDF file (English version)</label>
          <input type="file" name="pdf" accept="application/pdf">
          <label>Status</label>
          <select name="estado">
            <option value="borrador">Draft</option>
            <option value="publicada">Publish now</option>
          </select>
          <button type="submit" class="btn-submit">Save English version</button>
        </form>
      </div>
    </div>
  </div>
</div>

</body>
</html>