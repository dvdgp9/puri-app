<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesMonitorRequireMethod('GET');
    $sessionId = evaluacionesMonitorPositiveId($_GET['sesion_id'] ?? null, 'sesion_id');
    evaluacionesMonitorRespondSuccess(
        evaluacionesMonitorFetchSessionDetail($pdo, $sessionId, $monitor_center_id)
    );
} catch (EvaluacionesMonitorApiException $exception) {
    evaluacionesMonitorRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesMonitorRespondInternal($exception, 'error cargando detalle de evaluación del monitor');
}

