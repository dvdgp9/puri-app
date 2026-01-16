<?php
require_once 'config/config.php';

// Requiere sesión de administrador para generar informes
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['error'] = 'Debes iniciar sesión como administrador para generar informes';
    header('Location: admin/login.php');
    exit;
}

// Verificar que se enviaron todos los datos necesarios
if (!isset($_POST['centro_id'], $_POST['instalacion_id'], 
           $_POST['actividad_id'], $_POST['fecha_inicio'], $_POST['fecha_fin'])) {
    $_SESSION['error'] = 'Faltan datos requeridos';
    header('Location: informes.php');
    exit;
}

// Verificar permisos del admin sobre el centro (excepto superadmin)
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
    $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['admin_id'], $_POST['centro_id']]);
    $hasAccess = (bool)$stmt->fetchColumn();
    if (!$hasAccess) {
        $_SESSION['error'] = 'No tienes permisos para generar informes de este centro';
        header('Location: informes.php');
        exit;
    }
}

// Obtener información de la actividad
$stmt = $pdo->prepare("
    SELECT 
        a.nombre as actividad_nombre,
        a.horario as actividad_horario,
        a.grupo as actividad_grupo,
        i.nombre as instalacion_nombre,
        c.nombre as centro_nombre
    FROM actividades a
    JOIN instalaciones i ON a.instalacion_id = i.id
    JOIN centros c ON i.centro_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$_POST['actividad_id']]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// Asegurarse de que los datos estén en UTF-8
$info['centro_nombre'] = mb_convert_encoding($info['centro_nombre'], 'UTF-8', 'auto');
$info['instalacion_nombre'] = mb_convert_encoding($info['instalacion_nombre'], 'UTF-8', 'auto');
$info['actividad_nombre'] = mb_convert_encoding($info['actividad_nombre'], 'UTF-8', 'auto');
$info['actividad_horario'] = mb_convert_encoding($info['actividad_horario'], 'UTF-8', 'auto');
$info['actividad_grupo'] = mb_convert_encoding($info['actividad_grupo'] ?? '', 'UTF-8', 'auto');

// Obtener todas las fechas en el rango seleccionado donde hubo asistencias
$stmt_fechas = $pdo->prepare("
    SELECT DISTINCT fecha 
    FROM asistencias 
    WHERE actividad_id = ? AND fecha BETWEEN ? AND ? 
    ORDER BY fecha
");
$stmt_fechas->execute([
    $_POST['actividad_id'],
    $_POST['fecha_inicio'],
    $_POST['fecha_fin']
]);
$fechas = $stmt_fechas->fetchAll(PDO::FETCH_COLUMN);

// Si no hay fechas con asistencias, usamos las fechas del rango
if (empty($fechas)) {
    $fecha_inicio = new DateTime($_POST['fecha_inicio']);
    $fecha_fin = new DateTime($_POST['fecha_fin']);
    $intervalo = new DateInterval('P1D');
    $periodo = new DatePeriod($fecha_inicio, $intervalo, $fecha_fin->modify('+1 day'));
    
    $fechas = [];
    foreach ($periodo as $fecha) {
        $fechas[] = $fecha->format('Y-m-d');
    }
}

// Obtener lista de inscritos
$stmt_inscritos = $pdo->prepare("
    SELECT id, nombre, apellidos
    FROM inscritos
    WHERE actividad_id = ?
    ORDER BY apellidos, nombre
");
$stmt_inscritos->execute([$_POST['actividad_id']]);
$inscritos = $stmt_inscritos->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las asistencias para el período
$stmt_asistencias = $pdo->prepare("
    SELECT usuario_id, fecha, asistio
    FROM asistencias
    WHERE actividad_id = ? AND fecha BETWEEN ? AND ?
");
$stmt_asistencias->execute([
    $_POST['actividad_id'],
    $_POST['fecha_inicio'],
    $_POST['fecha_fin']
]);

// Organizar las asistencias por usuario y fecha
$asistencias_por_usuario = [];
while ($row = $stmt_asistencias->fetch(PDO::FETCH_ASSOC)) {
    $asistencias_por_usuario[$row['usuario_id']][$row['fecha']] = $row['asistio'];
}

// Obtener observaciones para cada fecha
$stmt_observaciones = $pdo->prepare("
    SELECT fecha, observacion
    FROM observaciones
    WHERE actividad_id = ? AND fecha BETWEEN ? AND ?
    ORDER BY fecha
");
$stmt_observaciones->execute([
    $_POST['actividad_id'],
    $_POST['fecha_inicio'],
    $_POST['fecha_fin']
]);

$observaciones_por_fecha = [];
while ($row = $stmt_observaciones->fetch(PDO::FETCH_ASSOC)) {
    $observaciones_por_fecha[$row['fecha']] = $row['observacion'];
}

// Generar el nombre del archivo
// Formato: Actividad_Grupo_Instalacion_Centro_Fecha
$fecha_hoy = date('Y-m-d');
$actividad_slug = preg_replace('/[^a-z0-9]+/i', '_', $info['actividad_nombre']);
$grupo_slug = !empty($info['actividad_grupo']) ? '_' . preg_replace('/[^a-z0-9]+/i', '_', $info['actividad_grupo']) : '';
$instalacion_slug = preg_replace('/[^a-z0-9]+/i', '_', $info['instalacion_nombre']);
$centro_slug = preg_replace('/[^a-z0-9]+/i', '_', $info['centro_nombre']);

$filename = sprintf(
    '%s%s_%s_%s_%s.xls',
    $actividad_slug,
    $grupo_slug,
    $instalacion_slug,
    $centro_slug,
    $fecha_hoy
);

// Configurar headers para descarga
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Asegurarse de que los datos estén correctamente codificados
$info['centro_nombre'] = htmlspecialchars(mb_convert_encoding($info['centro_nombre'], 'UTF-8', 'auto'));
$info['instalacion_nombre'] = htmlspecialchars(mb_convert_encoding($info['instalacion_nombre'], 'UTF-8', 'auto'));
$info['actividad_nombre'] = htmlspecialchars(mb_convert_encoding($info['actividad_nombre'], 'UTF-8', 'auto'));
$info['actividad_horario'] = htmlspecialchars(mb_convert_encoding($info['actividad_horario'], 'UTF-8', 'auto'));
$info['actividad_grupo'] = htmlspecialchars(mb_convert_encoding($info['actividad_grupo'] ?? '', 'UTF-8', 'auto'));

// Preparar fechas en formato corto para los encabezados
$fecha_headers = [];
foreach ($fechas as $fecha) {
    // Formato de fecha como en las capturas: 11/03, 12/03, 13/3
    $dia = date('d', strtotime($fecha));
    $mes = date('m', strtotime($fecha));
    
    // Si el día empieza con 0, quitarlo
    if (substr($dia, 0, 1) === '0') {
        $dia = substr($dia, 1);
    }
    
    // Formato para el mes
    $fecha_corta = $dia . '/' . $mes;
    
    $fecha_headers[] = $fecha_corta;
}

// Generar HTML para Excel
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Informe de Asistencias</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header-row { background-color: #e6e6e6; }
        .observaciones { margin-top: 20px; }
        .fecha-column { width: 100px; } /* Ancho fijo para columnas de fechas */
        .nombre-column { width: auto; } /* La columna de nombres se ajusta automáticamente */
        .total-column { width: 100px; } /* Ancho fijo para columna de total */
    </style>
</head>
<body>
    <table>
        <tr class="header-row">
            <td colspan="' . (count($fechas) + 2) . '">' . $info['centro_nombre'] . '</td>
        </tr>
        <tr class="header-row">
            <td colspan="' . (count($fechas) + 2) . '">' . $info['instalacion_nombre'] . '</td>
        </tr>
        <tr class="header-row">
            <td colspan="' . (count($fechas) + 2) . '">' . $info['actividad_nombre'] . ($info['actividad_grupo'] ? ' (' . $info['actividad_grupo'] . ')' : '') . ' | ' . $info['actividad_horario'] . '</td>
        </tr>
        <tr class="header-row">
            <td>Período:</td>
            <td colspan="' . (count($fechas) + 1) . '">"' . $_POST['fecha_inicio'] . ' a ' . $_POST['fecha_fin'] . '"</td>
        </tr>
        <tr>
            <td colspan="' . (count($fechas) + 2) . '"></td>
        </tr>
        <tr>
            <th class="nombre-column">Nombre completo</th>';

// Añadir encabezados de fechas
foreach ($fecha_headers as $fecha_header) {
    echo '<th class="fecha-column">' . $fecha_header . '</th>';
}
echo '<th class="total-column">Total</th>
        </tr>';

// Inicializar array para contar asistencias por fecha
$asistencias_por_fecha = [];
foreach ($fechas as $fecha) {
    $asistencias_por_fecha[$fecha] = 0;
}

// Añadir filas de asistencias
foreach ($inscritos as $inscrito) {
    echo '<tr>
            <td>' . htmlspecialchars($inscrito['apellidos'] . ', ' . $inscrito['nombre']) . '</td>';
    
    $total_asistencias = 0;
    foreach ($fechas as $fecha) {
        $asistio = isset($asistencias_por_usuario[$inscrito['id']][$fecha]) && 
                  $asistencias_por_usuario[$inscrito['id']][$fecha] == 1;
        
        echo '<td>' . ($asistio ? 'X' : '') . '</td>';
        if ($asistio) {
            $total_asistencias++;
            $asistencias_por_fecha[$fecha]++;
        }
    }
    
    echo '<td>' . $total_asistencias . '</td>
        </tr>';
}

// Añadir fila de sumatorios por columna
echo '<tr style="font-weight: bold; background-color: #f9f9f9;">
        <td>Total asistentes:</td>';

foreach ($fechas as $fecha) {
    echo '<td>' . $asistencias_por_fecha[$fecha] . '</td>';
}

// Celda vacía para la columna de total
echo '<td></td>
    </tr>';

// Añadir fila de observaciones
echo '<tr>
        <td>Observaciones:</td>';

// Añadir observaciones para cada fecha en la última fila
foreach ($fechas as $fecha) {
    $observacion = isset($observaciones_por_fecha[$fecha]) ? $observaciones_por_fecha[$fecha] : '';
    
    // Primero decodificar entidades HTML si las hay
    $observacion = html_entity_decode($observacion, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Luego escapar cualquier HTML para evitar problemas de seguridad
    // pero sin convertir caracteres especiales a entidades HTML
    $observacion = str_replace(['<', '>', '"', "'", '&'], ['&lt;', '&gt;', '&quot;', '&#039;', '&amp;'], $observacion);
    
    echo '<td>' . $observacion . '</td>';
}

// Celda vacía para la columna de total
echo '<td></td>
    </tr>';

echo '</table>
</body>
</html>';

exit;
?> 