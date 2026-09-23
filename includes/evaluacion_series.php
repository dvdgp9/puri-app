<?php

require_once __DIR__ . '/evaluaciones_helpers.php';

function evaluacionSeriesShift(string $date, int $months): string
{
    $day = (int) substr($date, 8, 2);
    $base = DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 7) . '-01');
    $target = $base->modify('+' . $months . ' months');
    $lastDay = (int) $target->format('t');
    return $target->setDate((int) $target->format('Y'), (int) $target->format('m'), min($day, $lastDay))->format('Y-m-d');
}

function evaluacionSeriesPeriods(string $start, string $end, string $until, string $type): array
{
    if (!evaluacionesIsDate($start) || !evaluacionesIsDate($end) || !evaluacionesIsDate($until)
        || $end < $start || $until < $end || !in_array($type, ['trimestral', 'retos'], true)) {
        throw new InvalidArgumentException('Período o tipo de serie inválido.');
    }
    $step = $type === 'retos' ? 1 : 3;
    if (evaluacionSeriesShift($start, $step) <= $end) {
        throw new InvalidArgumentException('El primer período se solapa con el siguiente ciclo.');
    }
    $periods = [];
    for ($cycle = 0; $cycle < 60; $cycle++) {
        $periodStart = evaluacionSeriesShift($start, $cycle * $step);
        $periodEnd = evaluacionSeriesShift($end, $cycle * $step);
        if ($periodEnd > $until) break;
        $periods[] = ['ciclo' => $cycle + 1, 'fecha_inicio' => $periodStart, 'fecha_fin' => $periodEnd];
    }
    if (!$periods) throw new InvalidArgumentException('La serie no contiene ningún ciclo.');
    return $periods;
}
