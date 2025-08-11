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
        // Verificar si se solicitan todas las actividades (solo para superadmin)
        $all = isset($_GET['all']) && $_GET['all'] === 'true' && $admin_role === 'superadmin';
        
        if ($all) {
            // Obtener todas las actividades con información del centro e instalación
            $stmt = $pdo->query("SELECT a.id, a.nombre, a.descripcion, a.fecha_inicio, a.fecha_fin, a.dias_semana, a.hora_inicio, a.hora_fin, a.instalacion_id, i.nombre as instalacion_nombre, i.centro_id, c.nombre as centro_nombre FROM actividades a LEFT JOIN instalaciones i ON a.instalacion_id = i.id LEFT JOIN centros c ON i.centro_id = c.id ORDER BY c.nombre, i.nombre, a.nombre");
            $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Obtener parámetros de filtro
            $centro_id = isset($_GET['centro_id']) ? (int)$_GET['centro_id'] : null;
            $instalacion_id = isset($_GET['instalacion_id']) ? (int)$_GET['instalacion_id'] : null;
            $estado = isset($_GET['estado']) ? $_GET['estado'] : null;
            
            // Construir consulta base
            $sql = "SELECT a.id, a.nombre, a.descripcion, a.fecha_inicio, a.fecha_fin, a.dias_semana, a.hora_inicio, a.hora_fin, a.instalacion_id, i.nombre as instalacion_nombre, i.centro_id, c.nombre as centro_nombre FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id JOIN centros c ON i.centro_id = c.id";
            
            // Construir condiciones
            $conditions = [];
            $params = [];
            
            if ($admin_role !== 'superadmin') {
                $conditions[] = "i.centro_id IN (SELECT centro_id FROM admin_asignaciones WHERE admin_id = ?)";
                $params[] = $admin_id;
            }
            
            if ($centro_id) {
                $conditions[] = "i.centro_id = ?";
                $params[] = $centro_id;
            }
            
            if ($instalacion_id) {
                $conditions[] = "a.instalacion_id = ?";
                $params[] = $instalacion_id;
            }
            
            if ($estado) {
                $today = date('Y-m-d');
                switch ($estado) {
                    case 'programadas':
                        $conditions[] = "a.fecha_inicio > ?";
                        $params[] = $today;
                        break;
                    case 'activas':
                        $conditions[] = "a.fecha_inicio <= ? AND (a.fecha_fin IS NULL OR a.fecha_fin >= ?)";
                        $params[] = $today;
                        $params[] = $today;
                        break;
                    case 'finalizadas':
                        $conditions[] = "a.fecha_fin < ?";
                        $params[] = $today;
                        break;
                }
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY a.nombre";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Formatear horarios
        foreach ($actividades as &$actividad) {
            $actividad['horario'] = formatHorario($actividad['dias_semana'], $actividad['hora_inicio'], $actividad['hora_fin']);
            $actividad['estado'] = getEstadoActividad($actividad['fecha_inicio'], $actividad['fecha_fin']);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $actividades
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
    }
}

function handlePost($pdo, $admin_id, $admin_role) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['nombre']) || !isset($input['descripcion']) || !isset($input['instalacion_id']) || !isset($input['fecha_inicio']) || !isset($input['dias_semana']) || !isset($input['hora_inicio']) || !isset($input['hora_fin'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }
    
    // Verificar que el admin tenga acceso a la instalación
    if ($admin_role !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones i JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ? AND i.id = ?");
        $stmt->execute([$admin_id, $input['instalacion_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes acceso a esta instalación']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO actividades (nombre, descripcion, instalacion_id, fecha_inicio, fecha_fin, dias_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['nombre'],
            $input['descripcion'],
            $input['instalacion_id'],
            $input['fecha_inicio'],
            $input['fecha_fin'] ?? null,
            $input['dias_semana'],
            $input['hora_inicio'],
            $input['hora_fin']
        ]);
        
        $actividad_id = $pdo->lastInsertId();
        
        // Obtener datos de la actividad creada
        $stmt = $pdo->prepare("SELECT a.id, a.nombre, a.descripcion, a.fecha_inicio, a.fecha_fin, a.dias_semana, a.hora_inicio, a.hora_fin, a.instalacion_id, i.nombre as instalacion_nombre, i.centro_id, c.nombre as centro_nombre FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id JOIN centros c ON i.centro_id = c.id WHERE a.id = ?");
        $stmt->execute([$actividad_id]);
        $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($actividad) {
            $actividad['horario'] = formatHorario($actividad['dias_semana'], $actividad['hora_inicio'], $actividad['hora_fin']);
            $actividad['estado'] = getEstadoActividad($actividad['fecha_inicio'], $actividad['fecha_fin']);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $actividad
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear la actividad']);
    }
}

function handlePut($pdo, $admin_id, $admin_role) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id']) || !isset($input['nombre']) || !isset($input['descripcion']) || !isset($input['instalacion_id']) || !isset($input['fecha_inicio']) || !isset($input['dias_semana']) || !isset($input['hora_inicio']) || !isset($input['hora_fin'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }
    
    // Verificar que el admin tenga acceso a la instalación
    if ($admin_role !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones i JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ? AND i.id = ?");
        $stmt->execute([$admin_id, $input['instalacion_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes acceso a esta instalación']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE actividades SET nombre = ?, descripcion = ?, instalacion_id = ?, fecha_inicio = ?, fecha_fin = ?, dias_semana = ?, hora_inicio = ?, hora_fin = ? WHERE id = ?");
        $stmt->execute([
            $input['nombre'],
            $input['descripcion'],
            $input['instalacion_id'],
            $input['fecha_inicio'],
            $input['fecha_fin'] ?? null,
            $input['dias_semana'],
            $input['hora_inicio'],
            $input['hora_fin'],
            $input['id']
        ]);
        
        if ($stmt->rowCount() > 0) {
            // Obtener datos de la actividad actualizada
            $stmt = $pdo->prepare("SELECT a.id, a.nombre, a.descripcion, a.fecha_inicio, a.fecha_fin, a.dias_semana, a.hora_inicio, a.hora_fin, a.instalacion_id, i.nombre as instalacion_nombre, i.centro_id, c.nombre as centro_nombre FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id JOIN centros c ON i.centro_id = c.id WHERE a.id = ?");
            $stmt->execute([$input['id']]);
            $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($actividad) {
                $actividad['horario'] = formatHorario($actividad['dias_semana'], $actividad['hora_inicio'], $actividad['hora_fin']);
                $actividad['estado'] = getEstadoActividad($actividad['fecha_inicio'], $actividad['fecha_fin']);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $actividad
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Actividad no encontrada']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar la actividad']);
    }
}

function handleDelete($pdo, $admin_id, $admin_role) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de actividad requerido']);
        return;
    }
    
    // Verificar que el admin tenga acceso a la actividad
    if ($admin_role !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ? AND a.id = ?");
        $stmt->execute([$admin_id, $input['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes acceso a esta actividad']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM actividades WHERE id = ?");
        $stmt->execute([$input['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Actividad eliminada correctamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Actividad no encontrada']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar la actividad']);
    }
}

function formatHorario($dias_semana, $hora_inicio, $hora_fin) {
    // Convertir días de la semana a formato corto
    $dias = explode(',', $dias_semana);
    $dias_cortos = [];
    
    foreach ($dias as $dia) {
        switch (trim($dia)) {
            case 'Lunes': $dias_cortos[] = 'L'; break;
            case 'Martes': $dias_cortos[] = 'M'; break;
            case 'Miércoles': $dias_cortos[] = 'X'; break;
            case 'Jueves': $dias_cortos[] = 'J'; break;
            case 'Viernes': $dias_cortos[] = 'V'; break;
            case 'Sábado': $dias_cortos[] = 'S'; break;
            case 'Domingo': $dias_cortos[] = 'D'; break;
            default: $dias_cortos[] = substr(trim($dia), 0, 1); break;
        }
    }
    
    return implode(' ', $dias_cortos) . ' ' . $hora_inicio . '-' . $hora_fin;
}

function getEstadoActividad($fecha_inicio, $fecha_fin) {
    $today = date('Y-m-d');
    
    if ($fecha_inicio > $today) {
        return 'programada';
    } elseif ($fecha_fin && $fecha_fin < $today) {
        return 'finalizada';
    } else {
        return 'activa';
    }
}
?>
