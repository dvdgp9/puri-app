<?php

/**
 * Pure helpers shared by the Admin and monitor evaluations APIs.
 *
 * This file must not open sessions, connect to the database or emit output so
 * its validation rules can be tested independently.
 */

function evaluacionesIsDate($value)
{
    if (!is_string($value) || $value === '') {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function evaluacionesDateInsidePeriod($date, $start, $end)
{
    return evaluacionesIsDate($date)
        && evaluacionesIsDate($start)
        && evaluacionesIsDate($end)
        && $date >= $start
        && $date <= $end;
}

function evaluacionesTextLength($value)
{
    return function_exists('mb_strlen')
        ? mb_strlen((string) $value, 'UTF-8')
        : strlen((string) $value);
}

function evaluacionesValidateDefinition(array $input)
{
    $errors = [];
    $name = trim((string) ($input['nombre'] ?? ''));
    $instructions = array_key_exists('instrucciones', $input)
        ? trim((string) ($input['instrucciones'] ?? ''))
        : '';
    $start = trim((string) ($input['fecha_inicio'] ?? ''));
    $end = trim((string) ($input['fecha_fin'] ?? ''));
    $field = isset($input['campo']) && is_array($input['campo']) ? $input['campo'] : [];
    $fieldName = trim((string) ($field['nombre'] ?? ''));
    $type = trim((string) ($field['tipo_dato'] ?? ''));
    $unit = array_key_exists('unidad', $field) ? trim((string) ($field['unidad'] ?? '')) : '';
    $configuration = $field['configuracion'] ?? null;

    if ($name === '' || evaluacionesTextLength($name) > 150) {
        $errors['nombre'] = 'Debe tener entre 1 y 150 caracteres.';
    }

    if (!evaluacionesIsDate($start)) {
        $errors['fecha_inicio'] = 'Debe ser una fecha válida en formato YYYY-MM-DD.';
    }

    if (!evaluacionesIsDate($end)) {
        $errors['fecha_fin'] = 'Debe ser una fecha válida en formato YYYY-MM-DD.';
    } elseif (evaluacionesIsDate($start) && $end < $start) {
        $errors['fecha_fin'] = 'No puede ser anterior a la fecha de inicio.';
    }

    if ($fieldName === '' || evaluacionesTextLength($fieldName) > 150) {
        $errors['campo.nombre'] = 'Debe tener entre 1 y 150 caracteres.';
    }

    $allowedTypes = ['entero', 'decimal', 'duracion', 'texto_corto'];
    if (!in_array($type, $allowedTypes, true)) {
        $errors['campo.tipo_dato'] = 'Tipo de dato no permitido.';
    }

    if (evaluacionesTextLength($unit) > 50) {
        $errors['campo.unidad'] = 'No puede superar 50 caracteres.';
    }

    if ($configuration !== null && !is_array($configuration)) {
        $errors['campo.configuracion'] = 'Debe ser un objeto JSON o null.';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => [
            'nombre' => $name,
            'instrucciones' => $instructions === '' ? null : $instructions,
            'fecha_inicio' => $start,
            'fecha_fin' => $end,
            'campo' => [
                'nombre' => $fieldName,
                'tipo_dato' => $type,
                'unidad' => $unit === '' ? null : $unit,
                'configuracion' => $configuration,
            ],
        ],
    ];
}

function evaluacionesDeriveState(array $evaluation, $today = null)
{
    $today = $today ?: date('Y-m-d');

    if (!empty($evaluation['archivada_at'])) {
        return 'archivada';
    }

    $sessionId = isset($evaluation['sesion_id']) ? (int) $evaluation['sesion_id'] : 0;
    $sessionState = $evaluation['sesion_estado'] ?? null;

    if ($sessionId > 0 && $sessionState === 'finalizada') {
        return 'finalizada';
    }

    if ($sessionId > 0 && $sessionState === 'en_curso') {
        return $today > $evaluation['fecha_fin'] ? 'en_curso_fuera_de_plazo' : 'en_curso';
    }

    if ($today < $evaluation['fecha_inicio']) {
        return 'programada';
    }

    if ($today > $evaluation['fecha_fin']) {
        return 'fuera_de_plazo';
    }

    return 'pendiente';
}

function evaluacionesNormalizeDecimal($value)
{
    if (is_int($value) || is_float($value)) {
        $raw = (string) $value;
    } elseif (is_string($value)) {
        $raw = trim($value);
    } else {
        return ['valid' => false, 'value' => null];
    }

    if (!preg_match('/^-?\d+(?:\.\d{1,3})?$/', $raw) || !is_numeric($raw)) {
        return ['valid' => false, 'value' => null];
    }

    $number = (float) $raw;
    if (!is_finite($number) || abs($number) > 999999999.999) {
        return ['valid' => false, 'value' => null];
    }

    return ['valid' => true, 'value' => $number];
}

function evaluacionesValidateResult(array $field, array $input)
{
    $state = trim((string) ($input['estado'] ?? ''));
    $type = trim((string) ($field['tipo_dato'] ?? ''));

    if ($state === 'sin_evaluar') {
        return [
            'valid' => true,
            'errors' => [],
            'data' => [
                'estado' => 'sin_evaluar',
                'valor_numero' => null,
                'valor_texto' => null,
                'calificador' => null,
            ],
        ];
    }

    if ($state !== 'medido') {
        return ['valid' => false, 'errors' => ['estado' => 'Estado no permitido.'], 'data' => null];
    }

    if ($type === 'texto_corto') {
        $text = trim((string) ($input['valor_texto'] ?? ''));
        if ($text === '' || evaluacionesTextLength($text) > 255) {
            return ['valid' => false, 'errors' => ['valor_texto' => 'Debe tener entre 1 y 255 caracteres.'], 'data' => null];
        }

        return [
            'valid' => true,
            'errors' => [],
            'data' => [
                'estado' => 'medido',
                'valor_numero' => null,
                'valor_texto' => $text,
                'calificador' => null,
            ],
        ];
    }

    if (!in_array($type, ['entero', 'decimal', 'duracion'], true)) {
        return ['valid' => false, 'errors' => ['tipo_dato' => 'Tipo de dato no permitido.'], 'data' => null];
    }

    $normalized = evaluacionesNormalizeDecimal($input['valor_numero'] ?? null);
    if (!$normalized['valid']) {
        return ['valid' => false, 'errors' => ['valor_numero' => 'Debe ser un número con un máximo de tres decimales.'], 'data' => null];
    }

    $number = $normalized['value'];
    if ($type === 'entero' && floor($number) !== $number) {
        return ['valid' => false, 'errors' => ['valor_numero' => 'Debe ser un número entero.'], 'data' => null];
    }

    if ($type === 'duracion' && $number < 0) {
        return ['valid' => false, 'errors' => ['valor_numero' => 'La duración no puede ser negativa.'], 'data' => null];
    }

    $qualifier = trim((string) ($input['calificador'] ?? 'exacto'));
    if (!in_array($qualifier, ['exacto', 'mayor_que', 'menor_que'], true)) {
        return ['valid' => false, 'errors' => ['calificador' => 'Calificador no permitido.'], 'data' => null];
    }

    return [
        'valid' => true,
        'errors' => [],
        'data' => [
            'estado' => 'medido',
            'valor_numero' => $number,
            'valor_texto' => null,
            'calificador' => $qualifier,
        ],
    ];
}

