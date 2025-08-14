<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin = getAdminInfo();

    // Leer JSON/body o query params
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = intval($input['id'] ?? ($_GET['id'] ?? 0));
    $activo = isset($input['activo']) ? intval($input['activo']) : (isset($_GET['activo']) ? intval($_GET['activo']) : null);

    if ($id <= 0 || !in_array($activo, [0,1], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        exit;
    }

    // Verificar que el centro existe
    $stmt = $pdo->prepare('SELECT id FROM centros WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Centro no encontrado']);
        exit;
    }

    // Autorización: si no es superadmin, validar asignación al centro
    if ($admin['role'] !== 'superadmin') {
        $stmt = $pdo->prepare('SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?');
        $stmt->execute([$admin['id'], $id]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
    }

    // Actualizar estado activo
    $stmt = $pdo->prepare('UPDATE centros SET activo = ? WHERE id = ?');
    $stmt->execute([$activo, $id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Error in centros/set_active.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
