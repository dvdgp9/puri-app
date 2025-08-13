<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin = getAdminInfo();

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = intval($input['id'] ?? ($_GET['id'] ?? 0));
    $nombre = trim($input['nombre'] ?? '');

    if ($id <= 0 || $nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        exit;
    }

    // Obtener centro_id de la instalación para autorización
    $stmt = $pdo->prepare('SELECT centro_id FROM instalaciones WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Instalación no encontrada']);
        exit;
    }
    $centro_id = intval($row['centro_id']);

    if ($admin['role'] !== 'superadmin') {
        $stmt = $pdo->prepare('SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?');
        $stmt->execute([$admin['id'], $centro_id]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
    }

    $stmt = $pdo->prepare('UPDATE instalaciones SET nombre = ? WHERE id = ?');
    $stmt->execute([$nombre, $id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Error in instalaciones/update.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
