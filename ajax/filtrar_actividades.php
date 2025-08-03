<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Verifica que se haya autenticado el centro
if(!isset($_SESSION['centro_id'])){
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $instalacion_id = $_GET['instalacion_id'] ?? null;
    $searchTerm = $_GET['search'] ?? '';
    $sortBy = $_GET['sort'] ?? 'nombre-asc';
    
    if (!$instalacion_id) {
        echo json_encode(['error' => 'ID de instalación no proporcionado']);
        exit;
    }
    
    // Consultamos las actividades de la instalación
    $sql = "SELECT * FROM actividades WHERE instalacion_id = ?";
    $params = [$instalacion_id];
    
    if (!empty($searchTerm)) {
        $sql .= " AND nombre LIKE ?";
        $params[] = "%{$searchTerm}%";
    }
    
    // Añadir ordenación
    if ($sortBy === 'nombre-asc') {
        $sql .= " ORDER BY nombre ASC";
    } else {
        $sql .= " ORDER BY nombre DESC";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $todas_actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separar actividades programadas, activas y finalizadas
    $actividades_programadas = [];
    $actividades_activas = [];
    $actividades_finalizadas = [];
    
    foreach ($todas_actividades as $actividad) {
        $hoy = strtotime(date('Y-m-d'));
        $fecha_inicio = strtotime($actividad['fecha_inicio']);
        
        // Si la fecha de inicio es posterior a hoy, está programada
        if ($fecha_inicio > $hoy) {
            $actividades_programadas[] = $actividad;
        }
        // Si no tiene fecha de fin o la fecha de fin es mayor o igual a hoy, está activa
        elseif (empty($actividad['fecha_fin']) || strtotime($actividad['fecha_fin']) >= $hoy) {
            $actividades_activas[] = $actividad;
        } else {
            $actividades_finalizadas[] = $actividad;
        }
    }
    
    // Función para formatear las horas
    function formatearHora($hora) {
        if (empty($hora)) return '';
        $horaObj = new DateTime($hora);
        return $horaObj->format('G:i') . 'h';
    }
    
    // Función para abreviar los días de la semana
    function abreviarDias($dias) {
        $abreviaciones = [
            'Lunes' => 'L',
            'Martes' => 'M',
            'Miércoles' => 'X',
            'Jueves' => 'J',
            'Viernes' => 'V',
            'Sábado' => 'S',
            'Domingo' => 'D'
        ];
        
        $diasArray = explode(',', $dias);
        $diasAbreviados = [];
        
        foreach ($diasArray as $dia) {
            $diasAbreviados[] = isset($abreviaciones[$dia]) ? $abreviaciones[$dia] : $dia;
        }
        
        return implode(' | ', $diasAbreviados);
    }
    
    // Preparar datos para la respuesta
    $respuesta = [
        'activas' => $actividades_activas,
        'programadas' => $actividades_programadas,
        'finalizadas' => $actividades_finalizadas,
        'total' => count($todas_actividades)
    ];
    
    echo json_encode($respuesta);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}
?>
