<?php
/**
 * Debug del login - para diagnosticar el problema
 */
session_start();

echo "<h1>Debug Login</h1>";
echo "<h2>Estado de la sesión:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Test de autenticación:</h2>";
$response = file_get_contents('http://localhost:8080/admin/check_session.php');
echo "<pre>";
echo "Response from check_session.php: " . $response;
echo "</pre>";

echo "<h2>Test de acceso al dashboard:</h2>";
echo '<a href="index.html">Ir al Dashboard</a>';
?>
