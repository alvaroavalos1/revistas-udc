<?php
// CONEXIÓN A BASE DE DATOS
require_once '../config/db.php';
assert($pdo instanceof PDO);

// DETECTAR IDIOMA E ID DE REVISTA
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'es';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// REDIRIGIR SI NO SE ESPECIFICA UN ID VÁLIDO
if (!$id) {
    header('Location: index.php?lang=' . $lang);
    exit;
}

// OBTENER DATOS DE LA REVISTA SEGÚN IDIOMA
if ($lang === 'en') {
    $stmt = $pdo->prepare('
        SELECT re.titulo, re.descripcion, re.portada_url, re.pdf_url, re.estado,
               r.id AS revista_id, r.categoria_id, r.publicada_en,
               c.nombre_en AS categoria, u.nombre AS autor
        FROM revistas_en re
        JOIN revistas r ON re.revista_id = r.id
        JOIN categorias c ON r.categoria_id = c.id
        JOIN usuarios u ON re.subida_por = u.id
        WHERE re.revista_id = ? AND re.estado = "publicada"
    ');
} else {
    $stmt = $pdo->prepare('
        SELECT r.id AS revista_id, r.titulo, r.descripcion, r.portada_url, r.pdf_url,
               r.estado, r.categoria_id, r.publicada_en,
               c.nombre_es AS categoria, u.nombre AS autor
        FROM revistas r
        JOIN categorias c ON r.categoria_id = c.id
        JOIN usuarios u ON r.subida_por = u.id
        WHERE r.id = ? AND r.estado = "publicada"
    ');
}

$stmt->execute([$id]);
$revista = $stmt->fetch(PDO::FETCH_ASSOC);

// REDIRIGIR SI LA REVISTA NO EXISTE O NO ESTÁ PUBLICADA
if (!$revista) {
    header('Location: index.php?lang=' . $lang);
    exit;
}

// REGISTRAR VISITA AL CARGAR LA PÁGINA
$pdo->prepare('UPDATE revistas SET visitas = visitas + 1 WHERE id = ?')->execute([$id]);

// OBTENER REVISTAS RELACIONADAS DE LA MISMA CATEGORÍA
if ($lang === 'en') {
    $stmt_rel = $pdo->prepare('
        SELECT re.titulo, re.portada_url, re.revista_id
        FROM revistas_en re
        JOIN revistas r ON re.revista_id = r.id
        WHERE r.categoria_id = ? AND re.revista_id != ? AND re.estado = "publicada"
        LIMIT 4
    ');
} else {
    $stmt_rel = $pdo->prepare('
        SELECT r.id AS revista_id, r.titulo, r.portada_url
        FROM revistas r
        WHERE r.categoria_id = ? AND r.id != ? AND r.estado = "publicada"
        LIMIT 4
    ');
}
$stmt_rel->execute([$revista['categoria_id'], $id]);
$relacionadas = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);

// OBTENER CATEGORÍAS PARA EL MENÚ LATERAL
$categorias = $pdo->query('SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre_es')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($revista['titulo']) ?> — Revistas UDC</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background: #f4f6fa; min-height: 100vh; }

    /* Topbar */
    .topbar { background: #003B7A; padding: 0 24px; height: 56px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
    .topbar-left { display: flex; align-items: center; gap: 14px; }
    .menu-btn { background: none; border: none; color: #F5C518; font-size: 24px; cursor: pointer; padding: 4px; }
    .brand { display: flex; align-items: center; gap: 8px; }
    .brand-dot { width: 7px; height: 7px; border-radius: 50%; background: #F5C518; }
    .brand-text { color: #fff; font-size: 16px; font-weight: 500; text-decoration: none; }
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
    .cat-link:hover { background: #f4f6fa; color: #003B7A; }

    /* Contenido */
    .content { max-width: 1000px; margin: 0 auto; padding: 28px 24px; display: grid; grid-template-columns: 1fr 280px; gap: 28px; align-items: start; }

    /* Artículo principal */
    .article { background: #fff; border-radius: 14px; border: 0.5px solid #e2e8f0; overflow: hidden; }
    .article-cover { width: 100%; height: 280px; max-height: 280px; background: #EBF3FB; display: flex; align-items: center; justify-content: center; font-size: 80px; overflow: hidden; position: relative; }
    .article-cover img { width: 100%; height: 100%; object-fit: cover; }
    .article-cover-bar { position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: #F5C518; }
    .article-body { padding: 28px; }
    .article-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
    .meta-cat { background: #EBF3FB; color: #003B7A; font-size: 12px; padding: 3px 12px; border-radius: 20px; font-weight: 500; }
    .meta-autor { font-size: 12px; color: #aaa; }
    .meta-fecha { font-size: 12px; color: #aaa; }
    .article-title { font-size: 22px; font-weight: 500; color: #1a202c; margin-bottom: 12px; line-height: 1.4; }
    .article-desc { font-size: 14px; color: #555; line-height: 1.7; margin-bottom: 24px; }
    .article-actions { display: flex; gap: 12px; flex-wrap: wrap; }
    .btn-leer { display: flex; align-items: center; gap: 8px; padding: 12px 24px; background: #003B7A; color: #fff; border: none; border-radius: 10px; font-size: 14px; cursor: pointer; font-weight: 500; text-decoration: none; }
    .btn-leer:hover { background: #00306a; }
    .btn-descargar { display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: #FEF9E7; color: #003B7A; border: 2px solid #F5C518; border-radius: 10px; font-size: 14px; cursor: pointer; font-weight: 500; text-decoration: none; }
    .btn-descargar:hover { background: #fdf3c0; }
    .no-pdf { background: #f4f6fa; border-radius: 10px; padding: 16px; text-align: center; font-size: 13px; color: #aaa; }

    /* Sidebar */
    .sidebar-content { display: flex; flex-direction: column; gap: 20px; }
    .side-card { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; }
    .side-card-header { background: #003B7A; padding: 12px 16px; display: flex; align-items: center; gap: 8px; }
    .side-card-bar { width: 3px; height: 16px; background: #F5C518; border-radius: 2px; }
    .side-card-header h3 { color: #fff; font-size: 13px; font-weight: 500; }
    .side-card-body { padding: 14px; }

    /* Revistas relacionadas */
    .rel-item { display: flex; gap: 10px; align-items: center; padding: 8px 0; border-bottom: 0.5px solid #f4f6fa; text-decoration: none; }
    .rel-item:last-child { border-bottom: none; }
    .rel-item:hover .rel-title { color: #003B7A; }
    .rel-img { width: 48px; height: 48px; border-radius: 8px; background: #EBF3FB; display: flex; align-items: center; justify-content: center; font-size: 20px; overflow: hidden; flex-shrink: 0; }
    .rel-img img { width: 100%; height: 100%; object-fit: cover; }
    .rel-title { font-size: 12px; font-weight: 500; color: #1a202c; line-height: 1.3; }
    .rel-empty { font-size: 12px; color: #aaa; text-align: center; padding: 12px; }

    /* Visor PDF */
    .pdf-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 300; flex-direction: column; }
    .pdf-overlay.open { display: flex; }
    .pdf-topbar { background: #003B7A; padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; border-bottom: 3px solid #F5C518; flex-wrap: wrap; gap: 8px; }
    .pdf-topbar h3 { color: #fff; font-size: 14px; font-weight: 500; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pdf-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .pdf-nav { display: flex; align-items: center; gap: 6px; }
    .pdf-nav-btn { background: rgba(255,255,255,0.15); border: none; color: #fff; font-size: 20px; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; }
    .pdf-nav-btn:hover:not(:disabled) { background: rgba(255,255,255,0.28); }
    .pdf-nav-btn:disabled { opacity: 0.3; cursor: default; }
    .pdf-page-info { color: rgba(255,255,255,0.85); font-size: 12px; white-space: nowrap; min-width: 80px; text-align: center; }
    .btn-download { padding: 6px 14px; background: #F5C518; color: #003B7A; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; }
    .btn-download:hover { background: #e6b800; }
    .btn-close { background: none; border: none; color: rgba(255,255,255,0.7); font-size: 22px; cursor: pointer; line-height: 1; }
    .btn-close:hover { color: #fff; }
    .pdf-container { flex: 1; overflow-y: auto; background: #525659; display: flex; flex-direction: column; align-items: center; padding: 16px 8px; gap: 10px; }
    .pdf-page-wrap { box-shadow: 0 2px 12px rgba(0,0,0,0.5); }
    .pdf-page-wrap canvas { display: block; max-width: 100%; }
    .pdf-loading-msg { color: rgba(255,255,255,0.75); font-size: 14px; text-align: center; padding: 48px 24px; }

    /* Breadcrumb */
    .breadcrumb-wrap { max-width: 1000px; margin: 0 auto; padding: 16px 24px 0; }
    .breadcrumb { display: flex; align-items: center; gap: 8px; background: #EEF3FA; padding: 10px 16px; border-radius: 99px; width: fit-content; margin-bottom: 20px; }
    .breadcrumb a { font-size: 13px; color: #003B7A; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 4px; }
    .breadcrumb-sep { color: #003B7A; opacity: 0.4; font-size: 13px; }
    .breadcrumb-current { font-size: 13px; color: #003B7A; opacity: 0.6; }

    /* Footer */
    .footer { background: #003B7A; color: rgba(255,255,255,0.5); text-align: center; padding: 24px; font-size: 12px; margin-top: 40px; border-top: 3px solid #F5C518; }
    .footer strong { color: #F5C518; }

    @media (max-width: 768px) {
      .content { grid-template-columns: 1fr; }
      .article-cover { height: 180px; max-height: 180px; }
    }
  </style>
</head>
<body>

<!-- TOPBAR: BARRA DE NAVEGACIÓN SUPERIOR -->
<div class="topbar">
  <div class="topbar-left">
    <button class="menu-btn" onclick="openDrawer()" aria-label="Menú">&#9776;</button>
    <div class="brand">
      <div class="brand-dot"></div>
      <a class="brand-text" href="index.php?lang=<?= $lang ?>">Revistas UDC</a>
    </div>
  </div>
  <span class="lang-pill"><?= $lang === 'es' ? 'MX Español' : 'US English' ?></span>
</div>

<!-- OVERLAY: FONDO OSCURO AL ABRIR EL DRAWER -->
<div class="overlay" id="overlay" onclick="closeDrawer()"></div>

<!-- DRAWER: MENÚ LATERAL CON IDIOMAS Y CATEGORÍAS -->
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
  <!-- SELECTOR DE IDIOMA DESPLEGABLE -->
  <div class="lang-sub" id="langSub">
    <a class="lang-option <?= $lang === 'es' ? 'active' : '' ?>" href="?id=<?= $id ?>&lang=es">🇲🇽 Español</a>
    <a class="lang-option <?= $lang === 'en' ? 'active' : '' ?>" href="?id=<?= $id ?>&lang=en">🇺🇸 English</a>
  </div>
  <div class="drawer-divider"></div>
  <div class="drawer-section"><?= $lang === 'es' ? 'Categorías' : 'Categories' ?></div>
  <!-- ENLACES DE CATEGORÍAS EN EL DRAWER -->
  <a class="cat-link" href="index.php?lang=<?= $lang ?>">
    <i class="ti ti-home" aria-hidden="true"></i>
    <?= $lang === 'es' ? 'Inicio' : 'Home' ?>
  </a>
  <?php foreach ($categorias as $c): ?>
    <a class="cat-link" href="index.php?lang=<?= $lang ?>&cat=<?= $c['id'] ?>">
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

<!-- BREADCRUMB: RUTA DE NAVEGACIÓN -->
<div class="breadcrumb-wrap">
  <div class="breadcrumb">
    <a href="index.php?lang=<?= $lang ?>"><i class="ti ti-home" style="font-size:14px"></i><?= $lang === 'es' ? 'Inicio' : 'Home' ?></a>
    <span class="breadcrumb-sep">/</span>
    <a href="index.php?lang=<?= $lang ?>&cat=<?= $revista['categoria_id'] ?>"><?= htmlspecialchars($revista['categoria']) ?></a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-current"><?= htmlspecialchars($revista['titulo']) ?></span>
  </div>
</div>

<!-- CONTENIDO PRINCIPAL: ARTÍCULO Y SIDEBAR -->
<div class="content">

  <!-- ARTÍCULO PRINCIPAL -->
  <div class="article">
    <!-- PORTADA DE LA REVISTA -->
    <div class="article-cover">
      <?php if ($revista['portada_url']): ?>
        <img src="<?= htmlspecialchars((str_starts_with($revista['portada_url'], 'http') || str_starts_with($revista['portada_url'], 'data:')) ? $revista['portada_url'] : '../' . $revista['portada_url']) ?>" alt="Portada" onerror="this.outerHTML='<span style=\'font-size:42px\'>📄</span>'">
      <?php else: ?>
        📄
      <?php endif; ?>
      <div class="article-cover-bar"></div>
    </div>
    <!-- CUERPO DEL ARTÍCULO: METADATOS, TÍTULO, DESCRIPCIÓN Y ACCIONES -->
    <div class="article-body">
      <!-- METADATOS: CATEGORÍA, AUTOR Y FECHA DE PUBLICACIÓN -->
      <div class="article-meta">
        <span class="meta-cat"><?= htmlspecialchars($revista['categoria']) ?></span>
        <span class="meta-autor">👤 <?= htmlspecialchars($revista['autor']) ?></span>
        <?php if ($revista['publicada_en']): ?>
          <span class="meta-fecha">📅 <?= date('d/m/Y', strtotime($revista['publicada_en'])) ?></span>
        <?php endif; ?>
      </div>
      <h1 class="article-title"><?= htmlspecialchars($revista['titulo']) ?></h1>
      <?php if ($revista['descripcion']): ?>
        <p class="article-desc"><?= nl2br(htmlspecialchars($revista['descripcion'])) ?></p>
      <?php endif; ?>

      <!-- BOTONES DE ACCIÓN: LEER EN LÍNEA Y DESCARGAR PDF -->
      <div class="article-actions">
        <?php if ($revista['pdf_url']): ?>
          <button class="btn-leer" onclick="abrirPDF('ver_pdf.php?id=<?= $id ?>&lang=<?= $lang ?>', '<?= addslashes(htmlspecialchars($revista['titulo'])) ?>')">
            📖 <?= $lang === 'es' ? 'Leer revista' : 'Read magazine' ?>
          </button>
          <a class="btn-descargar" href="<?= htmlspecialchars(str_starts_with($revista['pdf_url'], 'http') ? $revista['pdf_url'] : '../' . $revista['pdf_url']) ?>" download>
            ⬇️ <?= $lang === 'es' ? 'Descargar PDF' : 'Download PDF' ?>
          </a>
        <?php else: ?>
          <div class="no-pdf">📭 <?= $lang === 'es' ? 'PDF no disponible todavía.' : 'PDF not available yet.' ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- SIDEBAR: REVISTAS RELACIONADAS DE LA MISMA CATEGORÍA -->
  <div class="sidebar-content">
    <div class="side-card">
      <div class="side-card-header">
        <div class="side-card-bar"></div>
        <h3><?= $lang === 'es' ? 'Más de esta categoría' : 'More from this category' ?></h3>
      </div>
      <div class="side-card-body">
        <?php if (empty($relacionadas)): ?>
          <div class="rel-empty"><?= $lang === 'es' ? 'No hay más revistas en esta categoría.' : 'No more magazines in this category.' ?></div>
        <?php else: ?>
          <!-- RENDERIZAR TARJETAS DE REVISTAS RELACIONADAS -->
          <?php foreach ($relacionadas as $rel): ?>
            <a class="rel-item" href="revista.php?id=<?= $rel['revista_id'] ?>&lang=<?= $lang ?>">
              <div class="rel-img">
                <?php if (!empty($rel['portada_url'])): ?>
                  <img src="<?= htmlspecialchars((str_starts_with($rel['portada_url'], 'http') || str_starts_with($rel['portada_url'], 'data:')) ? $rel['portada_url'] : '../' . $rel['portada_url']) ?>" alt="" loading="lazy" onerror="this.outerHTML='<span style=\'font-size:42px\'>📄</span>'">
                <?php else: ?>
                  📄
                <?php endif; ?>
              </div>
              <span class="rel-title"><?= htmlspecialchars($rel['titulo']) ?></span>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- FOOTER -->
<div class="footer">
  <strong>Universidad de Colima</strong> &nbsp;·&nbsp;
  <?= $lang === 'es' ? 'Plataforma de Revistas Institucional' : 'Institutional Magazine Platform' ?>
  &nbsp;·&nbsp; <?= date('Y') ?>
</div>

<!-- VISOR PDF: MODAL FLOTANTE PARA LECTURA EN LÍNEA -->
<div class="pdf-overlay" id="pdfOverlay">
  <div class="pdf-topbar">
    <h3 id="pdfTitulo"></h3>
    <div class="pdf-actions">
      <div class="pdf-nav">
        <button class="pdf-nav-btn" id="btnPrev" onclick="cambiarPagina(-1)" disabled>&#8249;</button>
        <span class="pdf-page-info" id="pageInfo">—</span>
        <button class="pdf-nav-btn" id="btnNext" onclick="cambiarPagina(1)" disabled>&#8250;</button>
      </div>
      <a id="btnDescargar" href="#" download class="btn-download">
        ⬇️ <?= $lang === 'es' ? 'Descargar' : 'Download' ?>
      </a>
      <button class="btn-close" onclick="cerrarPDF()">&#x2715;</button>
    </div>
  </div>
  <div class="pdf-container" id="pdfContainer"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
// ABRIR DRAWER (MENÚ LATERAL)
function openDrawer() {
  document.getElementById('drawer').classList.add('open');
  document.getElementById('overlay').classList.add('open');
}
// CERRAR DRAWER
function closeDrawer() {
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('overlay').classList.remove('open');
}
// ALTERNAR SUBMENÚ DE IDIOMAS
function toggleLang() {
  const sub = document.getElementById('langSub');
  const ch  = document.getElementById('chevron');
  sub.classList.toggle('open');
  ch.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : '';
}

// PDF.js — configurar worker
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

var _pdf = null, _pdfCurrentPage = 1, _pdfTotalPages = 0, _pdfObserver = null, _pdfPageEls = [];

// ABRIR VISOR PDF CON PDF.JS
function abrirPDF(url, titulo) {
  document.getElementById('pdfTitulo').textContent = titulo;
  document.getElementById('btnDescargar').href = url;
  document.getElementById('pdfOverlay').classList.add('open');

  var container = document.getElementById('pdfContainer');
  container.innerHTML = '<div class="pdf-loading-msg"><?= $lang === "es" ? "Cargando PDF…" : "Loading PDF…" ?></div>';
  _pdfPageEls = []; _pdfCurrentPage = 1; _pdfTotalPages = 0;
  document.getElementById('pageInfo').textContent = '—';
  document.getElementById('btnPrev').disabled = true;
  document.getElementById('btnNext').disabled = true;

  if (_pdfObserver) { _pdfObserver.disconnect(); _pdfObserver = null; }
  if (_pdf) { _pdf.destroy(); _pdf = null; }

  pdfjsLib.getDocument(url).promise.then(function(pdf) {
    _pdf = pdf;
    _pdfTotalPages = pdf.numPages;
    container.innerHTML = '';

    var containerW = container.clientWidth - 24;
    var scale = Math.min(1.5, Math.max(0.4, containerW / 595));

    var chain = Promise.resolve();
    for (var i = 1; i <= _pdfTotalPages; i++) {
      (function(num) {
        chain = chain.then(function() {
          return pdf.getPage(num).then(function(page) {
            var vp     = page.getViewport({ scale: scale });
            var wrap   = document.createElement('div');
            wrap.className = 'pdf-page-wrap';
            wrap.id = 'pdf-page-' + num;
            var canvas = document.createElement('canvas');
            canvas.width  = vp.width;
            canvas.height = vp.height;
            wrap.appendChild(canvas);
            container.appendChild(wrap);
            _pdfPageEls.push(wrap);
            return page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
          });
        });
      })(i);
    }

    chain.then(function() {
      _pdfCurrentPage = 1;
      _actualizarNav();
      _pdfObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
          if (e.isIntersecting) {
            _pdfCurrentPage = parseInt(e.target.id.replace('pdf-page-', ''), 10);
            _actualizarNav();
          }
        });
      }, { root: container, threshold: 0.4 });
      _pdfPageEls.forEach(function(el) { _pdfObserver.observe(el); });
    });

  }).catch(function() {
    container.innerHTML = '<div class="pdf-loading-msg"><?= $lang === "es" ? "Error al cargar el PDF." : "Error loading PDF." ?></div>';
  });
}

function _actualizarNav() {
  document.getElementById('pageInfo').textContent = _pdfTotalPages > 0
    ? ('<?= $lang === "es" ? "Página" : "Page" ?> ' + _pdfCurrentPage + ' <?= $lang === "es" ? "de" : "of" ?> ' + _pdfTotalPages)
    : '—';
  document.getElementById('btnPrev').disabled = _pdfCurrentPage <= 1;
  document.getElementById('btnNext').disabled = _pdfCurrentPage >= _pdfTotalPages;
}

// NAVEGAR ENTRE PÁGINAS
function cambiarPagina(dir) {
  var t = _pdfCurrentPage + dir;
  if (t < 1 || t > _pdfTotalPages) return;
  _pdfCurrentPage = t;
  var el = document.getElementById('pdf-page-' + _pdfCurrentPage);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  _actualizarNav();
}

// CERRAR VISOR PDF Y LIBERAR MEMORIA
function cerrarPDF() {
  document.getElementById('pdfOverlay').classList.remove('open');
  if (_pdfObserver) { _pdfObserver.disconnect(); _pdfObserver = null; }
  if (_pdf) { _pdf.destroy(); _pdf = null; }
  document.getElementById('pdfContainer').innerHTML = '';
  _pdfPageEls = []; _pdfCurrentPage = 1; _pdfTotalPages = 0;
}
</script>

</body>
</html>
