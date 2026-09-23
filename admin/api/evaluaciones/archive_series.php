<?php
require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('POST');
    $input = evaluacionesAdminReadJson();
    $seriesId = evaluacionesAdminPositiveId($input['serie_id'] ?? null, 'serie_id');
    $stmt = $pdo->prepare('SELECT centro_id FROM evaluacion_series WHERE id = ?');
    $stmt->execute([$seriesId]);
    $centerId = $stmt->fetchColumn();
    if ($centerId === false) evaluacionesAdminFail(404, 'SERIE_NO_ENCONTRADA', 'Serie no encontrada.');
    if (!evaluacionesAdminCanAccessCenter($pdo, $admin_info, $centerId)) {
        evaluacionesAdminFail(403, 'CENTRO_NO_ASIGNADO', 'No tienes acceso a este centro.');
    }
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE evaluacion_series SET archivada_at = COALESCE(archivada_at, NOW()) WHERE id = ?')->execute([$seriesId]);
    $pdo->prepare('UPDATE evaluaciones e LEFT JOIN evaluacion_sesiones es ON es.evaluacion_id = e.id SET e.archivada_at = COALESCE(e.archivada_at, NOW()) WHERE e.serie_id = ? AND es.id IS NULL')->execute([$seriesId]);
    $pdo->commit();
    evaluacionesAdminRespondSuccess(['serie_id' => $seriesId]);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRollback($pdo);
    evaluacionesAdminRespondInternal($exception, 'Error archivando serie de evaluaciones');
}
