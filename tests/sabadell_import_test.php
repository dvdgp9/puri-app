<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/sabadell_import.php';
require_once __DIR__ . '/../includes/sabadell_permissions.php';

$checks = 0;
function sbCheck(bool $condition, string $message): void {
    global $checks; $checks++;
    if (!$condition) throw new RuntimeException($message);
}
function sbReject(callable $fn, string $message, ?int $code = null): void {
    try { $fn(); } catch (DomainException $e) { sbCheck($code === null || $e->getCode() === $code, $message); return; }
    throw new RuntimeException($message);
}
function sbPoint(float $x, float $y, string $text): array { return [[1, 0, 0, 1, $x, $y], $text]; }
function sbPage(array $names = ['PRUEBA SEGUNDO ANA', 'PRUEBA SEGUNDO ANA']): array {
    $items = [sbPoint(40, 540, "Tipus d'Activitat:"), sbPoint(120, 540, 'Ioga'), sbPoint(340, 540, 'Professor:'), sbPoint(440, 540, 'Docente'),
        sbPoint(700, 540, 'Dl, Dx'), sbPoint(40, 525, 'Equipament: Grup / Torn i horari:'), sbPoint(120, 525, 'Sala municipal'),
        sbPoint(440, 525, 'I-101 09.15 a 10.15'), sbPoint(34, 505, 'Cognoms i nom Telèfons'), sbPoint(34, 120, 'En compliment de la normativa')];
    foreach ($names as $i => $name) array_push($items, sbPoint(17, 485 - $i * 15, (string) ($i + 1)), sbPoint(26, 485 - $i * 15, $name), sbPoint(235, 485 - $i * 15, '600000001-600000001'), sbPoint(780, 485 - $i * 15, '01/01/1970'));
    return $items;
}
$page = sabadellParsePage(array_reverse(sbPage()), 4);
sbCheck(count($page['participants']) === 2 && $page['grupo'] === 'I-101', 'Parser por posición y duplicados conservados.');
sbCheck($page['dias_semana'] === 'Lunes,Miércoles' && $page['hora_inicio'] === '09:15:00', 'Días catalanes y horas.');
sbCheck($page['participants'][0]['id'] === 'p4-1' && !isset($page['participants'][0]['telefono']), 'Identificadores de origen sin columnas personales adicionales.');
$missing = sbPage(); unset($missing[5]);
sbReject(fn() => sabadellParsePage($missing, 1), 'No se admite una página sin instalación.');
$missing = sbPage(); $missing[14][1] = '3';
sbReject(fn() => sabadellParsePage($missing, 1), 'No se omiten filas no numeradas consecutivamente.');
$missing = sbPage(); $missing[] = sbPoint(26, 450, 'NOMBRE SIN NUMERAR');
sbReject(fn() => sabadellParsePage($missing, 1), 'No se omite texto sin interpretar.');
sbReject(fn() => sabadellDays('Dm, ZZ'), 'No se adivinan días.');

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE admins (id INTEGER PRIMARY KEY, role TEXT, puede_importar_sabadell INTEGER DEFAULT 0);
CREATE TABLE admin_asignaciones (admin_id INTEGER, centro_id INTEGER);
CREATE TABLE centros (id INTEGER PRIMARY KEY, nombre TEXT, activo INTEGER DEFAULT 1);
CREATE TABLE ediciones (id INTEGER PRIMARY KEY AUTOINCREMENT, centro_id INTEGER, fecha_inicio TEXT, fecha_fin TEXT);
CREATE TABLE instalaciones (id INTEGER PRIMARY KEY AUTOINCREMENT, centro_id INTEGER, nombre TEXT, activo INTEGER DEFAULT 1);
CREATE TABLE actividades (id INTEGER PRIMARY KEY AUTOINCREMENT, instalacion_id INTEGER, edicion_id INTEGER, nombre TEXT, grupo TEXT, tipo_control TEXT, horario TEXT, dias_semana TEXT, hora_inicio TEXT, hora_fin TEXT, fecha_inicio TEXT, fecha_fin TEXT, retirada_at TEXT);
CREATE TABLE inscritos (id INTEGER PRIMARY KEY AUTOINCREMENT, actividad_id INTEGER, nombre TEXT, apellidos TEXT, activo INTEGER, alta_at TEXT, baja_at TEXT);
CREATE TABLE inscrito_vigencias (id INTEGER PRIMARY KEY AUTOINCREMENT, inscrito_id INTEGER, inicio TEXT, fin TEXT);
CREATE TABLE asistencias (id INTEGER PRIMARY KEY, usuario_id INTEGER, fecha TEXT);
CREATE TABLE evaluaciones (id INTEGER PRIMARY KEY, actividad_id INTEGER, resultado TEXT);
INSERT INTO admins VALUES (1,"admin",0),(2,"admin",1),(3,"superadmin",0);
INSERT INTO centros VALUES (1,"Centro de prueba",1),(2,"Otro centro",1),(3,"Centro inactivo",0);
INSERT INTO admin_asignaciones VALUES (1,1),(2,1);');
sbCheck(!sabadellCanImport($pdo, 1) && sabadellCanImport($pdo, 2), 'El permiso es optativo.');
sbCheck(sabadellCanImport($pdo, 3), 'El superadmin puede gestionar y comprobar la función.');
sbReject(fn() => sabadellRequireAccess($pdo, 1, 1), 'Sin permiso no puede leer/revisar/guardar.', 403);
sbReject(fn() => sabadellRequireAccess($pdo, 2, 2), 'El permiso no permite usar centros ajenos.', 403);
sbReject(fn() => sabadellRequireAccess($pdo, 3, 3), 'El centro inactivo no se puede importar.', 404);
sabadellRequireAccess($pdo, 2, 1);
$pdo->exec('UPDATE admins SET puede_importar_sabadell = 0 WHERE id = 2');
sbReject(fn() => sabadellRequireAccess($pdo, 2, 1), 'Revocación inmediata sin renovar la sesión.', 403);
$pdo->exec('UPDATE admins SET puede_importar_sabadell = 1 WHERE id = 2');
sbReject(fn() => sabadellPermissionValue(['puede_importar_sabadell' => 'false']), 'El permiso requiere booleano.');
sbCheck(sabadellPermissionValue([]) === null, 'Editar otros datos conserva el permiso existente.');

$draft = ['id' => 'draft-one', 'groups' => [$page], 'source_count' => 2, 'admin_id' => 2, 'center_id' => 1, 'expires' => time() + 300];
$context = sabadellContext(programSnapshot($pdo, 1), $draft, []);
$group = $context['groups'][0]; $group['confirmed'] = true;
foreach ($group['participants'] as &$p) $p['exclude'] = false; unset($p);
$input = ['destination' => 'actual', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2027-06-30', 'groups' => [$group]];
sbReject(fn() => sabadellPlan(programSnapshot($pdo, 1), $draft, $input), 'Los duplicados no se eliminan automáticamente.');
$input['groups'][0]['participants'][1]['exclude'] = true;
$before = programSnapshot($pdo, 1);
$plan = sabadellPlan($before, $draft, $input);
sbCheck($plan['excluded_count'] === 1 && $plan['stats']['participantes_creados'] === 1, 'Se contabilizan las exclusiones explícitas.');
sbCheck(programSnapshot($pdo, 1) === $before, 'La revisión no escribe.');
$pdo->beginTransaction(); $edition = programApply($pdo, 1, $plan, '2026-09-01'); $pdo->commit();
$snapshot = programSnapshot($pdo, 1);
$activity = (int) $snapshot['actividades'][0]['id']; $person = (int) $snapshot['personas'][0]['id'];
$pdo->exec("INSERT INTO asistencias VALUES (1,$person,'2026-09-02'); INSERT INTO evaluaciones VALUES(1,$activity,'Historial');");
// An unrelated group and a missing person in the included group.
$other = ['nombre' => 'Otra', 'apellidos' => 'Persona', 'instalacion' => 'Otra sala', 'actividad' => 'Pilates', 'grupo' => 'PL-102', 'dias_semana' => 'Martes', 'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-07-30'];
$pdo->beginTransaction(); programApply($pdo, 1, programPlan(programSnapshot($pdo, 1), programNormalize([$other], 'participantes'), 'participantes', 'append', $edition)); $pdo->commit();
$missingPerson = rosterInsert($pdo, $activity, 'Baja', 'Mensual', '2026-09-01');
$context = sabadellContext(programSnapshot($pdo, 1), $draft, ['edicion_id' => $edition]);
$group = $context['groups'][0]; $group['confirmed'] = true;
foreach ($group['participants'] as $i => &$p) $p['exclude'] = $i === 1; unset($p);
$input['groups'] = [$group]; $input['edicion_id'] = $edition;
$plan = sabadellPlan(programSnapshot($pdo, 1), $draft, $input);
sbCheck($plan['stats']['actividades_creadas'] === 0 && $plan['stats']['participantes_creados'] === 0, 'Reimportar no duplica.');
sbCheck($plan['stats']['participantes_desactivados'] === 1 && !$plan['retire'], 'Bajas solo dentro del grupo incluido.');
sbCheck($plan['periodo']['fecha_inicio'] === '2026-08-01' && $plan['periodo']['fecha_fin'] === '2027-07-30', 'El período incluye las clases ausentes.');
$pdo->beginTransaction(); programApply($pdo, 1, $plan, '2026-10-13'); $pdo->commit();
sbCheck((int) $pdo->query("SELECT activo FROM inscritos WHERE id=$missingPerson")->fetchColumn() === 0, 'La baja cierra la inscripción.');
sbCheck((int) $pdo->query('SELECT COUNT(*) FROM actividades WHERE retirada_at IS NULL')->fetchColumn() === 2, 'Los grupos ausentes se mantienen.');
sbCheck((int) $pdo->query('SELECT COUNT(*) FROM asistencias')->fetchColumn() === 1 && $pdo->query('SELECT resultado FROM evaluaciones')->fetchColumn() === 'Historial', 'El historial permanece intacto.');
$repeat = sabadellPlan(programSnapshot($pdo, 1), $draft, $input);
sbCheck($repeat['stats']['participantes_desactivados'] === 0 && $repeat['stats']['participantes_creados'] === 0, 'Repetición sin cambios no aplica altas ni bajas.');
$unrelated = programSnapshot($pdo, 1);
$unrelated['personas'][] = $unrelated['personas'][1];
sbCheck(sabadellPlan($unrelated, $draft, $input)['stats']['participantes_mantenidos'] === 1, 'Los duplicados en otros grupos no bloquean una carga parcial.');
$legacy = $unrelated['actividades'][0]; $legacy['dias_semana'] = '';
sbCheck(!sabadellSameMetadata($draft['groups'][0], $legacy), 'Las clases antiguas sin horario se pueden resolver en la revisión.');
$changedInput = $input; $changedInput['groups'][0]['participants'][0]['nombre'] = 'Ana María';
sbCheck(sabadellPlan(programSnapshot($pdo, 1), $draft, $changedInput)['fingerprint'] !== $repeat['fingerprint'], 'La corrección invalida la revisión.');
$unconfirmed = $input; $unconfirmed['groups'][0]['confirmed'] = false;
sbCheck(sabadellPlan(programSnapshot($pdo, 1), $draft, $unconfirmed)['stats'] === $repeat['stats'], 'Se puede importar sin marcar revisado cuando no hay incidencias.');
unset($unconfirmed['groups'][0]['confirmed']);
sbCheck(sabadellPlan(programSnapshot($pdo, 1), $draft, $unconfirmed)['stats'] === $repeat['stats'], 'La confirmación manual no es un requisito de la API.');
$omitted = $input; array_pop($omitted['groups'][0]['participants']);
sbReject(fn() => sabadellPlan(programSnapshot($pdo, 1), $draft, $omitted), 'No se omiten filas del documento.');
$empty = $input; foreach ($empty['groups'][0]['participants'] as &$p) $p['exclude'] = true; unset($p);
sbReject(fn() => sabadellPlan(programSnapshot($pdo, 1), $draft, $empty), 'No se vacía accidentalmente un grupo.');
$changed = $draft; $changed['groups'][0]['hora_inicio'] = '11:00:00'; $changed['groups'][0]['hora_fin'] = '12:00:00';
$context = sabadellContext(programSnapshot($pdo, 1), $changed, ['edicion_id' => $edition]);
sbCheck($context['groups'][0]['action'] === '', 'Un cambio de horario necesita resolver la coincidencia.');
$changeInput = $input; $changeInput['groups'][0] = $context['groups'][0]; $changeInput['groups'][0]['confirmed'] = true;
foreach ($changeInput['groups'][0]['participants'] as $i => &$p) $p['exclude'] = $i === 1; unset($p);
sbReject(fn() => sabadellPlan(programSnapshot($pdo, 1), $changed, $changeInput), 'No se crea otra clase automáticamente.');
$changeInput['groups'][0]['action'] = 'replace';
$changedPlan = sabadellPlan(programSnapshot($pdo, 1), $changed, $changeInput);
sbCheck($changedPlan['retire'] === [$activity] && $changedPlan['stats']['actividades_creadas'] === 1, 'La sustitución conserva la clase antigua como retirada.');
$pdo->beginTransaction(); programApply($pdo, 1, $changedPlan, '2026-10-13'); $pdo->commit();
sbCheck((int) $pdo->query('SELECT COUNT(*) FROM asistencias')->fetchColumn() === 1, 'Sustituir horario conserva asistencias antiguas.');
$renewal = $input; $renewal['destination'] = 'nueva'; $renewal['groups'][0]['target_id'] = 0; $renewal['groups'][0]['action'] = 'new';
$newPlan = sabadellPlan(programSnapshot($pdo, 1), $draft, $renewal);
sbCheck($newPlan['nueva_edicion'] && !$newPlan['retire'] && !$newPlan['deactivate'], 'La renovación no altera el curso anterior.');
sbReject(fn() => sabadellDraft(['sabadell_draft' => $draft], 'draft-one', 2, 2), 'El borrador no se usa en otro centro.', 409);
$expired = $draft; $expired['expires'] = time() - 1;
sbReject(fn() => sabadellDraft(['sabadell_draft' => $expired], 'draft-one', 2, 1), 'El borrador caduca.', 409);
sbReject(fn() => sabadellDraft(['sabadell_draft' => $draft], 'draft-one', 1, 1), 'El borrador pertenece a su cuenta.', 409);
fwrite(STDOUT, "[OK] $checks comprobaciones de PDF, permisos, revisión, carga mensual y conservación del historial.\n");
