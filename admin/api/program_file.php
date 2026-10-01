<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/config.php';
require_once '../auth_middleware.php';
require_once '../../includes/program_spreadsheet.php';
try {
    getAdminInfo();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    $file = $_FILES['archivo'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) throw new DomainException('Selecciona un archivo válido.');
    if ($file['size'] > 5 * 1024 * 1024) throw new DomainException('El archivo supera 5 MB.');
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $result = programSpreadsheet($file['tmp_name'], $extension, isset($_POST['hoja']) ? (string) $_POST['hoja'] : null);
    echo json_encode(['success' => true] + $result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (DomainException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Error program file: ' . $e->getMessage());
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No se pudo leer el archivo. Comprueba que es un Excel o CSV válido.']);
}
