<?php
/**
 * API para generar informe de asistencias
 * Genera archivo Excel (.xlsx) con PhpSpreadsheet
 */

require_once '../../../config/config.php';
require_once '../../auth_middleware.php';
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Estilos reutilizables
function getHeaderStyle() {
    return [
        'font' => ['bold' => true, 'size' => 12],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E2E8F0']
        ],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
    ];
}

function getTableHeaderStyle() {
    return [
        'font' => ['bold' => true, 'size' => 10],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'CBD5E1']
        ],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];
}

function getTotalsStyle() {
    return [
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FEF3C7']
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];
}

function getCellBorderStyle() {
    return [
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];
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
            'Aforo_%s%s_%s_%s_%s.xlsx',
            $actividad_slug,
            $grupo_slug,
            $instalacion_slug,
            $centro_slug,
            $fecha_hoy
        );
        
        // Crear spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Informe Aforo');
        
        // Calcular totales por día
        $totales_por_dia = [];
        foreach ($registros_aforo as $r) {
            if (!isset($totales_por_dia[$r['fecha']])) {
                $totales_por_dia[$r['fecha']] = 0;
            }
            $totales_por_dia[$r['fecha']] += $r['num_personas'];
        }
        $total_general = array_sum($totales_por_dia);
        
        // Cabecera del informe
        $sheet->setCellValue('A1', $info['centro_nombre']);
        $sheet->setCellValue('A2', $info['instalacion_nombre']);
        $sheet->setCellValue('A3', $info['actividad_nombre'] . ' | ' . ($info['actividad_horario'] ?? '') . ' (AFORO)');
        $sheet->setCellValue('A4', 'Período: ' . $fechaInicio . ' a ' . $fechaFin);
        
        // Estilo cabecera
        $sheet->getStyle('A1:C4')->applyFromArray(getHeaderStyle());
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('A3:C3');
        $sheet->mergeCells('A4:C4');
        
        // Encabezados de tabla
        $sheet->setCellValue('A6', 'Fecha');
        $sheet->setCellValue('B6', 'Hora');
        $sheet->setCellValue('C6', 'Nº Personas');
        $sheet->getStyle('A6:C6')->applyFromArray(getTableHeaderStyle());
        
        // Datos
        $row = 7;
        $fecha_actual = '';
        foreach ($registros_aforo as $r) {
            // Total del día anterior
            if ($r['fecha'] !== $fecha_actual && $fecha_actual !== '') {
                $sheet->setCellValue('A' . $row, 'Total día');
                $sheet->setCellValue('C' . $row, $totales_por_dia[$fecha_actual]);
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray(getTotalsStyle());
                $row++;
            }
            $fecha_actual = $r['fecha'];
            
            $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($r['fecha'])));
            $sheet->setCellValue('B' . $row, substr($r['hora'], 0, 5));
            $sheet->setCellValue('C' . $row, $r['num_personas']);
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray(getCellBorderStyle());
            $row++;
        }
        
        // Total del último día
        if ($fecha_actual !== '') {
            $sheet->setCellValue('A' . $row, 'Total día');
            $sheet->setCellValue('C' . $row, $totales_por_dia[$fecha_actual]);
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray(getTotalsStyle());
            $row++;
        }
        
        // Total general
        $row++;
        $sheet->setCellValue('A' . $row, 'TOTAL PERÍODO');
        $sheet->setCellValue('C' . $row, $total_general);
        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray(getTotalsStyle());
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        
        if (empty($registros_aforo)) {
            $sheet->setCellValue('A7', 'No hay registros de aforo en este período');
            $sheet->mergeCells('A7:C7');
        }
        
        // Autosize columnas
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Headers y descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
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
        '%s%s_%s_%s_%s.xlsx',
        $actividad_slug,
        $grupo_slug,
        $instalacion_slug,
        $centro_slug,
        $fecha_hoy
    );

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
    
    // Crear spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Asistencias');
    
    // Preparar headers de fechas
    $fecha_headers = [];
    foreach ($fechas as $fecha) {
        $dia = ltrim(date('d', strtotime($fecha)), '0');
        $mes = date('m', strtotime($fecha));
        $fecha_headers[] = $dia . '/' . $mes;
    }
    
    // Cabecera del informe (filas 1-4)
    $sheet->setCellValue('A1', $info['centro_nombre']);
    $sheet->setCellValue('A2', $info['instalacion_nombre']);
    $sheet->setCellValue('A3', $info['actividad_nombre'] . ' | ' . ($info['actividad_horario'] ?? ''));
    $sheet->setCellValue('A4', 'Período: ' . $fechaInicio . ' a ' . $fechaFin);
    
    // Calcular última columna
    $numCols = count($fechas) + 2; // Nombre + fechas + Total
    $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numCols);
    
    // Estilo cabecera
    $sheet->getStyle('A1:' . $lastCol . '4')->applyFromArray(getHeaderStyle());
    $sheet->mergeCells('A1:' . $lastCol . '1');
    $sheet->mergeCells('A2:' . $lastCol . '2');
    $sheet->mergeCells('A3:' . $lastCol . '3');
    $sheet->mergeCells('A4:' . $lastCol . '4');
    
    // Encabezados de columnas (fila 6)
    $sheet->setCellValue('A6', 'Nombre completo');
    $col = 2;
    foreach ($fecha_headers as $fh) {
        $sheet->setCellValueByColumnAndRow($col, 6, $fh);
        $col++;
    }
    $sheet->setCellValueByColumnAndRow($col, 6, 'Total');
    
    $sheet->getStyle('A6:' . $lastCol . '6')->applyFromArray(getTableHeaderStyle());
    
    // Contadores por fecha
    $asistencias_por_fecha = array_fill_keys($fechas, 0);
    
    // Filas de inscritos (desde fila 7)
    $row = 7;
    foreach ($inscritos as $inscrito) {
        $sheet->setCellValue('A' . $row, $inscrito['apellidos'] . ', ' . $inscrito['nombre']);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $col = 2;
        $total = 0;
        foreach ($fechas as $fecha) {
            $asistio = isset($asistencias_por_usuario[$inscrito['id']][$fecha]) && 
                       $asistencias_por_usuario[$inscrito['id']][$fecha] == 1;
            
            $sheet->setCellValueByColumnAndRow($col, $row, $asistio ? 'X' : '');
            if ($asistio) {
                $total++;
                $asistencias_por_fecha[$fecha]++;
            }
            $col++;
        }
        $sheet->setCellValueByColumnAndRow($col, $row, $total);
        
        // Bordes para la fila
        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray(getCellBorderStyle());
        $row++;
    }
    
    // Fila de totales
    $sheet->setCellValue('A' . $row, 'Total asistentes:');
    $col = 2;
    foreach ($fechas as $fecha) {
        $sheet->setCellValueByColumnAndRow($col, $row, $asistencias_por_fecha[$fecha]);
        $col++;
    }
    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray(getTotalsStyle());
    $row++;
    
    // Fila de observaciones
    $sheet->setCellValue('A' . $row, 'Observaciones:');
    $col = 2;
    foreach ($fechas as $fecha) {
        $obs = isset($observaciones_por_fecha[$fecha]) ? $observaciones_por_fecha[$fecha] : '';
        $obs = html_entity_decode($obs, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $sheet->setCellValueByColumnAndRow($col, $row, $obs);
        $col++;
    }
    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]
    ]);
    
    // Autosize primera columna (nombres)
    $sheet->getColumnDimension('A')->setAutoSize(true);
    
    // Ancho fijo para columnas de fechas
    for ($i = 2; $i <= $numCols; $i++) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
        $sheet->getColumnDimension($colLetter)->setWidth(8);
    }
    
    // Headers y descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    error_log('Error en API informes/generar: ' . $e->getMessage());
    die('Error interno del servidor');
}
