<?php
require_once 'config/config.php';

// Verificar sesión
if(!isset($_SESSION['centro_id'])){
    header("Location: index.php");
    exit;
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // 1. Eliminar la columna DNI
    $stmt = $pdo->prepare("ALTER TABLE inscritos DROP COLUMN dni");
    $stmt->execute();
    
    // 2. Eliminar la columna teléfono
    $stmt = $pdo->prepare("ALTER TABLE inscritos DROP COLUMN telefono");
    $stmt->execute();
    
    // Confirmar los cambios
    $pdo->commit();
    
    $mensaje = "Se han eliminado correctamente las columnas DNI y teléfono de la tabla inscritos.";
    $tipo = "success";
    
} catch (PDOException $e) {
    // Revertir los cambios en caso de error
    $pdo->rollBack();
    
    $mensaje = "Error al modificar la tabla: " . $e->getMessage();
    $tipo = "error";
}

// Redirigir a una página de confirmación
$_SESSION['mensaje'] = $mensaje;
$_SESSION['tipo_mensaje'] = $tipo;
header("Location: confirmacion.php");
exit;
?> 