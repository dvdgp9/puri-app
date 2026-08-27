<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('POST');
    $input = evaluacionesAdminReadJson();
    $evaluationId = evaluacionesAdminPositiveId($input['evaluacion_id'] ?? null, 'evaluacion_id');
    evaluacionesAdminRequireEvaluation($pdo, $evaluationId, $admin_info);

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'UPDATE evaluaciones
         SET archivada_at = COALESCE(archivada_at, NOW()), updated_by_admin_id = ?
         WHERE id = ?'
    );
    $stmt->execute([(int) $admin_info['id'], $evaluationId]);
    $pdo->commit();

    evaluacionesAdminRespondSuccess(['evaluacion' => evaluacionesAdminFetchEvaluation($pdo, $evaluationId)]);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondInternal($exception, 'Error archivando evaluación');
}

