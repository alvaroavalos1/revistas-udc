<?php
require_once '../config/session.php';
require_once '../config/db.php';
assert($pdo instanceof PDO);

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']); exit;
}

$revista_id = isset($_POST['revista_id']) ? (int)$_POST['revista_id'] : 0;
if (!$revista_id) {
    echo json_encode(['error' => 'revista_id requerido']); exit;
}

$stmt = $pdo->prepare('SELECT titulo, descripcion FROM revistas WHERE id = ?');
$stmt->execute([$revista_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['error' => 'Revista no encontrada']); exit;
}

$api_key = getenv('DEEPL_API_KEY');
if (!$api_key) {
    echo json_encode(['error' => 'DEEPL_API_KEY no configurada en el servidor']); exit;
}

function deepl_translate(string $text, string $api_key): string {
    if (trim($text) === '') return '';
    $ch = curl_init('https://api-free.deepl.com/v2/translate');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Authorization: DeepL-Auth-Key ' . $api_key],
        CURLOPT_POSTFIELDS     => http_build_query([
            'text'        => $text,
            'source_lang' => 'ES',
            'target_lang' => 'EN',
        ]),
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['translations'][0]['text'] ?? '';
}

$titulo_en      = deepl_translate($row['titulo'],      $api_key);
$descripcion_en = deepl_translate($row['descripcion'], $api_key);

echo json_encode(['titulo' => $titulo_en, 'descripcion' => $descripcion_en]);
