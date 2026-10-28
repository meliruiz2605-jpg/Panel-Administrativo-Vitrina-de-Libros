<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'usuario_demo');
define('DB_PASS', 'contraseña_demo');
define('DB_NAME', 'nombre_basededatos');

try {
    $conn = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'contraseña_demo');
?>