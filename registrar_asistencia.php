<?php
require_once 'config/config.php';
require_once 'includes/observation_notifications.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actividad_id = filter_input(INPUT_POST, 'actividad_id', FILTER_VALIDATE_INT);
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: date('Y-m-d');
    $asistencias = $_POST['asistencias'] ?? [];
    // No sanitizar observaciones aquí - se guardan tal cual y se escapan al mostrar
    $observaciones = $_POST['observaciones'] ?? '';
    $notificationEventId = null;

    if (!$actividad_id || !isset($_SESSION['centro_id']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $_SESSION['mensaje_error'] = 'No se pudo validar la actividad o la fecha.';
        header('Location: index.php');
        exit;
    }

    try {
        // La actividad y su centro se resuelven en servidor; el formulario no decide destinatarios.
        $scopeStmt = $pdo->prepare(
            'SELECT a.id
             FROM actividades a
             INNER JOIN instalaciones i ON i.id = a.instalacion_id
             WHERE a.id = ? AND i.centro_id = ?'
        );
        $scopeStmt->execute([$actividad_id, (int) $_SESSION['centro_id']]);
        if (!$scopeStmt->fetchColumn()) {
            throw new RuntimeException('La actividad no pertenece al centro de la sesión.');
        }

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
        $stmt_check = $pdo->prepare("SELECT id, observacion FROM observaciones WHERE actividad_id = ? AND fecha = ? FOR UPDATE");
        $stmt_check->execute([$actividad_id, $fecha]);
        $existingObservation = $stmt_check->fetch(PDO::FETCH_ASSOC);
        $previousObservation = $existingObservation['observacion'] ?? '';

        if ($existingObservation) {
            // Si existe, actualizamos
            $stmt_obs = $pdo->prepare("UPDATE observaciones SET observacion = ? WHERE actividad_id = ? AND fecha = ?");
            $stmt_obs->execute([$observaciones, $actividad_id, $fecha]);
            $observationId = (int) $existingObservation['id'];
        } else {
            // Si no existe, insertamos
            $stmt_obs = $pdo->prepare("INSERT INTO observaciones (actividad_id, fecha, observacion) VALUES (?, ?, ?)");
            $stmt_obs->execute([$actividad_id, $fecha, $observaciones]);
            $observationId = (int) $pdo->lastInsertId();
        }

        // Crear la outbox dentro de la transacción. Un fallo de infraestructura de
        // notificaciones se diagnostica, pero nunca invalida asistencia/observación.
        $pdo->exec('SAVEPOINT observation_notification_queue');
        try {
            $notificationEventId = observationNotificationQueue(
                $pdo,
                $actividad_id,
                $observationId,
                $fecha,
                $previousObservation,
                $observaciones
            );
            $pdo->exec('RELEASE SAVEPOINT observation_notification_queue');
        } catch (Throwable $notificationException) {
            $pdo->exec('ROLLBACK TO SAVEPOINT observation_notification_queue');
            $pdo->exec('RELEASE SAVEPOINT observation_notification_queue');
            error_log(sprintf(
                'Puri no pudo encolar observacion: actividad_id=%d centro_id=%d error=%s',
                $actividad_id,
                (int) $_SESSION['centro_id'],
                observationNotificationSafeError($notificationException)
            ));
        }

        // Confirmamos la transacción
        $pdo->commit();

        // Confirmar la navegación antes del transporte y liberar el bloqueo de
        // sesión. En PHP-FPM, el monitor recibe la respuesta antes de hablar con SMTP.
        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        $mensaje = "Asistencias registradas correctamente para el día $fecha_formateada. ";
        $mensaje .= "Total de asistentes: $asistencias_registradas.";
        $_SESSION['mensaje_exito'] = $mensaje;
        header("Location: asistencia.php?actividad_id=" . $actividad_id . "&fecha=" . $fecha);
        session_write_close();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Intento inmediato fuera de la transacción. Si SMTP aún no está configurado,
        // el evento permanece pendiente para el worker de reintentos.
        if ($notificationEventId) {
            try {
                observationNotificationDispatchEvent($pdo, $notificationEventId);
            } catch (Throwable $notificationException) {
                error_log(sprintf(
                    'Puri no pudo procesar outbox: evento_id=%d error=%s',
                    $notificationEventId,
                    observationNotificationSafeError($notificationException)
                ));
            }
        }
        exit;
    } catch (Throwable $e) {
        // Si hay un error, revertimos la transacción
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log(sprintf(
            'Error registrando asistencia: actividad_id=%d centro_id=%d error=%s',
            (int) $actividad_id,
            (int) ($_SESSION['centro_id'] ?? 0),
            observationNotificationSafeError($e)
        ));
        
        // Guardar mensaje de error en la sesión
        $_SESSION['mensaje_error'] = 'No se pudo registrar la asistencia. Inténtalo de nuevo.';
        
        header("Location: asistencia.php?actividad_id=" . $actividad_id . "&fecha=" . $fecha);
        exit;
    }
} else {
    header("Location: index.php");
}
?>
