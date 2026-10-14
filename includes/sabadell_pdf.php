<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/program_import.php';

function sabadellDays(string $value): string
{
    $map = ['Dl' => 'Lunes', 'Dm' => 'Martes', 'Dx' => 'Miércoles', 'Dj' => 'Jueves', 'Dv' => 'Viernes', 'Ds' => 'Sábado', 'Dg' => 'Domingo'];
    $days = preg_split('/\s*,\s*/u', trim($value));
    foreach ($days as &$day) {
        if (!isset($map[$day])) throw new DomainException('Días no reconocidos: ' . $value);
        $day = $map[$day];
    }
    return programDays($days);
}

/** Conservative suggestion only. The reviewer must confirm every group. */
function sabadellNameSuggestion(string $original): array
{
    $parts = explode(' ', programText($original));
    return count($parts) >= 3
        ? ['apellidos' => implode(' ', array_slice($parts, 0, 2)), 'nombre' => implode(' ', array_slice($parts, 2))]
        : ['apellidos' => '', 'nombre' => programText($original)];
}

/** Position-based parser for the Sabadell print layout. Reject incomplete pages. */
function sabadellParsePage(array $items, int $page): array
{
    $points = [];
    foreach ($items as $item) {
        if (!isset($item[0][4], $item[0][5], $item[1])) continue;
        $text = programText($item[1]);
        if ($text !== '') $points[] = ['x' => (float) $item[0][4], 'y' => (float) $item[0][5], 'text' => $text];
    }
    $find = static function (callable $test) use ($points): array {
        $matches = array_values(array_filter($points, $test));
        if (count($matches) !== 1) throw new DomainException('Cabecera incompleta o ambigua.');
        return $matches[0];
    };
    try {
        $activityLabel = $find(static fn($p) => str_contains($p['text'], "Tipus d'Activitat:"));
        $venueLabel = $find(static fn($p) => str_contains($p['text'], 'Equipament:'));
        $teacherLabel = $find(static fn($p) => $p['text'] === 'Professor:');
        $table = $find(static fn($p) => str_contains($p['text'], 'Cognoms i nom'));
        $footer = $find(static fn($p) => str_starts_with($p['text'], 'En compliment'));
        $group = $find(static fn($p) => preg_match('/^[\p{L}\d]+-\d+\s+\d{1,2}[.:]\d{2}\s+a\s+\d{1,2}[.:]\d{2}$/u', $p['text']));
        preg_match('/^([\p{L}\d]+-\d+)\s+(\d{1,2}[.:]\d{2})\s+a\s+(\d{1,2}[.:]\d{2})$/u', $group['text'], $clock);
        $activity = $find(static fn($p) => abs($p['y'] - $activityLabel['y']) < 2 && $p['x'] > $activityLabel['x'] + 5 && $p['x'] < $teacherLabel['x']);
        $venue = $find(static fn($p) => abs($p['y'] - $venueLabel['y']) < 2 && $p['x'] > $venueLabel['x'] + 5 && $p['x'] < $group['x']);
        $days = $find(static fn($p) => preg_match('/^D[lmxjvsg](?:\s*,\s*D[lmxjvsg])*$/u', $p['text']));
        $body = array_values(array_filter($points, static fn($p) => $p['y'] < $table['y'] - 3 && $p['y'] > $footer['y'] + 3));
        if (!$body) throw new DomainException('El grupo no contiene participantes.');
        $phones = array_values(array_filter($body, static fn($p) => preg_match('/^\d+\s*-\s*\d+$/', $p['text'])));
        if (!$phones) throw new DomainException('No se reconocen las columnas del listado.');
        $phoneX = min(array_column($phones, 'x'));
        $numbers = array_values(array_filter($body, static fn($p) => $p['x'] < $table['x'] && ctype_digit($p['text'])));
        usort($numbers, static fn($a, $b) => $b['y'] <=> $a['y']);
        if (!$numbers) throw new DomainException('No se reconoce la numeración de participantes.');
        $people = [];
        $used = [];
        foreach ($numbers as $index => $number) {
            if ((int) $number['text'] !== $index + 1) throw new DomainException('Numeración incompleta de participantes.');
            $parts = [];
            foreach ($body as $key => $point) {
                if (abs($point['y'] - $number['y']) < 2 && $point['x'] > $number['x'] + 3 && $point['x'] < $phoneX - 3) {
                    $parts[] = $point; $used[$key] = true;
                }
            }
            usort($parts, static fn($a, $b) => $a['x'] <=> $b['x']);
            $original = programText(implode(' ', array_column($parts, 'text')));
            if ($original === '' || preg_match('/\d/u', $original)) throw new DomainException('Nombre no reconocido en la fila ' . ($index + 1) . '.');
            $people[] = ['id' => 'p' . $page . '-' . ($index + 1), 'original' => $original] + sabadellNameSuggestion($original);
        }
        foreach ($body as $key => $point) {
            if ($point['x'] < $phoneX - 3 && !ctype_digit($point['text']) && !isset($used[$key])) {
                throw new DomainException('Hay texto sin interpretar en la columna de participantes.');
            }
        }
        return ['id' => 'p' . $page, 'page' => $page, 'actividad' => $activity['text'], 'instalacion' => $venue['text'],
            'grupo' => $clock[1], 'hora_inicio' => programTime($clock[2]), 'hora_fin' => programTime($clock[3]),
            'dias_semana' => sabadellDays($days['text']), 'participants' => $people];
    } catch (DomainException $e) {
        throw new DomainException('Página ' . $page . ': ' . $e->getMessage());
    }
}

function sabadellPdf(string $path): array
{
    if (!is_file($path) || filesize($path) > 5 * 1024 * 1024) throw new DomainException('El PDF no está disponible o supera 5 MB.');
    $head = fopen($path, 'rb');
    $signature = fread($head, 5); fclose($head);
    if ($signature !== '%PDF-') throw new DomainException('Selecciona un PDF válido.');
    try { $pdf = (new \Smalot\PdfParser\Parser())->parseFile($path); }
    catch (Throwable $e) { throw new DomainException('No se pudo leer el PDF. Debe contener texto y no estar protegido.'); }
    $pages = $pdf->getPages();
    if (!$pages || count($pages) > 100) throw new DomainException('El PDF debe contener entre 1 y 100 páginas.');
    $groups = []; $codes = []; $count = 0; $date = null;
    foreach ($pages as $index => $page) {
        $items = $page->getDataTm();
        $group = sabadellParsePage($items, $index + 1);
        $key = programKey($group['grupo']);
        if (isset($codes[$key])) throw new DomainException('El grupo ' . $group['grupo'] . ' aparece en varias páginas. Revisa el documento.');
        $codes[$key] = true;
        $count += count($group['participants']);
        if ($count > 20000) throw new DomainException('El PDF supera 20.000 participantes.');
        $groups[] = $group;
        if ($index === 0) foreach ($items as $item) {
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', trim($item[1] ?? '')) && ($item[0][5] ?? 0) > 540) $date = programDate(trim($item[1]));
        }
    }
    return ['groups' => $groups, 'source_date' => $date, 'source_count' => $count];
}
