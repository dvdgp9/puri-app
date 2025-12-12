<?php
/**
 * Borrar centro en cascada (solo superadmin)
 * Elimina: asistencias, observaciones, inscritos, actividades, instalaciones, asignaciones y el centro
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin_info = getAdminInfo();

    // Solo superadmin puede borrar centros
    if ($admin_info['role'] !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Solo superadmin puede eliminar centros']);
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
        echo json_encode(['success' => false, 'message' => 'ID de centro inválido']);
        exit;
    }

    // Verificar que el centro existe
    $stmt = $pdo->prepare('SELECT id, nombre FROM centros WHERE id = ?');
    $stmt->execute([$id]);
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$centro) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Centro no encontrado']);
        exit;
    }

    // Obtener IDs de instalaciones de este centro
    $stmt = $pdo->prepare('SELECT id FROM instalaciones WHERE centro_id = ?');
    $stmt->execute([$id]);
    $instalacionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Obtener IDs de actividades de todas las instalaciones
    $actividadIds = [];
    if (!empty($instalacionIds)) {
        $placeholders = implode(',', array_fill(0, count($instalacionIds), '?'));
        $stmt = $pdo->prepare("SELECT id FROM actividades WHERE instalacion_id IN ($placeholders)");
        $stmt->execute($instalacionIds);
        $actividadIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Borrado en cascada transaccional
    $pdo->beginTransaction();
    try {
        $asistenciasEliminadas = 0;
        $observacionesEliminadas = 0;
        $inscritosEliminados = 0;
        $actividadesEliminadas = 0;
        $instalacionesEliminadas = 0;
        $asignacionesEliminadas = 0;

        if (!empty($actividadIds)) {
            $placeholders = implode(',', array_fill(0, count($actividadIds), '?'));

            // 1. Eliminar asistencias de todas las actividades
            $stmt = $pdo->prepare("DELETE FROM asistencias WHERE actividad_id IN ($placeholders)");
            $stmt->execute($actividadIds);
            $asistenciasEliminadas = $stmt->rowCount();

            // 2. Eliminar observaciones de todas las actividades
            $stmt = $pdo->prepare("DELETE FROM observaciones WHERE actividad_id IN ($placeholders)");
            $stmt->execute($actividadIds);
            $observacionesEliminadas = $stmt->rowCount();

            // 3. Eliminar inscritos de todas las actividades
            $stmt = $pdo->prepare("DELETE FROM inscritos WHERE actividad_id IN ($placeholders)");
            $stmt->execute($actividadIds);
            $inscritosEliminados = $stmt->rowCount();
        }

        if (!empty($instalacionIds)) {
            $placeholders = implode(',', array_fill(0, count($instalacionIds), '?'));

            // 4. Eliminar todas las actividades
            $stmt = $pdo->prepare("DELETE FROM actividades WHERE instalacion_id IN ($placeholders)");
            $stmt->execute($instalacionIds);
            $actividadesEliminadas = $stmt->rowCount();
        }

        // 5. Eliminar todas las instalaciones del centro
        $stmt = $pdo->prepare('DELETE FROM instalaciones WHERE centro_id = ?');
        $stmt->execute([$id]);
        $instalacionesEliminadas = $stmt->rowCount();

        // 6. Eliminar asignaciones de admins a este centro
        $stmt = $pdo->prepare('DELETE FROM admin_asignaciones WHERE centro_id = ?');
        $stmt->execute([$id]);
        $asignacionesEliminadas = $stmt->rowCount();

        // 7. Eliminar el centro
        $stmt = $pdo->prepare('DELETE FROM centros WHERE id = ?');
        $stmt->execute([$id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Centro '{$centro['nombre']}' eliminado correctamente",
            'stats' => [
                'instalaciones_eliminadas' => $instalacionesEliminadas,
                'actividades_eliminadas' => $actividadesEliminadas,
                'inscritos_eliminados' => $inscritosEliminados,
                'asistencias_eliminadas' => $asistenciasEliminadas,
                'observaciones_eliminadas' => $observacionesEliminadas,
                'asignaciones_eliminadas' => $asignacionesEliminadas
            ]
        ]);

    } catch (Exception $ex) {
        $pdo->rollBack();
        error_log('Error en borrado cascada centro: ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el centro']);
    }

} catch (Exception $e) {
    error_log('Error delete centro: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
