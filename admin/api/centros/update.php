<?php
/**
 * Actualizar centro (nombre, dirección)
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin = getAdminInfo();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = intval($input['id'] ?? 0);
    $nombre = trim($input['nombre'] ?? '');
    $direccion = trim($input['direccion'] ?? '');

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        exit;
    }
    if ($nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El nombre del centro es obligatorio']);
        exit;
    }

    // Verificar existencia del centro
    $stmt = $pdo->prepare('SELECT id FROM centros WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Centro no encontrado']);
        exit;
    }

    // Autorización: si no es superadmin, validar asignación
    if ($admin['role'] !== 'superadmin') {
        $stmt = $pdo->prepare('SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?');
        $stmt->execute([$admin['id'], $id]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }
    }

    // Evitar duplicados por nombre (si cambia)
    $stmt = $pdo->prepare('SELECT id FROM centros WHERE nombre = ? AND id <> ?');
    $stmt->execute([$nombre, $id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ya existe un centro con ese nombre']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE centros SET nombre = ?, direccion = ? WHERE id = ?');
    $stmt->execute([$nombre, $direccion, $id]);

    echo json_encode(['success' => true, 'message' => 'Centro actualizado']);
} catch (Exception $e) {
    error_log('Error en centros/update.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
