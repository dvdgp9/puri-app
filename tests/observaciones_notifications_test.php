<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$upPath = $root . '/migrations/20260812_create_observation_notifications.up.sql';
$downPath = $root . '/migrations/20260812_create_observation_notifications.down.sql';
$helperPath = $root . '/includes/observation_notifications.php';
$attendancePath = $root . '/registrar_asistencia.php';
$workerPath = $root . '/scripts/process_observation_notifications.php';
$docsPath = $root . '/docs/api/notificaciones-observaciones.md';
$tests = [];

function notificationTest(string $name, callable $test): void
{
    global $tests;
    $tests[] = [$name, $test];
}

function notificationExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function notificationRead(string $path): string
{
    notificationExpect(is_file($path), 'Falta archivo: ' . $path);
    $content = file_get_contents($path);
    notificationExpect($content !== false, 'No se pudo leer: ' . $path);
    return (string) $content;
}

notificationTest('existe una migración reversible para email y outbox', function () use ($upPath, $downPath): void {
    $up = notificationRead($upPath);
    $down = notificationRead($downPath);
    foreach (['ALTER TABLE admins', 'email VARCHAR(254)', 'notificacion_observacion_eventos', 'notificacion_observacion_destinatarios'] as $needle) {
        notificationExpect(str_contains($up, $needle), 'Falta en UP: ' . $needle);
    }
    foreach (['DROP TABLE notificacion_observacion_destinatarios', 'DROP TABLE notificacion_observacion_eventos', 'DROP COLUMN email'] as $needle) {
        notificationExpect(str_contains($down, $needle), 'Falta en DOWN: ' . $needle);
    }
});

notificationTest('la outbox deduplica direcciones por evento y conserva contexto', function () use ($upPath): void {
    $sql = notificationRead($upPath);
    foreach (['UNIQUE KEY', 'evento_id', 'destinatario_email', 'observacion_snapshot', 'cuerpo_texto', 'contenido_hash', 'ultimo_error'] as $needle) {
        notificationExpect(str_contains($sql, $needle), 'Falta dato de outbox: ' . $needle);
    }
});

notificationTest('la normalización distingue vacío, mismo texto y cambio real', function () use ($helperPath): void {
    notificationExpect(is_file($helperPath), 'Falta helper de notificaciones.');
    require_once $helperPath;
    notificationExpect(observationNotificationNormalize("  Hola\r\nMundo  ") === "Hola\nMundo", 'No normaliza espacios/line endings.');
    notificationExpect(!observationNotificationShouldQueue('Hola', " Hola\r\n"), 'Reenvía el mismo texto normalizado.');
    notificationExpect(!observationNotificationShouldQueue('Hola', '   '), 'Vaciar genera correo.');
    notificationExpect(observationNotificationShouldQueue('', 'Nueva observación'), 'Crear texto no genera correo.');
    notificationExpect(observationNotificationShouldQueue('Anterior', 'Nuevo'), 'Un cambio real no genera correo.');
});

notificationTest('los destinatarios provienen solo de asignaciones explícitas', function () use ($helperPath): void {
    $content = notificationRead($helperPath);
    foreach (['admin_asignaciones', 'admins', 'FILTER_VALIDATE_EMAIL', 'LOWER', 'centro_id'] as $needle) {
        notificationExpect(str_contains($content, $needle), 'Falta regla de destinatarios: ' . $needle);
    }
    notificationExpect(!str_contains($content, "role = 'superadmin'"), 'Se notifican superadmins solo por su rol.');
});

notificationTest('el correo incluye observación completa y contexto de actividad', function () use ($helperPath): void {
    $content = notificationRead($helperPath);
    foreach (['centro_nombre', 'instalacion_nombre', 'actividad_nombre', 'actividad_grupo', 'dias_semana', 'hora_inicio', 'hora_fin', 'fecha_observacion', 'observacion'] as $needle) {
        notificationExpect(str_contains($content, $needle), 'Falta contexto: ' . $needle);
    }
});

notificationTest('SMTP usa configuración externa y no incluye secretos', function () use ($helperPath): void {
    $content = notificationRead($helperPath);
    foreach (['PURI_SMTP_HOST', 'PURI_SMTP_PORT', 'PURI_SMTP_USERNAME', 'PURI_SMTP_PASSWORD', 'PURI_SMTP_ENCRYPTION', 'PURI_SMTP_FROM_EMAIL'] as $needle) {
        notificationExpect(str_contains($content, $needle), 'Falta variable SMTP: ' . $needle);
    }
    notificationExpect(!preg_match('/PURI_SMTP_PASSWORD[\s\S]{0,80}=\s*[\'\"][^\'\"]+[\'\"]/', $content), 'Parece haber una contraseña SMTP fija.');
});

notificationTest('la asistencia confirma antes de intentar entregar', function () use ($attendancePath): void {
    $content = notificationRead($attendancePath);
    $commit = strpos($content, '$pdo->commit()');
    $dispatch = strpos($content, 'observationNotificationDispatchEvent');
    notificationExpect(str_contains($content, 'observationNotificationQueue'), 'No se encola la observación.');
    notificationExpect($commit !== false && $dispatch !== false && $commit < $dispatch, 'La entrega ocurre antes del commit de asistencia.');
    notificationExpect(str_contains($content, 'SAVEPOINT observation_notification_queue'), 'Un fallo parcial de outbox puede dejar registros incompletos.');
    notificationExpect(str_contains($content, 'ROLLBACK TO SAVEPOINT observation_notification_queue'), 'No se revierte una outbox parcial.');
    notificationExpect(str_contains($content, 'error_log'), 'Falta diagnóstico no bloqueante.');
});

notificationTest('existe un worker de reintentos y documentación operativa', function () use ($workerPath, $docsPath): void {
    $worker = notificationRead($workerPath);
    $docs = notificationRead($docsPath);
    foreach (['observationNotificationProcessPending', 'PHP_SAPI', 'limit'] as $needle) {
        notificationExpect(str_contains($worker, $needle), 'Falta worker: ' . $needle);
    }
    foreach (['SMTP', 'deduplicación', 'reintento', 'PURI_SMTP_HOST', 'sin destinatarios'] as $needle) {
        notificationExpect(str_contains($docs, $needle), 'Falta documentación: ' . $needle);
    }
});

$passed = 0;
$failed = 0;
foreach ($tests as [$name, $test]) {
    try {
        $test();
        $passed++;
        fwrite(STDOUT, "[OK] {$name}\n");
    } catch (Throwable $error) {
        $failed++;
        fwrite(STDERR, "[FAIL] {$name}\n       {$error->getMessage()}\n");
    }
}

fwrite(STDOUT, sprintf("\nResultado notificaciones: %d correctas, %d fallidas.\n", $passed, $failed));
exit($failed === 0 ? 0 : 1);
