<?php
require_once '../config/session.php';
require_once '../config/db.php';
assert($pdo instanceof PDO);

$id   = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'es';

if (!$id) { http_response_code(404); exit; }

if ($lang === 'en') {
    $stmt = $pdo->prepare('SELECT pdf_blob FROM revistas_en WHERE revista_id = ?');
} else {
    $stmt = $pdo->prepare('SELECT pdf_blob FROM revistas WHERE id = ?');
}
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !$row['pdf_blob']) { http_response_code(404); exit; }

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="revista_' . $id . '.pdf"');
header('Content-Length: ' . strlen($row['pdf_blob']));
echo $row['pdf_blob'];
