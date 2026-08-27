<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/observation_notifications.php';

$limit = isset($argv[1]) ? filter_var($argv[1], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 100],
]) : 25;
$limit = $limit === false ? 25 : (int) $limit;

try {
    if (!observationNotificationSmtpIsConfigured()) {
        fwrite(STDERR, "SMTP no configurado; no se procesaron entregas.\n");
        exit(2);
    }
    $result = observationNotificationProcessPending($pdo, $limit);
    fwrite(STDOUT, sprintf(
        "Outbox procesada: %d evento(s), %d entrega(s).\n",
        (int) $result['eventos'],
        (int) $result['entregas_procesadas']
    ));
    exit(0);
} catch (Throwable $exception) {
    error_log('Error procesando outbox de observaciones: ' . observationNotificationSafeError($exception));
    fwrite(STDERR, "No se pudo procesar la outbox. Revisa el log técnico.\n");
    exit(1);
}

