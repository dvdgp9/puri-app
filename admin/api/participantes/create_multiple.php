<?php
/**
 * API para crear múltiples participantes de una vez
 * Recibe array de participantes y los inserta en lote
 */

require_once '../../../config/config.php';
require_once '../../auth_middleware.php';

// Verificar autenticación
$admin_info = getAdminInfo();

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

// Validar parámetros requeridos
if (!isset($input['actividad_id']) || !isset($input['participantes'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos']);
    exit;
}

$actividad_id = intval($input['actividad_id']);
$participantes = $input['participantes'];

if ($actividad_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de actividad inválido']);
    exit;
}

if (!is_array($participantes) || empty($participantes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lista de participantes vacía']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar que la actividad existe y el admin tiene acceso
    $stmt = $pdo->prepare("
        SELECT a.id, a.nombre, i.nombre as instalacion_nombre, c.nombre as centro_nombre
        FROM actividades a
        JOIN instalaciones i ON a.instalacion_id = i.id
        JOIN centros c ON i.centro_id = c.id
        WHERE a.id = ? AND a.activo = 1
    ");
    $stmt->execute([$actividad_id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actividad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
        exit;
    }
    
    // Verificar permisos del admin (si no es superadmin, debe tener acceso al centro)
    if (!isSuperAdmin()) {
        $stmt = $pdo->prepare("
            SELECT 1 FROM admin_centros ac
            JOIN instalaciones i ON ac.centro_id = i.centro_id
            JOIN actividades a ON i.id = a.instalacion_id
            WHERE ac.admin_id = ? AND a.id = ?
        ");
        $stmt->execute([$admin_info['id'], $actividad_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para esta actividad']);
            exit;
        }
    }
    
    // Preparar statement para inserción
    $insertStmt = $pdo->prepare("
        INSERT INTO inscritos (actividad_id, nombre, apellidos, fecha_inscripcion)
        VALUES (?, ?, ?, NOW())
    ");
    
    $resultados = [];
    $exitosos = 0;
    $errores = 0;
    
    // Procesar cada participante
    foreach ($participantes as $index => $participante) {
        $fila = $index + 1;
        
        // Validar datos del participante
        if (!isset($participante['nombre']) || !isset($participante['apellidos'])) {
            $resultados[] = [
                'fila' => $fila,
                'success' => false,
                'message' => 'Faltan campos nombre o apellidos'
            ];
            $errores++;
            continue;
        }
        
        $nombre = trim($participante['nombre']);
        $apellidos = trim($participante['apellidos']);
        
        if (empty($nombre) || empty($apellidos)) {
            $resultados[] = [
                'fila' => $fila,
                'success' => false,
                'message' => 'Nombre y apellidos no pueden estar vacíos'
            ];
            $errores++;
            continue;
        }
        
        // Normalizar datos
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        $apellidos = preg_replace('/\s+/', ' ', $apellidos);
        
        try {
            // Insertar participante
            $insertStmt->execute([$actividad_id, $nombre, $apellidos]);
            $resultados[] = [
                'fila' => $fila,
                'success' => true,
                'message' => 'Participante añadido correctamente',
                'nombre' => $nombre,
                'apellidos' => $apellidos
            ];
            $exitosos++;
        } catch (PDOException $e) {
            $resultados[] = [
                'fila' => $fila,
                'success' => false,
                'message' => 'Error al insertar: ' . $e->getMessage(),
                'nombre' => $nombre,
                'apellidos' => $apellidos
            ];
            $errores++;
        }
    }
    
    // Respuesta final
    $message = "Procesados: {$exitosos} exitosos, {$errores} errores";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'summary' => [
            'total' => count($participantes),
            'exitosos' => $exitosos,
            'errores' => $errores
        ],
        'resultados' => $resultados
    ]);
    
} catch (Exception $e) {
    error_log("Error en create_multiple.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
