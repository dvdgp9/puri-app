<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/config.php';
require_once '../auth_middleware.php';
require_once '../../includes/sabadell_permissions.php';
require_once '../../includes/sabadell_import.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new DomainException('Método no permitido.', 405);
    $admin = getAdminInfo();
    $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new DomainException('Solicitud inválida.');
    $centerId = filter_var($input['centro_id'] ?? null, FILTER_VALIDATE_INT);
    $action = $input['operation'] ?? '';
    if (!$centerId || !in_array($action, ['context', 'review', 'save'], true)) throw new DomainException('Centro u operación inválidos.');
    if (!hash_equals($_SESSION['sabadell_csrf'] ?? '', (string) ($input['csrf'] ?? '')) || empty($_SESSION['sabadell_csrf'])) throw new DomainException('Sesión de subida inválida. Recarga la página.', 403);
    if ($action === 'save') {
        $pdo->beginTransaction();
        $pdo->prepare('SELECT id FROM admins WHERE id = ? FOR UPDATE')->execute([$admin['id']]);
        $pdo->prepare('SELECT admin_id FROM admin_asignaciones WHERE admin_id = ? FOR UPDATE')->execute([$admin['id']]);
        $pdo->prepare('SELECT id FROM centros WHERE id = ? FOR UPDATE')->execute([$centerId]);
        $pdo->prepare('SELECT a.id FROM actividades a JOIN instalaciones i ON i.id = a.instalacion_id WHERE i.centro_id = ? FOR UPDATE')->execute([$centerId]);
        $pdo->prepare('SELECT ins.id FROM inscritos ins JOIN actividades a ON a.id = ins.actividad_id JOIN instalaciones i ON i.id = a.instalacion_id WHERE i.centro_id = ? FOR UPDATE')->execute([$centerId]);
    }
    sabadellRequireAccess($pdo, (int) $admin['id'], $centerId);
    $snapshot = programSnapshot($pdo, $centerId);
    if ($action === 'context' && empty($input['draft_id'])) {
        echo json_encode(['success' => true, 'ediciones' => $snapshot['ediciones'], 'edicion_id' => editionDefault($snapshot['ediciones'])], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $draft = sabadellDraft($_SESSION, (string) ($input['draft_id'] ?? ''), (int) $admin['id'], $centerId);
    if ($action === 'context') {
        unset($_SESSION['sabadell_preview']);
        echo json_encode(['success' => true] + sabadellContext($snapshot, $draft, $input), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    if ($action === 'review') unset($_SESSION['sabadell_preview']);
    // Operation and expected fingerprint are transport fields, not reviewed data.
    unset($input['operation'], $input['csrf']);
    $expected = $input['expected_fingerprint'] ?? '';
    unset($input['expected_fingerprint']);
    $plan = sabadellPlan($snapshot, $draft, $input);
    if ($action === 'review') {
        $_SESSION['sabadell_preview'] = $plan['fingerprint'];
        echo json_encode(programPreviewResponse($plan), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    if (!is_string($expected) || !$expected || !hash_equals($plan['fingerprint'], $expected) || !hash_equals($_SESSION['sabadell_preview'] ?? '', $expected)) {
        throw new DomainException('Los datos han cambiado. Revisa de nuevo antes de guardar.', 409);
    }
    $editionId = programApply($pdo, $centerId, $plan);
    $pdo->commit();
    unset($_SESSION['sabadell_draft'], $_SESSION['sabadell_preview']);
    echo json_encode(['success' => true, 'edicion_id' => $editionId, 'stats' => $plan['stats'], 'message' => 'Listado de Sabadell guardado. El historial se conserva.'], JSON_UNESCAPED_UNICODE);
} catch (DomainException | JsonException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (($action ?? '') === 'save') unset($_SESSION['sabadell_preview']);
    http_response_code(in_array($e->getCode(), [403, 404, 405, 409], true) ? $e->getCode() : 422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    unset($_SESSION['sabadell_preview']);
    error_log('Sabadell import: ' . get_class($e));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar el listado. Comprueba las migraciones del importador.']);
}
