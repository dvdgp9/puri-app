<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesMonitorRequireMethod('POST');
    $input = evaluacionesMonitorReadJson();
    $evaluationId = evaluacionesMonitorPositiveId($input['evaluacion_id'] ?? null, 'evaluacion_id');
    $evaluation = evaluacionesMonitorRequireEvaluation($pdo, $evaluationId, $monitor_center_id);
    $realDate = trim((string) ($input['fecha_realizacion'] ?? ''));

    if (!evaluacionesIsDate($realDate)) {
        evaluacionesMonitorFail(422, 'FECHA_INVALIDA', 'Selecciona una fecha de realización válida.');
    }
    if ($realDate > date('Y-m-d')) {
        evaluacionesMonitorFail(422, 'FECHA_FUTURA', 'La fecha de realización no puede ser futura.');
    }
    if (!evaluacionesDateInsidePeriod(date('Y-m-d'), $evaluation['fecha_inicio'], $evaluation['fecha_fin'])) {
        evaluacionesMonitorFail(409, 'EVALUACION_FUERA_DE_PLAZO', 'La evaluación no está disponible en la fecha actual.');
    }
    if (!evaluacionesDateInsidePeriod($realDate, $evaluation['fecha_inicio'], $evaluation['fecha_fin'])) {
        evaluacionesMonitorFail(422, 'FECHA_FUERA_DE_PERIODO', 'La fecha debe estar dentro del período de la evaluación.');
    }

    $pdo->beginTransaction();
    $lockStmt = $pdo->prepare('SELECT id FROM evaluaciones WHERE id = ? FOR UPDATE');
    $lockStmt->execute([$evaluationId]);

    $sessionStmt = $pdo->prepare(
        'SELECT id, estado
         FROM evaluacion_sesiones
         WHERE evaluacion_id = ? AND numero_intento = 1'
    );
    $sessionStmt->execute([$evaluationId]);
    $existing = $sessionStmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        if ($existing['estado'] === 'finalizada') {
            evaluacionesMonitorFail(409, 'EVALUACION_YA_REALIZADA', 'Esta evaluación ya está finalizada.');
        }
        $sessionId = (int) $existing['id'];
        $pdo->commit();
        evaluacionesMonitorRespondSuccess(evaluacionesMonitorFetchSessionDetail($pdo, $sessionId, $monitor_center_id));
    }

    $insertSession = $pdo->prepare(
        "INSERT INTO evaluacion_sesiones
            (evaluacion_id, numero_intento, fecha_realizacion, estado, registrada_por_centro_id)
         VALUES (?, 1, ?, 'en_curso', ?)"
    );
    $insertSession->execute([$evaluationId, $realDate, $monitor_center_id]);
    $sessionId = (int) $pdo->lastInsertId();

    $snapshotStmt = $pdo->prepare(
        "INSERT INTO evaluacion_resultados
            (evaluacion_sesion_id, evaluacion_campo_id, inscrito_id, participante_ref,
             participante_nombre, participante_apellidos, estado, updated_by_centro_id)
         SELECT ?, ec.id, ins.id, CONCAT('inscrito:', ins.id),
                COALESCE(ins.nombre, ''), COALESCE(ins.apellidos, ''), 'sin_evaluar', ?
         FROM inscritos ins
         INNER JOIN evaluacion_campos ec ON ec.evaluacion_id = ?
         WHERE ins.actividad_id = ?"
    );
    $snapshotStmt->execute([$sessionId, $monitor_center_id, $evaluationId, (int) $evaluation['actividad_id']]);
    $pdo->commit();

    evaluacionesMonitorRespondSuccess(
        evaluacionesMonitorFetchSessionDetail($pdo, $sessionId, $monitor_center_id),
        201
    );
} catch (EvaluacionesMonitorApiException $exception) {
    evaluacionesMonitorRollback($pdo);
    evaluacionesMonitorRespondException($exception);
} catch (PDOException $exception) {
    evaluacionesMonitorRollback($pdo);
    if ((string) $exception->getCode() === '23000') {
        evaluacionesMonitorRespond(409, [
            'success' => false,
            'error' => [
                'code' => 'INTENTO_YA_EXISTE',
                'message' => 'La evaluación se inició desde otra ventana. Recarga para continuar.',
            ],
        ]);
    }
    evaluacionesMonitorRespondInternal($exception, 'error iniciando evaluación del monitor');
} catch (Throwable $exception) {
    evaluacionesMonitorRollback($pdo);
    evaluacionesMonitorRespondInternal($exception, 'error iniciando evaluación del monitor');
}
