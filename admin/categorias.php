<?php
require_once '../config/session.php';
require_once '../config/db.php';
assert($pdo instanceof PDO);

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $nombre_es = trim($_POST['nombre_es']);
    $nombre_en = trim($_POST['nombre_en']);
    $desc_es   = trim($_POST['desc_es']);
    $desc_en   = trim($_POST['desc_en']);
    $slug      = strtolower(str_replace(' ', '-', $nombre_es));

    if ($nombre_es && $nombre_en) {
        try {
            $stmt = $pdo->prepare('INSERT INTO categorias (nombre_es, nombre_en, slug, descripcion_es, descripcion_en) VALUES (?,?,?,?,?)');
            $stmt->execute([$nombre_es, $nombre_en, $slug, $desc_es, $desc_en]);
            $mensaje = 'Categoría creada correctamente';
        } catch (PDOException $e) {
            $error = 'Esa categoría ya existe';
        }
    } else {
        $error = 'El nombre en ambos idiomas es obligatorio';
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare('UPDATE categorias SET activa = NOT activa WHERE id = ?')->execute([$id]);
    header('Location: categorias.php');
    exit;
}

$categorias = $pdo->query('SELECT * FROM categorias ORDER BY nombre_es ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categorías — Panel UDC</title>
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
    .main { flex: 1; display: flex; flex-direction: column; }
    .topbar { background: #fff; border-bottom: 3px solid #F5C518; padding: 0 24px; height: 54px; display: flex; align-items: center; justify-content: space-between; }
    .topbar h1 { font-size: 16px; color: #003B7A; font-weight: 500; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .btn-preview { display: flex; align-items: center; gap: 6px; padding: 7px 14px; background: #EBF3FB; border: 1px solid #b8d4ef; border-radius: 8px; font-size: 13px; color: #003B7A; text-decoration: none; font-weight: 500; }
    .btn-preview:hover { background: #d6e8f7; }
    .user-info { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #555; }
    .avatar { width: 34px; height: 34px; border-radius: 50%; background: #003B7A; color: #F5C518; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; }
    .badge-rol { background: #FEF9E7; color: #003B7A; border: 1px solid #F5C518; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 500; }
    .content { padding: 24px; flex: 1; display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
    .tabla-wrap { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; }
    .tabla-header { padding: 16px 20px; border-bottom: 0.5px solid #e2e8f0; font-size: 14px; font-weight: 500; color: #003B7A; display: flex; align-items: center; gap: 8px; }
    .tabla-header-bar { width: 3px; height: 18px; background: #F5C518; border-radius: 2px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #003B7A; color: rgba(255,255,255,0.8); padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 500; }
    td { padding: 12px 14px; font-size: 13px; color: #333; border-bottom: 1px solid #f4f6fa; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f9fbff; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge-activo   { background: #EAF3DE; color: #3B6D11; }
    .badge-inactivo { background: #FEF2F2; color: #B91C1C; }
    .btn-toggle { padding: 5px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; color: #333; text-decoration: none; }
    .btn-toggle:hover { background: #f4f6fa; }
    .form-card { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; }
    .form-card-header { background: #003B7A; padding: 16px 20px; }
    .form-card-header h2 { color: #fff; font-size: 14px; font-weight: 500; }
    .form-card-body { padding: 20px; }
    .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; margin-top: 12px; }
    input, textarea { width: 100%; padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; font-family: Arial, sans-serif; }
    input:focus, textarea:focus { border-color: #003B7A; }
    textarea { resize: vertical; min-height: 60px; }
    .btn-submit { width: 100%; padding: 11px; background: #003B7A; color: #fff; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; margin-top: 16px; font-weight: 500; }
    .btn-submit:hover { background: #00306a; }
    .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; border-left: 3px solid; }
    .alert-ok  { background: #EAF3DE; color: #3B6D11; border-color: #F5C518; }
    .alert-err { background: #FEF2F2; color: #B91C1C; border-color: #B91C1C; }
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
  <a class="sb-link" href="revistas_en.php"><i class="ti ti-world" aria-hidden="true"></i> Versión inglés</a>
  <a class="sb-link active" href="categorias.php"><i class="ti ti-tag" aria-hidden="true"></i> Categorías</a>
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
    <h1>Categorías</h1>
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
    <div class="tabla-wrap">
      <div class="tabla-header">
        <div class="tabla-header-bar"></div>
        Categorías registradas
      </div>
      <table>
        <thead>
          <tr>
            <th>🇲🇽 Español</th>
            <th>🇺🇸 Inglés</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categorias as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['nombre_es']) ?></td>
            <td><?= htmlspecialchars($c['nombre_en']) ?></td>
            <td><span class="badge badge-<?= $c['activa'] ? 'activo' : 'inactivo' ?>"><?= $c['activa'] ? 'Activa' : 'Inactiva' ?></span></td>
            <td><a class="btn-toggle" href="?toggle=<?= $c['id'] ?>"><?= $c['activa'] ? 'Desactivar' : 'Activar' ?></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="form-card">
      <div class="form-card-header">
        <h2>Nueva categoría</h2>
      </div>
      <div class="form-card-body">
        <?php if ($mensaje): ?><div class="alert alert-ok"><?= $mensaje ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-err"><?= $error ?></div><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="accion" value="agregar">
          <div class="row2">
            <div>
              <label>🇲🇽 Nombre en español</label>
              <input type="text" name="nombre_es" placeholder="Ej. Ciencia ficción" required>
            </div>
            <div>
              <label>🇺🇸 Name in English</label>
              <input type="text" name="nombre_en" placeholder="Ej. Science fiction" required>
            </div>
          </div>
          <label>🇲🇽 Descripción en español</label>
          <textarea name="desc_es" placeholder="Descripción breve..."></textarea>
          <label>🇺🇸 Description in English</label>
          <textarea name="desc_en" placeholder="Brief description..."></textarea>
          <button type="submit" class="btn-submit">Crear categoría</button>
        </form>
      </div>
    </div>
  </div>
</div>

</body>
</html>