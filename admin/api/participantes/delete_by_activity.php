<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin_info = getAdminInfo();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $actividad_id = intval($input['actividad_id'] ?? 0);
    if ($actividad_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de actividad inválido']);
        exit;
    }

    // Autorización
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM actividades a
             INNER JOIN instalaciones i ON a.instalacion_id = i.id
             INNER JOIN admin_asignaciones aa ON aa.centro_id = i.centro_id
             WHERE a.id = ? AND aa.admin_id = ?'
        );
        $stmt->execute([$actividad_id, $admin_info['id']]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado para esta actividad']);
            exit;
        }
    }

    $pdo->beginTransaction();
    try {
        // Eliminar asistencias de todos los inscritos de la actividad
        $stmtA = $pdo->prepare('DELETE FROM asistencias WHERE actividad_id = ?');
        $stmtA->execute([$actividad_id]);
        $asistencias_eliminadas = $stmtA->rowCount();

        // Eliminar inscritos de la actividad
        $stmtI = $pdo->prepare('DELETE FROM inscritos WHERE actividad_id = ?');
        $stmtI->execute([$actividad_id]);
        $inscritos_eliminados = $stmtI->rowCount();

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Listado eliminado correctamente',
            'asistencias_eliminadas' => $asistencias_eliminadas,
            'inscritos_eliminados' => $inscritos_eliminados
        ]);
    } catch (Exception $ex) {
        $pdo->rollBack();
        error_log('Tx error deleting participants by activity: ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el listado']);
    }

} catch (Exception $e) {
    error_log('Error in delete_by_activity: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
