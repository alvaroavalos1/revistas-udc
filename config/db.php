<?php
// Detectar si estamos en local o en el servidor
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // Configuración LOCAL (XAMPP)
    $host     = 'localhost';
    $port     = '3307';
    $dbname   = 'plataforma_revistas';
    $user     = 'root';
    $password = 'root';
} else {
    // Configuración SERVIDOR (InfinityFree)
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
    die(json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]));
}
?>