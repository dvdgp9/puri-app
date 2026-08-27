<?php

declare(strict_types=1);

/**
 * M2 tests for the evaluations Admin API.
 *
 * No endpoint is executed and no database connection is opened. Pure validation
 * helpers are tested directly and endpoint files are checked as contract assets.
 */

$root = dirname(__DIR__);
$helperPath = $root . '/includes/evaluaciones_helpers.php';
$apiDir = $root . '/admin/api/evaluaciones';
$docsPath = $root . '/docs/api/evaluaciones.md';
$tests = [];

function apiTest(string $name, callable $test): void
{
    global $tests;
    $tests[] = [$name, $test];
}

function apiExpect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function apiRead(string $path): string
{
    apiExpect(is_file($path), 'Falta el archivo requerido: ' . $path);
    $content = file_get_contents($path);
    apiExpect($content !== false && trim($content) !== '', 'Archivo vacío: ' . $path);
    return (string) $content;
}

function apiLoadHelpers(string $helperPath): void
{
    apiExpect(is_file($helperPath), 'Falta el helper puro: ' . $helperPath);
    require_once $helperPath;
}

apiTest('existen bootstrap, helper común y endpoints Admin', function () use ($apiDir): void {
    foreach ([
        '_bootstrap.php',
        '_common.php',
        'list_by_activity.php',
        'detail.php',
        'create.php',
        'update.php',
        'archive.php',
        'reopen.php',
        'update_result.php',
    ] as $file) {
        apiRead($apiDir . '/' . $file);
    }
});

apiTest('todos los endpoints cargan el bootstrap autenticado', function () use ($apiDir): void {
    foreach (['list_by_activity.php', 'detail.php', 'create.php', 'update.php', 'archive.php', 'reopen.php', 'update_result.php'] as $file) {
        $content = apiRead($apiDir . '/' . $file);
        apiExpect(str_contains($content, "require_once __DIR__ . '/_bootstrap.php';"), $file . ' no carga _bootstrap.php.');
    }

    $bootstrap = apiRead($apiDir . '/_bootstrap.php');
    foreach (['config/config.php', 'auth_middleware.php', 'getAdminInfo()'] as $needle) {
        apiExpect(str_contains($bootstrap, $needle), 'El bootstrap no garantiza: ' . $needle);
    }
});

apiTest('valida períodos inclusivos y fechas reales', function () use ($helperPath): void {
    apiLoadHelpers($helperPath);

    apiExpect(evaluacionesIsDate('2026-10-01'), 'Fecha ISO válida rechazada.');
    apiExpect(!evaluacionesIsDate('01/10/2026'), 'Formato de fecha incorrecto aceptado.');
    apiExpect(evaluacionesDateInsidePeriod('2026-10-01', '2026-10-01', '2026-10-31'), 'El inicio debe ser inclusivo.');
    apiExpect(evaluacionesDateInsidePeriod('2026-10-31', '2026-10-01', '2026-10-31'), 'El final debe ser inclusivo.');
    apiExpect(!evaluacionesDateInsidePeriod('2026-11-01', '2026-10-01', '2026-10-31'), 'Fecha fuera del período aceptada.');
});

apiTest('valida la definición de una evaluación y su campo', function () use ($helperPath): void {
    apiLoadHelpers($helperPath);

    $valid = evaluacionesValidateDefinition([
        'nombre' => 'Burpees en 1 minuto',
        'fecha_inicio' => '2026-10-01',
        'fecha_fin' => '2026-10-31',
        'campo' => [
            'nombre' => 'Burpees',
            'tipo_dato' => 'entero',
            'unidad' => 'repeticiones',
            'configuracion' => null,
        ],
    ]);
    apiExpect($valid['valid'] === true, 'Definición válida rechazada: ' . json_encode($valid));

    $invalid = evaluacionesValidateDefinition([
        'nombre' => '',
        'fecha_inicio' => '2026-11-01',
        'fecha_fin' => '2026-10-01',
        'campo' => ['nombre' => '', 'tipo_dato' => 'puntuacion'],
    ]);
    apiExpect($invalid['valid'] === false, 'Definición inválida aceptada.');
    foreach (['nombre', 'fecha_fin', 'campo.nombre', 'campo.tipo_dato'] as $field) {
        apiExpect(isset($invalid['errors'][$field]), 'Falta error de campo: ' . $field);
    }
});

apiTest('deriva estados sin guardarlos de forma redundante', function () use ($helperPath): void {
    apiLoadHelpers($helperPath);

    $base = ['fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31', 'archivada_at' => null, 'sesion_id' => null, 'sesion_estado' => null];
    apiExpect(evaluacionesDeriveState($base, '2026-09-30') === 'programada', 'Estado programada incorrecto.');
    apiExpect(evaluacionesDeriveState($base, '2026-10-12') === 'pendiente', 'Estado pendiente incorrecto.');
    apiExpect(evaluacionesDeriveState($base, '2026-11-01') === 'fuera_de_plazo', 'Estado fuera_de_plazo incorrecto.');
    apiExpect(evaluacionesDeriveState(array_merge($base, ['sesion_id' => 4, 'sesion_estado' => 'en_curso']), '2026-11-01') === 'en_curso_fuera_de_plazo', 'Estado en curso fuera de plazo incorrecto.');
    apiExpect(evaluacionesDeriveState(array_merge($base, ['sesion_id' => 4, 'sesion_estado' => 'finalizada']), '2026-10-12') === 'finalizada', 'Estado finalizada incorrecto.');
    apiExpect(evaluacionesDeriveState(array_merge($base, ['archivada_at' => '2026-09-01 12:00:00']), '2026-10-12') === 'archivada', 'Estado archivada incorrecto.');
});

apiTest('valida cero, enteros, decimales, duración, texto y sin evaluar', function () use ($helperPath): void {
    apiLoadHelpers($helperPath);

    $zero = evaluacionesValidateResult(['tipo_dato' => 'entero'], [
        'estado' => 'medido', 'valor_numero' => 0, 'valor_texto' => null, 'calificador' => 'exacto',
    ]);
    apiExpect($zero['valid'] === true && $zero['data']['valor_numero'] === 0.0, 'El cero medido no se conserva.');

    $badInteger = evaluacionesValidateResult(['tipo_dato' => 'entero'], [
        'estado' => 'medido', 'valor_numero' => 2.5, 'calificador' => 'exacto',
    ]);
    apiExpect($badInteger['valid'] === false, 'Un entero aceptó decimales.');

    $decimal = evaluacionesValidateResult(['tipo_dato' => 'decimal'], [
        'estado' => 'medido', 'valor_numero' => '12.375', 'calificador' => 'mayor_que',
    ]);
    apiExpect($decimal['valid'] === true, 'Decimal válido rechazado.');

    $badDuration = evaluacionesValidateResult(['tipo_dato' => 'duracion'], [
        'estado' => 'medido', 'valor_numero' => -1, 'calificador' => 'exacto',
    ]);
    apiExpect($badDuration['valid'] === false, 'Duración negativa aceptada.');

    $text = evaluacionesValidateResult(['tipo_dato' => 'texto_corto'], [
        'estado' => 'medido', 'valor_texto' => 'Realizado con apoyo',
    ]);
    apiExpect($text['valid'] === true && $text['data']['valor_numero'] === null, 'Texto corto válido rechazado.');

    $missing = evaluacionesValidateResult(['tipo_dato' => 'decimal'], [
        'estado' => 'sin_evaluar', 'valor_numero' => null, 'valor_texto' => null, 'calificador' => null,
    ]);
    apiExpect($missing['valid'] === true && $missing['data']['estado'] === 'sin_evaluar', 'Sin evaluar válido rechazado.');
});

apiTest('la capa común usa consultas preparadas y autorización por asignación', function () use ($apiDir): void {
    $common = apiRead($apiDir . '/_common.php');
    foreach (['->prepare(', 'admin_asignaciones', 'CENTRO_NO_ASIGNADO', 'evaluacionesAdminRequireActivity', 'evaluacionesAdminRequireEvaluation', 'evaluacionesAdminRequireSession'] as $needle) {
        apiExpect(str_contains($common, $needle), 'Falta garantía común: ' . $needle);
    }
});

apiTest('mutaciones usan transacciones y auditoría del Admin', function () use ($apiDir): void {
    foreach (['create.php', 'update.php', 'archive.php', 'reopen.php', 'update_result.php'] as $file) {
        $content = apiRead($apiDir . '/' . $file);
        apiExpect(str_contains($content, 'beginTransaction()'), $file . ' no inicia transacción.');
        apiExpect(str_contains($content, "admin_info['id']"), $file . ' no registra el Admin actual.');
    }
});

apiTest('el contrato incorpora el endpoint de detalle Admin', function () use ($docsPath): void {
    apiExpect(str_contains(apiRead($docsPath), 'GET /admin/api/evaluaciones/detail.php'), 'Falta documentar detail.php.');
});

$passed = 0;
$failed = 0;

foreach ($tests as [$name, $test]) {
    try {
        $test();
        $passed++;
        fwrite(STDOUT, "[OK] {$name}\n");
    } catch (Throwable $error) {
        $failed++;
        fwrite(STDERR, "[FAIL] {$name}\n       {$error->getMessage()}\n");
    }
}

fwrite(STDOUT, sprintf("\nResultado API Admin: %d correctas, %d fallidas.\n", $passed, $failed));
exit($failed === 0 ? 0 : 1);
