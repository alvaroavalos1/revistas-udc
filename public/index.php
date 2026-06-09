<?php
require_once '../config/db.php';
assert($pdo instanceof PDO);

$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'es';
$cat  = isset($_GET['cat']) ? (int)$_GET['cat'] : null;

if (isset($_GET['visita'])) {
    if ($pdo !== null) {
        try {
            $vid = (int)$_GET['visita'];
            $pdo->prepare('UPDATE revistas SET visitas = visitas + 1 WHERE id = ?')->execute([$vid]);
        } catch (\Throwable $e) { /* silencioso */ }
    }
    echo 'ok'; exit;
}

// Búsqueda
$busqueda = trim($_GET['q'] ?? '');

// Defaults en caso de error de BD
$ui             = [];
$categorias     = [];
$total_revistas = 0;
$conteos        = [];
$mas_visitadas  = [];
$recientes      = [];
$resultados_busqueda = [];
$revistas_cat   = [];
$cat_actual     = null;
$db_ok          = false;

if ($pdo !== null) {
try {
$stmt = $pdo->query('SELECT clave, texto_es, texto_en FROM ui_textos');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ui[$row['clave']] = $lang === 'en' ? $row['texto_en'] : $row['texto_es'];
}
$db_ok = true;

$categorias     = $pdo->query('SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre_es')->fetchAll(PDO::FETCH_ASSOC);
$total_revistas = $pdo->query('SELECT COUNT(*) FROM revistas WHERE estado = "publicada"')->fetchColumn();

// Conteo por categoría
$conteos = [];
$stmt_c  = $pdo->query('SELECT categoria_id, COUNT(*) as total FROM revistas WHERE estado = "publicada" GROUP BY categoria_id');
while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
    $conteos[$row['categoria_id']] = $row['total'];
}

if ($lang === 'en') {
    $mas_visitadas = $pdo->query('
        SELECT re.titulo, re.descripcion, re.portada_url, re.pdf_url, re.revista_id, r.visitas, r.categoria_id
        FROM revistas_en re JOIN revistas r ON re.revista_id = r.id
        WHERE re.estado = "publicada" ORDER BY r.visitas DESC LIMIT 6
    ')->fetchAll(PDO::FETCH_ASSOC);
    $recientes = $pdo->query('
        SELECT re.titulo, re.descripcion, re.portada_url, re.pdf_url, re.revista_id, r.categoria_id
        FROM revistas_en re JOIN revistas r ON re.revista_id = r.id
        WHERE re.estado = "publicada" ORDER BY re.creado_en DESC LIMIT 6
    ')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $mas_visitadas = $pdo->query('
        SELECT id AS revista_id, titulo, descripcion, portada_url, pdf_url, visitas, categoria_id
        FROM revistas WHERE estado = "publicada" ORDER BY visitas DESC LIMIT 6
    ')->fetchAll(PDO::FETCH_ASSOC);
    $recientes = $pdo->query('
        SELECT id AS revista_id, titulo, descripcion, portada_url, pdf_url, categoria_id
        FROM revistas WHERE estado = "publicada" ORDER BY creado_en DESC LIMIT 6
    ')->fetchAll(PDO::FETCH_ASSOC);
}

// Búsqueda
if ($busqueda) {
    if ($lang === 'en') {
        $stmt_b = $pdo->prepare('
            SELECT re.titulo, re.descripcion, re.portada_url, re.pdf_url, re.revista_id, r.categoria_id
            FROM revistas_en re JOIN revistas r ON re.revista_id = r.id
            WHERE re.estado = "publicada" AND (re.titulo LIKE ? OR re.descripcion LIKE ?)
        ');
    } else {
        $stmt_b = $pdo->prepare('
            SELECT id AS revista_id, titulo, descripcion, portada_url, pdf_url, categoria_id
            FROM revistas WHERE estado = "publicada" AND (titulo LIKE ? OR descripcion LIKE ?)
        ');
    }
    $like = '%' . $busqueda . '%';
    $stmt_b->execute([$like, $like]);
    $resultados_busqueda = $stmt_b->fetchAll(PDO::FETCH_ASSOC);
}

// Revistas por categoría
if ($cat) {
    $stmt_cat = $pdo->prepare('SELECT * FROM categorias WHERE id = ?');
    $stmt_cat->execute([$cat]);
    $cat_actual = $stmt_cat->fetch(PDO::FETCH_ASSOC);
    if ($lang === 'en') {
        $stmt_rev = $pdo->prepare('
            SELECT re.titulo, re.descripcion, re.portada_url, re.pdf_url, re.revista_id, r.categoria_id
            FROM revistas_en re JOIN revistas r ON re.revista_id = r.id
            WHERE re.estado = "publicada" AND r.categoria_id = ? ORDER BY re.creado_en DESC
        ');
    } else {
        $stmt_rev = $pdo->prepare('
            SELECT id AS revista_id, titulo, descripcion, portada_url, pdf_url, categoria_id
            FROM revistas WHERE estado = "publicada" AND categoria_id = ? ORDER BY creado_en DESC
        ');
    }
    $stmt_rev->execute([$cat]);
    $revistas_cat = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);
}
} catch (\Throwable $e) { /* usa defaults vacíos declarados arriba */ }
}

$iconos_cat = ['ti-rocket', 'ti-chart-bar', 'ti-building', 'ti-cpu', 'ti-heart', 'ti-book', 'ti-flask', 'ti-music'];
$colores_cat = ['#EBF3FB', '#EAF3DE', '#FAEEDA', '#EEEDFE', '#FEF2F2', '#E1F5EE', '#F0FFF4', '#FFF5F5'];
$colores_txt = ['#003B7A', '#3B6D11', '#856d00', '#5b21b6', '#B91C1C', '#0F6E56', '#276749', '#9B2C2C'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Revistas UDC</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background: #f4f6fa; min-height: 100vh; }

    /* Topbar */
    .topbar { background: #003B7A; padding: 0 24px; height: 56px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
    .topbar-left { display: flex; align-items: center; gap: 14px; }
    .menu-btn { background: none; border: none; color: #F5C518; font-size: 24px; cursor: pointer; padding: 4px; }
    .brand { display: flex; align-items: center; gap: 8px; }
    .brand-dot { width: 7px; height: 7px; border-radius: 50%; background: #F5C518; }
    .brand-text { color: #fff; font-size: 16px; font-weight: 500; }
    .lang-pill { background: #F5C518; color: #003B7A; font-size: 11px; font-weight: 500; padding: 3px 12px; border-radius: 20px; }

    /* Drawer */
    .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; }
    .overlay.open { display: block; }
    .drawer { position: fixed; top: 0; left: -280px; width: 260px; height: 100vh; background: #fff; z-index: 200; transition: left 0.25s ease; display: flex; flex-direction: column; overflow-y: auto; }
    .drawer.open { left: 0; }
    .drawer-header { background: #003B7A; padding: 22px 18px 16px; }
    .drawer-header h2 { color: #fff; font-size: 15px; font-weight: 500; }
    .drawer-header p  { color: rgba(255,255,255,0.5); font-size: 11px; margin-top: 3px; }
    .drawer-section { padding: 14px 18px 4px; font-size: 10px; color: #aaa; text-transform: uppercase; letter-spacing: 0.06em; }
    .drawer-item { display: flex; align-items: center; gap: 10px; padding: 11px 18px; font-size: 13px; color: #333; text-decoration: none; cursor: pointer; }
    .drawer-item:hover { background: #f4f6fa; }
    .drawer-item i { font-size: 17px; color: #888; }
    .lang-sub { display: none; flex-direction: column; }
    .lang-sub.open { display: flex; }
    .lang-option { padding: 9px 18px 9px 44px; font-size: 13px; color: #555; text-decoration: none; }
    .lang-option:hover { background: #f4f6fa; }
    .lang-option.active { color: #003B7A; font-weight: 500; }
    .drawer-divider { height: 1px; background: #f0f0f0; margin: 4px 0; }
    .cat-link { display: flex; align-items: center; gap: 10px; padding: 10px 18px; font-size: 13px; color: #555; text-decoration: none; }
    .cat-link i { font-size: 16px; color: #aaa; }
    .cat-link:hover { background: #f4f6fa; }
    .cat-link.active { background: #EBF3FB; color: #003B7A; font-weight: 500; border-left: 3px solid #F5C518; }
    .cat-link.active i { color: #003B7A; }

    /* Hero */
    .hero { background: #003B7A; padding: 40px 24px 52px; position: relative; overflow: hidden; }
    .hero::before { content: ''; position: absolute; top: -60px; right: -60px; width: 300px; height: 300px; border-radius: 50%; background: rgba(245,197,24,0.06); }
    .hero::after  { content: ''; position: absolute; bottom: -80px; left: 30%; width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,0.03); }
    .hero-title { color: #fff; font-size: 26px; font-weight: 500; margin-bottom: 8px; }
    .hero-title span { color: #F5C518; }
    .hero-sub { color: rgba(255,255,255,0.55); font-size: 14px; margin-bottom: 28px; }
    .hero-search { display: flex; gap: 0; max-width: 500px; position: relative; z-index: 1; }
    .hero-search input { flex: 1; padding: 13px 18px; border: none; border-radius: 10px 0 0 10px; font-size: 14px; outline: none; }
    .hero-search button { padding: 13px 20px; background: #F5C518; border: none; border-radius: 0 10px 10px 0; cursor: pointer; font-size: 16px; color: #003B7A; font-weight: bold; }
    .hero-search button:hover { background: #e6b800; }
    .hero-stats { display: flex; gap: 12px; margin-top: 28px; flex-wrap: wrap; position: relative; z-index: 1; }
    .hero-stat { background: rgba(255,255,255,0.08); border: 0.5px solid rgba(255,255,255,0.15); border-radius: 10px; padding: 12px 20px; }
    .hero-stat-val { color: #F5C518; font-size: 22px; font-weight: 500; }
    .hero-stat-label { color: rgba(255,255,255,0.5); font-size: 11px; margin-top: 2px; }
    .hero-bar { position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: #F5C518; }

    /* Barra compacta de categoría */
    .cat-bar { background: #003B7A; border-bottom: 3px solid #F5C518; padding: 14px 24px; display: flex; align-items: center; gap: 14px; }
    .cat-bar-back { display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.6); font-size: 13px; text-decoration: none; white-space: nowrap; }
    .cat-bar-back:hover { color: #F5C518; }
    .cat-bar-divider { color: rgba(255,255,255,0.25); font-size: 16px; }
    .cat-bar-name { color: #fff; font-size: 15px; font-weight: 500; }
    .cat-bar-count { margin-left: auto; background: rgba(245,197,24,0.15); color: #F5C518; border: 0.5px solid rgba(245,197,24,0.3); font-size: 12px; padding: 3px 10px; border-radius: 20px; white-space: nowrap; }

    /* Contenido */
    .content { padding: 28px 24px; max-width: 1100px; margin: 0 auto; }

    /* Categorías visuales */
    .cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-bottom: 36px; }
    .cat-card { border-radius: 12px; padding: 16px; cursor: pointer; text-decoration: none; display: flex; flex-direction: column; gap: 8px; border: 1.5px solid transparent; transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s; }
    .cat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); border-color: rgba(0,0,0,0.08); }
    .cat-card i { font-size: 24px; }
    .cat-card-name { font-size: 13px; font-weight: 500; }
    .cat-card-count { font-size: 11px; opacity: 0.7; }

    /* Secciones */
    .section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; margin-top: 8px; }
    .section-bar { width: 3px; height: 22px; background: #F5C518; border-radius: 2px; flex-shrink: 0; }
    .section-title { font-size: 15px; font-weight: 500; color: #1a202c; }
    .section-sub { font-size: 12px; color: #aaa; margin-left: auto; }
    .section-wrap { margin-bottom: 36px; }

    /* Grid de tarjetas */
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(175px, 1fr)); gap: 14px; }
    .card { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; opacity: 0; transform: translateY(20px); animation: fadeUp 0.4s ease forwards; }
    .card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,59,122,0.12); }
    @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
    .card:nth-child(1) { animation-delay: 0.05s; }
    .card:nth-child(2) { animation-delay: 0.10s; }
    .card:nth-child(3) { animation-delay: 0.15s; }
    .card:nth-child(4) { animation-delay: 0.20s; }
    .card:nth-child(5) { animation-delay: 0.25s; }
    .card:nth-child(6) { animation-delay: 0.30s; }
    .card-img { width: 100%; height: 140px; background: #EBF3FB; display: flex; align-items: center; justify-content: center; font-size: 42px; overflow: hidden; position: relative; }
    .card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
    .card:hover .card-img img { transform: scale(1.05); }
    .card-hover-btn { position: absolute; inset: 0; background: rgba(0,59,122,0.7); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; }
    .card:hover .card-hover-btn { opacity: 1; }
    .card-hover-btn span { background: #F5C518; color: #003B7A; padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 500; }
    .card-body { padding: 12px 12px 14px; }
    .card-title { font-size: 13px; font-weight: 500; color: #1a202c; margin-bottom: 3px; line-height: 1.35; }
    .card-cat { font-size: 11px; color: #aaa; margin-bottom: 8px; }
    .badge-visits { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #003B7A; background: #EBF3FB; padding: 3px 8px; border-radius: 20px; }
    .badge-new { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #3B6D11; background: #EAF3DE; padding: 3px 8px; border-radius: 20px; }
    .badge-visits i, .badge-new i { font-size: 11px; }

    /* Breadcrumb */
    .breadcrumb { font-size: 13px; color: #aaa; margin-bottom: 16px; }
    .breadcrumb a { color: #003B7A; text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }

    /* Búsqueda */
    .search-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
    .search-badge { background: #003B7A; color: #fff; font-size: 12px; padding: 3px 10px; border-radius: 20px; }

    .empty { text-align: center; padding: 60px 24px; color: #aaa; font-size: 14px; }

    /* Visor PDF */
    .pdf-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 300; flex-direction: column; }
    .pdf-overlay.open { display: flex; }
    .pdf-topbar { background: #003B7A; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; border-bottom: 3px solid #F5C518; }
    .pdf-topbar h3 { color: #fff; font-size: 14px; font-weight: 500; }
    .pdf-actions { display: flex; gap: 10px; align-items: center; }
    .btn-download { padding: 7px 16px; background: #F5C518; color: #003B7A; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; }
    .btn-download:hover { background: #e6b800; }
    .btn-close { background: none; border: none; color: rgba(255,255,255,0.7); font-size: 22px; cursor: pointer; }
    .btn-close:hover { color: #fff; }
    .pdf-frame { flex: 1; width: 100%; border: none; }

    /* Footer */
    .footer { background: #003B7A; color: rgba(255,255,255,0.5); text-align: center; padding: 24px; font-size: 12px; margin-top: 40px; border-top: 3px solid #F5C518; }
    .footer strong { color: #F5C518; }
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <button class="menu-btn" onclick="openDrawer()" aria-label="Menú">&#9776;</button>
    <div class="brand">
      <div class="brand-dot"></div>
      <span class="brand-text">Revistas UDC</span>
    </div>
  </div>
  <span class="lang-pill"><?= $lang === 'es' ? 'MX Español' : 'US English' ?></span>
</div>

<!-- Overlay -->
<div class="overlay" id="overlay" onclick="closeDrawer()"></div>

<!-- Drawer -->
<div class="drawer" id="drawer">
  <div class="drawer-header">
    <h2>Revistas UDC</h2>
    <p><?= $lang === 'es' ? 'Universidad de Colima' : 'University of Colima' ?></p>
  </div>
  <div class="drawer-section"><?= $lang === 'es' ? 'Idioma' : 'Language' ?></div>
  <div class="drawer-item" onclick="toggleLang()">
    <i class="ti ti-world" aria-hidden="true"></i>
    <?= $lang === 'es' ? 'Idioma' : 'Language' ?>
    <i class="ti ti-chevron-down" id="chevron" style="margin-left:auto;font-size:13px;" aria-hidden="true"></i>
  </div>
  <div class="lang-sub" id="langSub">
    <a class="lang-option <?= $lang === 'es' ? 'active' : '' ?>" href="?lang=es">🇲🇽 Español</a>
    <a class="lang-option <?= $lang === 'en' ? 'active' : '' ?>" href="?lang=en">🇺🇸 English</a>
  </div>
  <div class="drawer-divider"></div>
  <div class="drawer-section"><?= $lang === 'es' ? 'Categorías' : 'Categories' ?></div>
  <a class="cat-link <?= !$cat ? 'active' : '' ?>" href="?lang=<?= $lang ?>">
    <i class="ti ti-home" aria-hidden="true"></i>
    <?= $lang === 'es' ? 'Inicio' : 'Home' ?>
  </a>
  <?php foreach ($categorias as $c): ?>
    <a class="cat-link <?= $cat == $c['id'] ? 'active' : '' ?>" href="?lang=<?= $lang ?>&cat=<?= $c['id'] ?>">
      <i class="ti ti-bookmark" aria-hidden="true"></i>
      <?= htmlspecialchars($lang === 'en' ? $c['nombre_en'] : $c['nombre_es']) ?>
    </a>
  <?php endforeach; ?>
  <div class="drawer-divider"></div>
  <a class="drawer-item" href="login.php">
    <i class="ti ti-lock" aria-hidden="true"></i>
    <?= $lang === 'es' ? 'Administración' : 'Administration' ?>
  </a>
</div>

<?php if ($cat && $cat_actual): ?>
<!-- Barra compacta de categoría -->
<div class="cat-bar">
  <a class="cat-bar-back" href="?lang=<?= $lang ?>">&#8592; <?= $lang === 'es' ? 'Inicio' : 'Home' ?></a>
  <span class="cat-bar-divider">/</span>
  <span class="cat-bar-name"><?= htmlspecialchars($lang === 'en' ? ($cat_actual['nombre_en'] ?? $cat_actual['nombre_es']) : $cat_actual['nombre_es']) ?></span>
  <?php $n = $conteos[$cat] ?? 0; ?>
  <span class="cat-bar-count"><?= $n ?> <?= $lang === 'es' ? ($n === 1 ? 'revista' : 'revistas') : ($n === 1 ? 'magazine' : 'magazines') ?></span>
</div>
<?php else: ?>
<!-- Hero -->
<div class="hero">
  <div class="hero-title">
    <?= $lang === 'es' ? 'Plataforma de <span>Revistas</span>' : 'Magazine <span>Platform</span>' ?>
  </div>
  <div class="hero-sub">
    <?= $lang === 'es' ? 'Universidad de Colima — Difusión académica y cultural' : 'University of Colima — Academic and cultural outreach' ?>
  </div>
  <form class="hero-search" method="GET" action="">
    <input type="hidden" name="lang" value="<?= $lang ?>">
    <input type="text" name="q" placeholder="<?= $lang === 'es' ? 'Buscar revista...' : 'Search magazine...' ?>" value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit">&#128269;</button>
  </form>
  <div class="hero-stats">
    <div class="hero-stat">
      <div class="hero-stat-val"><?= $total_revistas ?></div>
      <div class="hero-stat-label"><?= $lang === 'es' ? 'Revistas' : 'Magazines' ?></div>
    </div>
    <div class="hero-stat">
      <div class="hero-stat-val"><?= count($categorias) ?></div>
      <div class="hero-stat-label"><?= $lang === 'es' ? 'Categorías' : 'Categories' ?></div>
    </div>
    <div class="hero-stat">
      <div class="hero-stat-val">2</div>
      <div class="hero-stat-label"><?= $lang === 'es' ? 'Idiomas' : 'Languages' ?></div>
    </div>
  </div>
  <div class="hero-bar"></div>
</div>
<?php endif; ?>

<!-- Contenido -->
<div class="content">

  <?php if ($busqueda): ?>
    <!-- Resultados de búsqueda -->
    <div class="search-header">
      <div class="section-bar"></div>
      <span class="section-title"><?= $lang === 'es' ? 'Resultados para:' : 'Results for:' ?></span>
      <span class="search-badge"><?= htmlspecialchars($busqueda) ?></span>
      <a href="?lang=<?= $lang ?>" style="margin-left:auto;font-size:12px;color:#003B7A;text-decoration:none;">✕ <?= $lang === 'es' ? 'Limpiar' : 'Clear' ?></a>
    </div>
    <?php if (empty($resultados_busqueda)): ?>
      <div class="empty">🔍 <?= $lang === 'es' ? 'No se encontraron revistas.' : 'No magazines found.' ?></div>
    <?php else: ?>
      <div class="grid"><?php foreach ($resultados_busqueda as $rev): echo tarjeta($rev, $lang, false); endforeach; ?></div>
    <?php endif; ?>

  <?php elseif ($cat && $cat_actual): ?>
    <!-- Vista de categoría -->
    <div class="breadcrumb">
      <a href="?lang=<?= $lang ?>">&#8592; <?= $lang === 'es' ? 'Inicio' : 'Home' ?></a>
      &nbsp;/&nbsp; <?= htmlspecialchars($lang === 'en' ? $cat_actual['nombre_en'] : $cat_actual['nombre_es']) ?>
    </div>
    <div class="section-header">
      <div class="section-bar"></div>
      <span class="section-title"><?= htmlspecialchars($lang === 'en' ? $cat_actual['nombre_en'] : $cat_actual['nombre_es']) ?></span>
      <span class="section-sub"><?= count($revistas_cat) ?> <?= $lang === 'es' ? 'revistas' : 'magazines' ?></span>
    </div>
    <?php if (empty($revistas_cat)): ?>
      <div class="empty">📭 <?= $lang === 'es' ? 'No hay revistas en esta categoría.' : 'No magazines in this category yet.' ?></div>
    <?php else: ?>
      <div class="grid"><?php foreach ($revistas_cat as $rev): echo tarjeta($rev, $lang, false); endforeach; ?></div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Inicio -->

    <!-- Categorías visuales -->
    <div class="section-header">
      <div class="section-bar"></div>
      <span class="section-title"><?= $lang === 'es' ? 'Explorar por categoría' : 'Browse by category' ?></span>
    </div>
    <div class="cat-grid">
      <?php foreach ($categorias as $i => $c):
        $idx   = $i % count($iconos_cat);
        $color = $colores_cat[$idx];
        $txt   = $colores_txt[$idx];
        $icono = $iconos_cat[$idx];
        $total = $conteos[$c['id']] ?? 0;
        $nombre = $lang === 'en' ? $c['nombre_en'] : $c['nombre_es'];
      ?>
      <a class="cat-card" href="?lang=<?= $lang ?>&cat=<?= $c['id'] ?>" style="background:<?= $color ?>; color:<?= $txt ?>">
        <i class="ti <?= $icono ?>" style="color:<?= $txt ?>" aria-hidden="true"></i>
        <span class="cat-card-name"><?= htmlspecialchars($nombre) ?></span>
        <span class="cat-card-count"><?= $total ?> <?= $lang === 'es' ? 'revistas' : 'magazines' ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Más visitadas -->
    <div class="section-wrap">
      <div class="section-header">
        <div class="section-bar"></div>
        <span class="section-title">🔥 <?= $lang === 'es' ? 'Más visitadas' : 'Most visited' ?></span>
        <span class="section-sub"><?= $lang === 'es' ? 'Top esta semana' : 'Top this week' ?></span>
      </div>
      <?php if (empty($mas_visitadas)): ?>
        <div class="empty">📭 <?= $lang === 'es' ? 'No hay revistas publicadas aún.' : 'No magazines published yet.' ?></div>
      <?php else: ?>
        <div class="grid"><?php foreach ($mas_visitadas as $rev): echo tarjeta($rev, $lang, true); endforeach; ?></div>
      <?php endif; ?>
    </div>

    <!-- Recientes -->
    <div class="section-wrap">
      <div class="section-header">
        <div class="section-bar"></div>
        <span class="section-title">🆕 <?= $lang === 'es' ? 'Recientes' : 'Recent' ?></span>
        <span class="section-sub"><?= $lang === 'es' ? 'Últimas publicaciones' : 'Latest publications' ?></span>
      </div>
      <?php if (empty($recientes)): ?>
        <div class="empty">📭 <?= $lang === 'es' ? 'No hay revistas publicadas aún.' : 'No magazines published yet.' ?></div>
      <?php else: ?>
        <div class="grid"><?php foreach ($recientes as $rev): echo tarjeta($rev, $lang, false); endforeach; ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<!-- Footer -->
<div class="footer">
  <strong>Universidad de Colima</strong> &nbsp;·&nbsp;
  <?= $lang === 'es' ? 'Plataforma de Revistas Institucional' : 'Institutional Magazine Platform' ?>
  &nbsp;·&nbsp; <?= date('Y') ?>
</div>

<!-- Visor PDF -->
<div class="pdf-overlay" id="pdfOverlay">
  <div class="pdf-topbar">
    <h3 id="pdfTitulo"></h3>
    <div class="pdf-actions">
      <a id="btnDescargar" href="#" download class="btn-download">
        &#8595; <?= $lang === 'es' ? 'Descargar' : 'Download' ?>
      </a>
      <button class="btn-close" onclick="cerrarPDF()">&#x2715;</button>
    </div>
  </div>
  <iframe class="pdf-frame" id="pdfFrame" src=""></iframe>
</div>

<?php
function tarjeta($rev, $lang, $mostrar_visitas = false) {
    $titulo = htmlspecialchars($rev['titulo']);
    $pdf    = htmlspecialchars($rev['pdf_url'] ?? '');
    $id     = $rev['revista_id'];
    ob_start(); ?>
    <div class="card" onclick="window.location='revista.php?id=<?= $id ?>&lang=<?= $lang ?>'">
      <div class="card-img">
        <?php if (!empty($rev['portada_url'])): ?>
          <img src="<?= htmlspecialchars((str_starts_with($rev['portada_url'], 'http') || str_starts_with($rev['portada_url'], 'data:')) ? $rev['portada_url'] : '../' . $rev['portada_url']) ?>" alt="Portada" onerror="this.outerHTML='<span style=\'font-size:42px\'>📄</span>'">
        <?php else: ?>
          &#x1F4C4;
        <?php endif; ?>
        <div class="card-hover-btn">
          <span><?= $lang === 'es' ? 'Leer' : 'Read' ?></span>
        </div>
      </div>
      <div class="card-body">
        <div class="card-title"><?= $titulo ?></div>
        <?php if (!empty($rev['descripcion'])): ?>
          <div class="card-cat"><?= htmlspecialchars(substr($rev['descripcion'], 0, 60)) ?>...</div>
        <?php endif; ?>
        <?php if ($mostrar_visitas && isset($rev['visitas'])): ?>
          <span class="badge-visits"><i class="ti ti-eye"></i> <?= number_format($rev['visitas']) ?> <?= $lang === 'es' ? 'visitas' : 'views' ?></span>
        <?php else: ?>
          <span class="badge-new"><i class="ti ti-sparkles"></i> <?= $lang === 'es' ? 'Nueva' : 'New' ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php return ob_get_clean();
}
?>

<script>
function openDrawer() {
  document.getElementById('drawer').classList.add('open');
  document.getElementById('overlay').classList.add('open');
}
function closeDrawer() {
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('overlay').classList.remove('open');
}
function toggleLang() {
  const sub = document.getElementById('langSub');
  const ch  = document.getElementById('chevron');
  sub.classList.toggle('open');
  ch.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : '';
}
function abrirPDF(pdf, titulo, id) {
  if (!pdf) {
    alert('<?= $lang === 'es' ? 'Esta revista no tiene PDF disponible.' : 'This magazine has no PDF available.' ?>');
    return;
  }
  fetch('?visita=' + id);
  document.getElementById('pdfTitulo').textContent = titulo;
  document.getElementById('pdfFrame').src          = '../' + pdf;
  document.getElementById('btnDescargar').href     = '../' + pdf;
  document.getElementById('pdfOverlay').classList.add('open');
}
function cerrarPDF() {
  document.getElementById('pdfOverlay').classList.remove('open');
  document.getElementById('pdfFrame').src = '';
}
</script>
</body>
</html>
