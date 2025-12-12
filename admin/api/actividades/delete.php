<?php
/**
 * Borrar actividad en cascada (solo superadmin)
 * Elimina: asistencias, observaciones, inscritos y la actividad
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin_info = getAdminInfo();

    // Solo superadmin puede borrar actividades
    if ($admin_info['role'] !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Solo superadmin puede eliminar actividades']);
        exit;
    }

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

    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de actividad inválido']);
        exit;
    }

    // Verificar que la actividad existe
    $stmt = $pdo->prepare('SELECT id, nombre FROM actividades WHERE id = ?');
    $stmt->execute([$id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actividad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
        exit;
    }

    // Borrado en cascada transaccional
    $pdo->beginTransaction();
    try {
        // 1. Eliminar asistencias de esta actividad
        $stmt = $pdo->prepare('DELETE FROM asistencias WHERE actividad_id = ?');
        $stmt->execute([$id]);
        $asistenciasEliminadas = $stmt->rowCount();

        // 2. Eliminar observaciones de esta actividad
        $stmt = $pdo->prepare('DELETE FROM observaciones WHERE actividad_id = ?');
        $stmt->execute([$id]);
        $observacionesEliminadas = $stmt->rowCount();

        // 3. Eliminar inscritos de esta actividad
        $stmt = $pdo->prepare('DELETE FROM inscritos WHERE actividad_id = ?');
        $stmt->execute([$id]);
        $inscritosEliminados = $stmt->rowCount();

        // 4. Eliminar la actividad
        $stmt = $pdo->prepare('DELETE FROM actividades WHERE id = ?');
        $stmt->execute([$id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Actividad '{$actividad['nombre']}' eliminada correctamente",
            'stats' => [
                'asistencias_eliminadas' => $asistenciasEliminadas,
                'observaciones_eliminadas' => $observacionesEliminadas,
                'inscritos_eliminados' => $inscritosEliminados
            ]
        ]);

    } catch (Exception $ex) {
        $pdo->rollBack();
        error_log('Error en borrado cascada actividad: ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la actividad']);
    }

} catch (Exception $e) {
    error_log('Error delete actividad: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
