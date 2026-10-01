<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/config.php';
require_once '../auth_middleware.php';
require_once '../../includes/program_import.php';

try {
    $admin = getAdminInfo();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $centerId = filter_var($input['centro_id'] ?? null, FILTER_VALIDATE_INT);
    $mode = $input['modo_importacion'] ?? 'participantes';
    $scope = $input['scope'] ?? 'append';
    $preview = ($input['preview'] ?? false) === true;
    if (!$centerId || !in_array($mode, ['participantes', 'aforo'], true) || !in_array($scope, ['complete', 'append'], true) || !is_array($input['rows'] ?? null)) throw new DomainException('Centro, modo o listado inválido.');
    $stmt = $pdo->prepare('SELECT id FROM centros WHERE id = ? AND activo = 1');
    $stmt->execute([$centerId]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Centro no encontrado']);
        exit;
    }
    if ($admin['role'] !== 'superadmin') {
        $stmt = $pdo->prepare('SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?');
        $stmt->execute([$admin['id'], $centerId]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado para este centro']);
            exit;
        }
    }
    $classes = programNormalize($input['rows'], $mode);
    if (!$preview) {
        $pdo->beginTransaction();
        $pdo->prepare('SELECT id FROM centros WHERE id = ? FOR UPDATE')->execute([$centerId]);
        $pdo->prepare('SELECT a.id FROM actividades a JOIN instalaciones i ON i.id = a.instalacion_id WHERE i.centro_id = ? FOR UPDATE')->execute([$centerId]);
        $pdo->prepare('SELECT ins.id FROM inscritos ins JOIN actividades a ON a.id = ins.actividad_id JOIN instalaciones i ON i.id = a.instalacion_id WHERE i.centro_id = ? FOR UPDATE')->execute([$centerId]);
    }
    $destination = $input['destino_edicion'] ?? 'auto';
    if (!is_string($destination)) throw new DomainException('Destino de la carga inválido.');
    $plan = programPlan(programSnapshot($pdo, $centerId), $classes, $mode, $scope, $input['edicion_id'] ?? null, $destination);
    if ($preview) {
        echo json_encode(programPreviewResponse($plan), JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($plan['necesita_edicion'] || empty($input['expected_fingerprint']) || !hash_equals($plan['fingerprint'], (string) $input['expected_fingerprint'])) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Revisa de nuevo el listado antes de guardar; los datos pueden haber cambiado.']);
        exit;
    }
    $editionId = programApply($pdo, $centerId, $plan);
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $plan['nueva_edicion'] ? 'Nueva edición creada' : 'Edición actualizada sin borrar el historial', 'edicion_id' => $editionId, 'stats' => $plan['stats']], JSON_UNESCAPED_UNICODE);
} catch (DomainException | JsonException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error program import: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo importar el programa. Comprueba que la migración de ediciones está aplicada.']);
}
