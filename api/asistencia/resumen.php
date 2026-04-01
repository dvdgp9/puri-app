<?php
/**
 * API resumen de asistencia por actividad y fecha.
 * GET: actividad_id, fecha
 * Devuelve: total, presentes, ausentes
 */
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['centro_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$actividad_id = filter_input(INPUT_GET, 'actividad_id', FILTER_VALIDATE_INT);
$fecha = $_GET['fecha'] ?? date('Y-m-d');

if (!$actividad_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

try {
    // Verificar que la actividad pertenece al centro de la sesión
    $stmtAct = $pdo->prepare("
        SELECT 1
        FROM actividades a
        INNER JOIN instalaciones i ON i.id = a.instalacion_id
        WHERE a.id = ? AND i.centro_id = ?
    ");
    $stmtAct->execute([$actividad_id, $_SESSION['centro_id']]);
    if (!$stmtAct->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    $stmtTotal = $pdo->prepare('SELECT COUNT(*) FROM inscritos WHERE actividad_id = ?');
    $stmtTotal->execute([$actividad_id]);
    $total = (int)$stmtTotal->fetchColumn();

    $stmtPresentes = $pdo->prepare("
        SELECT COUNT(*)
        FROM asistencias
        WHERE actividad_id = ?
          AND fecha = ?
          AND asistio = 1
    ");
    $stmtPresentes->execute([$actividad_id, $fecha]);
    $presentes = (int)$stmtPresentes->fetchColumn();

    if ($presentes > $total) {
        $presentes = $total;
    }
    $ausentes = max(0, $total - $presentes);

    echo json_encode([
        'success' => true,
        'actividad_id' => $actividad_id,
        'fecha' => $fecha,
        'total' => $total,
        'presentes' => $presentes,
        'ausentes' => $ausentes
    ]);
} catch (PDOException $e) {
    error_log('Error resumen asistencia: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

