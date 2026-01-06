<?php
/**
 * API para obtener actividades de un centro
 * Incluye información de fechas y estado (activa/finalizada)
 */

require_once '../../../config/config.php';
require_once '../../auth_middleware.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $centroId = isset($_GET['centro_id']) ? (int)$_GET['centro_id'] : 0;
    
    if ($centroId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Centro no especificado']);
        exit;
    }
    
    // Verificar permisos del admin sobre el centro (excepto superadmin)
    if (!isSuperAdmin()) {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_id'], $centroId]);
        if (!$stmt->fetchColumn()) {
            echo json_encode(['success' => false, 'error' => 'No tienes acceso a este centro']);
            exit;
        }
    }
    
    // Obtener actividades del centro con información completa
    $sql = "
        SELECT 
            a.id,
            a.nombre,
            a.horario,
            a.dias_semana,
            a.hora_inicio,
            a.hora_fin,
            a.fecha_inicio,
            a.fecha_fin,
            i.nombre AS instalacion_nombre,
            i.id AS instalacion_id,
            CASE 
                WHEN a.fecha_fin IS NOT NULL AND a.fecha_fin < CURDATE() THEN 1 
                ELSE 0 
            END AS finalizada
        FROM actividades a
        INNER JOIN instalaciones i ON a.instalacion_id = i.id
        WHERE i.centro_id = ?
        ORDER BY 
            finalizada ASC,
            a.nombre ASC,
            a.fecha_inicio DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$centroId]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    foreach ($actividades as &$act) {
        $act['id'] = (int)$act['id'];
        $act['instalacion_id'] = (int)$act['instalacion_id'];
        $act['finalizada'] = (bool)$act['finalizada'];
        
        // Construir horario legible si no existe
        if (empty($act['horario']) && $act['hora_inicio'] && $act['hora_fin']) {
            $dias = $act['dias_semana'] ? str_replace(',', ', ', $act['dias_semana']) : '';
            $horas = substr($act['hora_inicio'], 0, 5) . ' - ' . substr($act['hora_fin'], 0, 5);
            $act['horario'] = $dias ? "$dias $horas" : $horas;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $actividades
    ]);

} catch (Exception $e) {
    error_log('Error en API informes/actividades: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
