<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('POST');
    $input = evaluacionesAdminReadJson();
    $activityId = evaluacionesAdminPositiveId($input['actividad_id'] ?? null, 'actividad_id');
    evaluacionesAdminRequireActivity($pdo, $activityId, $admin_info);

    $validation = evaluacionesValidateDefinition($input);
    if (!$validation['valid']) {
        $code = isset($validation['errors']['campo.tipo_dato'])
            ? 'TIPO_DATO_INVALIDO'
            : 'VALIDACION_FALLIDA';
        evaluacionesAdminFail(422, $code, 'Revisa los datos de la evaluación.', $validation['errors']);
    }

    $data = $validation['data'];
    $configurationJson = $data['campo']['configuracion'] === null
        ? null
        : json_encode($data['campo']['configuracion'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO evaluaciones
            (actividad_id, nombre, instrucciones, fecha_inicio, fecha_fin, created_by_admin_id, updated_by_admin_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $activityId,
        $data['nombre'],
        $data['instrucciones'],
        $data['fecha_inicio'],
        $data['fecha_fin'],
        (int) $admin_info['id'],
        (int) $admin_info['id'],
    ]);
    $evaluationId = (int) $pdo->lastInsertId();

    $fieldStmt = $pdo->prepare(
        'INSERT INTO evaluacion_campos
            (evaluacion_id, nombre, tipo_dato, unidad, orden, configuracion_json)
         VALUES (?, ?, ?, ?, 1, ?)'
    );
    $fieldStmt->execute([
        $evaluationId,
        $data['campo']['nombre'],
        $data['campo']['tipo_dato'],
        $data['campo']['unidad'],
        $configurationJson,
    ]);

    $pdo->commit();
    evaluacionesAdminRespondSuccess(
        ['evaluacion' => evaluacionesAdminFetchEvaluation($pdo, $evaluationId)],
        201
    );
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondInternal($exception, 'Error creando evaluación');
}

