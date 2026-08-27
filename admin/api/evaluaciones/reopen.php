<?php

require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('POST');
    $input = evaluacionesAdminReadJson();
    $sessionId = evaluacionesAdminPositiveId($input['sesion_id'] ?? null, 'sesion_id');
    $session = evaluacionesAdminRequireSession($pdo, $sessionId, $admin_info);

    if ($session['estado'] === 'en_curso') {
        evaluacionesAdminFail(409, 'SESION_YA_EN_CURSO', 'La evaluación ya está abierta para el monitor.');
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "UPDATE evaluacion_sesiones
         SET estado = 'en_curso', finalizada_at = NULL, reopened_at = NOW(), reopened_by_admin_id = ?
         WHERE id = ? AND estado = 'finalizada'"
    );
    $stmt->execute([(int) $admin_info['id'], $sessionId]);
    if ($stmt->rowCount() !== 1) {
        evaluacionesAdminFail(409, 'ESTADO_CAMBIADO', 'La realización cambió de estado. Recarga los datos.');
    }
    $pdo->commit();

    evaluacionesAdminRespondSuccess([
        'evaluacion' => evaluacionesAdminFetchEvaluation($pdo, (int) $session['evaluacion_id']),
    ]);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondInternal($exception, 'Error reabriendo evaluación');
}

