<?php
if (isset($_SERVER['HTTP_HOST']) && (
    $_SERVER['HTTP_HOST'] === 'localhost' ||
    str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1') ||
    str_starts_with($_SERVER['HTTP_HOST'], 'localhost:')
)) {
    // Local XAMPP — MariaDB 10.4 en puerto 3307
    $host     = 'localhost';
    $port     = '3307';
    $dbname   = 'plataforma_revistas';
    $user     = 'root';
    $password = 'root';
} else {
    // Railway — variables de entorno del plugin MySQL
    $host     = getenv('MYSQLHOST');
    $port     = getenv('MYSQLPORT') ?: '3306';
    $dbname   = getenv('MYSQLDATABASE');
    $user     = getenv('MYSQLUSER');
    $password = getenv('MYSQLPASSWORD');
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(503);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body><p>Servicio temporalmente no disponible.</p></body></html>');
}
?>