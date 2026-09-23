<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.php';
require_once '../../auth_middleware.php';
require_once '../../../includes/roster.php';

function rosterResponse(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $admin = getAdminInfo();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        rosterResponse(405, ['success' => false, 'message' => 'Método no permitido']);
    }
    $activityId = filter_var($_POST['actividad_id'] ?? null, FILTER_VALIDATE_INT);
    $mode = (string) ($_POST['mode'] ?? 'sync');
    $preview = (string) ($_POST['preview'] ?? '') === '1';
    if (!$activityId || !in_array($mode, ['sync', 'append'], true)) {
        rosterResponse(400, ['success' => false, 'message' => 'Actividad o modo de importación inválido']);
    }
    $scope = $pdo->prepare('SELECT i.centro_id FROM actividades a JOIN instalaciones i ON i.id = a.instalacion_id WHERE a.id = ?');
    $scope->execute([$activityId]);
    $centerId = $scope->fetchColumn();
    if ($centerId === false) {
        rosterResponse(404, ['success' => false, 'message' => 'Actividad no encontrada']);
    }
    if ($admin['role'] !== 'superadmin') {
        $access = $pdo->prepare('SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?');
        $access->execute([$admin['id'], $centerId]);
        if (!$access->fetchColumn()) {
            rosterResponse(403, ['success' => false, 'message' => 'No autorizado para esta actividad']);
        }
    }
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
        rosterResponse(400, ['success' => false, 'message' => 'Selecciona un CSV válido']);
    }
    if (strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        rosterResponse(400, ['success' => false, 'message' => 'El archivo debe ser CSV']);
    }
    $content = file_get_contents($_FILES['csv']['tmp_name']);
    if ($content === false || strlen($content) > 5 * 1024 * 1024) {
        rosterResponse(400, ['success' => false, 'message' => 'El CSV no se pudo leer o supera 5 MB']);
    }
    $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16', 'Windows-1252', 'ISO-8859-1'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    }
    if (!mb_check_encoding($content, 'UTF-8')) {
        rosterResponse(422, ['success' => false, 'message' => 'El CSV debe usar una codificación de texto válida']);
    }
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    $firstLine = strtok($content, "\r\n") ?: '';
    $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    $handle = fopen('php://temp', 'w+');
    fwrite($handle, $content);
    rewind($handle);
    $headers = fgetcsv($handle, 0, $delimiter);
    $headers = array_map(static fn($h) => mb_strtolower(trim((string) $h), 'UTF-8'), $headers ?: []);
    $nameColumn = null;
    $surnameColumn = null;
    foreach ($headers as $index => $header) {
        if (in_array($header, ['nombre', 'name', 'nombres'], true)) $nameColumn = $index;
        if (in_array($header, ['apellidos', 'apellido', 'surname', 'last name', 'lastname'], true)) $surnameColumn = $index;
    }
    if ($nameColumn === null || $surnameColumn === null) {
        rosterResponse(422, ['success' => false, 'message' => 'El CSV necesita columnas Nombre y Apellidos']);
    }
    $incoming = [];
    $line = 1;
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $line++;
        if ($row === [null] || $row === []) continue;
        $name = preg_replace('/\s+/u', ' ', trim((string) ($row[$nameColumn] ?? '')));
        $surname = preg_replace('/\s+/u', ' ', trim((string) ($row[$surnameColumn] ?? '')));
        if ($name === '' || $surname === '') {
            rosterResponse(422, ['success' => false, 'message' => "Línea $line: faltan nombre o apellidos"]);
        }
        $key = rosterKey($name, $surname);
        if (isset($incoming[$key])) {
            rosterResponse(422, ['success' => false, 'message' => "Línea $line: nombre y apellidos repetidos en el CSV"]);
        }
        $incoming[$key] = ['nombre' => $name, 'apellidos' => $surname];
    }
    fclose($handle);
    if (!$incoming) {
        rosterResponse(422, ['success' => false, 'message' => 'El CSV no contiene participantes; no se ha modificado el listado']);
    }

    if (!$preview) {
        $pdo->beginTransaction();
        $pdo->prepare('SELECT id FROM actividades WHERE id = ? FOR UPDATE')->execute([$activityId]);
    }
    $stmt = $pdo->prepare('SELECT id, nombre, apellidos, activo FROM inscritos WHERE actividad_id = ? ORDER BY id');
    $stmt->execute([$activityId]);
    $existing = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $person) {
        $key = rosterKey($person['nombre'], $person['apellidos']);
        if (isset($existing[$key])) {
            throw new DomainException('Hay participantes con el mismo nombre y apellidos en esta actividad. Resuelve el duplicado antes de actualizar el listado.');
        }
        $existing[$key] = $person;
    }
    ksort($existing, SORT_STRING);
    $rosterFingerprint = hash('sha256', json_encode(array_map(static fn($person) => [
        'id' => (int) $person['id'],
        'activo' => (int) $person['activo'],
        'nombre' => $person['nombre'],
        'apellidos' => $person['apellidos'],
    ], $existing), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    $counts = ['mantenidos' => 0, 'nuevos' => 0, 'desactivados' => 0, 'reactivados' => 0];
    foreach ($incoming as $key => $person) {
        if (!isset($existing[$key])) $counts['nuevos']++;
        elseif ((int) $existing[$key]['activo'] === 0) $counts['reactivados']++;
        else $counts['mantenidos']++;
    }
    if ($mode === 'sync') {
        foreach ($existing as $key => $person) {
            if ((int) $person['activo'] === 1 && !isset($incoming[$key])) $counts['desactivados']++;
        }
    }
    if ($preview) {
        rosterResponse(200, ['success' => true, 'preview' => true, 'counts' => $counts, 'roster_fingerprint' => $rosterFingerprint]);
    }
    if (isset($_POST['expected_roster_fingerprint']) && !hash_equals($rosterFingerprint, (string) $_POST['expected_roster_fingerprint'])) {
        $pdo->rollBack();
        rosterResponse(409, ['success' => false, 'message' => 'El listado cambió desde la vista previa. Revísalo de nuevo antes de actualizar.']);
    }
    if (isset($_POST['expected_counts'])) {
        $expected = json_decode((string) $_POST['expected_counts'], true);
        if ($expected !== $counts) {
            $pdo->rollBack();
            rosterResponse(409, ['success' => false, 'message' => 'El listado cambió desde la vista previa. Revísalo de nuevo antes de actualizar.']);
        }
    }
    $today = date('Y-m-d');
    foreach ($incoming as $key => $person) {
        if (!isset($existing[$key])) {
            rosterInsert($pdo, $activityId, $person['nombre'], $person['apellidos'], $today);
        } elseif ((int) $existing[$key]['activo'] === 0) {
            rosterActivate($pdo, (int) $existing[$key]['id'], $today);
        }
    }
    if ($mode === 'sync') {
        foreach ($existing as $key => $person) {
            if ((int) $person['activo'] === 1 && !isset($incoming[$key])) {
                rosterDeactivate($pdo, (int) $person['id'], $today);
            }
        }
    }
    $pdo->commit();
    rosterResponse(200, ['success' => true, 'message' => 'Listado actualizado sin borrar el historial', 'counts' => $counts]);
} catch (DomainException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    rosterResponse(422, ['success' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Error importando listado: ' . $exception->getMessage());
    rosterResponse(500, ['success' => false, 'message' => 'No se pudo importar el listado']);
}
