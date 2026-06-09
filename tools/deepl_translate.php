<?php
// Script local: lee revistas ES, traduce con DeepL, imprime SQL para Railway.
// Uso: php tools/deepl_translate.php > tools/migration_en_deepl.sql

$apiKey = '60a4ae6c-4a56-4781-988d-353fa4d22684:fx';
$deeplUrl = 'https://api-free.deepl.com/v2/translate';

// Conexión local MariaDB XAMPP
$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3307;dbname=plataforma_revistas;charset=utf8mb4',
    'root', 'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$revistas = $pdo->query(
    'SELECT id, titulo, descripcion, publicada_en FROM revistas ORDER BY id'
)->fetchAll(PDO::FETCH_ASSOC);

// Armar array de textos: primero todos los títulos, luego todas las descripciones
$textos = [];
foreach ($revistas as $r) {
    $textos[] = $r['titulo'];
}
foreach ($revistas as $r) {
    $textos[] = $r['descripcion'] ?? '';
}

$n = count($revistas);

// Llamar DeepL (un único request con los $n*2 textos)
$payload = json_encode([
    'text'        => $textos,
    'source_lang' => 'ES',
    'target_lang' => 'EN-US',
]);

$ch = curl_init($deeplUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: DeepL-Auth-Key ' . $apiKey,
        'Content-Type: application/json',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    fwrite(STDERR, "DeepL error HTTP $httpCode: $response\n");
    exit(1);
}

$data = json_decode($response, true);
$translations = array_column($data['translations'], 'text');

if (count($translations) !== $n * 2) {
    fwrite(STDERR, "DeepL returned " . count($translations) . " translations, expected " . ($n * 2) . "\n");
    exit(1);
}

$titulos_en = array_slice($translations, 0, $n);
$descs_en   = array_slice($translations, $n);

// Post-process: DeepL confunde "UdeC" (Universidad de Colima) con "University of Córdoba"
foreach ($titulos_en as &$t) {
    $t = str_replace(['University of Córdoba', 'University of Cordoba'], 'University of Colima', $t);
}
unset($t);
foreach ($descs_en as &$d) {
    $d = str_replace(['University of Córdoba', 'University of Cordoba'], 'University of Colima', $d);
}
unset($d);

// Generar SQL usando $pdo->quote() para escape correcto de cualquier apóstrofe
echo "-- DeepL translations for revistas_en — generated " . date('Y-m-d H:i:s') . "\n";
echo "-- Deletes existing rows and re-inserts with DeepL translations.\n\n";
echo "DELETE FROM revistas_en;\n\n";

$insertLines = [];
foreach ($revistas as $i => $r) {
    $id     = (int) $r['id'];
    $titulo = $pdo->quote($titulos_en[$i]);
    $desc   = $pdo->quote($descs_en[$i]);
    $fecha  = $r['publicada_en'] ? $pdo->quote($r['publicada_en']) : 'NOW()';
    $insertLines[] = "($id, 1, $titulo, $desc, 'publicada', $fecha)";
}

echo "INSERT INTO revistas_en (revista_id, subida_por, titulo, descripcion, estado, publicada_en) VALUES\n";
echo implode(",\n", $insertLines) . ";\n\n";
echo "SELECT COUNT(*) AS inserted FROM revistas_en;\n";
