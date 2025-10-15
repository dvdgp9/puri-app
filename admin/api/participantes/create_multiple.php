<?php
require_once '../../../config/config.php';
require_once '../../auth_middleware.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!is_array($data)) {
    // permitir también form-data
    $data = $_POST;
}

$actividad_id = isset($data['actividad_id']) ? (int)$data['actividad_id'] : 0;
$participantes = isset($data['participantes']) ? $data['participantes'] : [];

if (!$actividad_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Actividad inválida']);
    exit;
}

if (!is_array($participantes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato inválido de participantes']);
    exit;
}

try {
    // Autenticación y autorización
    $admin_info = getAdminInfo();

    // Verificar que la actividad existe
    $stmt = $pdo->prepare('SELECT a.id, i.centro_id FROM actividades a INNER JOIN instalaciones i ON i.id = a.instalacion_id WHERE a.id = ?');
    $stmt->execute([$actividad_id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$actividad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
        exit;
    }

    // Si no es superadmin, validar que el centro esté asignado
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare('SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?');
        $stmt->execute([$admin_info['id'], $actividad['centro_id']]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado para esta actividad']);
            exit;
        }
    }

    // Transacción e inserciones
    $pdo->beginTransaction();
    $stmtIns = $pdo->prepare('INSERT INTO inscritos (actividad_id, nombre, apellidos) VALUES (?, ?, ?)');

    $inserted = 0;
    $errors = [];

    foreach ($participantes as $idx => $p) {
        $nombre = isset($p['nombre']) ? trim((string)$p['nombre']) : '';
        $apellidos = isset($p['apellidos']) ? trim((string)$p['apellidos']) : '';

        if ($nombre === '' && $apellidos === '') {
            continue; // fila vacía
        }
        if ($nombre === '' || $apellidos === '') {
            $errors[] = ['row' => $idx, 'message' => 'Faltan nombre o apellidos'];
            continue;
        }

        $nombre = preg_replace('/\s+/', ' ', $nombre);
        $apellidos = preg_replace('/\s+/', ' ', $apellidos);

        $stmtIns->execute([$actividad_id, $nombre, $apellidos]);
        $inserted++;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'inserted' => $inserted,
        'errors' => $errors,
        'message' => $inserted > 0 ? 'Participantes añadidos' : 'Sin filas válidas'
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('create_multiple error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
