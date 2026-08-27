<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('GET');
    $activityId = evaluacionesAdminPositiveId($_GET['actividad_id'] ?? null, 'actividad_id');
    $activity = evaluacionesAdminRequireActivity($pdo, $activityId, $admin_info);
    $includeArchived = isset($_GET['include_archived']) && (string) $_GET['include_archived'] === '1';

    $sql = 'SELECT id FROM evaluaciones WHERE actividad_id = ?';
    $params = [$activityId];
    if (!$includeArchived) {
        $sql .= ' AND archivada_at IS NULL';
    }
    $sql .= ' ORDER BY
                CASE WHEN archivada_at IS NULL THEN 0 ELSE 1 END,
                fecha_inicio DESC,
                id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $evaluationIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $evaluations = [];
    foreach ($evaluationIds as $evaluationId) {
        $evaluations[] = evaluacionesAdminFetchEvaluation($pdo, (int) $evaluationId);
    }

    evaluacionesAdminRespondSuccess([
        'actividad' => evaluacionesAdminActivityDto($activity),
        'evaluaciones' => $evaluations,
        'count' => count($evaluations),
    ]);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRespondInternal($exception, 'Error listando evaluaciones por actividad');
}

