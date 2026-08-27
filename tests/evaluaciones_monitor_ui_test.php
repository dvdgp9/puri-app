<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$attendancePath = $root . '/asistencia.php';
$capturePath = $root . '/evaluacion.php';
$jsPath = $root . '/public/assets/js/evaluaciones-monitor.js';
$cssPath = $root . '/public/assets/css/style.css';
$tests = [];

function monitorUiTest(string $name, callable $test): void
{
    global $tests;
    $tests[] = [$name, $test];
}

function monitorUiExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function monitorUiRead(string $path): string
{
    monitorUiExpect(is_file($path), 'Falta archivo: ' . $path);
    $content = file_get_contents($path);
    monitorUiExpect($content !== false, 'No se pudo leer: ' . $path);
    return (string) $content;
}

monitorUiTest('asistencia incluye una sección contextual oculta por defecto', function () use ($attendancePath): void {
    $content = monitorUiRead($attendancePath);
    foreach (['monitor-evaluations-section', 'monitor-evaluations-list', 'hidden', 'Evaluaciones'] as $needle) {
        monitorUiExpect(str_contains($content, $needle), 'Falta sección contextual: ' . $needle);
    }
});

monitorUiTest('las actividades sin trabajo no muestran la sección', function () use ($jsPath): void {
    $content = monitorUiRead($jsPath);
    foreach (['section.hidden = true', 'activeEvaluations.length', 'api/evaluaciones/listar.php'] as $needle) {
        monitorUiExpect(str_contains($content, $needle), 'Falta ocultación contextual: ' . $needle);
    }
});

monitorUiTest('el inicio pide una fecha flexible dentro del período', function () use ($attendancePath, $jsPath): void {
    $content = monitorUiRead($attendancePath) . monitorUiRead($jsPath);
    foreach (['Fecha de realización', 'evaluation-start-date', 'min=', 'max=', 'api/evaluaciones/iniciar.php'] as $needle) {
        monitorUiExpect(str_contains($content, $needle), 'Falta fecha de inicio: ' . $needle);
    }
});

monitorUiTest('existe una captura dedicada con progreso y filtros', function () use ($capturePath): void {
    $content = monitorUiRead($capturePath);
    foreach (['evaluation-capture-list', 'evaluation-progress', 'evaluation-filter-all', 'evaluation-filter-pending', 'Finalizar evaluación'] as $needle) {
        monitorUiExpect(str_contains($content, $needle), 'Falta captura: ' . $needle);
    }
});

monitorUiTest('la captura guarda por fila y permite finalizar con pendientes', function () use ($jsPath): void {
    $content = monitorUiRead($jsPath);
    foreach (['api/evaluaciones/detalle.php', 'api/evaluaciones/guardar_resultado.php', 'api/evaluaciones/finalizar.php', 'saveMonitorEvaluationResult', 'confirmar_pendientes'] as $needle) {
        monitorUiExpect(str_contains($content, $needle), 'Falta operación: ' . $needle);
    }
});

monitorUiTest('cero y sin evaluar se distinguen en cliente', function () use ($jsPath): void {
    $content = monitorUiRead($jsPath);
    monitorUiExpect(str_contains($content, "value === '' ? 'sin_evaluar' : 'medido'"), 'El vacío no se normaliza como sin evaluar.');
    monitorUiExpect(!str_contains($content, 'if (!value)'), 'Una comprobación falsy podría perder el cero.');
});

monitorUiTest('los estilos viven en style.css y se adaptan a móvil', function () use ($attendancePath, $capturePath, $cssPath): void {
    $markup = monitorUiRead($attendancePath) . monitorUiRead($capturePath);
    $css = monitorUiRead($cssPath);
    foreach (['.monitor-evaluations-section', '.monitor-evaluation-row', '.evaluation-capture-row', '.evaluation-start-form', '@media (max-width: 640px)'] as $needle) {
        monitorUiExpect(str_contains($css, $needle), 'Falta estilo: ' . $needle);
    }
    monitorUiExpect(!preg_match('/(?:monitor-evaluation|evaluation-capture|evaluation-start)[^>]*style="/i', $markup), 'Hay estilos inline en el marcado nuevo.');
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

fwrite(STDOUT, sprintf("\nResultado UI Monitor: %d correctas, %d fallidas.\n", $passed, $failed));
exit($failed === 0 ? 0 : 1);

