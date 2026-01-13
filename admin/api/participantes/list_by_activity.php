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

    // Obtener el número de días con paso de lista en los últimos 28 días para esta actividad
    $stmt_dias = $pdo->prepare('
        SELECT COUNT(DISTINCT fecha) as total_dias
        FROM asistencias 
        WHERE actividad_id = ? 
          AND fecha >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
    ');
    $stmt_dias->execute([$actividad_id]);
    $dias_con_lista = (int)$stmt_dias->fetchColumn();

    // Listado de participantes (inscritos) de la actividad con estadísticas de asistencia
    $stmt = $pdo->prepare('
        SELECT 
            i.id, 
            i.nombre, 
            i.apellidos,
            (SELECT COUNT(*) 
             FROM asistencias a 
             WHERE a.usuario_id = i.id 
               AND a.actividad_id = ? 
               AND a.asistio = 1
               AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)) AS asistencias_28d
        FROM inscritos i
        WHERE i.actividad_id = ? 
        ORDER BY i.apellidos ASC, i.nombre ASC
    ');
    $stmt->execute([$actividad_id, $actividad_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular porcentaje para cada participante
    foreach ($participants as &$p) {
        $p['asistencias_28d'] = (int)$p['asistencias_28d'];
        $p['dias_con_lista_28d'] = $dias_con_lista;
        $p['porcentaje_asistencia_28d'] = $dias_con_lista > 0 
            ? round(($p['asistencias_28d'] / $dias_con_lista) * 100, 0) 
            : 0;
    }
    unset($p);

    echo json_encode([
        'success' => true,
        'participants' => $participants,
        'count' => count($participants),
        'dias_con_lista_28d' => $dias_con_lista,
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
