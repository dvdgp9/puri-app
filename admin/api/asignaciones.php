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
        // Obtener asignaciones actuales
        try {
            $stmt = $pdo->query("SELECT aa.admin_id, aa.centro_id, a.nombre as admin_nombre, c.nombre as centro_nombre FROM admin_asignaciones aa JOIN admins a ON aa.admin_id = a.id JOIN centros c ON aa.centro_id = c.id");
            $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $asignaciones]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno del servidor']);
        }
        break;
        
    case 'POST':
        // Asignar centro a administrador
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $admin_id = $input['admin_id'] ?? 0;
            $centro_id = $input['centro_id'] ?? 0;
            
            // Validar datos
            if (empty($admin_id) || empty($centro_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de administrador y centro son obligatorios']);
                exit;
            }
            
            // Verificar que el administrador no sea un superadmin
            $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin_data = $stmt->fetch();
            
            if (!$admin_data || $admin_data['role'] === 'superadmin') {
                http_response_code(400);
                echo json_encode(['error' => 'No se puede asignar centros a un superadministrador']);
                exit;
            }
            
            // Verificar si la asignación ya existe
            $stmt = $pdo->prepare("SELECT id FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
            $stmt->execute([$admin_id, $centro_id]);
            
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Esta asignación ya existe']);
                exit;
            }
            
            // Crear asignación
            $stmt = $pdo->prepare("INSERT INTO admin_asignaciones (admin_id, centro_id) VALUES (?, ?)");
            $stmt->execute([$admin_id, $centro_id]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno del servidor']);
        }
        break;
        
    case 'DELETE':
        // Eliminar asignación
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $admin_id = $input['admin_id'] ?? 0;
            $centro_id = $input['centro_id'] ?? 0;
            
            // Validar datos
            if (empty($admin_id) || empty($centro_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de administrador y centro son obligatorios']);
                exit;
            }
            
            // Eliminar asignación
            $stmt = $pdo->prepare("DELETE FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
            $stmt->execute([$admin_id, $centro_id]);
            
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
