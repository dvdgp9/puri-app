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
        // Verificar si se solicitan todas las instalaciones (solo para superadmin)
        $all = isset($_GET['all']) && $_GET['all'] === 'true' && $admin_role === 'superadmin';
        
        if ($all) {
            // Obtener todas las instalaciones con información del centro
            $stmt = $pdo->query("SELECT i.*, c.nombre as centro_nombre, COUNT(a.id) as actividades_count FROM instalaciones i LEFT JOIN centros c ON i.centro_id = c.id LEFT JOIN actividades a ON i.id = a.instalacion_id GROUP BY i.id, i.nombre, i.descripcion, i.centro_id, c.nombre ORDER BY c.nombre, i.nombre");
            $instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else if ($admin_role === 'superadmin') {
            // Para superadmin, obtener todas las instalaciones
            $stmt = $pdo->query("SELECT i.*, c.nombre as centro_nombre, COUNT(a.id) as actividades_count FROM instalaciones i LEFT JOIN centros c ON i.centro_id = c.id LEFT JOIN actividades a ON i.id = a.instalacion_id GROUP BY i.id, i.nombre, i.descripcion, i.centro_id, c.nombre ORDER BY c.nombre, i.nombre");
            $instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Para admin normal, obtener solo instalaciones de centros asignados
            $stmt = $pdo->prepare("SELECT i.*, c.nombre as centro_nombre, COUNT(a.id) as actividades_count FROM instalaciones i JOIN centros c ON i.centro_id = c.id JOIN admin_asignaciones aa ON c.id = aa.centro_id LEFT JOIN actividades a ON i.id = a.instalacion_id WHERE aa.admin_id = ? GROUP BY i.id, i.nombre, i.descripcion, i.centro_id, c.nombre ORDER BY c.nombre, i.nombre");
            $stmt->execute([$admin_id]);
            $instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'data' => $instalaciones]);
        // Obtener nombres de centros
        $centros = [];
        if ($instalaciones) {
            $centro_ids = array_unique(array_column($instalaciones, 'centro_id'));
            $placeholders = str_repeat('?,', count($centro_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id, nombre FROM centros WHERE id IN ($placeholders)");
            $stmt->execute($centro_ids);
            $centros = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        
        // Añadir nombre del centro a cada instalación
        foreach ($instalaciones as &$instalacion) {
            $instalacion['centro_nombre'] = $centros[$instalacion['centro_id']] ?? 'Desconocido';
        }
        
        echo json_encode([
            'success' => true,
            'data' => $instalaciones
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
    }
}

function handlePost($pdo, $admin_id, $admin_role) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['nombre']) || !isset($input['descripcion']) || !isset($input['centro_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }
    
    // Verificar que el admin tenga acceso al centro
    if ($admin_role !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_id, $input['centro_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes acceso a este centro']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO instalaciones (nombre, descripcion, centro_id) VALUES (?, ?, ?)");
        $stmt->execute([$input['nombre'], $input['descripcion'], $input['centro_id']]);
        
        $instalacion_id = $pdo->lastInsertId();
        
        // Obtener nombre del centro
        $stmt = $pdo->prepare("SELECT nombre FROM centros WHERE id = ?");
        $stmt->execute([$input['centro_id']]);
        $centro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $instalacion_id,
                'nombre' => $input['nombre'],
                'descripcion' => $input['descripcion'],
                'centro_id' => $input['centro_id'],
                'centro_nombre' => $centro['nombre']
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear la instalación']);
    }
}

function handlePut($pdo, $admin_id, $admin_role) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id']) || !isset($input['nombre']) || !isset($input['descripcion']) || !isset($input['centro_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }
    
    // Verificar que el admin tenga acceso al centro
    if ($admin_role !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_id, $input['centro_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes acceso a este centro']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE instalaciones SET nombre = ?, descripcion = ?, centro_id = ? WHERE id = ?");
        $stmt->execute([$input['nombre'], $input['descripcion'], $input['centro_id'], $input['id']]);
        
        if ($stmt->rowCount() > 0) {
            // Obtener nombre del centro
            $stmt = $pdo->prepare("SELECT nombre FROM centros WHERE id = ?");
            $stmt->execute([$input['centro_id']]);
            $centro = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $input['id'],
                    'nombre' => $input['nombre'],
                    'descripcion' => $input['descripcion'],
                    'centro_id' => $input['centro_id'],
                    'centro_nombre' => $centro['nombre']
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Instalación no encontrada']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar la instalación']);
    }
}

function handleDelete($pdo, $admin_id, $admin_role) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de instalación requerido']);
        return;
    }
    
    // Verificar que el admin tenga acceso a la instalación
    if ($admin_role !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones i JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ? AND i.id = ?");
        $stmt->execute([$admin_id, $input['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes acceso a esta instalación']);
            return;
        }
    }
    
    try {
        // Verificar que la instalación no tenga actividades
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades WHERE instalacion_id = ?");
        $stmt->execute([$input['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'No se puede eliminar una instalación con actividades']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM instalaciones WHERE id = ?");
        $stmt->execute([$input['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Instalación eliminada correctamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Instalación no encontrada']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar la instalación']);
    }
}
?>
