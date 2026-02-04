<?php
/**
 * API para generar informe de asistencias
 * Genera archivo Excel para una actividad
 */

require_once '../../../config/config.php';
require_once '../../auth_middleware.php';

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
            'Aforo_%s%s_%s_%s_%s.xls',
            $actividad_slug,
            $grupo_slug,
            $instalacion_slug,
            $centro_slug,
            $fecha_hoy
        );
        
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Calcular totales por día
        $totales_por_dia = [];
        foreach ($registros_aforo as $r) {
            if (!isset($totales_por_dia[$r['fecha']])) {
                $totales_por_dia[$r['fecha']] = 0;
            }
            $totales_por_dia[$r['fecha']] += $r['num_personas'];
        }
        $total_general = array_sum($totales_por_dia);
        
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Informe de Aforo</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header-row { background-color: #e6e6e6; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .day-header { background-color: #dbeafe; font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr class="header-row">
            <td colspan="3">' . htmlspecialchars($info['centro_nombre']) . '</td>
        </tr>
        <tr class="header-row">
            <td colspan="3">' . htmlspecialchars($info['instalacion_nombre']) . '</td>
        </tr>
        <tr class="header-row">
            <td colspan="3">' . htmlspecialchars($info['actividad_nombre']) . ' | ' . htmlspecialchars($info['actividad_horario'] ?? '') . ' (AFORO)</td>
        </tr>
        <tr class="header-row">
            <td>Período:</td>
            <td colspan="2">' . $fechaInicio . ' a ' . $fechaFin . '</td>
        </tr>
        <tr>
            <td colspan="3"></td>
        </tr>
        <tr>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Nº Personas</th>
        </tr>';
        
        $fecha_actual = '';
        foreach ($registros_aforo as $r) {
            $fecha_fmt = date('d/m/Y', strtotime($r['fecha']));
            $hora_fmt = substr($r['hora'], 0, 5);
            
            // Mostrar cabecera de día con total
            if ($r['fecha'] !== $fecha_actual) {
                if ($fecha_actual !== '') {
                    // Fila de total del día anterior
                    echo '<tr class="total-row"><td colspan="2">Total día</td><td>' . $totales_por_dia[$fecha_actual] . '</td></tr>';
                }
                $fecha_actual = $r['fecha'];
            }
            
            echo '<tr>
                <td>' . $fecha_fmt . '</td>
                <td>' . $hora_fmt . '</td>
                <td>' . $r['num_personas'] . '</td>
            </tr>';
        }
        
        // Total del último día
        if ($fecha_actual !== '') {
            echo '<tr class="total-row"><td colspan="2">Total día</td><td>' . $totales_por_dia[$fecha_actual] . '</td></tr>';
        }
        
        // Total general
        echo '<tr><td colspan="3"></td></tr>';
        echo '<tr class="total-row"><td colspan="2">TOTAL PERÍODO</td><td>' . $total_general . '</td></tr>';
        
        if (empty($registros_aforo)) {
            echo '<tr><td colspan="3" style="text-align:center; color:#666;">No hay registros de aforo en este período</td></tr>';
        }
        
        echo '</table></body></html>';
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
    
    // Generar HTML para Excel
    $colspan = count($fechas) + 2;
    
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
        .header-row { background-color: #e6e6e6; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <table>
        <tr class="header-row">
            <td colspan="' . $colspan . '">' . htmlspecialchars($info['centro_nombre']) . '</td>
        </tr>
        <tr class="header-row">
            <td colspan="' . $colspan . '">' . htmlspecialchars($info['instalacion_nombre']) . '</td>
        </tr>
        <tr class="header-row">
            <td colspan="' . $colspan . '">' . htmlspecialchars($info['actividad_nombre']) . ' | ' . htmlspecialchars($info['actividad_horario'] ?? '') . '</td>
        </tr>
        <tr class="header-row">
            <td>Período:</td>
            <td colspan="' . ($colspan - 1) . '">' . $fechaInicio . ' a ' . $fechaFin . '</td>
        </tr>
        <tr>
            <td colspan="' . $colspan . '"></td>
        </tr>
        <tr>
            <th>Nombre completo</th>';

    foreach ($fecha_headers as $fh) {
        echo '<th>' . $fh . '</th>';
    }
    echo '<th>Total</th></tr>';
    
    // Contadores por fecha
    $asistencias_por_fecha = array_fill_keys($fechas, 0);
    
    // Filas de inscritos
    foreach ($inscritos as $inscrito) {
        $nombreCompleto = htmlspecialchars($inscrito['apellidos'] . ', ' . $inscrito['nombre']);
        echo '<tr><td>' . $nombreCompleto . '</td>';
        
        $total = 0;
        foreach ($fechas as $fecha) {
            $asistio = isset($asistencias_por_usuario[$inscrito['id']][$fecha]) && 
                       $asistencias_por_usuario[$inscrito['id']][$fecha] == 1;
            
            echo '<td>' . ($asistio ? 'X' : '') . '</td>';
            if ($asistio) {
                $total++;
                $asistencias_por_fecha[$fecha]++;
            }
        }
        
        echo '<td>' . $total . '</td></tr>';
    }
    
    // Fila de totales
    echo '<tr class="total-row"><td>Total asistentes:</td>';
    foreach ($fechas as $fecha) {
        echo '<td>' . $asistencias_por_fecha[$fecha] . '</td>';
    }
    echo '<td></td></tr>';
    
    // Fila de observaciones
    echo '<tr><td>Observaciones:</td>';
    foreach ($fechas as $fecha) {
        $obs = isset($observaciones_por_fecha[$fecha]) ? $observaciones_por_fecha[$fecha] : '';
        $obs = html_entity_decode($obs, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $obs = htmlspecialchars($obs);
        echo '<td>' . $obs . '</td>';
    }
    echo '<td></td></tr>';
    
    echo '</table></body></html>';
    exit;

} catch (Exception $e) {
    error_log('Error en API informes/generar: ' . $e->getMessage());
    die('Error interno del servidor');
}
