<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('POST');
    $input = evaluacionesAdminReadJson();
    $sessionId = evaluacionesAdminPositiveId($input['sesion_id'] ?? null, 'sesion_id');
    $fieldId = evaluacionesAdminPositiveId($input['campo_id'] ?? null, 'campo_id');
    $participantId = evaluacionesAdminPositiveId($input['inscrito_id'] ?? null, 'inscrito_id');
    $session = evaluacionesAdminRequireSession($pdo, $sessionId, $admin_info);

    $fieldStmt = $pdo->prepare(
        'SELECT id, evaluacion_id, tipo_dato
         FROM evaluacion_campos
         WHERE id = ? AND evaluacion_id = ?'
    );
    $fieldStmt->execute([$fieldId, (int) $session['evaluacion_id']]);
    $field = $fieldStmt->fetch(PDO::FETCH_ASSOC);
    if (!$field) {
        evaluacionesAdminFail(422, 'CAMPO_NO_PERTENECE', 'El campo no pertenece a esta evaluación.');
    }

    $participantStmt = $pdo->prepare('SELECT id, nombre, apellidos FROM inscritos WHERE id = ? AND actividad_id = ?');
    $participantStmt->execute([$participantId, (int) $session['actividad_id']]);
    $participant = $participantStmt->fetch(PDO::FETCH_ASSOC);
    if (!$participant) {
        evaluacionesAdminFail(422, 'PARTICIPANTE_NO_PERTENECE', 'El participante no pertenece a esta actividad.');
    }

    $validation = evaluacionesValidateResult($field, $input);
    if (!$validation['valid']) {
        evaluacionesAdminFail(422, 'TIPO_DATO_INVALIDO', 'El resultado no coincide con el tipo configurado.', $validation['errors']);
    }
    $data = $validation['data'];

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO evaluacion_resultados
            (evaluacion_sesion_id, evaluacion_campo_id, inscrito_id, participante_ref, participante_nombre, participante_apellidos, estado,
             valor_numero, valor_texto, calificador, updated_by_centro_id, updated_by_admin_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)
         ON DUPLICATE KEY UPDATE
            estado = VALUES(estado),
            valor_numero = VALUES(valor_numero),
            valor_texto = VALUES(valor_texto),
            calificador = VALUES(calificador),
            updated_by_centro_id = NULL,
            updated_by_admin_id = VALUES(updated_by_admin_id)'
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
        (int) $admin_info['id'],
    ]);
    $pdo->commit();

    $resultStmt = $pdo->prepare(
        'SELECT id, evaluacion_sesion_id AS sesion_id, evaluacion_campo_id AS campo_id,
                inscrito_id, estado, valor_numero, valor_texto, calificador, updated_at
         FROM evaluacion_resultados
         WHERE evaluacion_sesion_id = ? AND evaluacion_campo_id = ? AND inscrito_id = ?'
    );
    $resultStmt->execute([$sessionId, $fieldId, $participantId]);
    $result = $resultStmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $result['id'] = (int) $result['id'];
        $result['sesion_id'] = (int) $result['sesion_id'];
        $result['campo_id'] = (int) $result['campo_id'];
        $result['inscrito_id'] = (int) $result['inscrito_id'];
        $result['valor_numero'] = $result['valor_numero'] !== null ? (float) $result['valor_numero'] : null;
    }

    evaluacionesAdminRespondSuccess([
        'resultado' => $result,
        'cobertura' => evaluacionesAdminFetchCoverage($pdo, $sessionId, (int) $session['actividad_id']),
    ]);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondInternal($exception, 'Error corrigiendo resultado de evaluación');
}
