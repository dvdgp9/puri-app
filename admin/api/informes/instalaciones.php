<?php
/**
 * API para obtener instalaciones de un centro (para informes)
 */

require_once '../../../config/config.php';
require_once '../../auth_middleware.php';
require_once '../../../includes/ediciones.php';

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
    
    $editions = editionList($pdo, $centroId);
    $editionId = editionResolve($pdo, $centroId, $_GET['edicion_id'] ?? null);
    $activityScope = editionActivitySql($editionId, 'a', true);
    // Obtener instalaciones del centro
    $stmt = $pdo->prepare("
        SELECT i.id, i.nombre
        FROM instalaciones i
        WHERE i.centro_id = ? AND EXISTS (SELECT 1 FROM actividades a WHERE a.instalacion_id = i.id AND $activityScope)
        ORDER BY nombre
    ");
    $stmt->execute([$centroId]);
    $instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear IDs como enteros
    foreach ($instalaciones as &$inst) {
        $inst['id'] = (int)$inst['id'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $instalaciones,
        'ediciones' => $editions,
        'edicion_id' => $editionId
    ]);

} catch (Exception $e) {
    error_log('Error en API informes/instalaciones: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
