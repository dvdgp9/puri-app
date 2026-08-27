<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dashboard = file_get_contents($root . '/admin/dashboard.php');
$javascript = file_get_contents($root . '/admin/assets/js/dashboard.js');
$endpoint = file_get_contents($root . '/admin/api/bulk_import.php');
$css = file_get_contents($root . '/admin/assets/css/admin.css');

$tests = [
    'La interfaz ofrece los dos modos globales' =>
        str_contains($dashboard, 'name="bulk_import_mode"')
        && str_contains($dashboard, 'value="participantes"')
        && str_contains($dashboard, 'value="aforo"'),
    'El cliente envía el modo de importación al servidor' =>
        str_contains($javascript, 'modo_importacion: modoImportacion'),
    'El recuento de aforo usa instalación o actividad en lugar del participante' =>
        str_contains($javascript, "modoImportacion === 'aforo'")
        && str_contains($javascript, 'const tieneDatos = modoImportacion'),
    'El servidor valida el modo global' =>
        str_contains($endpoint, "in_array(\$modoImportacion, ['participantes', 'aforo'], true)"),
    'Aforo fuerza su tipo de control en el servidor' =>
        str_contains($endpoint, "\$modoImportacion === 'aforo' ? 'aforo'"),
    'Aforo omite la creación de inscritos' =>
        str_contains($endpoint, "if (\$modoImportacion === 'aforo')")
        && str_contains($endpoint, "\$stats['clases_aforo_procesadas']++"),
    'La identidad de una clase compara tipo fechas y horas' =>
        str_contains($endpoint, 'tipo_control, fecha_inicio, fecha_fin, hora_inicio, hora_fin')
        && str_contains($endpoint, "\$actExist['tipo_control'] === \$tipoControl")
        && str_contains($endpoint, "normalizarHoraComparacion(\$actExist['hora_inicio'])"),
    'La interfaz oculta columnas irrelevantes solo en modo aforo' =>
        str_contains($css, '.bulk-mode-aforo .bulk-participant-column')
        && str_contains($css, '.bulk-mode-aforo .bulk-type-column'),
    'No se añadieron estilos inline al selector nuevo' =>
        !preg_match('/bulk-import-mode[^>]*style=/i', $dashboard),
];

$passed = 0;
foreach ($tests as $name => $result) {
    if ($result) {
        $passed++;
        echo "[OK] $name\n";
    } else {
        echo "[FALLO] $name\n";
    }
}

$failed = count($tests) - $passed;
echo "\n$passed correctas, $failed fallidas\n";
exit($failed === 0 ? 0 : 1);
