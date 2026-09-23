<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/evaluacion_series.php';
require_once __DIR__ . '/../includes/roster.php';

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$monthly = evaluacionSeriesPeriods('2027-01-31', '2027-01-31', '2027-12-31', 'retos');
check(count($monthly) === 12, 'Los retos deben generar doce ciclos en un año completo.');
check($monthly[1]['fecha_inicio'] === '2027-02-28', 'El día 31 debe ajustarse a febrero.');
check($monthly[2]['fecha_inicio'] === '2027-03-31', 'El ancla debe recuperarse en marzo.');

$quarterly = evaluacionSeriesPeriods('2026-01-10', '2026-01-12', '2026-12-31', 'trimestral');
check(count($quarterly) === 4, 'Las evaluaciones trimestrales deben producir cuatro ciclos.');
check($quarterly[3]['fecha_inicio'] === '2026-10-10', 'El cuarto ciclo debe caer en octubre.');

try {
    evaluacionSeriesPeriods('2026-01-01', '2026-02-10', '2026-12-31', 'retos');
    throw new RuntimeException('Se permitió un solapamiento entre ciclos.');
} catch (InvalidArgumentException $expected) {
    // Correcto.
}

check(rosterKey('  ANA   María ', 'PÉREZ  Gómez') === rosterKey('ana maría', 'Pérez Gómez'), 'La clave debe ignorar mayúsculas y espacios.');
check(rosterKey('Maria', 'Perez') !== rosterKey('María', 'Pérez'), 'La clave no debe quitar tildes.');

fwrite(STDOUT, "[OK] Series mensuales/trimestrales y clave de participantes.\n");
