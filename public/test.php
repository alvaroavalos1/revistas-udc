<?php
require_once '../config/db.php';

if ($pdo) {
    echo '✅ Conexión exitosa a la base de datos';
} else {
    echo '❌ Error de conexión';
}
?>