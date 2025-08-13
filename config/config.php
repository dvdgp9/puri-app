<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Configurar el manejador de errores para mostrar todos los errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr en $errfile:$errline");
    return false;
});


// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'pasarusr');
define('DB_PASS', 'pasarcontr');
define('DB_NAME', 'pasarlistabdd');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    die("Error de conexión a la base de datos. Por favor, verifica las credenciales y que el servidor MySQL esté funcionando.");
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

