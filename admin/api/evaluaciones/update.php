<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('POST');
    $input = evaluacionesAdminReadJson();
    $evaluationId = evaluacionesAdminPositiveId($input['evaluacion_id'] ?? null, 'evaluacion_id');
    $current = evaluacionesAdminRequireEvaluation($pdo, $evaluationId, $admin_info);

    $fieldStmt = $pdo->prepare(
        'SELECT id, nombre, tipo_dato, unidad, configuracion_json
         FROM evaluacion_campos
         WHERE evaluacion_id = ?
         ORDER BY orden ASC, id ASC
         LIMIT 1'
    );
    $fieldStmt->execute([$evaluationId]);
    $currentField = $fieldStmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentField) {
        evaluacionesAdminFail(500, 'CAMPO_NO_ENCONTRADO', 'La evaluación no tiene un campo configurable.');
    }

    $inputField = isset($input['campo']) && is_array($input['campo']) ? $input['campo'] : [];
    $currentConfiguration = $currentField['configuracion_json'] !== null
        ? json_decode($currentField['configuracion_json'], true)
        : null;
    $definition = [
        'nombre' => array_key_exists('nombre', $input) ? $input['nombre'] : $current['nombre'],
        'instrucciones' => array_key_exists('instrucciones', $input) ? $input['instrucciones'] : $current['instrucciones'],
        'fecha_inicio' => array_key_exists('fecha_inicio', $input) ? $input['fecha_inicio'] : $current['fecha_inicio'],
        'fecha_fin' => array_key_exists('fecha_fin', $input) ? $input['fecha_fin'] : $current['fecha_fin'],
        'campo' => [
            'nombre' => array_key_exists('nombre', $inputField) ? $inputField['nombre'] : $currentField['nombre'],
            'tipo_dato' => array_key_exists('tipo_dato', $inputField) ? $inputField['tipo_dato'] : $currentField['tipo_dato'],
            'unidad' => array_key_exists('unidad', $inputField) ? $inputField['unidad'] : $currentField['unidad'],
            'configuracion' => array_key_exists('configuracion', $inputField) ? $inputField['configuracion'] : $currentConfiguration,
        ],
    ];

    $validation = evaluacionesValidateDefinition($definition);
    if (!$validation['valid']) {
        $code = isset($validation['errors']['campo.tipo_dato'])
            ? 'TIPO_DATO_INVALIDO'
            : 'VALIDACION_FALLIDA';
        evaluacionesAdminFail(422, $code, 'Revisa los datos de la evaluación.', $validation['errors']);
    }
    $data = $validation['data'];

    $sessionStmt = $pdo->prepare(
        'SELECT id, fecha_realizacion
         FROM evaluacion_sesiones
         WHERE evaluacion_id = ?
           AND (fecha_realizacion < ? OR fecha_realizacion > ?)
         LIMIT 1'
    );
    $sessionStmt->execute([$evaluationId, $data['fecha_inicio'], $data['fecha_fin']]);
    if ($sessionStmt->fetch(PDO::FETCH_ASSOC)) {
        evaluacionesAdminFail(
            422,
            'FECHA_FUERA_DE_PERIODO',
            'El período debe contener la fecha de realización existente.',
            ['fecha_inicio' => 'Revisa el período.', 'fecha_fin' => 'Revisa el período.']
        );
    }

    if ($data['campo']['tipo_dato'] !== $currentField['tipo_dato']) {
        $measuredStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM evaluacion_resultados
             WHERE evaluacion_campo_id = ? AND estado = 'medido'"
        );
        $measuredStmt->execute([(int) $currentField['id']]);
        if ((int) $measuredStmt->fetchColumn() > 0) {
            evaluacionesAdminFail(409, 'RESULTADOS_EXISTENTES', 'No se puede cambiar el tipo porque ya existen resultados medidos.');
        }
    }

    $configurationJson = $data['campo']['configuracion'] === null
        ? null
        : json_encode($data['campo']['configuracion'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE evaluaciones
         SET nombre = ?, instrucciones = ?, fecha_inicio = ?, fecha_fin = ?, updated_by_admin_id = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['nombre'],
        $data['instrucciones'],
        $data['fecha_inicio'],
        $data['fecha_fin'],
        (int) $admin_info['id'],
        $evaluationId,
    ]);

    $fieldUpdate = $pdo->prepare(
        'UPDATE evaluacion_campos
         SET nombre = ?, tipo_dato = ?, unidad = ?, configuracion_json = ?
         WHERE id = ? AND evaluacion_id = ?'
    );
    $fieldUpdate->execute([
        $data['campo']['nombre'],
        $data['campo']['tipo_dato'],
        $data['campo']['unidad'],
        $configurationJson,
        (int) $currentField['id'],
        $evaluationId,
    ]);

    $pdo->commit();
    evaluacionesAdminRespondSuccess(['evaluacion' => evaluacionesAdminFetchEvaluation($pdo, $evaluationId)]);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondInternal($exception, 'Error actualizando evaluación');
}

