<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin_info = getAdminInfo();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    // Cargar actividad y su instalación/centro
    $stmt = $pdo->prepare("SELECT a.id, a.instalacion_id, i.centro_id FROM actividades a INNER JOIN instalaciones i ON i.id = a.instalacion_id WHERE a.id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
        exit;
    }

    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_info['id'], $row['centro_id']]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
    }

    // Campos a actualizar (opcionales)
    $nombre = isset($input['nombre']) ? trim($input['nombre']) : null;
    $grupo = array_key_exists('grupo', $input) ? (trim((string)$input['grupo']) ?: null) : null;
    $dias_semana = $input['dias_semana'] ?? null; // puede ser array o string
    $hora_inicio = array_key_exists('hora_inicio', $input) ? trim((string)$input['hora_inicio']) : null;
    $hora_fin = array_key_exists('hora_fin', $input) ? trim((string)$input['hora_fin']) : null;
    $fecha_inicio = array_key_exists('fecha_inicio', $input) ? trim((string)$input['fecha_inicio']) : null;
    $fecha_fin = array_key_exists('fecha_fin', $input) ? (trim((string)$input['fecha_fin']) ?: null) : null;
    $tipo_control = array_key_exists('tipo_control', $input) ? (in_array($input['tipo_control'], ['asistencia', 'aforo']) ? $input['tipo_control'] : null) : null;

    if ($nombre !== null && $nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre no puede estar vacío']);
        exit;
    }

    // Normalizar dias_semana
    if ($dias_semana !== null) {
        if (is_array($dias_semana)) {
            $dias_semana_string = implode(',', $dias_semana);
        } else {
            $dias_semana_string = trim((string)$dias_semana);
        }
    }

    // Construir SET dinámico
    $fields = [];
    $params = [];
    if ($nombre !== null) { $fields[] = 'nombre = ?'; $params[] = $nombre; }
    if ($grupo !== null || array_key_exists('grupo', $input)) { $fields[] = 'grupo = ?'; $params[] = $grupo; }
    if (isset($dias_semana_string)) { $fields[] = 'dias_semana = ?'; $params[] = $dias_semana_string; }
    if ($hora_inicio !== null) { $fields[] = 'hora_inicio = ?'; $params[] = $hora_inicio; }
    if ($hora_fin !== null) { $fields[] = 'hora_fin = ?'; $params[] = $hora_fin; }
    if ($fecha_inicio !== null) { $fields[] = 'fecha_inicio = ?'; $params[] = $fecha_inicio; }
    if ($fecha_fin !== null || array_key_exists('fecha_fin', $input)) { $fields[] = 'fecha_fin = ?'; $params[] = $fecha_fin; }
    if ($tipo_control !== null) { $fields[] = 'tipo_control = ?'; $params[] = $tipo_control; }

    if (empty($fields)) {
        echo json_encode(['success' => true, 'message' => 'Sin cambios']);
        exit;
    }

    // Campo legacy horario si hay datos suficientes
    if ($nombre !== null || isset($dias_semana_string) || $hora_inicio !== null || $hora_fin !== null) {
        // reconstruir horario si tenemos dias y horas
        if (isset($dias_semana_string) && $hora_inicio !== null && $hora_fin !== null) {
            $dias_arr = array_filter(array_map('trim', explode(',', $dias_semana_string)));
            $horario = (empty($dias_arr) ? '' : implode(' y ', $dias_arr)) . ' ' . $hora_inicio . '-' . $hora_fin;
            $fields[] = 'horario = ?';
            $params[] = trim($horario);
        }
    }

    $params[] = $id;
    $sql = 'UPDATE actividades SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute($params);

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Actividad actualizada']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la actividad']);
    }
} catch (Exception $e) {
    error_log('Error in actividades/update.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
