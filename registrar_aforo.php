<?php
/**
 * Endpoint para registrar aforo de una actividad
 * Guarda la cantidad de personas presentes en una fecha específica
 */
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actividad_id = filter_input(INPUT_POST, 'actividad_id', FILTER_SANITIZE_NUMBER_INT);
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: date('Y-m-d');
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_SANITIZE_NUMBER_INT);
    $cantidad = max(0, (int)$cantidad); // Asegurar que sea >= 0
    $observaciones = $_POST['observaciones'] ?? '';

    try {
        // Verificar que la actividad existe y es de tipo aforo
        $stmtCheck = $pdo->prepare("SELECT tipo_control FROM actividades WHERE id = ?");
        $stmtCheck->execute([$actividad_id]);
        $tipo = $stmtCheck->fetchColumn();
        
        if ($tipo !== 'aforo') {
            $_SESSION['mensaje_error'] = "Esta actividad no es de tipo aforo.";
            header("Location: asistencia.php?actividad_id=" . $actividad_id . "&fecha=" . $fecha);
            exit;
        }

        $pdo->beginTransaction();

        // Insertar o actualizar registro de aforo (UPSERT)
        $stmtUpsert = $pdo->prepare("
            INSERT INTO aforo_registros (actividad_id, fecha, cantidad) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad), registrado_en = CURRENT_TIMESTAMP
        ");
        $stmtUpsert->execute([$actividad_id, $fecha, $cantidad]);

        // Guardar las observaciones
        $stmt_check = $pdo->prepare("SELECT id FROM observaciones WHERE actividad_id = ? AND fecha = ?");
        $stmt_check->execute([$actividad_id, $fecha]);
        
        if ($stmt_check->fetchColumn()) {
            $stmt_obs = $pdo->prepare("UPDATE observaciones SET observacion = ? WHERE actividad_id = ? AND fecha = ?");
            $stmt_obs->execute([$observaciones, $actividad_id, $fecha]);
        } else {
            $stmt_obs = $pdo->prepare("INSERT INTO observaciones (actividad_id, fecha, observacion) VALUES (?, ?, ?)");
            $stmt_obs->execute([$actividad_id, $fecha, $observaciones]);
        }

        $pdo->commit();

        // Crear mensaje de confirmación
        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        $mensaje = "Aforo registrado correctamente para el día $fecha_formateada. ";
        $mensaje .= "Personas registradas: $cantidad.";
        
        $_SESSION['mensaje_exito'] = $mensaje;

        header("Location: asistencia.php?actividad_id=" . $actividad_id . "&fecha=" . $fecha);
        exit;
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $_SESSION['mensaje_error'] = "Error al registrar el aforo: " . $e->getMessage();
        
        header("Location: asistencia.php?actividad_id=" . $actividad_id . "&fecha=" . $fecha);
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>
