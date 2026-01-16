<?php
require_once 'config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['instalacion_id'])) {
    die(json_encode(['success' => false, 'message' => 'Instalación no especificada']));
}

$instalacion_id = filter_input(INPUT_GET, 'instalacion_id', FILTER_VALIDATE_INT);
if (!$instalacion_id) {
    die(json_encode(['success' => false, 'message' => 'ID de instalación inválido']));
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre, horario, grupo FROM actividades WHERE instalacion_id = ? ORDER BY nombre");
    $stmt->execute([$instalacion_id]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'actividades' => $actividades
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las actividades'
    ]);
} 