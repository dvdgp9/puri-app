<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$pagePath = $root . '/admin/admins.php';
$jsPath = $root . '/admin/assets/js/admins.js';
$listPath = $root . '/admin/api/superadmin/admins/list.php';
$createPath = $root . '/admin/api/superadmin/admins/create.php';
$updatePath = $root . '/admin/api/superadmin/admins/update.php';
$tests = [];

function coordinatorTest(string $name, callable $test): void
{
    global $tests;
    $tests[] = [$name, $test];
}

function coordinatorExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function coordinatorRead(string $path): string
{
    coordinatorExpect(is_file($path), 'Falta archivo: ' . $path);
    $content = file_get_contents($path);
    coordinatorExpect($content !== false, 'No se pudo leer: ' . $path);
    return (string) $content;
}

coordinatorTest('crear y editar administrador incluye email', function () use ($pagePath): void {
    $content = coordinatorRead($pagePath);
    foreach (['id="adminEmail"', 'name="email"', 'type="email"', 'id="editAdminEmail"'] as $needle) {
        coordinatorExpect(str_contains($content, $needle), 'Falta control de email: ' . $needle);
    }
});

coordinatorTest('el listado muestra email o ausencia visible', function () use ($pagePath, $jsPath): void {
    $content = coordinatorRead($pagePath) . coordinatorRead($jsPath);
    foreach (['Correo de avisos', 'Sin email', 'admin.email'] as $needle) {
        coordinatorExpect(str_contains($content, $needle), 'Falta visibilidad de email: ' . $needle);
    }
});

coordinatorTest('la API lista, busca y devuelve email', function () use ($listPath): void {
    $content = coordinatorRead($listPath);
    foreach (['a.email', 'email LIKE'] as $needle) {
        coordinatorExpect(str_contains($content, $needle), 'Falta email en listado: ' . $needle);
    }
});

coordinatorTest('crear valida y persiste email opcional', function () use ($createPath): void {
    $content = coordinatorRead($createPath);
    foreach (['FILTER_VALIDATE_EMAIL', 'email', 'INSERT INTO admins'] as $needle) {
        coordinatorExpect(str_contains($content, $needle), 'Falta email en create: ' . $needle);
    }
});

coordinatorTest('editar valida y persiste email nullable', function () use ($updatePath): void {
    $content = coordinatorRead($updatePath);
    foreach (['FILTER_VALIDATE_EMAIL', "email = ?", "array_key_exists('email'", 'SELECT id, username, nombre, apellidos, email'] as $needle) {
        coordinatorExpect(str_contains($content, $needle), 'Falta email en update: ' . $needle);
    }
});

coordinatorTest('el cliente envía email en crear y editar', function () use ($jsPath): void {
    $content = coordinatorRead($jsPath);
    coordinatorExpect(substr_count($content, "formData.get('email')") >= 2, 'El cliente no envía email en ambos formularios.');
    coordinatorExpect(str_contains($content, 'editAdminEmail'), 'No se precarga el email al editar.');
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

fwrite(STDOUT, sprintf("\nResultado email coordinadores: %d correctas, %d fallidas.\n", $passed, $failed));
exit($failed === 0 ? 0 : 1);

