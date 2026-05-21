<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$total_revistas   = $pdo->query('SELECT COUNT(*) FROM revistas')->fetchColumn();
$publicadas       = $pdo->query('SELECT COUNT(*) FROM revistas WHERE estado = "publicada"')->fetchColumn();
$borradores       = $pdo->query('SELECT COUNT(*) FROM revistas WHERE estado = "borrador"')->fetchColumn();
$total_categorias = $pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Revistas UDC</title>
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
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
    .stat { background: #fff; border-radius: 12px; padding: 20px; border: 0.5px solid #e2e8f0; border-top: 3px solid #003B7A; }
    .stat-label { font-size: 12px; color: #aaa; margin-bottom: 8px; }
    .stat-val { font-size: 30px; font-weight: 500; color: #003B7A; }
    .stat:nth-child(2) { border-top-color: #F5C518; }
    .stat:nth-child(2) .stat-val { color: #856d00; }
    .stat:nth-child(3) { border-top-color: #e2e8f0; }
    .stat:nth-child(3) .stat-val { color: #888; }

    .section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .section-bar { width: 3px; height: 20px; background: #F5C518; border-radius: 2px; }
    .section-title { font-size: 14px; font-weight: 500; color: #1a202c; }

    .tabla { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; border: 0.5px solid #e2e8f0; }
    .tabla th { background: #003B7A; color: rgba(255,255,255,0.8); padding: 11px 14px; text-align: left; font-size: 12px; font-weight: 500; }
    .tabla td { padding: 11px 14px; font-size: 13px; color: #333; border-bottom: 1px solid #f4f6fa; }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr:hover td { background: #f9fbff; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge-pub { background: #EAF3DE; color: #3B6D11; }
    .badge-dra { background: #FEF9E7; color: #856d00; }
    .badge-arc { background: #f4f6fa; color: #888; }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-brand">
      <div class="sb-dot"></div>
      <h2>Revistas UDC</h2>
    </div>
    <p>Panel de administración</p>
  </div>
  <div class="sb-section">Navegación</div>
  <a class="sb-link active" href="dashboard.php"><i class="ti ti-home" aria-hidden="true"></i> Dashboard</a>
  <a class="sb-link" href="revistas.php"><i class="ti ti-file-text" aria-hidden="true"></i> Revistas</a>
  <a class="sb-link" href="categorias.php"><i class="ti ti-tag" aria-hidden="true"></i> Categorías</a>
  <?php if ($_SESSION['rol'] === 'admin'): ?>
  <a class="sb-link" href="usuarios.php"><i class="ti ti-users" aria-hidden="true"></i> Usuarios</a>
  <?php endif; ?>
  <div class="sb-bottom">
    <a class="sb-link" href="../public/logout.php"><i class="ti ti-logout" aria-hidden="true"></i> Cerrar sesión</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <h1>Dashboard</h1>
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
    <div class="stats">
      <div class="stat"><div class="stat-label">Total revistas</div><div class="stat-val"><?= $total_revistas ?></div></div>
      <div class="stat"><div class="stat-label">Publicadas</div><div class="stat-val"><?= $publicadas ?></div></div>
      <div class="stat"><div class="stat-label">Borradores</div><div class="stat-val"><?= $borradores ?></div></div>
      <div class="stat"><div class="stat-label">Categorías</div><div class="stat-val"><?= $total_categorias ?></div></div>
    </div>

    <div class="section-header">
      <div class="section-bar"></div>
      <span class="section-title">Últimas revistas</span>
    </div>
    <table class="tabla">
      <thead>
        <tr>
          <th>Título</th>
          <th>Categoría</th>
          <th>Estado</th>
          <th>Subida por</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $stmt = $pdo->query('
          SELECT r.titulo, c.nombre_es AS categoria, r.estado, u.nombre AS autor, r.creado_en
          FROM revistas r
          JOIN categorias c ON r.categoria_id = c.id
          JOIN usuarios u ON r.subida_por = u.id
          ORDER BY r.creado_en DESC LIMIT 10
        ');
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)):
          $badge = match($r['estado']) { 'publicada' => 'badge-pub', 'borrador' => 'badge-dra', default => 'badge-arc' };
        ?>
        <tr>
          <td><?= htmlspecialchars($r['titulo']) ?></td>
          <td><?= htmlspecialchars($r['categoria']) ?></td>
          <td><span class="badge <?= $badge ?>"><?= $r['estado'] ?></span></td>
          <td><?= htmlspecialchars($r['autor']) ?></td>
          <td><?= date('d/m/Y', strtotime($r['creado_en'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>