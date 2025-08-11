<?php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once 'auth.php';

// Verificar autenticación
requireAuth();

$admin = getCurrentAdmin();

// Solo los superadmins pueden acceder a esta API
if ($admin['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

// Determinar el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Obtener lista de administradores
        try {
            $stmt = $pdo->query("SELECT a.id, a.nombre, a.email, a.role, COUNT(aa.centro_id) as centros_asignados FROM admins a LEFT JOIN admin_asignaciones aa ON a.id = aa.admin_id GROUP BY a.id, a.nombre, a.email, a.role");
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $admins]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno del servidor']);
        }
        break;
        
    case 'POST':
        // Crear nuevo administrador
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $nombre = $input['nombre'] ?? '';
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            $role = $input['role'] ?? 'admin';
            
            // Validar datos
            if (empty($nombre) || empty($email) || empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Nombre, email y contraseña son obligatorios']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Email inválido']);
                exit;
            }
            
            // Verificar que el email no esté ya registrado
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'El email ya está registrado']);
                exit;
            }
            
            // Crear hash de contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar nuevo administrador
            $stmt = $pdo->prepare("INSERT INTO admins (nombre, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $password_hash, $role]);
            
            $admin_id = $pdo->lastInsertId();
            
            echo json_encode(['success' => true, 'data' => ['id' => $admin_id, 'nombre' => $nombre, 'email' => $email, 'role' => $role]]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno del servidor']);
        }
        break;
        
    case 'PUT':
        // Actualizar administrador
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $id = $input['id'] ?? 0;
            $nombre = $input['nombre'] ?? '';
            $email = $input['email'] ?? '';
            $role = $input['role'] ?? 'admin';
            
            // Validar datos
            if (empty($id) || empty($nombre) || empty($email)) {
                http_response_code(400);
                echo json_encode(['error' => 'ID, nombre y email son obligatorios']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Email inválido']);
                exit;
            }
            
            // Verificar que el email no esté ya registrado por otro admin
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'El email ya está registrado por otro administrador']);
                exit;
            }
            
            // Actualizar administrador
            $stmt = $pdo->prepare("UPDATE admins SET nombre = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$nombre, $email, $role, $id]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno del servidor']);
        }
        break;
        
    case 'DELETE':
        // Eliminar administrador
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $id = $input['id'] ?? 0;
            
            // Validar datos
            if (empty($id)) {
                http_response_code(400);
                echo json_encode(['error' => 'ID es obligatorio']);
                exit;
            }
            
            // No permitir eliminar al propio superadmin
            if ($id == $admin['id']) {
                http_response_code(400);
                echo json_encode(['error' => 'No puedes eliminarte a ti mismo']);
                exit;
            }
            
            // Eliminar asignaciones del administrador
            $stmt = $pdo->prepare("DELETE FROM admin_asignaciones WHERE admin_id = ?");
            $stmt->execute([$id]);
            
            // Eliminar administrador
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno del servidor']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>
