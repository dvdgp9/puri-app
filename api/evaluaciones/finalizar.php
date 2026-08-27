<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesMonitorRequireMethod('POST');
    $input = evaluacionesMonitorReadJson();
    $sessionId = evaluacionesMonitorPositiveId($input['sesion_id'] ?? null, 'sesion_id');
    $confirmPending = ($input['confirmar_pendientes'] ?? false) === true;
    $session = evaluacionesMonitorRequireSession($pdo, $sessionId, $monitor_center_id);

    $pdo->beginTransaction();
    $lockStmt = $pdo->prepare('SELECT estado FROM evaluacion_sesiones WHERE id = ? FOR UPDATE');
    $lockStmt->execute([$sessionId]);
    $state = $lockStmt->fetchColumn();
    if ($state === 'finalizada') {
        $pdo->commit();
        evaluacionesMonitorRespondSuccess(evaluacionesMonitorFetchSessionDetail($pdo, $sessionId, $monitor_center_id));
    }
    evaluacionesMonitorRequireWritablePeriod($session);

    $pendingStmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT participante_ref)
         FROM evaluacion_resultados
         WHERE evaluacion_sesion_id = ? AND estado = 'sin_evaluar'"
    );
    $pendingStmt->execute([$sessionId]);
    $pending = (int) $pendingStmt->fetchColumn();
    if ($pending > 0 && !$confirmPending) {
        evaluacionesMonitorFail(
            409,
            'HAY_RESULTADOS_PENDIENTES',
            'Quedan participantes sin evaluar.',
            ['pendientes' => (string) $pending]
        );
    }

    $stmt = $pdo->prepare(
        "UPDATE evaluacion_sesiones
         SET estado = 'finalizada', finalizada_at = NOW()
         WHERE id = ? AND estado = 'en_curso'"
    );
    $stmt->execute([$sessionId]);
    $pdo->commit();

    evaluacionesMonitorRespondSuccess(evaluacionesMonitorFetchSessionDetail($pdo, $sessionId, $monitor_center_id));
} catch (EvaluacionesMonitorApiException $exception) {
    evaluacionesMonitorRollback($pdo);
    evaluacionesMonitorRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesMonitorRollback($pdo);
    evaluacionesMonitorRespondInternal($exception, 'error finalizando evaluación del monitor');
}

