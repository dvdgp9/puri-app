<?php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once 'auth.php';

// Verificar autenticación
requireAuth();

$admin = getCurrentAdmin();
$admin_id = $admin['id'];
$admin_role = $admin['role'];

// Determinar método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($pdo, $admin_id, $admin_role);
        break;
    case 'POST':
        handlePost($pdo, $admin_id, $admin_role);
        break;
    case 'PUT':
        handlePut($pdo, $admin_id, $admin_role);
        break;
    case 'DELETE':
        handleDelete($pdo, $admin_id, $admin_role);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGet($pdo, $admin_id, $admin_role) {
    try {
        // Verificar si se solicitan todos los centros (solo para superadmin)
        $all = isset($_GET['all']) && $_GET['all'] === 'true' && $admin_role === 'superadmin';
        
        if ($all) {
            // Obtener todos los centros con estadísticas
            $stmt = $pdo->query("SELECT c.*, COUNT(DISTINCT i.id) as instalaciones_count, COUNT(DISTINCT a.id) as actividades_count FROM centros c LEFT JOIN instalaciones i ON c.id = i.centro_id LEFT JOIN actividades a ON i.id = a.instalacion_id GROUP BY c.id, c.nombre, c.descripcion ORDER BY c.nombre");
            $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else if ($admin_role === 'superadmin') {
            // Para superadmin, obtener todos los centros asignados
            $stmt = $pdo->query("SELECT c.*, COUNT(DISTINCT i.id) as instalaciones_count, COUNT(DISTINCT a.id) as actividades_count FROM centros c LEFT JOIN instalaciones i ON c.id = i.centro_id LEFT JOIN actividades a ON i.id = a.instalacion_id GROUP BY c.id, c.nombre, c.descripcion ORDER BY c.nombre");
            $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Para admin normal, obtener solo centros asignados
            $stmt = $pdo->prepare("SELECT c.*, COUNT(DISTINCT i.id) as instalaciones_count, COUNT(DISTINCT a.id) as actividades_count FROM centros c JOIN admin_asignaciones aa ON c.id = aa.centro_id LEFT JOIN instalaciones i ON c.id = i.centro_id LEFT JOIN actividades a ON i.id = a.instalacion_id WHERE aa.admin_id = ? GROUP BY c.id, c.nombre, c.descripcion ORDER BY c.nombre");
            $stmt->execute([$admin_id]);
            $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'data' => $centros]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
    }
}

function handlePost($pdo, $admin_id, $admin_role) {
    // Solo superadmin puede crear centros
    if ($admin_role !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['error' => 'Solo superadmin puede crear centros']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['nombre']) || !isset($input['descripcion'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO centros (nombre, descripcion) VALUES (?, ?)");
        $stmt->execute([$input['nombre'], $input['descripcion']]);
        
        $centro_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $centro_id,
                'nombre' => $input['nombre'],
                'descripcion' => $input['descripcion']
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el centro']);
    }
}

function handlePut($pdo, $admin_id, $admin_role) {
    // Solo superadmin puede editar centros
    if ($admin_role !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['error' => 'Solo superadmin puede editar centros']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id']) || !isset($input['nombre']) || !isset($input['descripcion'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE centros SET nombre = ?, descripcion = ? WHERE id = ?");
        $stmt->execute([$input['nombre'], $input['descripcion'], $input['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $input['id'],
                    'nombre' => $input['nombre'],
                    'descripcion' => $input['descripcion']
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Centro no encontrado']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el centro']);
    }
}

function handleDelete($pdo, $admin_id, $admin_role) {
    // Solo superadmin puede eliminar centros
    if ($admin_role !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['error' => 'Solo superadmin puede eliminar centros']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de centro requerido']);
        return;
    }
    
    try {
        // Verificar que el centro no tenga instalaciones
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones WHERE centro_id = ?");
        $stmt->execute([$input['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'No se puede eliminar un centro con instalaciones']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM centros WHERE id = ?");
        $stmt->execute([$input['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Centro eliminado correctamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Centro no encontrado']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar el centro']);
    }
}
?>
