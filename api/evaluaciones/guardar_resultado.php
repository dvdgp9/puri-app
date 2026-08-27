<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesMonitorRequireMethod('POST');
    $input = evaluacionesMonitorReadJson();
    $sessionId = evaluacionesMonitorPositiveId($input['sesion_id'] ?? null, 'sesion_id');
    $fieldId = evaluacionesMonitorPositiveId($input['campo_id'] ?? null, 'campo_id');
    $participantId = evaluacionesMonitorPositiveId($input['inscrito_id'] ?? null, 'inscrito_id');
    $session = evaluacionesMonitorRequireSession($pdo, $sessionId, $monitor_center_id);

    $fieldStmt = $pdo->prepare(
        'SELECT id, evaluacion_id, tipo_dato
         FROM evaluacion_campos
         WHERE id = ? AND evaluacion_id = ?'
    );
    $fieldStmt->execute([$fieldId, (int) $session['evaluacion_id']]);
    $field = $fieldStmt->fetch(PDO::FETCH_ASSOC);
    if (!$field) {
        evaluacionesMonitorFail(422, 'CAMPO_NO_PERTENECE', 'El campo no pertenece a esta evaluación.');
    }

    $participantStmt = $pdo->prepare(
        'SELECT id, nombre, apellidos FROM inscritos WHERE id = ? AND actividad_id = ?'
    );
    $participantStmt->execute([$participantId, (int) $session['actividad_id']]);
    $participant = $participantStmt->fetch(PDO::FETCH_ASSOC);
    if (!$participant) {
        evaluacionesMonitorFail(422, 'PARTICIPANTE_NO_PERTENECE', 'El participante no pertenece a esta actividad.');
    }

    $validation = evaluacionesValidateResult($field, $input);
    if (!$validation['valid']) {
        evaluacionesMonitorFail(422, 'TIPO_DATO_INVALIDO', 'El resultado no coincide con el formato configurado.', $validation['errors']);
    }
    $data = $validation['data'];

    $pdo->beginTransaction();
    $lockStmt = $pdo->prepare('SELECT estado FROM evaluacion_sesiones WHERE id = ? FOR UPDATE');
    $lockStmt->execute([$sessionId]);
    $currentState = $lockStmt->fetchColumn();
    if ($currentState === 'finalizada') {
        evaluacionesMonitorFail(409, 'SESION_YA_FINALIZADA', 'La evaluación ya está finalizada y es de solo lectura.');
    }
    evaluacionesMonitorRequireWritablePeriod($session);

    $stmt = $pdo->prepare(
        'INSERT INTO evaluacion_resultados
            (evaluacion_sesion_id, evaluacion_campo_id, inscrito_id, participante_ref,
             participante_nombre, participante_apellidos, estado, valor_numero,
             valor_texto, calificador, updated_by_centro_id, updated_by_admin_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
         ON DUPLICATE KEY UPDATE
            estado = VALUES(estado),
            valor_numero = VALUES(valor_numero),
            valor_texto = VALUES(valor_texto),
            calificador = VALUES(calificador),
            updated_by_centro_id = VALUES(updated_by_centro_id),
            updated_by_admin_id = NULL'
    );
    $stmt->execute([
        $sessionId,
        $fieldId,
        $participantId,
        'inscrito:' . $participantId,
        $participant['nombre'],
        $participant['apellidos'],
        $data['estado'],
        $data['valor_numero'],
        $data['valor_texto'],
        $data['calificador'],
        $monitor_center_id,
    ]);
    $pdo->commit();

    $resultStmt = $pdo->prepare(
        'SELECT id, evaluacion_sesion_id AS sesion_id, evaluacion_campo_id AS campo_id,
                inscrito_id, estado, valor_numero, valor_texto, calificador, updated_at
         FROM evaluacion_resultados
         WHERE evaluacion_sesion_id = ? AND evaluacion_campo_id = ? AND participante_ref = ?'
    );
    $resultStmt->execute([$sessionId, $fieldId, 'inscrito:' . $participantId]);
    $result = $resultStmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $result['id'] = (int) $result['id'];
        $result['sesion_id'] = (int) $result['sesion_id'];
        $result['campo_id'] = (int) $result['campo_id'];
        $result['inscrito_id'] = (int) $result['inscrito_id'];
        $result['valor_numero'] = $result['valor_numero'] !== null ? (float) $result['valor_numero'] : null;
    }

    evaluacionesMonitorRespondSuccess([
        'resultado' => $result,
        'cobertura' => evaluacionesMonitorFetchCoverage($pdo, $sessionId),
    ]);
} catch (EvaluacionesMonitorApiException $exception) {
    evaluacionesMonitorRollback($pdo);
    evaluacionesMonitorRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesMonitorRollback($pdo);
    evaluacionesMonitorRespondInternal($exception, 'error guardando resultado de evaluación del monitor');
}

