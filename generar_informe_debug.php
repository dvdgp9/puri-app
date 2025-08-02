<?php
require_once 'config/config.php';

// Simulamos los datos POST para pruebas
$_POST['centro_id'] = 1; // Ajusta según tu base de datos
$_POST['password'] = 'password'; // Ajusta según tu base de datos
$_POST['instalacion_id'] = 1; // Ajusta según tu base de datos
$_POST['actividad_id'] = 1; // Ajusta según tu base de datos
$_POST['fecha_inicio'] = '2025-03-11'; // Ajusta según tus necesidades
$_POST['fecha_fin'] = '2025-03-13'; // Ajusta según tus necesidades

// Validar la contraseña del centro (omitimos esta validación para pruebas)
/*
$stmt = $pdo->prepare("SELECT password FROM centros WHERE id = ?");
$stmt->execute([$_POST['centro_id']]);
$centro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$centro || $centro['password'] !== $_POST['password']) {
    $_SESSION['error'] = 'Contraseña incorrecta';
    header('Location: informes.php');
    exit;
}
*/

// Obtener información de la actividad
$stmt = $pdo->prepare("
    SELECT 
        a.nombre as actividad_nombre,
        a.horario as actividad_horario,
        i.nombre as instalacion_nombre,
        c.nombre as centro_nombre
    FROM actividades a
    JOIN instalaciones i ON a.instalacion_id = i.id
    JOIN centros c ON i.centro_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$_POST['actividad_id']]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

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

// Crear un archivo temporal para el CSV
$temp_file = tempnam(sys_get_temp_dir(), 'csv_');
$output = fopen($temp_file, 'w');

// Escribir el BOM UTF-8 para correcta interpretación de caracteres
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Definir el separador de campo y el delimitador de texto
$delimiter = ',';
$enclosure = '"';

// Función personalizada para escribir en CSV con codificación UTF-8
function write_csv_line($handle, $fields, $delimiter = ',', $enclosure = '"') {
    $line = '';
    foreach ($fields as $field) {
        // Asegurarse de que el campo esté en UTF-8
        $field = mb_convert_encoding($field, 'UTF-8', 'auto');
        // Escapar el delimitador de texto si está presente en el campo
        if (strpos($field, $enclosure) !== false) {
            $field = str_replace($enclosure, $enclosure . $enclosure, $field);
        }
        // Encerrar el campo entre delimitadores si contiene el separador, saltos de línea o el delimitador
        if (strpos($field, $delimiter) !== false || strpos($field, "\n") !== false || strpos($field, $enclosure) !== false) {
            $field = $enclosure . $field . $enclosure;
        }
        // Añadir el campo a la línea
        $line .= $field . $delimiter;
    }
    // Eliminar el último delimitador y añadir un salto de línea
    $line = rtrim($line, $delimiter) . "\n";
    // Escribir la línea en el archivo
    fwrite($handle, $line);
}

// Asegurarse de que los datos estén en UTF-8
$centro_nombre = mb_convert_encoding($info['centro_nombre'], 'UTF-8', 'auto');
$instalacion_nombre = mb_convert_encoding($info['instalacion_nombre'], 'UTF-8', 'auto');
$actividad_nombre = mb_convert_encoding($info['actividad_nombre'], 'UTF-8', 'auto');
$actividad_horario = mb_convert_encoding($info['actividad_horario'], 'UTF-8', 'auto');

// Escribir encabezado con información de la actividad
write_csv_line($output, [$centro_nombre], $delimiter, $enclosure);
write_csv_line($output, [$instalacion_nombre], $delimiter, $enclosure);
write_csv_line($output, [$actividad_nombre . ' | ' . $actividad_horario], $delimiter, $enclosure);
write_csv_line($output, ['Período:', $_POST['fecha_inicio'] . ' a ' . $_POST['fecha_fin']], $delimiter, $enclosure);
write_csv_line($output, [], $delimiter, $enclosure); // Línea en blanco

// Preparar encabezados de columnas con fechas en formato corto
$headers = ['Nombre completo'];
foreach ($fechas as $fecha) {
    // Formato de fecha como en las capturas: 11/03, 12/03, 13/3
    $dia = date('d', strtotime($fecha));
    $mes = date('m', strtotime($fecha));
    
    // Si el día empieza con 0, quitarlo
    if (substr($dia, 0, 1) === '0') {
        $dia = substr($dia, 1);
    }
    
    // Formato para el mes (mantener el 0 en marzo como en la captura)
    $fecha_corta = $dia . '/' . $mes;
    
    $headers[] = $fecha_corta;
}
$headers[] = 'Total';

// Escribir encabezados
write_csv_line($output, $headers, $delimiter, $enclosure);

// Escribir datos de asistencia para cada inscrito
foreach ($inscritos as $inscrito) {
    $row = [$inscrito['apellidos'] . ', ' . $inscrito['nombre']];
    $total_asistencias = 0;
    
    foreach ($fechas as $fecha) {
        $asistio = isset($asistencias_por_usuario[$inscrito['id']][$fecha]) && 
                  $asistencias_por_usuario[$inscrito['id']][$fecha] == 1;
        
        $row[] = $asistio ? 'X' : '';
        if ($asistio) $total_asistencias++;
    }
    
    $row[] = $total_asistencias; // Total de asistencias
    write_csv_line($output, $row, $delimiter, $enclosure);
}

// Añadir línea en blanco
write_csv_line($output, [], $delimiter, $enclosure);

// Añadir observaciones para cada fecha
write_csv_line($output, ['Observaciones:'], $delimiter, $enclosure);
foreach ($fechas as $fecha) {
    $fecha_formateada = date('d/m/Y', strtotime($fecha));
    $observacion = isset($observaciones_por_fecha[$fecha]) ? $observaciones_por_fecha[$fecha] : '';
    
    if (!empty($observacion)) {
        // Usar un array para separar la fecha y la observación en columnas distintas
        $row_obs = [$fecha_formateada . ':', $observacion];
        write_csv_line($output, $row_obs, $delimiter, $enclosure);
    }
}

fclose($output);

// Mostrar el contenido del archivo CSV
echo "<h1>Contenido del archivo CSV generado:</h1>";
echo "<pre>";
echo htmlspecialchars(file_get_contents($temp_file));
echo "</pre>";

// Mostrar información de depuración
echo "<h2>Información de depuración:</h2>";
echo "<h3>Fechas:</h3>";
echo "<pre>";
print_r($fechas);
echo "</pre>";

echo "<h3>Inscritos:</h3>";
echo "<pre>";
print_r($inscritos);
echo "</pre>";

echo "<h3>Asistencias por usuario:</h3>";
echo "<pre>";
print_r($asistencias_por_usuario);
echo "</pre>";

echo "<h3>Observaciones por fecha:</h3>";
echo "<pre>";
print_r($observaciones_por_fecha);
echo "</pre>";

// Eliminar el archivo temporal
unlink($temp_file);
?> 