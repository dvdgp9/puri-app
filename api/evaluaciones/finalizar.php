<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesMonitorRequireMethod('POST');
    $input = evaluacionesMonitorReadJson();
    $sessionId = evaluacionesMonitorPositiveId($input['sesion_id'] ?? null, 'sesion_id');
    $confirmPending = ($input['confirmar_pendientes'] ?? false) === true;
    $updates = $input['resultados'] ?? [];
    if (!is_array($updates) || count($updates) > 10000) {
        evaluacionesMonitorFail(422, 'RESULTADOS_INVALIDOS', 'La lista de resultados no es válida.');
    }
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

    // Los cambios visibles se validan y guardan antes del cierre, en esta misma
    // transacción. Si una fila falla, tampoco se finaliza la sesión.
    $fieldStmt = $pdo->prepare('SELECT id, tipo_dato FROM evaluacion_campos WHERE id = ? AND evaluacion_id = ?');
    $snapshotStmt = $pdo->prepare('SELECT id FROM evaluacion_resultados WHERE evaluacion_sesion_id = ? AND evaluacion_campo_id = ? AND inscrito_id = ?');
    $saveStmt = $pdo->prepare('UPDATE evaluacion_resultados SET estado = ?, valor_numero = ?, valor_texto = ?, calificador = ?, updated_by_centro_id = ?, updated_by_admin_id = NULL WHERE id = ?');
    $seen = [];
    foreach ($updates as $index => $update) {
        if (!is_array($update)) evaluacionesMonitorFail(422, 'RESULTADO_INVALIDO', 'Revisa la fila ' . ($index + 1) . '.');
        $fieldId = evaluacionesMonitorPositiveId($update['campo_id'] ?? null, 'campo_id');
        $participantId = evaluacionesMonitorPositiveId($update['inscrito_id'] ?? null, 'inscrito_id');
        $key = $fieldId . ':' . $participantId;
        if (isset($seen[$key])) evaluacionesMonitorFail(422, 'RESULTADO_DUPLICADO', 'Hay resultados repetidos en el envío.');
        $seen[$key] = true;
        $fieldStmt->execute([$fieldId, (int) $session['evaluacion_id']]);
        $field = $fieldStmt->fetch(PDO::FETCH_ASSOC);
        $snapshotStmt->execute([$sessionId, $fieldId, $participantId]);
        $resultId = $snapshotStmt->fetchColumn();
        if (!$field || !$resultId) evaluacionesMonitorFail(422, 'RESULTADO_NO_PERTENECE', 'Una fila no pertenece a esta evaluación.');
        $validated = evaluacionesValidateResult($field, $update);
        if (!$validated['valid']) {
            evaluacionesMonitorFail(422, 'TIPO_DATO_INVALIDO', 'Revisa el valor de una prueba antes de finalizar.', ['resultado:' . $key => 'Valor inválido']);
        }
        $data = $validated['data'];
        $saveStmt->execute([$data['estado'], $data['valor_numero'], $data['valor_texto'], $data['calificador'], $monitor_center_id, $resultId]);
    }

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
