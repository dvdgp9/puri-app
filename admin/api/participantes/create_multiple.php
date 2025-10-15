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
    $pdo = getPDO();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO participantes (actividad_id, nombre, apellidos) VALUES (?, ?, ?)');

    $inserted = 0;
    $errors = [];

    foreach ($participantes as $idx => $p) {
        $nombre = isset($p['nombre']) ? trim((string)$p['nombre']) : '';
        $apellidos = isset($p['apellidos']) ? trim((string)$p['apellidos']) : '';

        if ($nombre === '' && $apellidos === '') {
            continue;
        }
        if ($nombre === '' || $apellidos === '') {
            $errors[] = ['row' => $idx, 'message' => 'Faltan nombre o apellidos'];
            continue;
        }

        $nombre = preg_replace('/\s+/', ' ', $nombre);
        $apellidos = preg_replace('/\s+/', ' ', $apellidos);
        $stmt->execute([$actividad_id, $nombre, $apellidos]);
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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
