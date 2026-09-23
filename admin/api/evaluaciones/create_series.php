<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../../includes/evaluacion_series.php';

try {
    evaluacionesAdminRequireMethod('POST');
    $input = evaluacionesAdminReadJson();
    $activityId = evaluacionesAdminPositiveId($input['actividad_id'] ?? null, 'actividad_id');
    $activity = evaluacionesAdminRequireActivity($pdo, $activityId, $admin_info);
    $scope = (string) ($input['ambito'] ?? 'actividad');
    $type = (string) ($input['tipo'] ?? '');
    $name = trim((string) ($input['nombre'] ?? ''));
    $instructions = trim((string) ($input['instrucciones'] ?? ''));
    $start = (string) ($input['primer_inicio'] ?? '');
    $end = (string) ($input['primer_fin'] ?? '');
    $until = (string) ($input['hasta'] ?? '');
    $fields = $input['campos'] ?? [];
    if (!in_array($scope, ['actividad', 'varias', 'instalacion', 'centro'], true)) {
        evaluacionesAdminFail(422, 'AMBITO_INVALIDO', 'Selecciona dónde se aplicarán las pruebas.');
    }
    if ($name === '' || evaluacionesTextLength($name) > 150) {
        evaluacionesAdminFail(422, 'NOMBRE_INVALIDO', 'El nombre debe tener entre 1 y 150 caracteres.');
    }
    if (!is_array($fields) || count($fields) < 1 || count($fields) > 10) {
        evaluacionesAdminFail(422, 'PRUEBAS_INVALIDAS', 'Añade entre 1 y 10 pruebas.');
    }
    foreach ($fields as $index => $field) {
        if (!is_array($field) || trim((string) ($field['nombre'] ?? '')) === ''
            || evaluacionesTextLength($field['nombre']) > 150
            || !in_array((string) ($field['tipo_dato'] ?? ''), ['entero', 'decimal', 'duracion', 'texto_corto'], true)
            || evaluacionesTextLength($field['unidad'] ?? '') > 50) {
            evaluacionesAdminFail(422, 'PRUEBA_INVALIDA', 'Revisa el nombre, formato y unidad de la prueba ' . ($index + 1) . '.');
        }
    }
    try {
        $periods = evaluacionSeriesPeriods($start, $end, $until, $type);
    } catch (InvalidArgumentException $exception) {
        evaluacionesAdminFail(422, 'PERIODO_INVALIDO', $exception->getMessage());
    }
    if (count($periods) > 24) {
        evaluacionesAdminFail(422, 'SERIE_DEMASIADO_LARGA', 'Planifica como máximo 24 ciclos por serie.');
    }

    $stmt = $pdo->prepare("SELECT a.id, a.fecha_inicio, a.fecha_fin, i.id AS instalacion_id
                           FROM actividades a JOIN instalaciones i ON i.id = a.instalacion_id
                           WHERE i.centro_id = ? AND (a.tipo_control IS NULL OR a.tipo_control <> 'aforo')");
    $stmt->execute([(int) $activity['centro_id']]);
    $available = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $available[(int) $row['id']] = $row;
    if (!isset($available[$activityId])) {
        evaluacionesAdminFail(422, 'ACTIVIDAD_NO_COMPATIBLE', 'Esta actividad no admite evaluaciones de participantes.');
    }
    $selected = [];
    if ($scope === 'actividad') {
        $selected[$activityId] = true;
    } elseif ($scope === 'varias') {
        if (!is_array($input['actividad_ids'] ?? null)) {
            evaluacionesAdminFail(422, 'ACTIVIDADES_INVALIDAS', 'Selecciona las actividades.');
        }
        foreach ($input['actividad_ids'] as $id) {
            $parsed = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$parsed || !isset($available[(int) $parsed])) {
                evaluacionesAdminFail(422, 'ACTIVIDADES_INVALIDAS', 'Una actividad no pertenece a este centro.');
            }
            $selected[(int) $parsed] = true;
        }
        $selected[$activityId] = true;
    } else {
        foreach ($available as $id => $row) {
            if ($scope === 'centro' || (int) $row['instalacion_id'] === (int) $activity['instalacion_id']) $selected[$id] = true;
        }
    }
    if (!$selected || count($selected) * count($periods) > 2000) {
        evaluacionesAdminFail(422, 'SERIE_DEMASIADO_GRANDE', 'Selecciona menos actividades o un período más corto.');
    }

    $pdo->beginTransaction();
    $insertSeries = $pdo->prepare('INSERT INTO evaluacion_series (centro_id, nombre, tipo, instrucciones, primer_inicio, primer_fin, hasta, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $insertSeries->execute([(int) $activity['centro_id'], $name, $type, $instructions ?: null, $start, $end, $until, (int) $admin_info['id']]);
    $seriesId = (int) $pdo->lastInsertId();
    $insertSeriesField = $pdo->prepare('INSERT INTO evaluacion_serie_campos (serie_id, nombre, tipo_dato, unidad, orden) VALUES (?, ?, ?, ?, ?)');
    foreach ($fields as $index => $field) {
        $insertSeriesField->execute([$seriesId, trim($field['nombre']), $field['tipo_dato'], trim((string) ($field['unidad'] ?? '')) ?: null, $index + 1]);
    }
    $assign = $pdo->prepare('INSERT INTO evaluacion_serie_actividades (serie_id, actividad_id) VALUES (?, ?)');
    $insertEvaluation = $pdo->prepare('INSERT INTO evaluaciones (actividad_id, nombre, instrucciones, fecha_inicio, fecha_fin, serie_id, ciclo, created_by_admin_id, updated_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insertField = $pdo->prepare('INSERT INTO evaluacion_campos (evaluacion_id, nombre, tipo_dato, unidad, orden) VALUES (?, ?, ?, ?, ?)');
    $created = 0;
    $currentCreated = 0;
    foreach (array_keys($selected) as $id) {
        $assign->execute([$seriesId, $id]);
        $row = $available[$id];
        foreach ($periods as $period) {
            if (($row['fecha_inicio'] && $period['fecha_inicio'] < substr($row['fecha_inicio'], 0, 10))
                || ($row['fecha_fin'] && $period['fecha_inicio'] > substr($row['fecha_fin'], 0, 10))) continue;
            $insertEvaluation->execute([$id, $name, $instructions ?: null, $period['fecha_inicio'], $period['fecha_fin'], $seriesId, $period['ciclo'], (int) $admin_info['id'], (int) $admin_info['id']]);
            $evaluationId = (int) $pdo->lastInsertId();
            foreach ($fields as $index => $field) {
                $insertField->execute([$evaluationId, trim($field['nombre']), $field['tipo_dato'], trim((string) ($field['unidad'] ?? '')) ?: null, $index + 1]);
            }
            $created++;
            if ($id === $activityId) $currentCreated++;
        }
    }
    if ($currentCreated === 0) evaluacionesAdminFail(422, 'SIN_CICLOS', 'El período no coincide con la actividad desde la que estás creando la serie.');
    $pdo->commit();
    evaluacionesAdminRespondSuccess(['serie_id' => $seriesId, 'evaluaciones_creadas' => $created, 'actividades' => count($selected), 'ciclos' => count($periods)], 201);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondInternal($exception, 'Error creando serie de evaluaciones');
}
