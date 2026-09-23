<?php
require_once __DIR__ . '/_bootstrap.php';

try {
    evaluacionesAdminRequireMethod('GET');
    $activityId = evaluacionesAdminPositiveId($_GET['actividad_id'] ?? null, 'actividad_id');
    $activity = evaluacionesAdminRequireActivity($pdo, $activityId, $admin_info);
    $stmt = $pdo->prepare("SELECT a.id, a.nombre, a.grupo, a.fecha_inicio, a.fecha_fin,
                                 i.id AS instalacion_id, i.nombre AS instalacion_nombre
                          FROM actividades a JOIN instalaciones i ON i.id = a.instalacion_id
                          WHERE i.centro_id = ? AND (a.tipo_control IS NULL OR a.tipo_control <> 'aforo')
                          ORDER BY i.nombre, a.nombre, a.grupo");
    $stmt->execute([(int) $activity['centro_id']]);
    evaluacionesAdminRespondSuccess([
        'centro' => ['id' => (int) $activity['centro_id'], 'nombre' => $activity['centro_nombre']],
        'instalacion' => ['id' => (int) $activity['instalacion_id'], 'nombre' => $activity['instalacion_nombre']],
        'actividades' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ]);
} catch (EvaluacionesApiException $exception) {
    evaluacionesAdminRespondException($exception);
} catch (Throwable $exception) {
    evaluacionesAdminRespondInternal($exception, 'Error cargando ámbito de evaluaciones');
}
