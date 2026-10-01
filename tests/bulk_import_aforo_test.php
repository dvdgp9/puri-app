<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/program_import.php';

function aforoCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$row = ['instalacion' => 'Sala municipal', 'actividad' => 'Movilidad', 'grupo' => 'A', 'fecha_inicio' => '2026-09-01',
    'fecha_fin' => '2027-06-30', 'hora_inicio' => '9:15', 'hora_fin' => '10:00', 'dias_semana' => 'Lunes,Miércoles'];
$classes = programNormalize([$row, array_replace($row, ['hora_inicio' => '10:15', 'hora_fin' => '11:00'])], 'aforo');
aforoCheck(count($classes) === 2, 'Cada horario distinto debe conservarse como una clase independiente.');
foreach ($classes as $class) {
    aforoCheck($class['tipo_control'] === 'aforo' && $class['personas'] === [], 'El modo aforo crea clases sin personas.');
}
$equivalent = programNormalize([$row, array_replace($row, ['hora_inicio' => '09:15:00', 'hora_fin' => '10:00:00', 'dias_semana' => 'Miércoles,Lunes'])], 'aforo');
aforoCheck(count($equivalent) === 1, 'Las horas y días equivalentes no duplican la clase.');
$mixed = programNormalize([array_replace($row, ['nombre' => 'Lara', 'apellidos' => 'Castiello']), array_replace($row, ['grupo' => 'B', 'tipo_control' => 'A'])], 'participantes');
aforoCheck(count($mixed) === 2 && array_values($mixed)[1]['personas'] === [], 'El listado completo permite mezclar asistencia y aforo.');
$empty = ['ediciones' => [], 'instalaciones' => [], 'actividades' => [], 'personas' => []];
$plan = programPlan($empty, $classes, 'aforo', 'complete');
aforoCheck($plan['stats']['participantes_creados'] === 0 && $plan['stats']['clases_aforo_procesadas'] === 2, 'La revisión no cuenta participantes ficticios.');
$dashboard = file_get_contents(__DIR__ . '/../admin/dashboard.php');
$css = file_get_contents(__DIR__ . '/../admin/assets/css/admin.css');
aforoCheck(str_contains($dashboard, 'name="bulk_import_mode"') && str_contains($dashboard, 'value="aforo"'), 'La interfaz mantiene el modo de clases de aforo.');
aforoCheck(str_contains($css, '.bulk-mode-aforo .bulk-participant-column') && str_contains($css, '.bulk-mode-aforo .bulk-type-column'), 'Las columnas de personas se ocultan en modo aforo.');
fwrite(STDOUT, "[OK] Importación de aforo y listados mixtos.\n");
