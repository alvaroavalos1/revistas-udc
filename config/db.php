<?php
$host     = 'sql305.infinityfree.com';
$dbname   = 'if0_42050746_revistas';
$user     = 'if0_42050746';
$password = 'S6Q1bpOaiT';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]));
}
?>