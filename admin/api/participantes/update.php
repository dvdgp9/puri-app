<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    // Verificar autenticación de admin
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

    $id = intval($input['id'] ?? 0);
    $nombre = trim($input['nombre'] ?? '');
    $apellidos = trim($input['apellidos'] ?? '');

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de participante inválido']);
        exit;
    }
    if ($nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }
    if ($apellidos === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Los apellidos son obligatorios']);
        exit;
    }

    // Obtener el participante y su actividad
    $stmt = $pdo->prepare("SELECT id, actividad_id FROM inscritos WHERE id = ?");
    $stmt->execute([$id]);
    $participante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participante) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Participante no encontrado']);
        exit;
    }

    $actividad_id = intval($participante['actividad_id']);

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

    // Actualizar datos del participante
    $stmt = $pdo->prepare("UPDATE inscritos SET nombre = ?, apellidos = ? WHERE id = ?");
    $ok = $stmt->execute([$nombre, $apellidos, $id]);

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Participante actualizado correctamente']);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el participante']);
        exit;
    }

} catch (Exception $e) {
    error_log('Error updating participante: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
