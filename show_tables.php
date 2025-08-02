<?php
require_once 'config/config.php';

// Mostrar todas las tablas
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h1>Tablas en la base de datos</h1>";
echo "<ul>";
foreach ($tables as $table) {
    echo "<li>$table</li>";
}
echo "</ul>";

// Mostrar la estructura de la tabla de asistencias
echo "<h2>Estructura de la tabla 'asistencias'</h2>";
$stmt = $pdo->query("DESCRIBE asistencias");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";

// Mostrar la estructura de la tabla de inscritos
echo "<h2>Estructura de la tabla 'inscritos'</h2>";
$stmt = $pdo->query("DESCRIBE inscritos");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";
?> 