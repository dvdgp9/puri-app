<?php
require_once 'config/config.php';

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
    // Primero eliminar registros de asistencia relacionados
    $stmtAsistencias = $pdo->prepare("DELETE FROM asistencias WHERE usuario_id = ? AND actividad_id = ?");
    $stmtAsistencias->execute([$inscrito_id, $actividad_id]);
    
    // Luego eliminar el inscrito SOLO de esta actividad específica
    $stmtInscrito = $pdo->prepare("DELETE FROM inscritos WHERE id = ? AND actividad_id = ?");
    $stmtInscrito->execute([$inscrito_id, $actividad_id]);
    
    if($stmtInscrito->rowCount() > 0){
        $response = [
            'success' => true,
            'message' => 'Inscrito eliminado correctamente de esta actividad'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'No se encontró el inscrito en esta actividad o no tienes permiso para eliminarlo'
        ];
    }
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Error al eliminar el inscrito: ' . $e->getMessage()
    ];
}

echo json_encode($response);
exit; 