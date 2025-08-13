<?php
/**
 * Actualizar un centro
 */

header('Content-Type: application/json');
require_once '../../auth_middleware.php';
require_once '../../../config/config.php';

try {
    $admin_info = getAdminInfo();
    
    // Solo superadmins pueden actualizar centros
    if ($admin_info['role'] !== 'superadmin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Solo los superadministradores pueden actualizar centros'
        ]);
        exit;
    }
    
    // Obtener datos del cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Datos inválidos'
        ]);
        exit;
    }
    
    // Validar datos requeridos
    $centro_id = isset($input['id']) ? intval($input['id']) : 0;
    $nombre = isset($input['nombre']) ? trim($input['nombre']) : '';
    $direccion = isset($input['direccion']) ? trim($input['direccion']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';
    
    if ($centro_id <= 0 || empty($nombre) || empty($direccion)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Datos incompletos'
        ]);
        exit;
    }
    
    // Verificar que el centro existe
    $stmt = $pdo->prepare("SELECT id, password_hash FROM centros WHERE id = ?");
    $stmt->execute([$centro_id]);
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$centro) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Centro no encontrado'
        ]);
        exit;
    }
    
    // Verificar que no existe otro centro con el mismo nombre
    $stmt = $pdo->prepare("SELECT id FROM centros WHERE nombre = ? AND id != ?");
    $stmt->execute([$nombre, $centro_id]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Ya existe un centro con ese nombre'
        ]);
        exit;
    }
    
    // Preparar datos para actualizar
    $update_fields = [
        'nombre' => $nombre,
        'direccion' => $direccion
    ];
    
    // Si se proporciona una nueva contraseña, actualizarla
    if (!empty($password)) {
        $update_fields['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
    }
    
    // Construir consulta de actualización
    $set_clause = '';
    $params = [];
    
    foreach ($update_fields as $field => $value) {
        if (!empty($set_clause)) {
            $set_clause .= ', ';
        }
        $set_clause .= "$field = ?";
        $params[] = $value;
    }
    
    $params[] = $centro_id;
    
    // Actualizar el centro
    $stmt = $pdo->prepare("UPDATE centros SET $set_clause WHERE id = ?");
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Centro actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el centro');
    }
    
} catch (PDOException $e) {
    error_log("Error de BD en API centros/update: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en API centros/update: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
