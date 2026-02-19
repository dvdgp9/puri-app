<?php
/**
 * API para generar informe de asistencias
 * Genera archivo CSV compatible con Excel
 */

require_once '../../../config/config.php';
require_once '../../auth_middleware.php';

// Función para escribir línea CSV con UTF-8 BOM compatible con Excel
function writeCsvLine($handle, $fields, $delimiter = ';') {
    $line = '';
    foreach ($fields as $field) {
        $field = mb_convert_encoding($field, 'UTF-8', 'auto');
        // Escapar comillas dobles
        $field = str_replace('"', '""', $field);
        // Encerrar en comillas si contiene delimitador, saltos de línea o comillas
        if (strpos($field, $delimiter) !== false || strpos($field, "\n") !== false || strpos($field, '"') !== false) {
            $field = '"' . $field . '"';
        }
        $line .= $field . $delimiter;
    }
    $line = rtrim($line, $delimiter) . "\r\n";
    fwrite($handle, $line);
}

try {
    // Verificar datos requeridos
    if (!isset($_POST['actividad_id'], $_POST['fecha_inicio'], $_POST['fecha_fin'])) {
        die('Faltan datos requeridos');
    }
    
    $actividadId = (int)$_POST['actividad_id'];
    $fechaInicio = $_POST['fecha_inicio'];
    $fechaFin = $_POST['fecha_fin'];
    
    // Obtener información de la actividad y verificar permisos
    $stmt = $pdo->prepare("
        SELECT 
            a.nombre as actividad_nombre,
            a.horario as actividad_horario,
            a.grupo as actividad_grupo,
            a.tipo_control,
            a.fecha_inicio,
            a.fecha_fin as actividad_fecha_fin,
            i.nombre as instalacion_nombre,
            i.centro_id,
            c.nombre as centro_nombre
        FROM actividades a
        JOIN instalaciones i ON a.instalacion_id = i.id
        JOIN centros c ON i.centro_id = c.id
        WHERE a.id = ?
    ");
    $stmt->execute([$actividadId]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$info) {
        die('Actividad no encontrada');
    }
    
    // Verificar permisos del admin sobre el centro (excepto superadmin)
    if (!isSuperAdmin()) {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_id'], $info['centro_id']]);
        if (!$stmt->fetchColumn()) {
            die('No tienes permisos para generar informes de este centro');
        }
    }
    
    // Asegurar UTF-8
    foreach (['centro_nombre', 'instalacion_nombre', 'actividad_nombre', 'actividad_horario', 'actividad_grupo'] as $campo) {
        if (isset($info[$campo])) {
            $info[$campo] = mb_convert_encoding($info[$campo], 'UTF-8', 'auto');
        }
    }
    
    // Determinar tipo de control
    $es_aforo = ($info['tipo_control'] ?? 'asistencia') === 'aforo';
    
    // ============================================================
    // INFORME DE AFORO (formato diferente)
    // ============================================================
    if ($es_aforo) {
        // Obtener registros de aforo
        $stmt_aforo = $pdo->prepare("
            SELECT fecha, hora, num_personas, created_at
            FROM registros_aforo
            WHERE actividad_id = ? AND fecha BETWEEN ? AND ?
            ORDER BY fecha, hora
        ");
        $stmt_aforo->execute([$actividadId, $fechaInicio, $fechaFin]);
        $registros_aforo = $stmt_aforo->fetchAll(PDO::FETCH_ASSOC);
        
        // Generar nombre del archivo
        $fecha_hoy = date('Y-m-d');
        $actividad_slug = preg_replace('/[^a-z0-9]+/i', '_', $info['actividad_nombre']);
        $grupo_slug = !empty($info['actividad_grupo']) ? '_' . preg_replace('/[^a-z0-9]+/i', '_', $info['actividad_grupo']) : '';
        $instalacion_slug = preg_replace('/[^a-z0-9]+/i', '_', $info['instalacion_nombre']);
        $centro_slug = preg_replace('/[^a-z0-9]+/i', '_', $info['centro_nombre']);
        
        $filename = sprintf(
            'Aforo_%s%s_%s_%s_%s.csv',
            $actividad_slug,
            $grupo_slug,
            $instalacion_slug,
            $centro_slug,
            $fecha_hoy
        );
        
        // Headers para CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Abrir output y escribir BOM para Excel
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM
        
        // Calcular totales por día
        $totales_por_dia = [];
        foreach ($registros_aforo as $r) {
            if (!isset($totales_por_dia[$r['fecha']])) {
                $totales_por_dia[$r['fecha']] = 0;
            }
            $totales_por_dia[$r['fecha']] += $r['num_personas'];
        }
        $total_general = array_sum($totales_por_dia);
        
        // Escribir cabecera
        writeCsvLine($output, [$info['centro_nombre']]);
        writeCsvLine($output, [$info['instalacion_nombre']]);
        writeCsvLine($output, [$info['actividad_nombre'] . ' | ' . ($info['actividad_horario'] ?? '') . ' (AFORO)']);
        writeCsvLine($output, ['Período:', $fechaInicio . ' a ' . $fechaFin]);
        writeCsvLine($output, []); // Línea en blanco
        writeCsvLine($output, ['Fecha', 'Hora', 'Nº Personas']);
        
        $fecha_actual = '';
        foreach ($registros_aforo as $r) {
            $fecha_fmt = date('d/m/Y', strtotime($r['fecha']));
            $hora_fmt = substr($r['hora'], 0, 5);
            
            // Total del día anterior
            if ($r['fecha'] !== $fecha_actual && $fecha_actual !== '') {
                writeCsvLine($output, ['Total día', '', $totales_por_dia[$fecha_actual]]);
            }
            $fecha_actual = $r['fecha'];
            
            writeCsvLine($output, [$fecha_fmt, $hora_fmt, $r['num_personas']]);
        }
        
        // Total del último día
        if ($fecha_actual !== '') {
            writeCsvLine($output, ['Total día', '', $totales_por_dia[$fecha_actual]]);
        }
        
        // Total general
        writeCsvLine($output, []);
        writeCsvLine($output, ['TOTAL PERÍODO', '', $total_general]);
        
        if (empty($registros_aforo)) {
            writeCsvLine($output, ['No hay registros de aforo en este período']);
        }
        
        fclose($output);
        exit;
    }
    
    // ============================================================
    // INFORME DE ASISTENCIA (formato original)
    // ============================================================
    
    // Obtener fechas con asistencias
    $stmt_fechas = $pdo->prepare("
        SELECT DISTINCT fecha 
        FROM asistencias 
        WHERE actividad_id = ? AND fecha BETWEEN ? AND ? 
        ORDER BY fecha
    ");
    $stmt_fechas->execute([$actividadId, $fechaInicio, $fechaFin]);
    $fechas = $stmt_fechas->fetchAll(PDO::FETCH_COLUMN);
    
    // Si no hay fechas, crear rango
    if (empty($fechas)) {
        $fecha_inicio_dt = new DateTime($fechaInicio);
        $fecha_fin_dt = new DateTime($fechaFin);
        $intervalo = new DateInterval('P1D');
        $periodo = new DatePeriod($fecha_inicio_dt, $intervalo, $fecha_fin_dt->modify('+1 day'));
        
        $fechas = [];
        foreach ($periodo as $fecha) {
            $fechas[] = $fecha->format('Y-m-d');
        }
    }
    
    // Obtener inscritos
    $stmt_inscritos = $pdo->prepare("
        SELECT id, nombre, apellidos
        FROM inscritos
        WHERE actividad_id = ?
        ORDER BY apellidos, nombre
    ");
    $stmt_inscritos->execute([$actividadId]);
    $inscritos = $stmt_inscritos->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener todas las asistencias para el período
    $stmt_asistencias = $pdo->prepare("
        SELECT usuario_id, fecha, asistio
        FROM asistencias
        WHERE actividad_id = ? AND fecha BETWEEN ? AND ?
    ");
    $stmt_asistencias->execute([$actividadId, $fechaInicio, $fechaFin]);
    
    // Organizar las asistencias por usuario y fecha
    $asistencias_por_usuario = [];
    while ($row = $stmt_asistencias->fetch(PDO::FETCH_ASSOC)) {
        $asistencias_por_usuario[$row['usuario_id']][$row['fecha']] = $row['asistio'];
    }
    
    // Generar el nombre del archivo
    // Formato: Actividad_Grupo_Instalacion_Centro_Fecha
    $fecha_hoy = date('Y-m-d');
    $actividad_slug = preg_replace('/[^a-z0-9]+/i', '_', $info['actividad_nombre']);
    $grupo_slug = !empty($info['actividad_grupo']) ? '_' . preg_replace('/[^a-z0-9]+/i', '_', $info['actividad_grupo']) : '';
    $instalacion_slug = preg_replace('/[^a-z0-9]+/i', '_', $info['instalacion_nombre']);
    $centro_slug = preg_replace('/[^a-z0-9]+/i', '_', $info['centro_nombre']);

    $filename = sprintf(
        '%s%s_%s_%s_%s.csv',
        $actividad_slug,
        $grupo_slug,
        $instalacion_slug,
        $centro_slug,
        $fecha_hoy
    );

    // Configurar headers para CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Abrir output y escribir BOM para Excel
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM
    
    // Obtener observaciones
    $stmt_obs = $pdo->prepare("
        SELECT fecha, observacion
        FROM observaciones
        WHERE actividad_id = ? AND fecha BETWEEN ? AND ?
        ORDER BY fecha
    ");
    $stmt_obs->execute([$actividadId, $fechaInicio, $fechaFin]);
    
    $observaciones_por_fecha = [];
    while ($row = $stmt_obs->fetch(PDO::FETCH_ASSOC)) {
        $observaciones_por_fecha[$row['fecha']] = $row['observacion'];
    }
    
    // Preparar headers de fechas
    $fecha_headers = [];
    foreach ($fechas as $fecha) {
        $dia = ltrim(date('d', strtotime($fecha)), '0');
        $mes = date('m', strtotime($fecha));
        $fecha_headers[] = $dia . '/' . $mes;
    }
    
    // Escribir cabecera del informe
    writeCsvLine($output, [$info['centro_nombre']]);
    writeCsvLine($output, [$info['instalacion_nombre']]);
    writeCsvLine($output, [$info['actividad_nombre'] . ' | ' . ($info['actividad_horario'] ?? '')]);
    writeCsvLine($output, ['Período:', $fechaInicio . ' a ' . $fechaFin]);
    writeCsvLine($output, []); // Línea en blanco
    
    // Cabecera de columnas
    $headers = array_merge(['Nombre completo'], $fecha_headers, ['Total']);
    writeCsvLine($output, $headers);
    
    // Contadores por fecha
    $asistencias_por_fecha = array_fill_keys($fechas, 0);
    
    // Filas de inscritos
    foreach ($inscritos as $inscrito) {
        $row = [$inscrito['apellidos'] . ', ' . $inscrito['nombre']];
        $total = 0;
        
        foreach ($fechas as $fecha) {
            $asistio = isset($asistencias_por_usuario[$inscrito['id']][$fecha]) && 
                       $asistencias_por_usuario[$inscrito['id']][$fecha] == 1;
            
            $row[] = $asistio ? 'X' : '';
            if ($asistio) {
                $total++;
                $asistencias_por_fecha[$fecha]++;
            }
        }
        
        $row[] = $total;
        writeCsvLine($output, $row);
    }
    
    // Fila de totales
    $totales_row = ['Total asistentes:'];
    foreach ($fechas as $fecha) {
        $totales_row[] = $asistencias_por_fecha[$fecha];
    }
    $totales_row[] = '';
    writeCsvLine($output, $totales_row);
    
    // Fila de observaciones
    $obs_row = ['Observaciones:'];
    foreach ($fechas as $fecha) {
        $obs = isset($observaciones_por_fecha[$fecha]) ? $observaciones_por_fecha[$fecha] : '';
        $obs = html_entity_decode($obs, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $obs_row[] = $obs;
    }
    $obs_row[] = '';
    writeCsvLine($output, $obs_row);
    
    fclose($output);
    exit;

} catch (Exception $e) {
    error_log('Error en API informes/generar: ' . $e->getMessage());
    die('Error interno del servidor');
}
