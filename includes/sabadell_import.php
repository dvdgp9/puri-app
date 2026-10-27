<?php
require_once __DIR__ . '/program_import.php';
require_once __DIR__ . '/sabadell_pdf.php';

function sabadellEdition(array $snapshot, array $input): ?int
{
    $destination = $input['destination'] ?? 'actual';
    if (!in_array($destination, ['actual', 'nueva'], true)) throw new DomainException('Destino de carga inválido.');
    if ($destination === 'nueva') return null;
    $id = $input['edicion_id'] ?? editionDefault($snapshot['ediciones']);
    if ($id === null || $id === '') {
        if ($snapshot['ediciones']) throw new DomainException('Selecciona el curso de destino.');
        return null;
    }
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if (!$id || !in_array($id, array_column($snapshot['ediciones'], 'id'), true)) throw new DomainException('El curso no pertenece a este centro.');
    return $id;
}

function sabadellCandidates(array $snapshot, string $code, ?int $edition): array
{
    if ($edition === null) return [];
    $matches = array_values(array_filter($snapshot['actividades'], static fn($a) => (int) $a['edicion_id'] === $edition && programKey($a['grupo'] ?? '') === programKey($code)));
    $active = array_values(array_filter($matches, static fn($a) => !$a['retirada_at']));
    return $active ?: $matches;
}

function sabadellSameMetadata(array $group, array $activity): bool
{
    try {
        return programKey($group['instalacion']) === programKey($activity['instalacion'])
        && programKey($group['actividad']) === programKey($activity['nombre'])
        && $activity['tipo_control'] === 'asistencia'
        && programDays($group['dias_semana']) === programDays($activity['dias_semana'])
        && programTime($group['hora_inicio']) === programTime($activity['hora_inicio'])
        && programTime($group['hora_fin']) === programTime($activity['hora_fin']);
    } catch (DomainException $e) {
        return false; // A legacy class with missing schedule data needs explicit resolution.
    }
}

/** Suggest existing names only when the full source name identifies one person. */
function sabadellContext(array $snapshot, array $draft, array $input): array
{
    $edition = sabadellEdition($snapshot, $input);
    $groups = $draft['groups'];
    foreach ($groups as &$group) {
        $candidates = sabadellCandidates($snapshot, $group['grupo'], $edition);
        $group['candidates'] = array_map(static fn($a) => array_intersect_key($a, array_flip(['id', 'nombre', 'instalacion', 'dias_semana', 'hora_inicio', 'hora_fin', 'fecha_inicio', 'fecha_fin', 'retirada_at'])), $candidates);
        $group['target_id'] = count($candidates) === 1 ? (int) $candidates[0]['id'] : 0;
        $group['action'] = !$candidates ? 'new' : (count($candidates) === 1 && sabadellSameMetadata($group, $candidates[0]) ? 'reuse' : '');
        if (count($candidates) === 1) {
            $names = [];
            foreach ($snapshot['personas'] as $person) {
                if ((int) $person['actividad_id'] !== (int) $candidates[0]['id']) continue;
                $key = programKey($person['apellidos'] . ' ' . $person['nombre']);
                $names[$key][] = $person;
            }
            foreach ($group['participants'] as &$person) {
                $match = $names[programKey($person['original'])] ?? [];
                if (count($match) === 1) {
                    $person['nombre'] = $match[0]['nombre']; $person['apellidos'] = $match[0]['apellidos'];
                }
            }
            unset($person);
        }
    }
    unset($group);
    $period = null;
    foreach ($snapshot['ediciones'] as $e) if ($e['id'] === $edition) $period = $e;
    return ['groups' => $groups, 'edicion_id' => $edition, 'ediciones' => $snapshot['ediciones'], 'period' => $period];
}

/** Account for every source row and validate real issues; manual review is optional. */
function sabadellPlan(array $snapshot, array $draft, array $input): array
{
    $edition = sabadellEdition($snapshot, $input);
    if (!is_array($input['groups'] ?? null) || count($input['groups']) !== count($draft['groups'])) throw new DomainException('Faltan grupos del PDF. Vuelve a revisar el archivo.');
    $source = array_column($draft['groups'], null, 'id');
    $seen = []; $codes = []; $rows = []; $replace = []; $excluded = 0;
    foreach ($input['groups'] as $group) {
        if (!is_array($group)) throw new DomainException('Grupo inválido.');
        $id = $group['id'] ?? '';
        if (!is_string($id) || !isset($source[$id]) || isset($seen[$id])) throw new DomainException('Grupo desconocido o repetido.');
        $seen[$id] = true;
        $class = [];
        foreach (['instalacion', 'actividad', 'grupo', 'dias_semana', 'hora_inicio', 'hora_fin'] as $field) {
            if (!is_string($group[$field] ?? null)) throw new DomainException('Faltan datos del grupo.');
            $class[$field] = programText($group[$field]);
        }
        $code = programKey($class['grupo']);
        if ($code === '' || isset($codes[$code])) throw new DomainException('Código de grupo vacío o repetido.');
        $codes[$code] = true;
        $candidates = sabadellCandidates($snapshot, $class['grupo'], $edition);
        $target = null;
        foreach ($candidates as $candidate) if ((int) $candidate['id'] === (int) ($group['target_id'] ?? 0)) $target = $candidate;
        $action = $group['action'] ?? '';
        if ($candidates && (!$target || !in_array($action, ['reuse', 'replace'], true))) throw new DomainException('Resuelve la coincidencia del grupo ' . $class['grupo'] . ' antes de guardar.');
        if (!$candidates && ($action !== 'new' || !empty($group['target_id']))) throw new DomainException('Destino inválido para el grupo ' . $class['grupo'] . '.');
        if ($target && $action === 'reuse') {
            // Reusing a class is an explicit decision to preserve its schedule and period.
            if ($target['tipo_control'] !== 'asistencia') throw new DomainException('No se puede reutilizar una clase de aforo para participantes.');
            $class = ['instalacion' => $target['instalacion'], 'actividad' => $target['nombre'], 'grupo' => $target['grupo'],
                'dias_semana' => $target['dias_semana'], 'hora_inicio' => $target['hora_inicio'], 'hora_fin' => $target['hora_fin'],
                'fecha_inicio' => $target['fecha_inicio'], 'fecha_fin' => $target['fecha_fin']];
        } else {
            $class['fecha_inicio'] = programDate($group['fecha_inicio'] ?? $input['fecha_inicio'] ?? '');
            $class['fecha_fin'] = programDate($group['fecha_fin'] ?? $input['fecha_fin'] ?? '');
            if ($target) $replace[(int) $target['id']] = $target;
        }
        $class['tipo_control'] = 'asistencia';
        $originalPeople = array_column($source[$id]['participants'], null, 'id');
        if (!is_array($group['participants'] ?? null) || count($group['participants']) !== count($originalPeople)) throw new DomainException('Faltan participantes del grupo ' . $class['grupo'] . '.');
        $peopleSeen = []; $groupRows = [];
        foreach ($group['participants'] as $person) {
            if (!is_array($person) || !is_string($person['id'] ?? null) || !isset($originalPeople[$person['id']]) || isset($peopleSeen[$person['id']])) throw new DomainException('Participante desconocido o repetido.');
            $peopleSeen[$person['id']] = true;
            if (!is_bool($person['exclude'] ?? null)) throw new DomainException('Indica si se incluye cada participante.');
            if ($person['exclude']) { $excluded++; continue; }
            if (!is_string($person['nombre'] ?? null) || !is_string($person['apellidos'] ?? null)) throw new DomainException('Nombre o apellidos inválidos.');
            $groupRows[] = $class + ['nombre' => $person['nombre'], 'apellidos' => $person['apellidos']];
        }
        if (!$groupRows) throw new DomainException('El grupo ' . $class['grupo'] . ' está vacío. No se puede sincronizar.');
        try { programNormalize($groupRows, 'participantes'); }
        catch (DomainException $e) {
            throw new DomainException('Grupo ' . $class['grupo'] . ' (página ' . $source[$id]['page'] . '): ' . $e->getMessage());
        }
        array_push($rows, ...$groupRows);
    }
    $classes = programNormalize($rows, 'participantes');
    $plan = programPlan($snapshot, $classes, 'participantes', 'groups', $edition ?? 0, $input['destination'] ?? 'actual');
    $used = array_filter(array_column($plan['classes'], 'id'));
    foreach ($replace as $id => $old) {
        if (in_array($id, $used, true)) throw new DomainException('La sustitución del grupo ' . $old['grupo'] . ' no cambia sus datos. Elige actualizar el grupo existente.');
        if (!$old['retirada_at']) {
            $plan['retire'][] = $id;
            $plan['retirement_names'][] = $old['nombre'] . ' · ' . $old['grupo'] . ' · ' . $old['instalacion'];
            foreach ($snapshot['personas'] as $person) if ((int) $person['actividad_id'] === $id && (int) $person['activo'] === 1) {
                $plan['deactivate'][] = (int) $person['id'];
                $plan['deactivation_names'][] = trim($person['nombre'] . ' ' . $person['apellidos']);
            }
        }
    }
    $plan['deactivate'] = array_values(array_unique($plan['deactivate']));
    $plan['stats']['actividades_retiradas'] = count($plan['retire']);
    $plan['stats']['participantes_desactivados'] = count($plan['deactivate']);
    $plan['source_count'] = $draft['source_count'];
    $plan['excluded_count'] = $excluded;
    $remaining = array_filter($snapshot['actividades'], static fn($a) => $edition !== null && (int) $a['edicion_id'] === $edition && !$a['retirada_at'] && !in_array((int) $a['id'], $plan['retire'], true));
    $plan['periodo'] = editionPeriod(array_merge(array_values($plan['classes']), array_values($remaining)));
    $plan['nombre_edicion'] = editionLabel($plan['periodo']);
    $plan['fingerprint'] = hash('sha256', json_encode([$plan['fingerprint'], $draft['id'], $input, $plan['retire'], $plan['deactivate']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    return $plan;
}

function sabadellDraft(array $session, string $id, int $adminId, int $centerId): array
{
    $draft = $session['sabadell_draft'] ?? null;
    if (!$draft || !hash_equals($draft['id'], $id) || $draft['admin_id'] !== $adminId || $draft['center_id'] !== $centerId || $draft['expires'] < time()) {
        throw new DomainException('La lectura del PDF ha caducado o pertenece a otro centro. Vuelve a subirlo.', 409);
    }
    return $draft;
}
