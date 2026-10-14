<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/config.php';
require_once '../auth_middleware.php';
require_once '../../includes/sabadell_permissions.php';
require_once '../../includes/sabadell_pdf.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new DomainException('Método no permitido.', 405);
    $admin = getAdminInfo();
    $centerId = filter_var($_POST['centro_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$centerId) throw new DomainException('Selecciona un centro.');
    sabadellRequireAccess($pdo, (int) $admin['id'], $centerId);
    if (!hash_equals($_SESSION['sabadell_csrf'] ?? '', (string) ($_POST['csrf'] ?? '')) || empty($_SESSION['sabadell_csrf'])) throw new DomainException('Sesión de subida inválida. Recarga la página.', 403);
    unset($_SESSION['sabadell_draft'], $_SESSION['sabadell_preview']);
    $file = $_FILES['archivo'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') throw new DomainException('Selecciona un PDF válido.');
    $data = sabadellPdf($file['tmp_name']);
    $_SESSION['sabadell_draft'] = $data + ['id' => bin2hex(random_bytes(20)), 'admin_id' => (int) $admin['id'], 'center_id' => $centerId, 'expires' => time() + 3600];
    echo json_encode(['success' => true, 'draft_id' => $_SESSION['sabadell_draft']['id'], 'source_count' => $data['source_count'], 'source_date' => $data['source_date']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (DomainException $e) {
    http_response_code(in_array($e->getCode(), [403, 404, 405, 409], true) ? $e->getCode() : 422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Sabadell PDF: ' . get_class($e));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo leer el PDF. Comprueba que el importador está instalado.']);
}
