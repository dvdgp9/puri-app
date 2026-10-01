<?php
require_once __DIR__ . '/ediciones.php';
require_once __DIR__ . '/roster.php';

function programText($value): string { return preg_replace('/\s+/u', ' ', trim((string) $value)); }
function programKey($value): string { return mb_strtolower(programText($value), 'UTF-8'); }

function programDate($value): ?string
{
    $value = trim((string) $value);
    if ($value === '') return null;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
        [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
    } elseif (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{2}|\d{4})$/', $value, $m)) {
        [$year, $month, $day] = [(int) $m[3], (int) $m[2], (int) $m[1]];
        if (strlen($m[3]) === 2) $year += 2000;
    } else { throw new DomainException('Fecha inválida: ' . $value); }
    if ($year < 1000 || !checkdate($month, $day, $year)) throw new DomainException('Fecha inválida: ' . $value);
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

function programTime($value): ?string
{
    $value = trim((string) $value);
    if ($value === '') return null;
    if (!preg_match('/^(\d{1,2})[:.](\d{2})(?::(\d{2}))?$/', $value, $m)
        || (int) $m[1] > 23 || (int) $m[2] > 59 || (int) ($m[3] ?? 0) > 59) throw new DomainException('Hora inválida: ' . $value);
    return sprintf('%02d:%02d:%02d', $m[1], $m[2], $m[3] ?? 0);
}

function programDays($value): string
{
    $aliases = [
        'lunes' => 'Lunes', 'lu' => 'Lunes', 'l' => 'Lunes', 'martes' => 'Martes', 'ma' => 'Martes', 'm' => 'Martes',
        'miércoles' => 'Miércoles', 'miercoles' => 'Miércoles', 'mi' => 'Miércoles', 'x' => 'Miércoles',
        'jueves' => 'Jueves', 'ju' => 'Jueves', 'j' => 'Jueves', 'viernes' => 'Viernes', 'vi' => 'Viernes', 'v' => 'Viernes',
        'sábado' => 'Sábado', 'sabado' => 'Sábado', 'sa' => 'Sábado', 's' => 'Sábado', 'domingo' => 'Domingo', 'do' => 'Domingo', 'd' => 'Domingo',
    ];
    $days = is_array($value) ? $value : preg_split('/[,;|\/\s]+/u', (string) $value);
    $result = [];
    foreach ($days as $day) {
        $key = programKey($day);
        if ($key === '' || $key === 'y') continue;
        if (!isset($aliases[$key])) throw new DomainException('Día de la semana desconocido: ' . $day);
        $result[$aliases[$key]] = true;
    }
    if (!$result) throw new DomainException('Faltan los días de la semana.');
    return implode(',', array_filter(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'], static fn($day) => isset($result[$day])));
}

function programClassKey(array $class): string
{
    return json_encode([programKey($class['instalacion']), programKey($class['actividad']), programKey($class['grupo'] ?? ''),
        $class['tipo_control'], programDays($class['dias_semana']), programTime($class['hora_inicio']), programTime($class['hora_fin']),
        $class['fecha_inicio'], $class['fecha_fin']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

/** Validate the entire list before planning; never save only the valid rows. */
function programNormalize(array $rows, string $mode): array
{
    if (!$rows || count($rows) > 20000) throw new DomainException('El listado debe contener entre 1 y 20.000 filas.');
    $classes = [];
    foreach ($rows as $index => $row) {
        try {
            if (!is_array($row)) throw new DomainException('Fila inválida.');
            $raw = programKey($row['tipo_control'] ?? '');
            if (!in_array($raw, ['', 'a', 'aforo', 'asistencia'], true)) throw new DomainException('Tipo de control desconocido.');
            $class = [
                'instalacion' => programText($row['instalacion'] ?? ''), 'actividad' => programText($row['actividad'] ?? ''),
                'grupo' => programText($row['grupo'] ?? '') ?: null,
                'tipo_control' => $mode === 'aforo' || in_array($raw, ['a', 'aforo'], true) ? 'aforo' : 'asistencia',
                'fecha_inicio' => programDate($row['fecha_inicio'] ?? ''), 'fecha_fin' => programDate($row['fecha_fin'] ?? ''),
                'hora_inicio' => programTime($row['hora_inicio'] ?? ''), 'hora_fin' => programTime($row['hora_fin'] ?? ''),
                'dias_semana' => programDays($row['dias_semana'] ?? ''), 'personas' => [],
            ];
            if ($class['instalacion'] === '' || $class['actividad'] === '' || !$class['fecha_inicio']) throw new DomainException('Faltan instalación, actividad o fecha de inicio.');
            foreach (['instalacion', 'actividad', 'grupo'] as $field) {
                if (mb_strlen($class[$field] ?? '') > 150) throw new DomainException('Un nombre supera 150 caracteres.');
            }
            if ($class['fecha_fin'] && $class['fecha_fin'] < $class['fecha_inicio']) throw new DomainException('La fecha de fin es anterior al inicio.');
            if (($class['hora_inicio'] === null) !== ($class['hora_fin'] === null)) throw new DomainException('Indica ambas horas o deja las dos vacías.');
            if ($class['hora_fin'] !== null && $class['hora_fin'] <= $class['hora_inicio']) throw new DomainException('La hora de fin debe ser posterior al inicio.');
            $key = programClassKey($class);
            if (!isset($classes[$key])) $classes[$key] = $class;
            if ($class['tipo_control'] === 'asistencia') {
                $person = ['nombre' => programText($row['nombre'] ?? ''), 'apellidos' => programText($row['apellidos'] ?? '')];
                if (!$person['nombre']) throw new DomainException('Falta el nombre del participante.');
                if (mb_strlen($person['nombre']) > 100 || mb_strlen($person['apellidos']) > 150) throw new DomainException('El nombre del participante es demasiado largo.');
                $personKey = rosterKey($person['nombre'], $person['apellidos']);
                if (isset($classes[$key]['personas'][$personKey])) throw new DomainException('Participante repetido en la misma clase.');
                $classes[$key]['personas'][$personKey] = $person;
            }
        } catch (DomainException $e) { throw new DomainException('Fila ' . ($index + 2) . ': ' . $e->getMessage()); }
    }
    return $classes;
}

/** Include IDs and inactive records for safe reactivation and stale-preview detection. */
function programSnapshot(PDO $pdo, int $centerId): array
{
    $stmt = $pdo->prepare('SELECT id, nombre, activo FROM instalaciones WHERE centro_id = ? ORDER BY id');
    $stmt->execute([$centerId]);
    $installations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare('SELECT a.*, i.nombre AS instalacion FROM actividades a JOIN instalaciones i ON i.id = a.instalacion_id WHERE i.centro_id = ? ORDER BY a.id');
    $stmt->execute([$centerId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare('SELECT ins.id, ins.actividad_id, ins.nombre, ins.apellidos, ins.activo, ins.alta_at, ins.baja_at FROM inscritos ins JOIN actividades a ON a.id = ins.actividad_id JOIN instalaciones i ON i.id = a.instalacion_id WHERE i.centro_id = ? ORDER BY ins.id');
    $stmt->execute([$centerId]);
    return ['ediciones' => editionList($pdo, $centerId), 'instalaciones' => $installations, 'actividades' => $activities, 'personas' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

function programEditionCandidates(array $editions, array $period, array $classes, array $activities): array
{
    $exact = [];
    foreach ($activities as $activity) {
        $class = $activity;
        $class['actividad'] = $activity['nombre'];
        try { $key = programClassKey($class); } catch (DomainException $e) { continue; }
        if (isset($classes[$key]) && $activity['edicion_id']) $exact[(int) $activity['edicion_id']] = true;
    }
    if ($exact) return array_values(array_filter($editions, static fn($e) => isset($exact[$e['id']])));
    return array_values(array_filter($editions, static function ($e) use ($period) {
        if (!$e['fecha_inicio']) return false;
        // An old course without an end must not absorb all subsequent renewals.
        if ($e['fecha_fin'] && $period['fecha_inicio'] > $e['fecha_fin']) return false;
        if ($period['fecha_fin'] && $e['fecha_inicio'] > $period['fecha_fin']) return false;
        if (!$e['fecha_fin'] || !$period['fecha_fin']) return substr($e['fecha_inicio'], 0, 4) === substr($period['fecha_inicio'], 0, 4);
        return $e['fecha_inicio'] <= $period['fecha_fin'] && $period['fecha_inicio'] <= $e['fecha_fin'];
    }));
}

function programPlan(array $snapshot, array $classes, string $mode, string $scope, $requestedEdition = null, string $destination = 'auto'): array
{
    if (!in_array($destination, ['auto', 'actual', 'nueva'], true)) throw new DomainException('Destino de la carga inválido.');
    if ($destination === 'nueva') $requestedEdition = 0;
    elseif ($destination === 'actual' && ($requestedEdition === null || $requestedEdition === '')) {
        // Ordinary uploads stay in the current edition even when their dates do not overlap.
        $requestedEdition = editionDefault($snapshot['ediciones']) ?? 0;
    }
    $period = editionPeriod(array_values($classes));
    $candidates = programEditionCandidates($snapshot['ediciones'], $period, $classes, $snapshot['actividades']);
    $editionId = null;
    if ($requestedEdition !== null && $requestedEdition !== '') {
        $parsed = filter_var($requestedEdition, FILTER_VALIDATE_INT);
        if ($parsed === false || $parsed < 0) throw new DomainException('Edición inválida.');
        if ($parsed > 0) {
            if (!in_array($parsed, array_column($snapshot['ediciones'], 'id'), true)) throw new DomainException('La edición no pertenece a este centro.');
            $editionId = $parsed;
        }
    } elseif (count($candidates) > 1) {
        return ['necesita_edicion' => true, 'ediciones' => $snapshot['ediciones'], 'periodo' => $period];
    } elseif ($candidates) { $editionId = $candidates[0]['id']; }

    $installationMap = [];
    foreach ($snapshot['instalaciones'] as $installation) {
        $key = programKey($installation['nombre']);
        if (isset($installationMap[$key])) throw new DomainException('Hay instalaciones con el mismo nombre en el centro. Revísalas antes de importar.');
        $installationMap[$key] = $installation;
    }
    $activityMap = $inScope = [];
    foreach ($snapshot['actividades'] as $activity) {
        if ($editionId === null || (int) $activity['edicion_id'] !== $editionId) continue;
        if ($mode === 'aforo' && $activity['tipo_control'] !== 'aforo') continue;
        $inScope[(int) $activity['id']] = $activity;
        $class = $activity;
        $class['actividad'] = $activity['nombre'];
        try { $key = programClassKey($class); }
        catch (DomainException $e) { $key = 'legacy:' . $activity['id']; }
        if (isset($activityMap[$key])) throw new DomainException('Hay clases duplicadas en la edición. Revísalas antes de importar.');
        $activityMap[$key] = $activity;
    }
    $peopleMap = [];
    foreach ($snapshot['personas'] as $person) {
        if (!isset($inScope[(int) $person['actividad_id']])) continue;
        $key = rosterKey($person['nombre'], $person['apellidos']);
        if (isset($peopleMap[$person['actividad_id']][$key])) throw new DomainException('Hay participantes duplicados en una actividad de la edición.');
        $peopleMap[$person['actividad_id']][$key] = $person;
    }
    $counts = ['instalaciones_creadas' => 0, 'instalaciones_reutilizadas' => 0, 'instalaciones_reactivadas' => 0,
        'actividades_creadas' => 0, 'actividades_reutilizadas' => 0, 'actividades_reactivadas' => 0, 'actividades_retiradas' => 0,
        'participantes_creados' => 0, 'participantes_mantenidos' => 0, 'participantes_reactivados' => 0,
        'participantes_desactivados' => 0, 'clases_aforo_procesadas' => 0, 'errores' => []];
    $usedInstallations = $usedActivities = $deactivate = [];
    $deactivationNames = [];
    foreach ($classes as $key => &$class) {
        $installationKey = programKey($class['instalacion']);
        $class['instalacion_id'] = isset($installationMap[$installationKey]) ? (int) $installationMap[$installationKey]['id'] : null;
        if (!isset($usedInstallations[$installationKey])) {
            $usedInstallations[$installationKey] = true;
            $counts[$class['instalacion_id'] ? 'instalaciones_reutilizadas' : 'instalaciones_creadas']++;
            if ($class['instalacion_id'] && !(int) $installationMap[$installationKey]['activo']) $counts['instalaciones_reactivadas']++;
        }
        $existing = $activityMap[$key] ?? null;
        $class['id'] = $existing ? (int) $existing['id'] : null;
        $counts[$existing ? 'actividades_reutilizadas' : 'actividades_creadas']++;
        if ($existing) {
            $usedActivities[$class['id']] = true;
            if ($existing['retirada_at']) $counts['actividades_reactivadas']++;
        }
        if ($class['tipo_control'] === 'aforo') $counts['clases_aforo_procesadas']++;
        $existingPeople = $class['id'] ? ($peopleMap[$class['id']] ?? []) : [];
        foreach ($class['personas'] as $personKey => &$person) {
            $match = $existingPeople[$personKey] ?? null;
            $person['id'] = $match ? (int) $match['id'] : null;
            $person['accion'] = !$match ? 'nuevo' : ((int) $match['activo'] === 1 ? 'mantenido' : 'reactivado');
            $counts[['nuevo' => 'participantes_creados', 'mantenido' => 'participantes_mantenidos', 'reactivado' => 'participantes_reactivados'][$person['accion']]]++;
        }
        unset($person);
        if ($scope === 'complete' && $class['tipo_control'] === 'asistencia') {
            foreach ($existingPeople as $personKey => $person) {
                if ((int) $person['activo'] === 1 && !isset($class['personas'][$personKey])) {
                    $deactivate[(int) $person['id']] = true;
                    $deactivationNames[] = trim($person['nombre'] . ' ' . $person['apellidos']);
                }
            }
        }
    }
    unset($class);
    $retire = $retirementNames = [];
    if ($scope === 'complete') {
        foreach ($inScope as $id => $activity) {
            if (!isset($usedActivities[$id]) && !$activity['retirada_at']) {
                $retire[] = $id;
                $retirementNames[] = $activity['nombre'] . ($activity['grupo'] ? ' · ' . $activity['grupo'] : '') . ' · ' . $activity['instalacion'];
                foreach ($peopleMap[$id] ?? [] as $person) {
                    if ((int) $person['activo'] === 1) {
                        $deactivate[(int) $person['id']] = true;
                        $deactivationNames[] = trim($person['nombre'] . ' ' . $person['apellidos']);
                    }
                }
            }
        }
    }
    $counts['actividades_retiradas'] = count($retire);
    $counts['participantes_desactivados'] = count($deactivate);
    if ($scope === 'append' || $mode === 'aforo') {
        $remaining = array_filter($snapshot['actividades'], static fn($a) => $editionId !== null && (int) $a['edicion_id'] === $editionId && !$a['retirada_at'] && !in_array((int) $a['id'], $retire, true));
        $period = editionPeriod(array_merge(array_values($classes), array_values($remaining)));
    }
    $fingerprint = hash('sha256', json_encode([$snapshot, $classes, $mode, $scope, $editionId], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    return ['necesita_edicion' => false, 'edicion_id' => $editionId, 'nueva_edicion' => $editionId === null,
        'nombre_edicion' => editionLabel($period), 'periodo' => $period, 'stats' => $counts, 'ediciones' => $snapshot['ediciones'], 'fingerprint' => $fingerprint,
        'retirement_names' => array_slice($retirementNames, 0, 8), 'deactivation_names' => array_slice($deactivationNames, 0, 8),
        'classes' => $classes, 'retire' => $retire, 'deactivate' => array_keys($deactivate)];
}

/** Caller owns the transaction and center lock. No historical records are deleted. */
function programApply(PDO $pdo, int $centerId, array $plan, ?string $today = null): int
{
    $today = $today ?? date('Y-m-d');
    $editionId = $plan['edicion_id'] ?? editionCreate($pdo, $centerId, $plan['periodo']);
    $installations = [];
    foreach ($plan['classes'] as $class) {
        $installationKey = programKey($class['instalacion']);
        if (!isset($installations[$installationKey])) {
            $id = $class['instalacion_id'];
            if (!$id) {
                $pdo->prepare('INSERT INTO instalaciones (nombre, centro_id, activo) VALUES (?, ?, 1)')->execute([$class['instalacion'], $centerId]);
                $id = (int) $pdo->lastInsertId();
            } else { $pdo->prepare('UPDATE instalaciones SET activo = 1 WHERE id = ?')->execute([$id]); }
            $installations[$installationKey] = $id;
        }
        $activityId = $class['id'];
        if (!$activityId) {
            $schedule = str_replace(',', ' y ', $class['dias_semana']);
            if ($class['hora_inicio']) $schedule .= ' ' . substr($class['hora_inicio'], 0, 5) . '-' . substr($class['hora_fin'], 0, 5);
            $pdo->prepare('INSERT INTO actividades (nombre, grupo, tipo_control, horario, dias_semana, hora_inicio, hora_fin, instalacion_id, fecha_inicio, fecha_fin, edicion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$class['actividad'], $class['grupo'], $class['tipo_control'], $schedule, $class['dias_semana'], $class['hora_inicio'], $class['hora_fin'], $installations[$installationKey], $class['fecha_inicio'], $class['fecha_fin'], $editionId]);
            $activityId = (int) $pdo->lastInsertId();
        } else { $pdo->prepare('UPDATE actividades SET retirada_at = NULL WHERE id = ?')->execute([$activityId]); }
        foreach ($class['personas'] as $person) {
            // New classes start with their annual roster; changes to existing ones start today.
            $start = !$class['id'] ? $class['fecha_inicio'] : max($class['fecha_inicio'], $today);
            if ($person['accion'] === 'nuevo') rosterInsert($pdo, $activityId, $person['nombre'], $person['apellidos'], $start);
            elseif ($person['accion'] === 'reactivado') rosterActivate($pdo, $person['id'], $start);
        }
    }
    foreach ($plan['deactivate'] as $id) {
        $stmt = $pdo->prepare('SELECT MAX(inicio) FROM inscrito_vigencias WHERE inscrito_id = ? AND fin IS NULL');
        $stmt->execute([$id]);
        rosterDeactivate($pdo, $id, max($today, (string) $stmt->fetchColumn()));
    }
    foreach ($plan['retire'] as $id) $pdo->prepare('UPDATE actividades SET retirada_at = ? WHERE id = ?')->execute([$today, $id]);
    editionRecalculate($pdo, $editionId);
    return $editionId;
}

function programPreviewResponse(array $plan): array
{
    unset($plan['classes'], $plan['retire'], $plan['deactivate']);
    return ['success' => true, 'preview' => true] + $plan;
}
