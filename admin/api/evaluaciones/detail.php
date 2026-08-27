<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('GET');
    $evaluationId = evaluacionesAdminPositiveId($_GET['evaluacion_id'] ?? null, 'evaluacion_id');
    $evaluation = evaluacionesAdminRequireEvaluation($pdo, $evaluationId, $admin_info);

    $evaluationDto = evaluacionesAdminFetchEvaluation($pdo, $evaluationId);
    $sessionId = !empty($evaluationDto['sesion']['id']) ? (int) $evaluationDto['sesion']['id'] : null;

    evaluacionesAdminRespondSuccess([
        'evaluacion' => $evaluationDto,
        'participantes' => evaluacionesAdminFetchSessionResults($pdo, $evaluationId, $sessionId),
        'contexto' => [
            'actividad' => [
                'id' => (int) $evaluation['actividad_id'],
                'nombre' => $evaluation['actividad_nombre'],
                'grupo' => $evaluation['actividad_grupo'],
            ],
            'instalacion' => [
                'id' => (int) $evaluation['instalacion_id'],
                'nombre' => $evaluation['instalacion_nombre'],
            ],
            'centro' => [
                'id' => (int) $evaluation['centro_id'],
                'nombre' => $evaluation['centro_nombre'],
            ],
        ],
    ]);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRespondInternal($exception, 'Error cargando el detalle de evaluación');
}
