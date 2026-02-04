<?php
/**
 * Bulk Import API - Subida en lote de instalaciones, actividades y participantes
 * 
 * Recibe:
 * - centro_id: ID del centro seleccionado manualmente
 * - rows: array de filas con { nombre, apellidos, instalacion, actividad, fecha_inicio, dias_semana, tipo_control }
 * 
 * Lógica:
 * - Instalación: si existe en el centro → reutilizar, si no → crear
 * - Actividad: si existe en esa instalación CON los mismos días → error
 *              si no existe o días diferentes → crear nueva
 * - Participante: nombre Y apellidos obligatorios, si falta alguno → error en esa fila
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../config/config.php';
    require_once '../auth_middleware.php';
    
    $admin_info = getAdminInfo();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $centro_id = intval($input['centro_id'] ?? 0);
    $rows = $input['rows'] ?? [];

    if ($centro_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un centro']);
        exit;
    }

    if (!is_array($rows) || count($rows) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No hay filas para importar']);
        exit;
    }

    // Verificar que el centro existe
    $stmt = $pdo->prepare("SELECT id, nombre FROM centros WHERE id = ? AND activo = 1");
    $stmt->execute([$centro_id]);
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$centro) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Centro no encontrado']);
        exit;
    }

    // Autorización: si no es superadmin, validar asignación del centro
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_info['id'], $centro_id]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado para este centro']);
            exit;
        }
    }

    // Cache de instalaciones existentes en el centro
    $stmt = $pdo->prepare("SELECT id, LOWER(TRIM(nombre)) as nombre_lower, nombre FROM instalaciones WHERE centro_id = ?");
    $stmt->execute([$centro_id]);
    $instalacionesExistentes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $instalacionesExistentes[$row['nombre_lower']] = $row;
    }

    // Cache de actividades por instalación: key = instalacion_id, value = array de { id, nombre_lower, grupo_lower, dias_semana }
    $actividadesCache = [];

    // Resultados
    $stats = [
        'instalaciones_creadas' => 0,
        'instalaciones_reutilizadas' => 0,
        'actividades_creadas' => 0,
        'participantes_creados' => 0,
        'errores' => []
    ];

    $pdo->beginTransaction();

    foreach ($rows as $idx => $row) {
        $lineNum = $idx + 2; // +2 porque la fila 1 es el header
        
        $nombre = isset($row['nombre']) ? trim((string)$row['nombre']) : '';
        $apellidos = isset($row['apellidos']) ? trim((string)$row['apellidos']) : '';
        $instalacionNombre = isset($row['instalacion']) ? trim((string)$row['instalacion']) : '';
        $actividadNombre = isset($row['actividad']) ? trim((string)$row['actividad']) : '';
        $grupo = isset($row['grupo']) ? trim((string)$row['grupo']) : null;
        if ($grupo === '') $grupo = null;
        $fechaInicio = isset($row['fecha_inicio']) ? trim((string)$row['fecha_inicio']) : '';
        $fechaFin = isset($row['fecha_fin']) ? trim((string)$row['fecha_fin']) : '';
        $horaInicio = isset($row['hora_inicio']) ? trim((string)$row['hora_inicio']) : '';
        $horaFin = isset($row['hora_fin']) ? trim((string)$row['hora_fin']) : '';
        $diasSemana = isset($row['dias_semana']) ? $row['dias_semana'] : [];
        
        // Tipo de control: 'asistencia' (default) o 'aforo'
        $tipoControlRaw = isset($row['tipo_control']) ? mb_strtolower(trim((string)$row['tipo_control']), 'UTF-8') : '';
        // Aceptar: 'aforo', 'si' (para aforo), cualquier otra cosa = asistencia
        $tipoControl = in_array($tipoControlRaw, ['aforo', 'si', 'sí', 's', '1', 'true']) ? 'aforo' : 'asistencia';
        
        // Normalizar días si viene como string separado por comas
        if (is_string($diasSemana)) {
            $diasSemana = array_map('trim', explode(',', $diasSemana));
            $diasSemana = array_filter($diasSemana);
        }
        
        // Validar participante: solo obligatorio si NO es aforo
        // Si es aforo, no necesita nombre de participante
        if ($tipoControl === 'asistencia' && empty($nombre)) {
            $stats['errores'][] = "Línea $lineNum: Falta el nombre del participante (requerido para actividades de asistencia)";
            continue;
        }
        
        // Validar instalación
        if (empty($instalacionNombre)) {
            $stats['errores'][] = "Línea $lineNum: Falta nombre de instalación";
            continue;
        }
        
        // Validar actividad
        if (empty($actividadNombre)) {
            $stats['errores'][] = "Línea $lineNum: Falta nombre de actividad";
            continue;
        }
        
        // Validar fecha inicio
        if (empty($fechaInicio)) {
            $stats['errores'][] = "Línea $lineNum: Falta fecha de inicio";
            continue;
        }
        
        // Normalizar fecha inicio (puede venir como d/m/y o d/m/yy o yyyy-mm-dd)
        $fechaInicioNorm = normalizarFecha($fechaInicio);
        if (!$fechaInicioNorm) {
            $stats['errores'][] = "Línea $lineNum: Formato de fecha de inicio inválido ($fechaInicio)";
            continue;
        }
        
        $fechaFinNorm = null;
        if (!empty($fechaFin)) {
            $fechaFinNorm = normalizarFecha($fechaFin);
            if ($fechaFinNorm === '0000-00-00') {
                $fechaFinNorm = null;
            }
        }
        if ($fechaFinNorm === '0000-00-00' || $fechaFinNorm === '') {
            $fechaFinNorm = null;
        }
        
        // Normalizar horas (usar valores por defecto si están vacías)
        $horaInicioNorm = !empty($horaInicio) ? $horaInicio : null;
        $horaFinNorm = !empty($horaFin) ? $horaFin : null;
        
        // Validar días
        if (empty($diasSemana)) {
            $stats['errores'][] = "Línea $lineNum: Faltan días de la semana";
            continue;
        }
        
        // Normalizar días de la semana
        $diasSemana = normalizarDias($diasSemana);
        $diasSemanaStr = implode(',', $diasSemana);
        
        // --- Buscar o crear instalación ---
        $instalacionKey = mb_strtolower(trim($instalacionNombre), 'UTF-8');
        $instalacion_id = null;
        
        if (isset($instalacionesExistentes[$instalacionKey])) {
            $instalacion_id = $instalacionesExistentes[$instalacionKey]['id'];
            // Solo contamos como reutilizada la primera vez que la usamos en esta importación
            if (!isset($instalacionesExistentes[$instalacionKey]['_used'])) {
                $stats['instalaciones_reutilizadas']++;
                $instalacionesExistentes[$instalacionKey]['_used'] = true;
            }
        } else {
            // Crear instalación
            $stmtIns = $pdo->prepare("INSERT INTO instalaciones (nombre, centro_id) VALUES (?, ?)");
            $stmtIns->execute([$instalacionNombre, $centro_id]);
            $instalacion_id = $pdo->lastInsertId();
            
            $instalacionesExistentes[$instalacionKey] = [
                'id' => $instalacion_id,
                'nombre_lower' => $instalacionKey,
                'nombre' => $instalacionNombre,
                '_used' => true
            ];
            $stats['instalaciones_creadas']++;
        }
        
        // --- Buscar o crear actividad ---
        // Cargar cache de actividades para esta instalación si no existe
        if (!isset($actividadesCache[$instalacion_id])) {
            $stmtAct = $pdo->prepare("SELECT id, LOWER(TRIM(nombre)) as nombre_lower, LOWER(TRIM(grupo)) as grupo_lower, dias_semana FROM actividades WHERE instalacion_id = ?");
            $stmtAct->execute([$instalacion_id]);
            $actividadesCache[$instalacion_id] = [];
            while ($act = $stmtAct->fetch(PDO::FETCH_ASSOC)) {
                $actividadesCache[$instalacion_id][] = $act;
            }
        }
        
        $actividadKey = mb_strtolower(trim($actividadNombre), 'UTF-8');
        $grupoKey = $grupo ? mb_strtolower(trim($grupo), 'UTF-8') : null;
        $actividad_id = null;
        
        // Buscar si existe actividad con mismo nombre + mismo grupo + mismos días → REUTILIZAR
        foreach ($actividadesCache[$instalacion_id] as $actExist) {
            if ($actExist['nombre_lower'] === $actividadKey && $actExist['grupo_lower'] === $grupoKey) {
                $diasExistentes = array_map('trim', explode(',', $actExist['dias_semana'] ?? ''));
                sort($diasExistentes);
                $diasNuevos = $diasSemana;
                sort($diasNuevos);
                
                if ($diasExistentes == $diasNuevos) {
                    // Mismo nombre + mismos días = REUTILIZAR esta actividad
                    $actividad_id = $actExist['id'];
                    break;
                }
            }
        }
        
        // Si no encontramos actividad existente, crear una nueva
        if (!$actividad_id) {
            // Crear actividad
            $horarioPartes = $diasSemana;
            if ($horaInicioNorm && $horaFinNorm) {
                $horarioPartes[] = $horaInicioNorm . '-' . $horaFinNorm;
            }
            $horario = implode(' y ', $horarioPartes); // Campo legacy

            $stmtActIns = $pdo->prepare("
                INSERT INTO actividades (nombre, grupo, horario, dias_semana, hora_inicio, hora_fin, instalacion_id, fecha_inicio, fecha_fin, tipo_control) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtActIns->execute([
                $actividadNombre,
                $grupo,
                $horario,
                $diasSemanaStr,
                $horaInicioNorm,
                $horaFinNorm,
                $instalacion_id,
                $fechaInicioNorm,
                $fechaFinNorm,
                $tipoControl
            ]);
            $actividad_id = $pdo->lastInsertId();
            
            // Agregar al cache
            $actividadesCache[$instalacion_id][] = [
                'id' => $actividad_id,
                'nombre_lower' => $actividadKey,
                'grupo_lower' => $grupoKey,
                'dias_semana' => $diasSemanaStr
            ];
            $stats['actividades_creadas']++;
        }
        
        // --- Crear participante (solo si es actividad de asistencia y hay nombre) ---
        if ($tipoControl === 'asistencia' && !empty($nombre)) {
            $nombre = preg_replace('/\s+/', ' ', $nombre);
            $apellidos = preg_replace('/\s+/', ' ', $apellidos);
            
            $stmtPart = $pdo->prepare("INSERT INTO inscritos (actividad_id, nombre, apellidos) VALUES (?, ?, ?)");
            $stmtPart->execute([$actividad_id, $nombre, $apellidos]);
            $stats['participantes_creados']++;
        }
    }

    $pdo->commit();

    // Construir mensaje de resultado
    $mensaje = "Importación completada: ";
    $partes = [];
    if ($stats['instalaciones_creadas'] > 0) {
        $partes[] = $stats['instalaciones_creadas'] . " instalación(es) creada(s)";
    }
    if ($stats['instalaciones_reutilizadas'] > 0) {
        $partes[] = $stats['instalaciones_reutilizadas'] . " instalación(es) reutilizada(s)";
    }
    if ($stats['actividades_creadas'] > 0) {
        $partes[] = $stats['actividades_creadas'] . " actividad(es) creada(s)";
    }
    if ($stats['participantes_creados'] > 0) {
        $partes[] = $stats['participantes_creados'] . " participante(s) inscrito(s)";
    }
    $mensaje .= implode(', ', $partes) ?: "sin cambios";
    
    if (count($stats['errores']) > 0) {
        $mensaje .= ". Errores: " . count($stats['errores']);
    }

    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error bulk_import: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

/**
 * Normaliza una fecha a formato YYYY-MM-DD
 * Acepta: d/m/y, d/m/yy, d/m/yyyy, yyyy-mm-dd
 */
function normalizarFecha($fecha) {
    if (empty($fecha) || $fecha === '00/00/0000' || $fecha === '0000-00-00') return null;
    
    // Si ya viene en formato YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha;
    }

    $fecha = str_replace('/', '-', $fecha);
    $timestamp = strtotime($fecha);
    
    if (!$timestamp) {
        // Re-intentar con formato d-m-Y si strtotime falló (a veces confunde d-m-y con m-d-y)
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})$/', $fecha, $matches)) {
            $dia = $matches[1];
            $mes = $matches[2];
            $anio = $matches[3];
            if (strlen($anio) === 2) $anio = '20' . $anio;
            if (checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        }
        return null;
    }
    
    $formateada = date('Y-m-d', $timestamp);
    return ($formateada === '0000-00-00' || $formateada === '1970-01-01') ? null : $formateada;
}

/**
 * Normaliza los días de la semana a formato estándar
 */
function normalizarDias($dias) {
    $mapa = [
        'lunes' => 'Lunes',
        'martes' => 'Martes',
        'miercoles' => 'Miércoles',
        'miércoles' => 'Miércoles',
        'jueves' => 'Jueves',
        'viernes' => 'Viernes',
        'sabado' => 'Sábado',
        'sábado' => 'Sábado',
        'domingo' => 'Domingo',
        // Abreviaciones comunes
        'lu' => 'Lunes',
        'ma' => 'Martes',
        'mi' => 'Miércoles',
        'ju' => 'Jueves',
        'vi' => 'Viernes',
        'sa' => 'Sábado',
        'do' => 'Domingo',
        'l' => 'Lunes',
        'm' => 'Martes',
        'x' => 'Miércoles',
        'j' => 'Jueves',
        'v' => 'Viernes',
        's' => 'Sábado',
        'd' => 'Domingo',
    ];
    
    $resultado = [];
    foreach ($dias as $dia) {
        $diaLower = mb_strtolower(trim($dia), 'UTF-8');
        if (isset($mapa[$diaLower])) {
            $resultado[] = $mapa[$diaLower];
        } else {
            // Si no está en el mapa, usar el valor original capitalizado
            $resultado[] = ucfirst($diaLower);
        }
    }
    
    return array_unique($resultado);
}
?>
