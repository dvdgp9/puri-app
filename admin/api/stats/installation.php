<?php
/**
 * Estadísticas específicas para una instalación
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Cargar configuración y autenticación
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin_info = getAdminInfo();

    $instalacion_id = intval($_GET['id'] ?? 0);
    if ($instalacion_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de instalación inválido']);
        exit;
    }

    // Verificar que la instalación existe y autorización
    $stmt = $pdo->prepare("SELECT i.id, i.centro_id FROM instalaciones i WHERE i.id = ?");
    $stmt->execute([$instalacion_id]);
    $instalacion = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$instalacion) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Instalación no encontrada']);
        exit;
    }

    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_info['id'], $instalacion['centro_id']]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No autorizado para esta instalación']);
            exit;
        }
    }

    $stats = [];

    // Actividades activas
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM actividades a WHERE a.instalacion_id = ? AND (a.fecha_inicio <= CURDATE() AND (a.fecha_fin IS NULL OR a.fecha_fin >= CURDATE()))");
    $stmt->execute([$instalacion_id]);
    $stats['actividades_activas'] = (int)($stmt->fetch()['total'] ?? 0);

    // Actividades programadas
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM actividades a WHERE a.instalacion_id = ? AND a.fecha_inicio > CURDATE()");
    $stmt->execute([$instalacion_id]);
    $stats['actividades_programadas'] = (int)($stmt->fetch()['total'] ?? 0);

    // Total de participantes inscritos en actividades de esta instalación
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM inscritos ins INNER JOIN actividades a ON a.id = ins.actividad_id WHERE a.instalacion_id = ?");
    $stmt->execute([$instalacion_id]);
    $stats['total_participantes'] = (int)($stmt->fetch()['total'] ?? 0);

    echo json_encode(['success' => true, 'data' => $stats]);
} catch (Exception $e) {
    error_log('Error in stats/installation.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
