<?php
require_once '../config/session.php';
require_once '../config/db.php';
assert($pdo instanceof PDO);
require_once '../config/r2.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: revistas_en.php');
    exit;
}

// Eliminar portada
if (isset($_GET['eliminar_portada'])) {
    $row = $pdo->prepare('SELECT portada_url FROM revistas_en WHERE id = ?');
    $row->execute([$id]);
    $old = $row->fetchColumn();
    if ($old) delete_from_r2($old);
    $pdo->prepare('UPDATE revistas_en SET portada_url = NULL WHERE id = ?')->execute([$id]);
    header('Location: editar_revista_en.php?id=' . $id . '&msg=portada_eliminada');
    exit;
}

// Eliminar PDF
if (isset($_GET['eliminar_pdf'])) {
    $row = $pdo->prepare('SELECT pdf_url FROM revistas_en WHERE id = ?');
    $row->execute([$id]);
    $old = $row->fetchColumn();
    if ($old) delete_from_r2($old);
    $pdo->prepare('UPDATE revistas_en SET pdf_url = NULL, pdf_blob = NULL WHERE id = ?')->execute([$id]);
    header('Location: editar_revista_en.php?id=' . $id . '&msg=pdf_eliminado');
    exit;
}

// Obtener versión en inglés con datos de la revista original
$stmt = $pdo->prepare('
    SELECT re.*, r.titulo AS titulo_es, r.categoria_id, c.nombre_es AS categoria
    FROM revistas_en re
    JOIN revistas r ON re.revista_id = r.id
    JOIN categorias c ON r.categoria_id = c.id
    WHERE re.id = ?
');
$stmt->execute([$id]);
$rev = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rev) {
    header('Location: revistas_en.php');
    exit;
}

$mensaje = '';
$error   = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'portada_eliminada') $mensaje = 'Cover image removed successfully';
    if ($_GET['msg'] === 'pdf_eliminado')     $mensaje = 'PDF removed successfully';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $estado      = $_POST['estado'];

    $portada_url = $rev['portada_url'];
    if (!empty($_FILES['portada']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $key     = 'portadas/portada_en_' . uniqid() . '.' . $ext;
            $new_url = upload_to_r2($_FILES['portada']['tmp_name'], $key, $_FILES['portada']['type']);
            if ($new_url) {
                if ($rev['portada_url']) delete_from_r2($rev['portada_url']);
                $portada_url = $new_url;
            } else {
                $portada_url = 'data:' . $_FILES['portada']['type'] . ';base64,' . base64_encode(file_get_contents($_FILES['portada']['tmp_name']));
            }
        } else {
            $error = 'Cover must be an image (jpg, png, gif, webp)';
        }
    }

    $pdf_url     = $rev['pdf_url'];
    $new_pdf_blob = null;
    $update_blob  = false;
    if (!empty($_FILES['pdf']['name']) && !$error) {
        $ext_pdf = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
        if ($ext_pdf === 'pdf') {
            $key     = 'pdfs/revista_en_' . uniqid() . '.pdf';
            $new_url = upload_to_r2($_FILES['pdf']['tmp_name'], $key, 'application/pdf');
            if ($new_url) {
                if ($rev['pdf_url']) delete_from_r2($rev['pdf_url']);
                $pdf_url     = $new_url;
                $update_blob = true;
            } else {
                $new_pdf_blob = file_get_contents($_FILES['pdf']['tmp_name']);
                $pdf_url      = 'public/ver_pdf.php?id=' . $rev['revista_id'] . '&lang=en';
                $update_blob  = true;
            }
        } else {
            $error = 'File must be a PDF';
        }
    }

    if (!$error && $titulo) {
        $stmt = $pdo->prepare('
            UPDATE revistas_en
            SET titulo = ?, descripcion = ?, portada_url = ?, pdf_url = ?, estado = ?, publicada_en = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $titulo, $descripcion, $portada_url, $pdf_url, $estado,
            $estado === 'publicada' ? date('Y-m-d H:i:s') : null,
            $id
        ]);
        if ($update_blob) {
            $pdo->prepare('UPDATE revistas_en SET pdf_blob = ? WHERE id = ?')->execute([$new_pdf_blob, $id]);
        }
        $mensaje = 'English version updated successfully';

        // Refrescar datos
        $stmt2 = $pdo->prepare('
            SELECT re.*, r.titulo AS titulo_es, r.categoria_id, c.nombre_es AS categoria
            FROM revistas_en re
            JOIN revistas r ON re.revista_id = r.id
            JOIN categorias c ON r.categoria_id = c.id
            WHERE re.id = ?
        ');
        $stmt2->execute([$id]);
        $rev = $stmt2->fetch(PDO::FETCH_ASSOC);
    } elseif (!$error) {
        $error = 'Title is required';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit English version — Panel UDC</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
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
    .sb-link { display: flex; align-items: center; gap: 10px; padding: 10px 18px; font-size: 13px; color: rgba(255,255,255,0.65); text-decoration: none; border-left: 3px solid transparent; transition: background 0.2s, color 0.2s; }
    .sb-link:hover { background: rgba(255,255,255,0.07); color: #fff; }
    .sb-link.active { background: rgba(245,197,24,0.12); color: #F5C518; border-left-color: #F5C518; }
    .sb-link i { font-size: 18px; }
    .sb-bottom { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding: 10px 0; }
    .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .topbar { background: #fff; border-bottom: 3px solid #F5C518; padding: 0 24px; height: 54px; display: flex; align-items: center; justify-content: space-between; }
    .topbar h1 { font-size: 16px; color: #003B7A; font-weight: 500; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .btn-back { display: flex; align-items: center; gap: 6px; padding: 7px 14px; background: #EBF3FB; border: 1px solid #b8d4ef; border-radius: 8px; font-size: 12px; color: #003B7A; text-decoration: none; font-weight: 500; }
    .btn-back:hover { background: #d6e8f7; }
    .user-info { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #555; }
    .avatar { width: 32px; height: 32px; border-radius: 50%; background: #003B7A; color: #F5C518; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; }
    .badge-rol { background: #F5C518; color: #003B7A; font-size: 10px; padding: 2px 8px; border-radius: 20px; font-weight: 500; }
    .content { padding: 24px; flex: 1; display: grid; grid-template-columns: 1fr 300px; gap: 24px; align-items: start; }
    .form-card { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; }
    .form-card-header { background: #003B7A; padding: 16px 20px; display: flex; align-items: center; gap: 10px; }
    .form-card-header h2 { color: #fff; font-size: 14px; font-weight: 500; }
    .form-card-header span { background: #F5C518; color: #003B7A; font-size: 10px; font-weight: 500; padding: 2px 8px; border-radius: 20px; }
    .form-card-body { padding: 24px; }
    .original-ref { background: #f4f6fa; border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; font-size: 12px; color: #555; border-left: 3px solid #F5C518; }
    .original-ref strong { color: #003B7A; }
    label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; margin-top: 16px; }
    input[type=text], textarea, select { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; font-family: Arial, sans-serif; }
    input[type=text]:focus, textarea:focus, select:focus { border-color: #003B7A; }
    textarea { resize: vertical; min-height: 100px; }
    input[type=file] { font-size: 13px; width: 100%; }
    .file-actual { display: flex; align-items: center; justify-content: space-between; background: #f4f6fa; border-radius: 8px; padding: 8px 12px; margin-top: 6px; }
    .file-actual-info { font-size: 11px; color: #555; }
    .file-actual-info a { color: #003B7A; text-decoration: none; font-weight: 500; }
    .file-actual-info a:hover { text-decoration: underline; }
    .btn-eliminar-archivo { display: flex; align-items: center; gap: 4px; padding: 4px 10px; background: #FEF2F2; color: #B91C1C; border: 1px solid #fca5a5; border-radius: 6px; font-size: 11px; cursor: pointer; text-decoration: none; }
    .btn-eliminar-archivo:hover { background: #fee2e2; }
    .no-archivo { font-size: 11px; color: #aaa; margin-top: 4px; background: #f4f6fa; border-radius: 8px; padding: 8px 12px; }
    .btn-submit { width: 100%; padding: 12px; background: #003B7A; color: #fff; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; margin-top: 20px; font-weight: 500; }
    .btn-submit:hover { background: #00306a; }
    .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; border-left: 3px solid; }
    .alert-ok  { background: #EAF3DE; color: #3B6D11; border-color: #F5C518; }
    .alert-err { background: #FEF2F2; color: #B91C1C; border-color: #B91C1C; }
    .preview-card { background: #fff; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; position: sticky; top: 24px; }
    .preview-header { background: #003B7A; padding: 14px 18px; }
    .preview-header h3 { color: #fff; font-size: 13px; font-weight: 500; }
    .preview-body { padding: 16px; }
    .preview-img { width: 100%; height: 160px; background: #EBF3FB; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 48px; overflow: hidden; margin-bottom: 12px; }
    .preview-img img { width: 100%; height: 100%; object-fit: cover; }
    .preview-title { font-size: 14px; font-weight: 500; color: #1a202c; margin-bottom: 4px; }
    .preview-cat { font-size: 12px; color: #aaa; margin-bottom: 12px; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge-pub { background: #EAF3DE; color: #3B6D11; }
    .badge-dra { background: #FEF9E7; color: #856d00; }
    .badge-arc { background: #f4f6fa; color: #888; }
    .btn-ver-pdf { display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: #003B7A; color: #fff; border-radius: 8px; font-size: 12px; text-decoration: none; justify-content: center; margin-top: 12px; }
    .btn-ver-pdf:hover { background: #00306a; }
    .no-pdf { font-size: 12px; color: #aaa; text-align: center; padding: 8px; background: #f4f6fa; border-radius: 8px; margin-top: 12px; }
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
    <h1>Edit English version</h1>
    <div class="topbar-right">
      <a class="btn-back" href="revistas_en.php"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back</a>
      <div class="user-info">
        <span><?= htmlspecialchars($_SESSION['nombre']) ?></span>
        <span class="badge-rol"><?= $_SESSION['rol'] ?></span>
        <div class="avatar"><?= strtoupper(substr($_SESSION['nombre'], 0, 2)) ?></div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="form-card">
      <div class="form-card-header">
        <h2>English version data</h2>
        <span>🇺🇸 English</span>
      </div>
      <div class="form-card-body">
        <?php if ($mensaje): ?><div class="alert alert-ok"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="original-ref">
          <strong>Original (ES):</strong> <?= htmlspecialchars($rev['titulo_es']) ?>
          &nbsp;·&nbsp; <?= htmlspecialchars($rev['categoria']) ?>
        </div>

        <form method="POST" enctype="multipart/form-data">
          <label>Title in English *</label>
          <input type="text" name="titulo" value="<?= htmlspecialchars($rev['titulo']) ?>" required>

          <label>Description in English</label>
          <textarea name="descripcion"><?= htmlspecialchars($rev['descripcion'] ?? '') ?></textarea>

          <label>Status</label>
          <select name="estado">
            <option value="borrador"  <?= $rev['estado'] === 'borrador'  ? 'selected' : '' ?>>Draft</option>
            <option value="publicada" <?= $rev['estado'] === 'publicada' ? 'selected' : '' ?>>Published</option>
            <option value="archivada" <?= $rev['estado'] === 'archivada' ? 'selected' : '' ?>>Archived</option>
          </select>

          <label>Cover image</label>
          <?php if ($rev['portada_url']): ?>
            <div class="file-actual">
              <span class="file-actual-info">✅ <a href="<?= htmlspecialchars(url_asset($rev['portada_url'])) ?>" target="_blank">View current cover</a></span>
              <a class="btn-eliminar-archivo" href="?id=<?= $id ?>&eliminar_portada=1" onclick="return confirm('Remove cover image?')">
                <i class="ti ti-trash" style="font-size:12px"></i> Remove
              </a>
            </div>
            <div style="margin-top:8px;font-size:12px;color:#aaa;">Upload a new one to replace it:</div>
          <?php else: ?>
            <div class="no-archivo">📷 No cover — upload an image</div>
          <?php endif; ?>
          <input type="file" name="portada" accept="image/*" style="margin-top:8px;">

          <label>PDF file</label>
          <?php if ($rev['pdf_url']): ?>
            <div class="file-actual">
              <span class="file-actual-info">✅ <a href="<?= htmlspecialchars(url_asset($rev['pdf_url'])) ?>" target="_blank">View current PDF</a></span>
              <a class="btn-eliminar-archivo" href="?id=<?= $id ?>&eliminar_pdf=1" onclick="return confirm('Remove PDF?')">
                <i class="ti ti-trash" style="font-size:12px"></i> Remove
              </a>
            </div>
            <div style="margin-top:8px;font-size:12px;color:#aaa;">Upload a new PDF to replace it:</div>
          <?php else: ?>
            <div class="no-archivo">📄 No PDF — upload the file</div>
          <?php endif; ?>
          <input type="file" name="pdf" accept="application/pdf" style="margin-top:8px;">

          <button type="submit" class="btn-submit">Save changes</button>
        </form>
      </div>
    </div>

    <!-- Preview -->
    <div class="preview-card">
      <div class="preview-header"><h3>Preview</h3></div>
      <div class="preview-body">
        <div class="preview-img">
          <?php if ($rev['portada_url']): ?>
            <img src="<?= htmlspecialchars(url_asset($rev['portada_url'])) ?>" alt="Cover">
          <?php else: ?>
            📄
          <?php endif; ?>
        </div>
        <div class="preview-title"><?= htmlspecialchars($rev['titulo']) ?></div>
        <div class="preview-cat"><?= htmlspecialchars($rev['categoria']) ?></div>
        <?php
          $badge = match($rev['estado']) { 'publicada' => 'badge-pub', 'borrador' => 'badge-dra', default => 'badge-arc' };
        ?>
        <span class="badge <?= $badge ?>"><?= $rev['estado'] ?></span>
        <?php if ($rev['pdf_url']): ?>
          <a class="btn-ver-pdf" href="<?= htmlspecialchars(url_asset($rev['pdf_url'])) ?>" target="_blank">
            <i class="ti ti-file-text" aria-hidden="true"></i> View PDF
          </a>
        <?php else: ?>
          <div class="no-pdf">📭 No PDF yet</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

</body>
</html>
