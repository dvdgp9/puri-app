<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin_info = getAdminInfo();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $actividad_id = isset($_GET['actividad_id']) ? intval($_GET['actividad_id']) : 0;
    if ($actividad_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'actividad_id inválido']);
        exit;
    }

    // Cargar contexto de actividad + instalación + centro
    $stmt = $pdo->prepare(
        'SELECT a.id AS actividad_id, a.nombre AS actividad_nombre,
                i.id AS instalacion_id, i.nombre AS instalacion_nombre,
                c.id AS centro_id, c.nombre AS centro_nombre
         FROM actividades a
         INNER JOIN instalaciones i ON i.id = a.instalacion_id
         INNER JOIN centros c ON c.id = i.centro_id
         WHERE a.id = ?'
    );
    $stmt->execute([$actividad_id]);
    $ctx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ctx) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
        exit;
    }

    // Autorización: si no es superadmin, validar asignación al centro
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare('SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?');
        $stmt->execute([$admin_info['id'], $ctx['centro_id']]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado para esta actividad']);
            exit;
        }
    }

    // Listado de participantes (inscritos) de la actividad
    $stmt = $pdo->prepare('SELECT id, nombre, apellidos FROM inscritos WHERE actividad_id = ? ORDER BY nombre ASC, apellidos ASC');
    $stmt->execute([$actividad_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'participants' => $participants,
        'count' => count($participants),
        'activity' => [
            'id' => intval($ctx['actividad_id']),
            'nombre' => $ctx['actividad_nombre']
        ],
        'installation' => [
            'id' => intval($ctx['instalacion_id']),
            'nombre' => $ctx['instalacion_nombre']
        ],
        'center' => [
            'id' => intval($ctx['centro_id']),
            'nombre' => $ctx['centro_nombre']
        ]
    ]);
} catch (Exception $e) {
    error_log('Error in participantes/list_by_activity.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
