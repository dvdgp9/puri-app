<?php
/**
 * Script de prueba para verificar el parser de horarios
 * antes de ejecutar la migración real
 */

// Incluir la función de parseo del script de migración
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

// Casos de prueba basados en los datos reales
$casosPrueba = [
    "Lunes y Miércoles 10:00-11:30",
    "Martes y Jueves 18:00-19:30", 
    "Lunes, Miércoles y Viernes 09:00-10:00",
    "LMV",
    "Lunes miércoles viernes 9:30-10:30",
    "lunes miércoles viernes 10:30-11:30",
    "Viernes 19:15",
    "lunes miércoles viernes 16:30-20:30",
    "Martes y jueves 19:45-20:45",
    "viernes 17:30-19:00",
    "martes 17:30-19:00",
    "L-M 16h",
    "M - J 11:15-12:05",
    "M - J 10:00-11:00"
];

echo "=== PRUEBA DEL PARSER DE HORARIOS ===\n\n";

foreach ($casosPrueba as $caso) {
    echo "Horario: '$caso'\n";
    $resultado = parsearHorario($caso);
    
    echo "  Días: " . (empty($resultado['dias']) ? 'No detectados' : implode(', ', $resultado['dias'])) . "\n";
    echo "  Hora inicio: " . ($resultado['hora_inicio'] ?: 'No detectada') . "\n";
    echo "  Hora fin: " . ($resultado['hora_fin'] ?: 'No detectada') . "\n";
    echo "  SET value: " . (empty($resultado['dias']) ? 'NULL' : implode(',', $resultado['dias'])) . "\n";
    echo "\n";
}

echo "=== FIN DE PRUEBAS ===\n";
?>
