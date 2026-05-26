<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$mensaje = '';
$error   = '';

if (isset($_GET['toggle'])) {
    $id   = (int)$_GET['toggle'];
    $stmt = $pdo->prepare('UPDATE usuarios SET activo = NOT activo WHERE id = ? AND id != ?');
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    header('Location: usuarios.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $rol      = $_POST['rol'];

    if ($nombre && $email && $password) {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash, rol, creado_por) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nombre, $email, $hash, $rol, $_SESSION['usuario_id']]);
            $mensaje = 'Usuario creado correctamente';
        } catch (PDOException $e) {
            $error = 'Ese correo ya está registrado';
        }
    } else {
        $error = 'Todos los campos son obligatorios';
    }
}

$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY creado_en DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuarios — Panel UDC</title>
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
    .content { padding: 24px; flex: 1; display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
    .tabla-wrap { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; }
    .tabla-header { padding: 16px 20px; border-bottom: 0.5px solid #e2e8f0; font-size: 14px; font-weight: 500; color: #003B7A; display: flex; align-items: center; gap: 8px; }
    .tabla-header-bar { width: 3px; height: 18px; background: #F5C518; border-radius: 2px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #003B7A; color: rgba(255,255,255,0.8); padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 500; }
    td { padding: 12px 14px; font-size: 13px; color: #333; border-bottom: 1px solid #f4f6fa; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f9fbff; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge-admin    { background: #EBF3FB; color: #003B7A; border: 1px solid #b8d4ef; }
    .badge-editor   { background: #FEF9E7; color: #856d00; border: 1px solid #F5C518; }
    .badge-activo   { background: #EAF3DE; color: #3B6D11; }
    .badge-inactivo { background: #FEF2F2; color: #B91C1C; }
    .btn-toggle { padding: 5px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; color: #333; text-decoration: none; }
    .btn-toggle:hover { background: #f4f6fa; }
    .form-card { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; }
    .form-card-header { background: #003B7A; padding: 16px 20px; }
    .form-card-header h2 { color: #fff; font-size: 14px; font-weight: 500; }
    .form-card-body { padding: 20px; }
    label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; margin-top: 12px; }
    input, select { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; }
    input:focus, select:focus { border-color: #003B7A; }
    .btn-submit { width: 100%; padding: 11px; background: #003B7A; color: #fff; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; margin-top: 16px; font-weight: 500; }
    .btn-submit:hover { background: #00306a; }
    .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; border-left: 3px solid; }
    .alert-ok  { background: #EAF3DE; color: #3B6D11; border-color: #F5C518; }
    .alert-err { background: #FEF2F2; color: #B91C1C; border-color: #B91C1C; }
    .yo { font-size: 11px; color: #aaa; margin-left: 6px; }
    .user-avatar { width: 30px; height: 30px; border-radius: 50%; background: #003B7A; color: #F5C518; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; margin-right: 8px; vertical-align: middle; }
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
  <a class="sb-link" href="categorias.php"><i class="ti ti-tag" aria-hidden="true"></i> Categorías</a>
  <a class="sb-link" href="accesos.php"><i class="ti ti-shield" aria-hidden="true"></i> Registro IP</a>
  <a class="sb-link active" href="usuarios.php"><i class="ti ti-users" aria-hidden="true"></i> Usuarios</a>
  <div class="sb-bottom">
    <a class="sb-link" href="../public/logout.php"><i class="ti ti-logout" aria-hidden="true"></i> Cerrar sesión</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <h1>Usuarios</h1>
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
        Usuarios registrados
      </div>
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
          <tr>
            <td>
              <span class="user-avatar"><?= strtoupper(substr($u['nombre'], 0, 2)) ?></span>
              <?= htmlspecialchars($u['nombre']) ?>
              <?php if ($u['id'] == $_SESSION['usuario_id']): ?>
                <span class="yo">(tú)</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge badge-<?= $u['rol'] ?>"><?= $u['rol'] ?></span></td>
            <td><span class="badge badge-<?= $u['activo'] ? 'activo' : 'inactivo' ?>"><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
            <td>
              <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                <a class="btn-toggle" href="?toggle=<?= $u['id'] ?>"><?= $u['activo'] ? 'Desactivar' : 'Activar' ?></a>
              <?php else: ?>
                <span style="font-size:12px;color:#ccc;">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="form-card">
      <div class="form-card-header">
        <h2>Agregar usuario</h2>
      </div>
      <div class="form-card-body">
        <?php if ($mensaje): ?><div class="alert alert-ok"><?= $mensaje ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-err"><?= $error ?></div><?php endif; ?>
        <form method="POST">
          <label>Nombre completo</label>
          <input type="text" name="nombre" placeholder="Ej. Ana Torres" required>
          <label>Correo electrónico</label>
          <input type="email" name="email" placeholder="correo@ucol.mx" required>
          <label>Contraseña</label>
          <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
          <label>Rol</label>
          <select name="rol">
            <option value="editor">Editor</option>
            <option value="admin">Admin</option>
          </select>
          <button type="submit" class="btn-submit">Crear usuario</button>
        </form>
      </div>
    </div>
  </div>
</div>

</body>
</html>