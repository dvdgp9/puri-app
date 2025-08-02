<?php
/**
 * Script para migrar datos de horarios existentes del campo 'horario' 
 * a los nuevos campos estructurados 'dias_semana', 'hora_inicio', 'hora_fin'
 */

require_once 'config/config.php';

echo "Iniciando migración de datos de horarios...\n";

// Mapeo de días completos
$diasCompletos = [
    'lunes' => 'Lunes',
    'martes' => 'Martes', 
    'miércoles' => 'Miércoles',
    'miercoles' => 'Miércoles',
    'jueves' => 'Jueves',
    'viernes' => 'Viernes',
    'sábado' => 'Sábado',
    'sabado' => 'Sábado',
    'domingo' => 'Domingo'
];

// Mapeo de abreviaciones - contextuales
$abreviacionesMap = [
    'l' => 'Lunes',
    'x' => 'Miércoles',
    'j' => 'Jueves', 
    'v' => 'Viernes',
    's' => 'Sábado',
    'd' => 'Domingo'
];

// Casos especiales para 'M'
function resolverM($contexto) {
    // En rangos como "M - J", M = Martes
    if (strpos($contexto, '-') !== false) {
        return 'Martes';
    }
    // En secuencias como "LMV", M = Miércoles  
    if (preg_match('/[lv]/i', $contexto)) {
        return 'Miércoles';
    }
    // Por defecto, asumir Martes
    return 'Martes';
}

/**
 * Parsea el campo horario y extrae días y horas
 */
function parsearHorario($horario) {
    global $diasCompletos, $abreviacionesMap;
    
    $horario = trim($horario);
    $resultado = [
        'dias' => [],
        'hora_inicio' => null,
        'hora_fin' => null
    ];
    
    // Normalizar texto
    $horarioNorm = strtolower($horario);
    
    // Extraer horarios (formato HH:MM-HH:MM o HH:MM)
    if (preg_match('/(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})/', $horario, $matches)) {
        $resultado['hora_inicio'] = sprintf('%02d:%02d:00', $matches[1], $matches[2]);
        $resultado['hora_fin'] = sprintf('%02d:%02d:00', $matches[3], $matches[4]);
    } elseif (preg_match('/(\d{1,2}):(\d{2})/', $horario, $matches)) {
        $resultado['hora_inicio'] = sprintf('%02d:%02d:00', $matches[1], $matches[2]);
    } elseif (preg_match('/(\d{1,2})h/', $horario, $matches)) {
        $resultado['hora_inicio'] = sprintf('%02d:00:00', $matches[1]);
    }
    
    // Extraer días - Casos especiales primero
    
    // Caso: "L-M" o "M - J" (rangos con guiones)
    if (preg_match('/^([lmxjvsd])\s*-\s*([lmxjvsd])/i', $horarioNorm, $matches)) {
        $diaInicio = strtolower($matches[1]);
        $diaFin = strtolower($matches[2]);
        
        // Resolver 'M' contextualmente
        if ($diaInicio === 'm') {
            $resultado['dias'][] = resolverM($horarioNorm);
        } elseif (isset($abreviacionesMap[$diaInicio])) {
            $resultado['dias'][] = $abreviacionesMap[$diaInicio];
        }
        
        if ($diaFin === 'm') {
            $diaFinResuelto = resolverM($horarioNorm);
            if (!in_array($diaFinResuelto, $resultado['dias'])) {
                $resultado['dias'][] = $diaFinResuelto;
            }
        } elseif (isset($abreviacionesMap[$diaFin])) {
            if (!in_array($abreviacionesMap[$diaFin], $resultado['dias'])) {
                $resultado['dias'][] = $abreviacionesMap[$diaFin];
            }
        }
    } elseif (preg_match('/^[lmxjvsd]+$/i', $horarioNorm)) {
        // Abreviaciones consecutivas como "LMV"
        $chars = str_split(strtolower($horarioNorm));
        foreach ($chars as $char) {
            if ($char === 'm') {
                $resultado['dias'][] = resolverM($horarioNorm);
            } elseif (isset($abreviacionesMap[$char])) {
                $resultado['dias'][] = $abreviacionesMap[$char];
            }
        }
    } else {
        // Buscar días completos en el texto
        foreach ($diasCompletos as $diaLower => $diaCompleto) {
            if (strpos($horarioNorm, $diaLower) !== false) {
                if (!in_array($diaCompleto, $resultado['dias'])) {
                    $resultado['dias'][] = $diaCompleto;
                }
            }
        }
    }
    
    // Eliminar duplicados y ordenar
    $resultado['dias'] = array_unique($resultado['dias']);
    
    return $resultado;
}

try {
    // Obtener todas las actividades con horarios
    $stmt = $pdo->query("SELECT id, nombre, horario FROM actividades WHERE horario IS NOT NULL AND horario != ''");
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontradas " . count($actividades) . " actividades para migrar.\n\n";
    
    $migradas = 0;
    $errores = 0;
    
    foreach ($actividades as $actividad) {
        echo "Procesando ID {$actividad['id']}: {$actividad['nombre']}\n";
        echo "  Horario original: '{$actividad['horario']}'\n";
        
        $parsed = parsearHorario($actividad['horario']);
        
        echo "  Días extraídos: " . implode(', ', $parsed['dias']) . "\n";
        echo "  Hora inicio: " . ($parsed['hora_inicio'] ?: 'No detectada') . "\n";
        echo "  Hora fin: " . ($parsed['hora_fin'] ?: 'No detectada') . "\n";
        
        // Preparar valores para la base de datos
        $diasSemana = !empty($parsed['dias']) ? implode(',', $parsed['dias']) : null;
        $horaInicio = $parsed['hora_inicio'];
        $horaFin = $parsed['hora_fin'];
        
        // Actualizar la base de datos
        try {
            $updateStmt = $pdo->prepare("
                UPDATE actividades 
                SET dias_semana = ?, hora_inicio = ?, hora_fin = ? 
                WHERE id = ?
            ");
            
            $updateStmt->execute([$diasSemana, $horaInicio, $horaFin, $actividad['id']]);
            
            echo "  ✅ Migrada exitosamente\n\n";
            $migradas++;
            
        } catch (PDOException $e) {
            echo "  ❌ Error al actualizar: " . $e->getMessage() . "\n\n";
            $errores++;
        }
    }
    
    echo "=== RESUMEN DE MIGRACIÓN ===\n";
    echo "Total actividades procesadas: " . count($actividades) . "\n";
    echo "Migradas exitosamente: $migradas\n";
    echo "Errores: $errores\n";
    
    if ($errores == 0) {
        echo "\n🎉 Migración completada sin errores!\n";
    } else {
        echo "\n⚠️  Migración completada con algunos errores. Revisar logs.\n";
    }
    
} catch (Exception $e) {
    echo "Error durante la migración: " . $e->getMessage() . "\n";
    error_log("Error de migración: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
?>
