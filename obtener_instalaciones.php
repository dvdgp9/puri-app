<?php
require_once 'config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['centro_id'])) {
    die(json_encode(['success' => false, 'message' => 'Centro no especificado']));
}

$centro_id = filter_input(INPUT_GET, 'centro_id', FILTER_VALIDATE_INT);
if (!$centro_id) {
    die(json_encode(['success' => false, 'message' => 'ID de centro inválido']));
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre FROM instalaciones WHERE centro_id = ? ORDER BY nombre");
    $stmt->execute([$centro_id]);
    $instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'instalaciones' => $instalaciones
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las instalaciones'
    ]);
} 