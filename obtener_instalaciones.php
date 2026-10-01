<?php
require_once 'config/config.php';
require_once 'includes/ediciones.php';

header('Content-Type: application/json');

if (!isset($_GET['centro_id'])) {
    die(json_encode(['success' => false, 'message' => 'Centro no especificado']));
}

$centro_id = filter_input(INPUT_GET, 'centro_id', FILTER_VALIDATE_INT);
if (!$centro_id) {
    die(json_encode(['success' => false, 'message' => 'ID de centro inválido']));
}

try {
    $editionId = editionResolve($pdo, (int) $centro_id, $_GET['edicion_id'] ?? null);
    $activityScope = editionActivitySql($editionId);
    $stmt = $pdo->prepare("SELECT i.id, i.nombre FROM instalaciones i WHERE i.centro_id = ? AND (EXISTS (SELECT 1 FROM actividades a WHERE a.instalacion_id = i.id AND $activityScope) OR NOT EXISTS (SELECT 1 FROM actividades a WHERE a.instalacion_id = i.id)) ORDER BY nombre");
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