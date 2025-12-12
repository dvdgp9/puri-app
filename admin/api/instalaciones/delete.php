<?php
/**
 * Borrar instalación en cascada (solo superadmin)
 * Elimina: asistencias, observaciones, inscritos, actividades y la instalación
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin_info = getAdminInfo();

    // Solo superadmin puede borrar instalaciones
    if ($admin_info['role'] !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Solo superadmin puede eliminar instalaciones']);
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
        echo json_encode(['success' => false, 'message' => 'ID de instalación inválido']);
        exit;
    }

    // Verificar que la instalación existe
    $stmt = $pdo->prepare('SELECT id, nombre FROM instalaciones WHERE id = ?');
    $stmt->execute([$id]);
    $instalacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instalacion) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Instalación no encontrada']);
        exit;
    }

    // Obtener IDs de actividades de esta instalación
    $stmt = $pdo->prepare('SELECT id FROM actividades WHERE instalacion_id = ?');
    $stmt->execute([$id]);
    $actividadIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Borrado en cascada transaccional
    $pdo->beginTransaction();
    try {
        $asistenciasEliminadas = 0;
        $observacionesEliminadas = 0;
        $inscritosEliminados = 0;
        $actividadesEliminadas = 0;

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

            // 4. Eliminar todas las actividades
            $stmt = $pdo->prepare("DELETE FROM actividades WHERE instalacion_id = ?");
            $stmt->execute([$id]);
            $actividadesEliminadas = $stmt->rowCount();
        }

        // 5. Eliminar la instalación
        $stmt = $pdo->prepare('DELETE FROM instalaciones WHERE id = ?');
        $stmt->execute([$id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Instalación '{$instalacion['nombre']}' eliminada correctamente",
            'stats' => [
                'actividades_eliminadas' => $actividadesEliminadas,
                'inscritos_eliminados' => $inscritosEliminados,
                'asistencias_eliminadas' => $asistenciasEliminadas,
                'observaciones_eliminadas' => $observacionesEliminadas
            ]
        ]);

    } catch (Exception $ex) {
        $pdo->rollBack();
        error_log('Error en borrado cascada instalación: ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la instalación']);
    }

} catch (Exception $e) {
    error_log('Error delete instalación: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
