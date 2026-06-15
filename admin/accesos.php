<?php
require_once '../config/session.php';
require_once '../config/db.php';
assert($pdo instanceof PDO);

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../public/login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Filtros
$filtro_accion = $_GET['accion'] ?? '';
$filtro_ip     = trim($_GET['ip'] ?? '');

$where  = '1=1';
$params = [];

if ($filtro_accion) {
    $where   .= ' AND a.accion = ?';
    $params[] = $filtro_accion;
}
if ($filtro_ip) {
    $where   .= ' AND a.ip LIKE ?';
    $params[] = '%' . $filtro_ip . '%';
}

$stmt = $pdo->prepare("
    SELECT a.*, u.nombre AS nombre_usuario
    FROM accesos_log a
    LEFT JOIN usuarios u ON a.usuario_id = u.id
    WHERE $where
    ORDER BY a.creado_en DESC
    LIMIT 100
");
$stmt->execute($params);
$accesos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$total_exitosos = $pdo->query('SELECT COUNT(*) FROM accesos_log WHERE exitoso = 1')->fetchColumn();
$total_fallidos = $pdo->query('SELECT COUNT(*) FROM accesos_log WHERE exitoso = 0')->fetchColumn();
$ips_distintas  = $pdo->query('SELECT COUNT(DISTINCT ip) FROM accesos_log')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de accesos — Panel UDC</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background: #f4f6fa; display: flex; min-height: 100vh; }
    .sidebar { width: 220px; background: #003B7A; display: flex; flex-direction: column; flex-shrink: 0; height: 100vh; position: fixed; top: 0; left: 0; overflow-y: auto; z-index: 200; }
    .sb-logo { padding: 22px 18px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .sb-logo-brand { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
    .sb-dot { width: 8px; height: 8px; border-radius: 50%; background: #F5C518; flex-shrink: 0; }
    .sb-logo h2 { color: #fff; font-size: 15px; font-weight: 500; }
    .sb-logo p  { color: rgba(255,255,255,0.4); font-size: 11px; margin-top: 2px; padding-left: 16px; }
    .sb-section { padding: 16px 18px 4px; font-size: 10px; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.06em; }
    .sb-link { display: flex; align-items: center; gap: 10px; padding: 10px 18px; font-size: 13px; color: rgba(255,255,255,0.65); text-decoration: none; border-left: 3px solid transparent; transition: background 0.2s, color 0.2s; }
    .sb-link:hover { background: rgba(255,255,255,0.07); color: #fff; }
    .sb-link.active { background: rgba(245,197,24,0.12); color: #F5C518; border-left-color: #F5C518; }
    .sb-link i { font-size: 18px; }
    .sb-bottom { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding: 10px 0; }
    .main { flex: 1; display: flex; flex-direction: column; min-width: 0; margin-left: 220px; }
    .topbar { background: #fff; border-bottom: 3px solid #F5C518; padding: 0 24px; height: 54px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
    .topbar h1 { font-size: 16px; color: #003B7A; font-weight: 500; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .btn-preview { display: flex; align-items: center; gap: 6px; padding: 7px 14px; background: #EBF3FB; border: 1px solid #b8d4ef; border-radius: 8px; font-size: 12px; color: #003B7A; text-decoration: none; font-weight: 500; }
    .btn-preview:hover { background: #d6e8f7; }
    .user-info { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #555; }
    .avatar { width: 32px; height: 32px; border-radius: 50%; background: #003B7A; color: #F5C518; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; }
    .badge-rol { background: #F5C518; color: #003B7A; font-size: 10px; padding: 2px 8px; border-radius: 20px; font-weight: 500; }
    .content { padding: 24px; flex: 1; }
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat { background: #fff; border-radius: 12px; padding: 16px; border: 0.5px solid #e2e8f0; border-top: 3px solid #003B7A; }
    .stat-label { font-size: 12px; color: #aaa; margin-bottom: 6px; }
    .stat-val { font-size: 28px; font-weight: 500; color: #003B7A; }
    .stat.exitoso { border-top-color: #3B6D11; }
    .stat.exitoso .stat-val { color: #3B6D11; }
    .stat.fallido { border-top-color: #B91C1C; }
    .stat.fallido .stat-val { color: #B91C1C; }

    .filtros { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; padding: 16px 20px; margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
    .filtro-group { display: flex; flex-direction: column; gap: 4px; }
    .filtro-group label { font-size: 12px; color: #555; }
    .filtro-group select, .filtro-group input { padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; }
    .filtro-group select:focus, .filtro-group input:focus { border-color: #003B7A; }
    .btn-filtrar { padding: 9px 18px; background: #003B7A; color: #fff; border: none; border-radius: 8px; font-size: 13px; cursor: pointer; font-weight: 500; }
    .btn-filtrar:hover { background: #00306a; }
    .btn-limpiar { padding: 9px 14px; background: #fff; color: #555; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; cursor: pointer; text-decoration: none; }
    .btn-limpiar:hover { background: #f4f6fa; }

    .tabla-wrap { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; }
    .tabla-header { padding: 16px 20px; border-bottom: 0.5px solid #e2e8f0; font-size: 14px; font-weight: 500; color: #003B7A; display: flex; align-items: center; gap: 8px; }
    .tabla-header-bar { width: 3px; height: 18px; background: #F5C518; border-radius: 2px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #003B7A; color: rgba(255,255,255,0.8); padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 500; }
    td { padding: 11px 14px; font-size: 13px; color: #333; border-bottom: 1px solid #f4f6fa; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f9fbff; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge-exitoso { background: #EAF3DE; color: #3B6D11; }
    .badge-fallido { background: #FEF2F2; color: #B91C1C; }
    .ip-badge { display: inline-flex; align-items: center; gap: 4px; background: #EBF3FB; color: #003B7A; font-size: 11px; padding: 3px 8px; border-radius: 6px; font-family: monospace; }
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
  <a class="sb-link" href="revistas_en.php"><i class="ti ti-world" aria-hidden="true"></i> Versión inglés</a>
  <a class="sb-link" href="categorias.php"><i class="ti ti-tag" aria-hidden="true"></i> Categorías</a>
  <a class="sb-link active" href="accesos.php"><i class="ti ti-shield" aria-hidden="true"></i> Registro IP</a>
  <?php if ($_SESSION['rol'] === 'admin'): ?>
  <a class="sb-link" href="usuarios.php"><i class="ti ti-users" aria-hidden="true"></i> Usuarios</a>
  <?php endif; ?>
  <div class="sb-bottom">
    <a class="sb-link" href="../public/logout.php"><i class="ti ti-logout" aria-hidden="true"></i> Cerrar sesión</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <h1>Registro de accesos por IP</h1>
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
    <!-- Estadísticas -->
    <div class="stats">
      <div class="stat exitoso">
        <div class="stat-label">Accesos exitosos</div>
        <div class="stat-val"><?= $total_exitosos ?></div>
      </div>
      <div class="stat fallido">
        <div class="stat-label">Intentos fallidos</div>
        <div class="stat-val"><?= $total_fallidos ?></div>
      </div>
      <div class="stat">
        <div class="stat-label">IPs distintas</div>
        <div class="stat-val"><?= $ips_distintas ?></div>
      </div>
    </div>

    <!-- Filtros -->
    <form class="filtros" method="GET">
      <div class="filtro-group">
        <label>Tipo de acceso</label>
        <select name="accion">
          <option value="">Todos</option>
          <option value="login_exitoso" <?= $filtro_accion === 'login_exitoso' ? 'selected' : '' ?>>Exitosos</option>
          <option value="login_fallido" <?= $filtro_accion === 'login_fallido' ? 'selected' : '' ?>>Fallidos</option>
        </select>
      </div>
      <div class="filtro-group">
        <label>Buscar por IP</label>
        <input type="text" name="ip" placeholder="Ej. 192.168.1.1" value="<?= htmlspecialchars($filtro_ip) ?>">
      </div>
      <button type="submit" class="btn-filtrar">Filtrar</button>
      <a class="btn-limpiar" href="accesos.php">Limpiar</a>
    </form>

    <!-- Tabla -->
    <div class="tabla-wrap">
      <div class="tabla-header">
        <div class="tabla-header-bar"></div>
        Últimos 100 accesos
      </div>
      <?php if (empty($accesos)): ?>
        <div class="empty">📭 No hay registros de acceso todavía.</div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Correo intentado</th>
            <th>IP</th>
            <th>Resultado</th>
            <th>Fecha y hora</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($accesos as $a): ?>
          <tr>
            <td><?= $a['nombre_usuario'] ? htmlspecialchars($a['nombre_usuario']) : '<span style="color:#aaa">Desconocido</span>' ?></td>
            <td><?= htmlspecialchars($a['email'] ?? '—') ?></td>
            <td><span class="ip-badge"><i class="ti ti-map-pin" style="font-size:11px"></i> <?= htmlspecialchars($a['ip']) ?></span></td>
            <td>
              <span class="badge badge-<?= $a['exitoso'] ? 'exitoso' : 'fallido' ?>">
                <?= $a['exitoso'] ? '✓ Exitoso' : '✗ Fallido' ?>
              </span>
            </td>
            <td><?= date('d/m/Y H:i:s', strtotime($a['creado_en'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>