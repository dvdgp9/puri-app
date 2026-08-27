<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$apiDir = $root . '/api/evaluaciones';
$tests = [];

function monitorApiTest(string $name, callable $test): void
{
    global $tests;
    $tests[] = [$name, $test];
}

function monitorApiExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function monitorApiRead(string $path): string
{
    monitorApiExpect(is_file($path), 'Falta archivo: ' . $path);
    $content = file_get_contents($path);
    monitorApiExpect($content !== false, 'No se pudo leer: ' . $path);
    return (string) $content;
}

monitorApiTest('existen bootstrap, helper y endpoints operativos', function () use ($apiDir): void {
    foreach (['_bootstrap.php', '_common.php', 'listar.php', 'iniciar.php', 'detalle.php', 'guardar_resultado.php', 'finalizar.php'] as $file) {
        monitorApiExpect(is_file($apiDir . '/' . $file), 'Falta endpoint: ' . $file);
    }
});

monitorApiTest('todos los endpoints usan el bootstrap del monitor', function () use ($apiDir): void {
    foreach (['listar.php', 'iniciar.php', 'detalle.php', 'guardar_resultado.php', 'finalizar.php'] as $file) {
        monitorApiExpect(str_contains(monitorApiRead($apiDir . '/' . $file), "require_once __DIR__ . '/_bootstrap.php'"), 'Falta bootstrap en ' . $file);
    }
});

monitorApiTest('la autorización deriva el centro de la sesión PHP', function () use ($apiDir): void {
    $content = monitorApiRead($apiDir . '/_bootstrap.php') . monitorApiRead($apiDir . '/_common.php');
    foreach (["\$_SESSION['centro_id']", 'SESION_CENTRO_REQUERIDA', 'CENTRO_INCORRECTO', 'instalaciones'] as $needle) {
        monitorApiExpect(str_contains($content, $needle), 'Falta control de centro: ' . $needle);
    }
    monitorApiExpect(!str_contains($content, "\$input['centro_id']"), 'Se confía en un centro_id enviado por el cliente.');
});

monitorApiTest('iniciar valida fecha, período y una sola realización', function () use ($apiDir): void {
    $content = monitorApiRead($apiDir . '/iniciar.php');
    foreach (['evaluacionesDateInsidePeriod', 'FECHA_FUTURA', 'FECHA_FUERA_DE_PERIODO', 'numero_intento', 'FOR UPDATE', 'EVALUACION_YA_REALIZADA'] as $needle) {
        monitorApiExpect(str_contains($content, $needle), 'Falta regla de inicio: ' . $needle);
    }
});

monitorApiTest('iniciar crea instantáneas sin confundir participantes eliminados', function () use ($apiDir): void {
    $content = monitorApiRead($apiDir . '/iniciar.php');
    foreach (['participante_ref', 'participante_nombre', 'participante_apellidos', "CONCAT('inscrito:',", 'evaluacion_resultados'] as $needle) {
        monitorApiExpect(str_contains($content, $needle), 'Falta instantánea: ' . $needle);
    }
});

monitorApiTest('guardar valida pertenencia, tipo, estado y usa upsert', function () use ($apiDir): void {
    $content = monitorApiRead($apiDir . '/guardar_resultado.php');
    foreach (['SESION_YA_FINALIZADA', 'PARTICIPANTE_NO_PERTENECE', 'CAMPO_NO_PERTENECE', 'evaluacionesValidateResult', 'ON DUPLICATE KEY UPDATE', 'updated_by_centro_id'] as $needle) {
        monitorApiExpect(str_contains($content, $needle), 'Falta regla de guardado: ' . $needle);
    }
});

monitorApiTest('finalizar protege pendientes y es idempotente', function () use ($apiDir): void {
    $content = monitorApiRead($apiDir . '/finalizar.php');
    foreach (['confirmar_pendientes', 'HAY_RESULTADOS_PENDIENTES', "estado = 'finalizada'", 'finalizada_at', 'FOR UPDATE'] as $needle) {
        monitorApiExpect(str_contains($content, $needle), 'Falta regla de finalización: ' . $needle);
    }
});

monitorApiTest('las mutaciones son transaccionales y registran contexto técnico', function () use ($apiDir): void {
    foreach (['iniciar.php', 'guardar_resultado.php', 'finalizar.php'] as $file) {
        $content = monitorApiRead($apiDir . '/' . $file);
        monitorApiExpect(str_contains($content, 'beginTransaction'), 'Falta transacción en ' . $file);
        monitorApiExpect(str_contains($content, 'error'), 'Falta diagnóstico en ' . $file);
    }
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

fwrite(STDOUT, sprintf("\nResultado API Monitor: %d correctas, %d fallidas.\n", $passed, $failed));
exit($failed === 0 ? 0 : 1);

