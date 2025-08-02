<?php
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actividad_id = filter_input(INPUT_POST, 'actividad_id', FILTER_SANITIZE_NUMBER_INT);
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: date('Y-m-d');
    $asistencias = $_POST['asistencias'] ?? [];
    $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    try {
        // Iniciamos una transacción para asegurar la integridad de los datos
        $pdo->beginTransaction();

        // Primero eliminamos las asistencias existentes para esa fecha y actividad
        $stmt_delete = $pdo->prepare("DELETE FROM asistencias WHERE actividad_id = ? AND fecha = ?");
        $stmt_delete->execute([$actividad_id, $fecha]);

        // Luego insertamos las nuevas asistencias
        $stmt = $pdo->prepare("INSERT INTO asistencias (actividad_id, usuario_id, fecha, asistio) VALUES (?, ?, ?, ?)");
        
        // Contador de asistencias registradas
        $asistencias_registradas = 0;
        
        foreach ($asistencias as $usuario_id => $estado) {
            $stmt->execute([$actividad_id, $usuario_id, $fecha, $estado]);
            if ($estado == 1) {
                $asistencias_registradas++;
            }
        }

        // Guardar las observaciones
        // Primero verificamos si ya existe una entrada para esta fecha y actividad
        $stmt_check = $pdo->prepare("SELECT id FROM observaciones WHERE actividad_id = ? AND fecha = ?");
        $stmt_check->execute([$actividad_id, $fecha]);
        
        if ($stmt_check->fetchColumn()) {
            // Si existe, actualizamos
            $stmt_obs = $pdo->prepare("UPDATE observaciones SET observacion = ? WHERE actividad_id = ? AND fecha = ?");
            $stmt_obs->execute([$observaciones, $actividad_id, $fecha]);
        } else {
            // Si no existe, insertamos
            $stmt_obs = $pdo->prepare("INSERT INTO observaciones (actividad_id, fecha, observacion) VALUES (?, ?, ?)");
            $stmt_obs->execute([$actividad_id, $fecha, $observaciones]);
        }

        // Confirmamos la transacción
        $pdo->commit();

        // Crear mensaje de confirmación
        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        $mensaje = "Asistencias registradas correctamente para el día $fecha_formateada. ";
        $mensaje .= "Total de asistentes: $asistencias_registradas.";
        
        // Guardar mensaje en la sesión
        $_SESSION['mensaje_exito'] = $mensaje;

        header("Location: asistencia.php?actividad_id=" . $actividad_id . "&fecha=" . $fecha);
    } catch (PDOException $e) {
        // Si hay un error, revertimos la transacción
        $pdo->rollBack();
        
        // Guardar mensaje de error en la sesión
        $_SESSION['mensaje_error'] = "Error al registrar las asistencias: " . $e->getMessage();
        
        header("Location: asistencia.php?actividad_id=" . $actividad_id . "&fecha=" . $fecha);
        exit;
    }
} else {
    header("Location: index.php");
}
?>
