<?php
require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('GET');
    $seriesId = evaluacionesAdminPositiveId($_GET['serie_id'] ?? null, 'serie_id');
    $activityId = evaluacionesAdminPositiveId($_GET['actividad_id'] ?? null, 'actividad_id');
    $activity = evaluacionesAdminRequireActivity($pdo, $activityId, $admin_info);
    $stmt = $pdo->prepare('SELECT s.* FROM evaluacion_series s JOIN evaluacion_serie_actividades ea ON ea.serie_id = s.id WHERE s.id = ? AND ea.actividad_id = ? AND s.centro_id = ?');
    $stmt->execute([$seriesId, $activityId, (int) $activity['centro_id']]);
    $series = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$series) evaluacionesAdminFail(404, 'SERIE_NO_ENCONTRADA', 'Serie no encontrada en esta actividad.');
    $tests = $pdo->prepare('SELECT orden, nombre, tipo_dato, unidad FROM evaluacion_serie_campos WHERE serie_id = ? ORDER BY orden');
    $tests->execute([$seriesId]);
    $testRows = $tests->fetchAll(PDO::FETCH_ASSOC);
    $cycles = $pdo->prepare('SELECT e.id, e.ciclo, e.fecha_inicio, e.fecha_fin, es.id AS sesion_id, es.fecha_realizacion, es.estado FROM evaluaciones e LEFT JOIN evaluacion_sesiones es ON es.evaluacion_id = e.id AND es.numero_intento = 1 WHERE e.serie_id = ? AND e.actividad_id = ? ORDER BY e.ciclo');
    $cycles->execute([$seriesId, $activityId]);
    $cycleRows = $cycles->fetchAll(PDO::FETCH_ASSOC);
    $results = $pdo->prepare('SELECT e.ciclo, er.participante_ref, er.participante_nombre, er.participante_apellidos, ec.orden, er.estado, er.valor_numero, er.valor_texto, er.calificador FROM evaluaciones e JOIN evaluacion_sesiones es ON es.evaluacion_id = e.id AND es.numero_intento = 1 JOIN evaluacion_resultados er ON er.evaluacion_sesion_id = es.id JOIN evaluacion_campos ec ON ec.id = er.evaluacion_campo_id WHERE e.serie_id = ? AND e.actividad_id = ? ORDER BY er.participante_apellidos, er.participante_nombre, e.ciclo, ec.orden');
    $results->execute([$seriesId, $activityId]);
    $participants = [];
    foreach ($results->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = $row['participante_ref'];
        if (!isset($participants[$key])) {
            $participants[$key] = ['ref' => $key, 'nombre' => $row['participante_nombre'], 'apellidos' => $row['participante_apellidos'], 'mediciones' => []];
        }
        $participants[$key]['mediciones'][(int) $row['ciclo']][(int) $row['orden']] = [
            'estado' => $row['estado'],
            'valor_numero' => $row['valor_numero'] !== null ? (float) $row['valor_numero'] : null,
            'valor_texto' => $row['valor_texto'],
            'calificador' => $row['calificador'],
        ];
    }
    evaluacionesAdminRespondSuccess([
        'serie' => ['id' => (int) $series['id'], 'nombre' => $series['nombre'], 'tipo' => $series['tipo']],
        'pruebas' => $testRows,
        'ciclos' => $cycleRows,
        'participantes' => array_values($participants),
    ]);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRespondInternal($exception, 'Error cargando progresión de evaluaciones');
}
