<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$pagePath = $root . '/admin/activity.php';
$jsPath = $root . '/admin/assets/js/activity.js';
$cssPath = $root . '/admin/assets/css/admin.css';
$tests = [];

function uiTest(string $name, callable $test): void
{
    global $tests;
    $tests[] = [$name, $test];
}

function uiExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function uiRead(string $path): string
{
    uiExpect(is_file($path), 'Falta archivo: ' . $path);
    $content = file_get_contents($path);
    uiExpect($content !== false, 'No se pudo leer: ' . $path);
    return (string) $content;
}

uiTest('la actividad usa navegación local accesible', function () use ($pagePath): void {
    $page = uiRead($pagePath);
    foreach (['activity-section-tabs', 'role="tablist"', 'id="participants-tab"', 'id="evaluations-tab"', 'aria-controls="participants-panel"', 'aria-controls="evaluations-panel"'] as $needle) {
        uiExpect(str_contains($page, $needle), 'Falta navegación local: ' . $needle);
    }
});

uiTest('participantes sigue siendo la vista inicial', function () use ($pagePath): void {
    $page = uiRead($pagePath);
    uiExpect(preg_match('/id="participants-tab"[^>]*aria-selected="true"/', $page) === 1, 'Participantes no es la pestaña inicial.');
    uiExpect(preg_match('/id="evaluations-panel"[^>]*hidden/', $page) === 1, 'Evaluaciones no empieza oculta.');
});

uiTest('la vista evaluaciones incluye carga, vacío, error y acción contextual', function () use ($pagePath, $jsPath): void {
    $content = uiRead($pagePath) . uiRead($jsPath);
    foreach (['evaluations-list', 'evaluation-list-skeleton', 'No hay evaluaciones', 'No se pudieron cargar las evaluaciones', 'Nueva evaluación'] as $needle) {
        uiExpect(str_contains($content, $needle), 'Falta estado o acción: ' . $needle);
    }
});

uiTest('el formulario Admin configura período y un campo', function () use ($pagePath): void {
    $page = uiRead($pagePath);
    foreach (['evaluationForm', 'evaluationName', 'evaluationInstructions', 'evaluationDateStart', 'evaluationDateEnd', 'evaluationFieldName', 'evaluationDataType', 'evaluationUnit'] as $id) {
        uiExpect(str_contains($page, 'id="' . $id . '"'), 'Falta control: ' . $id);
    }
});

uiTest('la vista de resultados permite corrección y reapertura', function () use ($pagePath, $jsPath): void {
    $content = uiRead($pagePath) . uiRead($jsPath);
    foreach (['evaluationResultsModal', 'evaluation-results-list', 'saveAdminEvaluationResult', 'reopenEvaluation', 'api/evaluaciones/update_result.php', 'api/evaluaciones/reopen.php'] as $needle) {
        uiExpect(str_contains($content, $needle), 'Falta flujo de resultados: ' . $needle);
    }
});

uiTest('el JavaScript consume toda la API Admin de M2', function () use ($jsPath): void {
    $js = uiRead($jsPath);
    foreach (['list_by_activity.php', 'detail.php', 'create.php', 'update.php', 'archive.php'] as $endpoint) {
        uiExpect(str_contains($js, 'api/evaluaciones/' . $endpoint), 'Falta endpoint en UI: ' . $endpoint);
    }
});

uiTest('los estilos nuevos viven en admin.css y son responsive', function () use ($pagePath, $cssPath): void {
    $page = uiRead($pagePath);
    $css = uiRead($cssPath);
    foreach (['.activity-section-tabs', '.evaluation-row', '.evaluation-list-skeleton', '.evaluation-results-row', '@media (max-width: 768px)'] as $selector) {
        uiExpect(str_contains($css, $selector), 'Falta estilo: ' . $selector);
    }
    uiExpect(!preg_match('/evaluation[^>]*style="/i', $page), 'Hay estilos inline en el nuevo marcado de evaluaciones.');
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

fwrite(STDOUT, sprintf("\nResultado UI Admin: %d correctas, %d fallidas.\n", $passed, $failed));
exit($failed === 0 ? 0 : 1);
