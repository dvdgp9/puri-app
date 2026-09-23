<?php
require_once 'config/config.php';
require_once 'includes/roster.php';

// Verificar sesión
if(!isset($_SESSION['centro_id'])){
    header("Location: index.php");
    exit;
}

// Verificar que se recibieron los parámetros necesarios
if(!isset($_POST['inscrito_id']) || !isset($_POST['actividad_id'])){
    $response = [
        'success' => false,
        'message' => 'Parámetros incorrectos'
    ];
    echo json_encode($response);
    exit;
}

$inscrito_id = filter_input(INPUT_POST, 'inscrito_id', FILTER_SANITIZE_NUMBER_INT);
$actividad_id = filter_input(INPUT_POST, 'actividad_id', FILTER_SANITIZE_NUMBER_INT);

// Verificar que la actividad pertenece al centro actual
$stmt = $pdo->prepare("
    SELECT a.id 
    FROM actividades a 
    JOIN instalaciones i ON a.instalacion_id = i.id 
    WHERE a.id = ? AND i.centro_id = ?
");
$stmt->execute([$actividad_id, $_SESSION['centro_id']]);
$actividad = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$actividad){
    $response = [
        'success' => false,
        'message' => 'No tienes permiso para eliminar este inscrito'
    ];
    echo json_encode($response);
    exit;
}

try {
    $stmtInscrito = $pdo->prepare('SELECT id FROM inscritos WHERE id = ? AND actividad_id = ? AND activo = 1');
    $stmtInscrito->execute([$inscrito_id, $actividad_id]);
    if ($stmtInscrito->fetchColumn()) {
        $pdo->beginTransaction();
        rosterDeactivate($pdo, (int) $inscrito_id, date('Y-m-d'));
        $pdo->commit();
        $response = [
            'success' => true,
            'message' => 'Inscrito desactivado; historial conservado'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'No se encontró el inscrito en esta actividad o no tienes permiso para eliminarlo'
        ];
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response = [
        'success' => false,
        'message' => 'Error al desactivar el inscrito'
    ];
}

echo json_encode($response);
exit;
