<?php
require_once 'config/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: instalaciones.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // Borra las actividades asociadas a esta instalación.
    $stmt_del_actividades = $pdo->prepare("DELETE FROM actividades WHERE instalacion_id = ?");
    $stmt_del_actividades->execute([$id]);

    // Borra la instalación.
    $stmt_del_instalacion = $pdo->prepare("DELETE FROM instalaciones WHERE id = ?");
    $stmt_del_instalacion->execute([$id]);

    $pdo->commit();
    header("Location: instalaciones.php"); // Redirige a la lista de instalaciones
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error al borrar: " . $e->getMessage(); // Manejo de errores apropiado
    exit;
} 