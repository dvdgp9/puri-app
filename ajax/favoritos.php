<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'list':
            $device_id = $_GET['device_id'] ?? '';
            if (empty($device_id)) {
                echo json_encode(['success' => false, 'error' => 'Device ID requerido']);
                exit;
            }
            
            $sql = "SELECT f.*, 
                           c.nombre as centro_nombre,
                           i.nombre as instalacion_nombre, 
                           a.nombre as actividad_nombre
                    FROM favoritos f
                    JOIN centros c ON f.centro_id = c.id
                    JOIN instalaciones i ON f.instalacion_id = i.id  
                    JOIN actividades a ON f.actividad_id = a.id
                    WHERE f.device_id = ?
                    ORDER BY f.created_at DESC
                    LIMIT 3";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$device_id]);
            $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'favoritos' => $favoritos]);
            break;
            
        case 'add':
            $input = json_decode(file_get_contents('php://input'), true);
            $device_id = $input['device_id'] ?? '';
            $centro_id = $input['centro_id'] ?? '';
            $instalacion_id = $input['instalacion_id'] ?? '';
            $actividad_id = $input['actividad_id'] ?? '';
            
            if (empty($device_id) || empty($centro_id) || empty($instalacion_id) || empty($actividad_id)) {
                echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                exit;
            }
            
            // Verificar que no exceda el límite de 3
            $countSql = "SELECT COUNT(*) FROM favoritos WHERE device_id = ?";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute([$device_id]);
            $count = $countStmt->fetchColumn();
            
            if ($count >= 3) {
                echo json_encode(['success' => false, 'error' => 'Máximo 3 favoritos permitidos']);
                exit;
            }
            
            // Verificar que la actividad existe
            $checkSql = "SELECT a.id FROM actividades a 
                        JOIN instalaciones i ON a.instalacion_id = i.id
                        JOIN centros c ON i.centro_id = c.id
                        WHERE a.id = ? AND i.id = ? AND c.id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$actividad_id, $instalacion_id, $centro_id]);
            
            if (!$checkStmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Actividad no encontrada']);
                exit;
            }
            
            // Insertar favorito (ON DUPLICATE KEY para evitar duplicados)
            $insertSql = "INSERT INTO favoritos (device_id, centro_id, instalacion_id, actividad_id) 
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([$device_id, $centro_id, $instalacion_id, $actividad_id]);
            
            echo json_encode(['success' => true, 'message' => 'Favorito añadido']);
            break;
            
        case 'remove':
            $input = json_decode(file_get_contents('php://input'), true);
            $device_id = $input['device_id'] ?? '';
            $actividad_id = $input['actividad_id'] ?? '';
            
            if (empty($device_id) || empty($actividad_id)) {
                echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                exit;
            }
            
            $deleteSql = "DELETE FROM favoritos WHERE device_id = ? AND actividad_id = ?";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([$device_id, $actividad_id]);
            
            echo json_encode(['success' => true, 'message' => 'Favorito eliminado']);
            break;
            
        case 'check':
            $device_id = $_GET['device_id'] ?? '';
            $actividad_id = $_GET['actividad_id'] ?? '';
            
            if (empty($device_id) || empty($actividad_id)) {
                echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                exit;
            }
            
            $checkSql = "SELECT COUNT(*) FROM favoritos WHERE device_id = ? AND actividad_id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$device_id, $actividad_id]);
            $isFavorite = $checkStmt->fetchColumn() > 0;
            
            echo json_encode(['success' => true, 'is_favorite' => $isFavorite]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
