<?php
if (getenv('MYSQL_HOST')) {
    // Railway — env vars del plugin MySQL
    $host     = getenv('MYSQL_HOST');
    $port     = getenv('MYSQL_PORT') ?: '3306';
    $dbname   = getenv('MYSQL_DATABASE');
    $user     = getenv('MYSQL_USER');
    $password = getenv('MYSQL_PASSWORD');
} elseif (isset($_SERVER['HTTP_HOST']) && (
    $_SERVER['HTTP_HOST'] === 'localhost' ||
    str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1') ||
    str_starts_with($_SERVER['HTTP_HOST'], 'localhost:')
)) {
    // Local XAMPP
    $host     = 'localhost';
    $port     = '3307';
    $dbname   = 'plataforma_revistas';
    $user     = 'root';
    $password = 'root';
} else {
    // InfinityFree (fallback)
    $host     = 'sql305.infinityfree.com';
    $dbname   = 'if0_42050746_revistas';
    $user     = 'if0_42050746';
    $password = 'S6Q1bpOaiT';
}

try {
    $dsn = isset($port)
        ? "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4"
        : "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(503);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body><p>Servicio temporalmente no disponible.</p></body></html>');
}
?>