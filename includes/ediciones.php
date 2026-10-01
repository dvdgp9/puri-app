<?php

/** Edition periods always come from their included activities. */
function editionPeriod(array $activities): array
{
    if (!$activities) throw new DomainException('El listado no contiene actividades.');
    $starts = array_values(array_filter(array_column($activities, 'fecha_inicio')));
    $ends = array_column($activities, 'fecha_fin');
    return ['fecha_inicio' => $starts ? min($starts) : null, 'fecha_fin' => in_array(null, $ends, true) || in_array('', $ends, true) ? null : max($ends)];
}

function editionLabel(array $edition): string
{
    if (!$edition['fecha_inicio']) return 'Histórico sin fecha de inicio';
    $start = substr($edition['fecha_inicio'], 0, 4);
    $end = $edition['fecha_fin'] ? substr($edition['fecha_fin'], 0, 4) : null;
    return $end && $end !== $start ? "Curso {$start}–{$end}" : "Curso $start";
}

function editionList(PDO $pdo, int $centerId): array
{
    $stmt = $pdo->prepare('SELECT id, centro_id, fecha_inicio, fecha_fin FROM ediciones WHERE centro_id = ? ORDER BY fecha_inicio DESC, id DESC');
    $stmt->execute([$centerId]);
    return array_map(static function ($row) {
        $row['id'] = (int) $row['id'];
        $row['nombre'] = editionLabel($row);
        return $row;
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

/** Prefer the current course, then the nearest upcoming course, then the last one. */
function editionDefault(array $editions, ?string $today = null): ?int
{
    $today = $today ?? date('Y-m-d');
    $current = array_values(array_filter($editions, static fn($e) => $e['fecha_inicio'] && $e['fecha_inicio'] <= $today && (!$e['fecha_fin'] || $e['fecha_fin'] >= $today)));
    usort($current, static fn($a, $b) => [$b['fecha_inicio'], $b['id']] <=> [$a['fecha_inicio'], $a['id']]);
    if ($current) return (int) $current[0]['id'];
    $future = array_values(array_filter($editions, static fn($e) => $e['fecha_inicio'] > $today));
    usort($future, static fn($a, $b) => [$a['fecha_inicio'], $a['id']] <=> [$b['fecha_inicio'], $b['id']]);
    if ($future) return (int) $future[0]['id'];
    usort($editions, static fn($a, $b) => [$b['fecha_inicio'], $b['id']] <=> [$a['fecha_inicio'], $a['id']]);
    return $editions ? (int) $editions[0]['id'] : null;
}

function editionResolve(PDO $pdo, int $centerId, $requested = null): ?int
{
    $editions = editionList($pdo, $centerId);
    if ($requested === null || $requested === '') return editionDefault($editions);
    $id = filter_var($requested, FILTER_VALIDATE_INT);
    foreach ($editions as $edition) {
        if ($id && $edition['id'] === $id) return $id;
    }
    throw new DomainException('La edición no pertenece a este centro.');
}

/** Shared SQL for lists and totals. Aliases are supplied only by application code. */
function editionActivitySql(?int $editionId, string $alias = 'a', bool $history = false): string
{
    $scope = $editionId === null ? "$alias.edicion_id IS NULL" : "$alias.edicion_id = " . (int) $editionId;
    return '(' . $scope . ($history ? '' : " AND $alias.retirada_at IS NULL") . ')';
}

/** Correlated version used by the dashboard, where every center has its own default. */
function editionCurrentSql(string $activity = 'a', string $center = 'i.centro_id'): string
{
    return "$activity.retirada_at IS NULL AND ($activity.edicion_id IS NULL OR $activity.edicion_id = (
        SELECT e.id FROM ediciones e WHERE e.centro_id = $center
        ORDER BY CASE WHEN e.fecha_inicio <= CURDATE() AND (e.fecha_fin IS NULL OR e.fecha_fin >= CURDATE()) THEN 0
                      WHEN e.fecha_inicio > CURDATE() THEN 1 ELSE 2 END,
                 CASE WHEN e.fecha_inicio > CURDATE() THEN e.fecha_inicio END ASC,
                 e.fecha_inicio DESC, e.id DESC LIMIT 1))";
}

function editionRecalculate(PDO $pdo, int $editionId): void
{
    $stmt = $pdo->prepare('SELECT fecha_inicio, fecha_fin FROM actividades WHERE edicion_id = ? AND retirada_at IS NULL');
    $stmt->execute([$editionId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$activities) return; // An empty historical edition keeps its last known period.
    $period = editionPeriod($activities);
    $pdo->prepare('UPDATE ediciones SET fecha_inicio = ?, fecha_fin = ? WHERE id = ?')
        ->execute([$period['fecha_inicio'], $period['fecha_fin'], $editionId]);
}

function editionCreate(PDO $pdo, int $centerId, array $period): int
{
    $pdo->prepare('INSERT INTO ediciones (centro_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?)')
        ->execute([$centerId, $period['fecha_inicio'], $period['fecha_fin']]);
    return (int) $pdo->lastInsertId();
}

/** Only render a course selector when there is an actual choice. */
function editionSelector(array $editions, ?int $selectedId, string $id = 'edition-select'): string
{
    if (count($editions) < 2) return '';
    $names = array_count_values(array_column($editions, 'nombre'));
    $html = '<div class="edition-control"><label for="' . htmlspecialchars($id) . '">Edición</label><select id="' . htmlspecialchars($id) . '" class="form-input" name="edicion_id">';
    foreach ($editions as $edition) {
        $dates = ($edition['fecha_inicio'] ? date('d/m/Y', strtotime($edition['fecha_inicio'])) : 'sin inicio definido') . ' → ' . ($edition['fecha_fin'] ? date('d/m/Y', strtotime($edition['fecha_fin'])) : 'sin fin definido');
        $label = $edition['nombre'] . ($names[$edition['nombre']] > 1 ? ' · ' . $dates : '');
        $html .= '<option title="' . htmlspecialchars($dates) . '" value="' . (int) $edition['id'] . '"' . ($edition['id'] === $selectedId ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
    }
    return $html . '</select></div>';
}

function editionContextHtml(int $centerId, ?int $editionId, string $prefix = ''): string
{
    $context = json_encode(['centerId' => $centerId, 'id' => $editionId], JSON_THROW_ON_ERROR);
    return '<link rel="stylesheet" href="' . $prefix . 'public/assets/css/ediciones.css?v=' . filemtime(__DIR__ . '/../public/assets/css/ediciones.css') . '">'
        . '<script>window.__EDITION_CTX__ = ' . $context . ';</script>'
        . '<script src="' . $prefix . 'public/assets/js/ediciones.js?v=' . filemtime(__DIR__ . '/../public/assets/js/ediciones.js') . '"></script>';
}
