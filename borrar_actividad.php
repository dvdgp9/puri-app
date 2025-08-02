<?php
require_once 'config/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: actividades.php"); // O error 404, etc.
    exit;
}
// Obtener instalacion_id antes de borrar
$stmt = $pdo->prepare("SELECT instalacion_id FROM actividades WHERE id=?");
$stmt->execute([$id]);
$actividad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actividad) {
    header("Location: actividades.php"); // O error 404
    exit;
}

$instalacion_id = $actividad['instalacion_id'];

$stmt_del = $pdo->prepare("DELETE FROM actividades WHERE id = ?");
$result = $stmt_del->execute([$id]);

if ($result) {
    header("Location: actividades.php?instalacion_id=" . $instalacion_id); // Redirigir, *con* el ID
    exit;
} else {
    // Manejar error
    echo "Error al borrar la actividad.";
    exit;
} 