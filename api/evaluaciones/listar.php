<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesMonitorRequireMethod('GET');
    $activityId = evaluacionesMonitorPositiveId($_GET['actividad_id'] ?? null, 'actividad_id');
    evaluacionesMonitorRequireActivity($pdo, $activityId, $monitor_center_id);

    $stmt = $pdo->prepare(
        'SELECT id
         FROM evaluaciones
         WHERE actividad_id = ? AND archivada_at IS NULL
         ORDER BY fecha_inicio ASC, id ASC'
    );
    $stmt->execute([$activityId]);
    $pending = [];
    $inProgress = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $evaluationId) {
        $evaluation = evaluacionesMonitorFetchEvaluationDto($pdo, (int) $evaluationId);
        if ($evaluation['estado'] === 'pendiente') {
            $evaluation['accion'] = 'realizar';
            $pending[] = $evaluation;
        } elseif (in_array($evaluation['estado'], ['en_curso', 'en_curso_fuera_de_plazo'], true)) {
            $evaluation['accion'] = $evaluation['estado'] === 'en_curso' ? 'continuar' : 'requiere_admin';
            $inProgress[] = $evaluation;
        }
    }

    evaluacionesMonitorRespondSuccess([
        'pendientes' => $pending,
        'en_curso' => $inProgress,
        'count' => count($pending) + count($inProgress),
    ]);
} catch (EvaluacionesMonitorApiException $exception) {
    evaluacionesMonitorRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesMonitorRespondInternal($exception, 'error listando evaluaciones del monitor');
}

