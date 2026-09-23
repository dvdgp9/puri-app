<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Cargar configuración y autenticación
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';
    require_once '../../../includes/roster.php';
    
    // Verificar autenticación de admin
    $admin_info = getAdminInfo();

    // Solo aceptar POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    // Validar campos requeridos
    $nombre = trim($input['nombre'] ?? '');
    $apellidos = trim($input['apellidos'] ?? '');
    $actividad_id = intval($input['actividad_id'] ?? 0);

    // Validaciones
    if (empty($nombre)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }

    if (empty($apellidos)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Los apellidos son obligatorios']);
        exit;
    }

    if ($actividad_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar una actividad']);
        exit;
    }

    // Verificar que la actividad existe
    $stmt = $pdo->prepare("SELECT id FROM actividades WHERE id = ?");
    $stmt->execute([$actividad_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
        exit;
    }
    
    // Autorización: si no es superadmin, validar que la actividad pertenezca a un centro asignado
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare(
            "SELECT 1
             FROM actividades a
             INNER JOIN instalaciones i ON a.instalacion_id = i.id
             INNER JOIN admin_asignaciones aa ON aa.centro_id = i.centro_id
             WHERE a.id = ? AND aa.admin_id = ?"
        );
        $stmt->execute([$actividad_id, $admin_info['id']]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado para esta actividad']);
            exit;
        }
    }
    
    $pdo->beginTransaction();
    $pdo->prepare('SELECT id FROM actividades WHERE id = ? FOR UPDATE')->execute([$actividad_id]);
    $stmt = $pdo->prepare('SELECT id, nombre, apellidos, activo FROM inscritos WHERE actividad_id = ?');
    $stmt->execute([$actividad_id]);
    $matches = array_values(array_filter($stmt->fetchAll(PDO::FETCH_ASSOC),
        static fn($row) => rosterKey($row['nombre'], $row['apellidos']) === rosterKey($nombre, $apellidos)));
    if (count($matches) > 1) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Hay varios registros con este nombre; resuelve el duplicado']);
        exit;
    }
    if ($matches && (int) $matches[0]['activo'] === 1) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Este participante ya está inscrito en la actividad']);
        exit;
    }
    if ($matches) {
        $participante_id = (int) $matches[0]['id'];
        rosterActivate($pdo, $participante_id, date('Y-m-d'));
    } else {
        $participante_id = rosterInsert($pdo, $actividad_id, $nombre, $apellidos, date('Y-m-d'));
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $matches ? 'Participante reactivado' : 'Participante inscrito', 'participante_id' => $participante_id]);
    exit;
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Error creating participante: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
