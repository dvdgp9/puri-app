<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/program_import.php';
set_error_handler(static function($severity, $message) { throw new RuntimeException($message); });

$checks = 0;
if (editionLabel(['fecha_inicio' => '2026-09-01', 'fecha_fin' => '2027-06-30']) !== 'Curso 2026–2027') throw new RuntimeException('Nombre de edición incorrecto.');
function verify(bool $condition, string $message): void {
    global $checks; $checks++;
    if (!$condition) throw new RuntimeException($message);
}
function rejected(callable $action, string $message): void {
    try { $action(); } catch (DomainException $expected) { verify(true, $message); return; }
    verify(false, $message);
}
function row(array $overrides = []): array {
    return array_replace(['nombre' => 'Alicia', 'apellidos' => 'Cuervo', 'instalacion' => 'Piscina municipal', 'actividad' => 'Natación',
        'grupo' => '1', 'fecha_inicio' => '2026-09-03', 'fecha_fin' => '2027-06-30', 'hora_inicio' => '9:15', 'hora_fin' => '10:15',
        'dias_semana' => 'Lunes, Miércoles', 'tipo_control' => ''], $overrides);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
$pdo->exec('CREATE TABLE centros (id INTEGER PRIMARY KEY, nombre TEXT, activo INTEGER DEFAULT 1);
CREATE TABLE ediciones (id INTEGER PRIMARY KEY AUTOINCREMENT, centro_id INTEGER REFERENCES centros(id), fecha_inicio TEXT, fecha_fin TEXT);
CREATE TABLE instalaciones (id INTEGER PRIMARY KEY AUTOINCREMENT, centro_id INTEGER REFERENCES centros(id), nombre TEXT, activo INTEGER DEFAULT 1);
CREATE TABLE actividades (id INTEGER PRIMARY KEY AUTOINCREMENT, instalacion_id INTEGER REFERENCES instalaciones(id), edicion_id INTEGER REFERENCES ediciones(id), nombre TEXT, grupo TEXT, tipo_control TEXT, horario TEXT, dias_semana TEXT, hora_inicio TEXT, hora_fin TEXT, fecha_inicio TEXT, fecha_fin TEXT, retirada_at TEXT);
CREATE TABLE inscritos (id INTEGER PRIMARY KEY AUTOINCREMENT, actividad_id INTEGER REFERENCES actividades(id), nombre TEXT, apellidos TEXT, activo INTEGER DEFAULT 1, alta_at TEXT, baja_at TEXT);
CREATE TABLE inscrito_vigencias (id INTEGER PRIMARY KEY AUTOINCREMENT, inscrito_id INTEGER REFERENCES inscritos(id), inicio TEXT, fin TEXT);
CREATE TABLE asistencias (id INTEGER PRIMARY KEY, actividad_id INTEGER, usuario_id INTEGER, fecha TEXT, asistio INTEGER);
CREATE TABLE evaluaciones (id INTEGER PRIMARY KEY, actividad_id INTEGER, resultado TEXT);
INSERT INTO centros(id, nombre) VALUES(1, "Centro Gijón"), (2, "Otro centro");');
$oldRows = [row(['fecha_inicio' => '2025-09-01', 'fecha_fin' => '2026-06-30']), row(['nombre' => 'Lucía', 'apellidos' => 'Valdés', 'instalacion' => 'Pabellón antiguo', 'actividad' => 'Gimnasia', 'fecha_inicio' => '2025-10-01', 'fecha_fin' => '2026-06-20'])];
$old = programPlan(programSnapshot($pdo, 1), programNormalize($oldRows, 'participantes'), 'participantes', 'complete');
$pdo->beginTransaction(); $oldId = programApply($pdo, 1, $old, '2025-09-01'); $pdo->commit();
verify(editionSelector(editionList($pdo, 1), $oldId) === '', 'No debe aparecer un selector con una sola edición.');
$oldSnapshot = programSnapshot($pdo, 1);
$oldPerson = (int) $oldSnapshot['personas'][0]['id'];
$oldActivity = (int) $oldSnapshot['actividades'][0]['id'];
$pdo->exec("INSERT INTO asistencias VALUES(1, $oldActivity, $oldPerson, '2025-09-08', 1); INSERT INTO evaluaciones VALUES(1, $oldActivity, 'Conservado');");

$newRows = [row(), row(['nombre' => 'Mateo', 'apellidos' => 'Artime']),
    row(['instalacion' => 'Sala nueva', 'actividad' => 'Yoga', 'nombre' => 'Nerea', 'apellidos' => 'Mieres', 'fecha_inicio' => '2026-10-01']),
    row(['nombre' => '', 'apellidos' => '', 'instalacion' => 'Sala retos', 'actividad' => 'Retos', 'tipo_control' => 'A', 'fecha_inicio' => '2026-09-10', 'fecha_fin' => '2027-07-02'])];
$before = programSnapshot($pdo, 1);
$classes = programNormalize($newRows, 'participantes');
$new = programPlan($before, $classes, 'participantes', 'complete');
verify($new['nueva_edicion'], 'Una renovación debe crear una edición nueva.');
verify($new['periodo'] === ['fecha_inicio' => '2026-09-03', 'fecha_fin' => '2027-07-02'], 'El período usa el primer inicio y el último fin.');
verify($new['stats']['instalaciones_reutilizadas'] === 1 && $new['stats']['instalaciones_creadas'] === 2, 'Las instalaciones que continúan se reutilizan.');
verify($new['stats']['participantes_creados'] === 3 && $new['stats']['clases_aforo_procesadas'] === 1, 'Las clases de aforo no crean personas.');
verify($new['stats']['actividades_retiradas'] === 0 && $new['stats']['participantes_desactivados'] === 0, 'La renovación no da de baja nada de la edición anterior.');
verify(programSnapshot($pdo, 1) === $before, 'La revisión no escribe en la base de datos.');
verify(!isset(programPreviewResponse($new)['classes']), 'La respuesta de revisión no expone el plan interno.');
$pdo->beginTransaction(); $newId = programApply($pdo, 1, $new, '2026-10-01'); $pdo->commit();
verify($oldId !== $newId && count(editionList($pdo, 1)) === 2, 'Las ediciones tienen identidades separadas.');
verify(editionDefault(editionList($pdo, 1), '2026-10-01') === $newId, 'La edición vigente es la predeterminada.');
verify(str_contains(editionSelector(editionList($pdo, 1), $newId), 'select'), 'El selector aparece cuando hay dos ediciones.');
verify(editionDefault(editionList($pdo, 1), '2026-07-15') === $newId, 'Entre cursos se elige el próximo curso.');
verify(editionDefault(editionList($pdo, 1), '2028-01-01') === $newId, 'Sin un curso vigente ni futuro se elige el último.');
verify((int) $pdo->query('SELECT COUNT(*) FROM instalaciones')->fetchColumn() === 4, 'No se duplica la instalación reutilizada.');
verify((int) $pdo->query('SELECT COUNT(*) FROM asistencias')->fetchColumn() === 1 && $pdo->query('SELECT resultado FROM evaluaciones')->fetchColumn() === 'Conservado', 'Asistencias y evaluaciones antiguas permanecen intactas.');
verify($pdo->query("SELECT activo FROM inscritos WHERE id = $oldPerson")->fetchColumn() === 1, 'No se modifica la inscripción del año pasado.');
$repeat = programPlan(programSnapshot($pdo, 1), $classes, 'participantes', 'complete');
verify($repeat['edicion_id'] === $newId && !$repeat['nueva_edicion'], 'Volver a cargar el listado detecta su edición.');
verify($repeat['stats']['actividades_creadas'] === 0 && $repeat['stats']['participantes_creados'] === 0, 'Volver a cargarlo no duplica actividades ni personas.');
$pdo->beginTransaction(); programApply($pdo, 1, $repeat, '2026-10-01'); $pdo->commit();
verify((int) $pdo->query('SELECT COUNT(*) FROM inscritos')->fetchColumn() === 5, 'La segunda carga es idempotente.');

$changedRows = [$newRows[0], $newRows[3]];
$changed = programPlan(programSnapshot($pdo, 1), programNormalize($changedRows, 'participantes'), 'participantes', 'complete');
verify($changed['stats']['actividades_retiradas'] === 1 && $changed['stats']['participantes_desactivados'] === 2, 'El listado completo retira las actividades y personas ausentes solo de su edición.');
$pdo->beginTransaction(); programApply($pdo, 1, $changed, '2026-10-01'); $pdo->commit();
verify($pdo->query('SELECT COUNT(*) FROM actividades WHERE retirada_at IS NOT NULL')->fetchColumn() === 1, 'Las actividades se retiran sin borrarlas.');
verify((int) $pdo->query('SELECT COUNT(*) FROM actividades a WHERE ' . editionActivitySql($newId))->fetchColumn() === 2, 'La vista habitual oculta actividades retiradas.');
verify((int) $pdo->query('SELECT COUNT(*) FROM actividades a WHERE ' . editionActivitySql($newId, 'a', true))->fetchColumn() === 3, 'Los informes pueden consultar las actividades retiradas.');
verify((int) $pdo->query('SELECT COUNT(*) FROM actividades a WHERE ' . editionActivitySql($oldId))->fetchColumn() === 2, 'El histórico de la edición anterior no cambia.');
$installQuery = 'SELECT i.nombre FROM instalaciones i WHERE EXISTS (SELECT 1 FROM actividades a WHERE a.instalacion_id=i.id AND ' . editionActivitySql($newId) . ')';
$names = $pdo->query($installQuery)->fetchAll(PDO::FETCH_COLUMN);
verify(!in_array('Pabellón antiguo', $names, true) && !in_array('Sala nueva', $names, true), 'Las instalaciones que no participan no aparecen en el curso seleccionado.');
$reactivation = programPlan(programSnapshot($pdo, 1), $classes, 'participantes', 'complete');
verify($reactivation['stats']['participantes_reactivados'] === 2 && $reactivation['stats']['actividades_reactivadas'] === 1, 'Las reincorporaciones conservan su identidad.');
$pdo->beginTransaction(); programApply($pdo, 1, $reactivation, '2026-10-02'); $pdo->commit();
verify((int) $pdo->query('SELECT COUNT(*) FROM inscritos')->fetchColumn() === 5, 'Reactivar no crea registros duplicados.');
verify((int) $pdo->query('SELECT COUNT(*) FROM inscrito_vigencias WHERE fin IS NOT NULL')->fetchColumn() === 2, 'Las bajas conservan sus intervalos anteriores.');
$partial = programPlan(programSnapshot($pdo, 1), programNormalize([row(['nombre' => 'Vera', 'apellidos' => 'Llaneza'])], 'participantes'), 'participantes', 'append');
verify($partial['stats']['actividades_retiradas'] === 0 && $partial['stats']['participantes_desactivados'] === 0, 'Un listado parcial no da de baja ausentes.');
verify($partial['periodo']['fecha_fin'] === '2027-07-02', 'Un listado parcial conserva el período de las demás actividades.');
$capacity = programPlan(programSnapshot($pdo, 1), programNormalize([$newRows[3]], 'aforo'), 'aforo', 'complete');
verify($capacity['stats']['actividades_retiradas'] === 0 && $capacity['stats']['participantes_desactivados'] === 0, 'El modo aforo no retira actividades con participantes.');

$beforeFingerprint = programPlan(programSnapshot($pdo, 1), $classes, 'participantes', 'complete')['fingerprint'];
$pdo->exec("UPDATE inscritos SET apellidos='Cuervo Díaz' WHERE id=(SELECT MIN(id) FROM inscritos WHERE actividad_id IN (SELECT id FROM actividades WHERE edicion_id=$newId))");
$afterFingerprint = programPlan(programSnapshot($pdo, 1), $classes, 'participantes', 'complete')['fingerprint'];
verify($beforeFingerprint !== $afterFingerprint, 'Una modificación después de la revisión invalida su huella.');
rejected(fn() => programPlan(programSnapshot($pdo, 1), $classes, 'participantes', 'complete', 999), 'No se puede elegir una edición ajena.');
rejected(fn() => editionResolve($pdo, 2, $newId), 'La resolución de filtros comprueba el centro.');
rejected(fn() => programNormalize([], 'participantes'), 'Un listado vacío no puede vaciar una edición.');
rejected(fn() => programNormalize([row(), row()], 'participantes'), 'Los participantes repetidos se revisan antes de guardar.');
rejected(fn() => programNormalize([row(['fecha_inicio' => '2026-02-30'])], 'participantes'), 'Las fechas inexistentes no se normalizan silenciosamente.');
rejected(fn() => programNormalize([row(['fecha_fin' => '2026-01-01'])], 'participantes'), 'No se permite un fin anterior al inicio.');
rejected(fn() => programNormalize([row(['dias_semana' => 'Festivo'])], 'participantes'), 'Un día desconocido bloquea el listado.');
rejected(fn() => programNormalize([row(['hora_inicio' => '25:00'])], 'participantes'), 'Una hora inválida bloquea el listado.');
rejected(fn() => programNormalize([row(['hora_fin' => '08:00'])], 'participantes'), 'Una hora de fin anterior al inicio bloquea el listado.');
verify(editionPeriod([row(['fecha_fin' => null]), row()])['fecha_fin'] === null, 'Con una actividad sin fin no se inventa un fin del programa.');
verify(programDate('3/9/26') === '2026-09-03', 'Se reconocen fechas españolas y años abreviados.');
verify(programTime('18.30') === '18:30:00', 'Las horas de texto con punto se normalizan igual que las horas con dos puntos.');
rejected(fn() => programTime('25.30'), 'El formato con punto sigue rechazando horas fuera de rango.');
verify(programClassKey(programNormalize([row()], 'participantes')[array_key_first(programNormalize([row()], 'participantes'))]) === programClassKey(array_values(programNormalize([row(['hora_inicio' => '09:15:00', 'dias_semana' => 'Miércoles,Lunes'])], 'participantes'))[0]), 'Horas equivalentes y días desordenados reconocen la misma clase.');
$overlap = ['ediciones' => [['id' => 1, 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31'], ['id' => 2, 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2027-02-01']], 'actividades' => [], 'instalaciones' => [], 'personas' => []];
verify(programPlan($overlap, $classes, 'participantes', 'complete')['necesita_edicion'], 'Los períodos ambiguos piden una elección en la revisión.');
verify(programPlan($overlap, $classes, 'participantes', 'complete', 0)['nueva_edicion'], 'La revisión permite crear explícitamente una edición nueva.');
$open = [['id' => 1, 'fecha_inicio' => '2025-09-01', 'fecha_fin' => null]];
verify(programEditionCandidates($open, ['fecha_inicio' => '2026-09-01', 'fecha_fin' => null], [], []) === [], 'Un curso abierto anterior no absorbe todas las renovaciones.');
$closed = [['id' => 1, 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30']];
verify(programEditionCandidates($closed, ['fecha_inicio' => '2026-09-01', 'fecha_fin' => null], [], []) === [], 'Un curso abierto nuevo no actualiza un período cerrado disjunto del mismo año.');
verify(editionPeriod([['fecha_inicio' => null, 'fecha_fin' => null]])['fecha_inicio'] === null, 'El histórico sin fechas no recibe un inicio inventado.');
verify(editionLabel(['fecha_inicio' => null, 'fecha_fin' => null]) === 'Histórico sin fecha de inicio', 'Los cursos antiguos sin fechas tienen una etiqueta clara.');
verify(editionDefault([['id' => 99, 'fecha_inicio' => null, 'fecha_fin' => null], ['id' => 100, 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2027-06-30']], '2026-10-01') === 100, 'El histórico sin fecha no se impone al curso vigente.');
verify(programDays('Lunes y Miércoles / Viernes') === 'Lunes,Miércoles,Viernes', 'Se reconocen los separadores habituales de días.');
// SQL used in global counters must choose a different default per center.
$pdo->sqliteCreateFunction('CURDATE', static fn() => '2026-10-01');
verify((int) $pdo->query('SELECT COUNT(*) FROM actividades a JOIN instalaciones i ON i.id=a.instalacion_id WHERE ' . editionCurrentSql())->fetchColumn() === 3, 'Los contadores globales solo incluyen la edición vigente.');
$futureRows = [row(['fecha_inicio' => '2028-01-01', 'fecha_fin' => '2028-06-01'])];
$futurePlan = programPlan(programSnapshot($pdo, 1), programNormalize($futureRows, 'participantes'), 'participantes', 'complete');
$pdo->beginTransaction(); $futureId = programApply($pdo, 1, $futurePlan, '2026-10-01'); $pdo->commit();
$removeFuture = programPlan(programSnapshot($pdo, 1), programNormalize([row(['tipo_control' => 'A', 'nombre' => '', 'fecha_inicio' => '2028-01-01', 'fecha_fin' => '2028-06-01'])], 'participantes'), 'participantes', 'complete', $futureId);
$pdo->beginTransaction(); programApply($pdo, 1, $removeFuture, '2026-10-01'); $pdo->commit();
verify((int) $pdo->query('SELECT COUNT(*) FROM inscrito_vigencias WHERE fin < inicio')->fetchColumn() === 0, 'Las bajas de participantes futuros no crean vigencias invertidas.');
verify(editionDefault(editionList($pdo, 1), '2026-10-01') === $newId, 'Preparar la renovación futura no oculta el curso vigente.');

// An ordinary upload is explicitly different from a renewal, including disjoint dates.
$snapshot = programSnapshot($pdo, 1);
$targetId = editionDefault($snapshot['ediciones']);
$extraClasses = programNormalize([row(['actividad' => 'Taller adicional', 'fecha_inicio' => '2032-09-01', 'fecha_fin' => '2033-06-30'])], 'participantes');
$ordinary = programPlan($snapshot, $extraClasses, 'participantes', 'append', null, 'actual');
verify(!$ordinary['nueva_edicion'] && $ordinary['edicion_id'] === $targetId, 'Añadir datos conserva la edición actual aunque las fechas no coincidan.');
verify($ordinary['stats']['actividades_retiradas'] === 0 && $ordinary['stats']['participantes_desactivados'] === 0, 'La carga ordinaria mantiene a todos los ausentes.');
$editionCount = count($snapshot['ediciones']);
$existingPeople = $pdo->query('SELECT id,activo FROM inscritos ORDER BY id')->fetchAll();
$pdo->beginTransaction(); $ordinaryId = programApply($pdo, 1, $ordinary, '2026-10-01'); $pdo->commit();
verify($ordinaryId === $targetId && count(editionList($pdo, 1)) === $editionCount, 'Guardar una carga ordinaria no crea otra edición.');
$afterPeople = $pdo->query('SELECT id,activo FROM inscritos ORDER BY id')->fetchAll();
verify(array_slice($afterPeople, 0, count($existingPeople)) === $existingPeople, 'Guardar datos parciales conserva el estado de todas las inscripciones anteriores.');
$ordinaryRepeat = programPlan(programSnapshot($pdo, 1), $extraClasses, 'participantes', 'append', null, 'actual');
verify($ordinaryRepeat['stats']['actividades_creadas'] === 0 && $ordinaryRepeat['stats']['participantes_creados'] === 0, 'Repetir la carga ordinaria tampoco duplica datos.');
$explicitRenewal = programPlan(programSnapshot($pdo, 1), $classes, 'participantes', 'complete', $targetId, 'nueva');
verify($explicitRenewal['nueva_edicion'], 'Crear nueva edición separa el curso incluso cuando coinciden las fechas.');
verify($explicitRenewal['stats']['actividades_retiradas'] === 0 && $explicitRenewal['stats']['participantes_desactivados'] === 0, 'La renovación explícita no retira datos existentes.');
$existingUpdate = programPlan(programSnapshot($pdo, 1), $classes, 'participantes', 'complete', $oldId, 'actual');
verify($existingUpdate['edicion_id'] === $oldId && !$existingUpdate['nueva_edicion'], 'Se puede elegir otra edición existente para una actualización.');
$firstUpload = programPlan(programSnapshot($pdo, 2), $classes, 'participantes', 'append', null, 'actual');
verify($firstUpload['nueva_edicion'], 'Un centro sin ediciones recibe su primera edición en una carga ordinaria.');
rejected(fn() => programPlan($snapshot, $classes, 'participantes', 'append', null, 'incorrecto'), 'Un destino desconocido se rechaza.');
fwrite(STDOUT, "[OK] $checks comprobaciones de renovaciones, sincronización e historial.\n");
