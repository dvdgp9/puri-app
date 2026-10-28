<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/program_import.php';

$checks = 0;
function expectRoster(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) throw new RuntimeException($message);
}

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON;
CREATE TABLE centros (id INTEGER PRIMARY KEY, nombre TEXT, activo INTEGER DEFAULT 1);
CREATE TABLE ediciones (id INTEGER PRIMARY KEY AUTOINCREMENT, centro_id INTEGER REFERENCES centros(id), fecha_inicio TEXT, fecha_fin TEXT);
CREATE TABLE instalaciones (id INTEGER PRIMARY KEY AUTOINCREMENT, centro_id INTEGER REFERENCES centros(id), nombre TEXT, activo INTEGER DEFAULT 1);
CREATE TABLE actividades (id INTEGER PRIMARY KEY AUTOINCREMENT, instalacion_id INTEGER REFERENCES instalaciones(id), edicion_id INTEGER REFERENCES ediciones(id), nombre TEXT, grupo TEXT, tipo_control TEXT, horario TEXT, dias_semana TEXT, hora_inicio TEXT, hora_fin TEXT, fecha_inicio TEXT, fecha_fin TEXT, retirada_at TEXT);
CREATE TABLE inscritos (id INTEGER PRIMARY KEY AUTOINCREMENT, actividad_id INTEGER REFERENCES actividades(id), nombre TEXT, apellidos TEXT, activo INTEGER DEFAULT 1, alta_at TEXT, baja_at TEXT);
CREATE TABLE inscrito_vigencias (id INTEGER PRIMARY KEY AUTOINCREMENT, inscrito_id INTEGER REFERENCES inscritos(id), inicio TEXT, fin TEXT);
CREATE TABLE asistencias (id INTEGER PRIMARY KEY, actividad_id INTEGER, usuario_id INTEGER, fecha TEXT, asistio INTEGER);
CREATE TABLE evaluaciones (id INTEGER PRIMARY KEY, actividad_id INTEGER, resultado TEXT);
INSERT INTO centros (id, nombre) VALUES (1, "Centro de prueba");');

$rows = [];
foreach (['Ana', 'Bea'] as $name) $rows[] = [
    'nombre' => $name, 'apellidos' => 'Prueba', 'instalacion' => 'Sala municipal',
    'actividad' => 'Equilibrio', 'grupo' => 'E-101', 'fecha_inicio' => '2026-11-01',
    'fecha_fin' => '2027-06-30', 'dias_semana' => 'Lunes,Miércoles,Viernes',
    'hora_inicio' => '09:15', 'hora_fin' => '10:15',
];
$plan = programPlan(programSnapshot($pdo, 1), programNormalize($rows, 'participantes'), 'participantes', 'groups');
$pdo->beginTransaction();
$edition = programApply($pdo, 1, $plan, '2026-10-28');
$pdo->commit();
$activity = (int) $pdo->query('SELECT id FROM actividades')->fetchColumn();
$initial = $pdo->query('SELECT id FROM inscritos ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

// The same eligibility query used by the monitor and attendance writes.
function rosterEligible(PDO $pdo, int $activity, string $date): array
{
    $stmt = $pdo->prepare('SELECT i.id FROM inscritos i WHERE i.actividad_id = ?
        AND EXISTS (SELECT 1 FROM inscrito_vigencias v WHERE v.inscrito_id = i.id
                    AND v.inicio <= ? AND (v.fin IS NULL OR v.fin > ?)) ORDER BY i.id');
    $stmt->execute([$activity, $date, $date]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
function personState(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT * FROM inscritos WHERE id = ?');
    $stmt->execute([$id]);
    $person = $stmt->fetch();
    $stmt = $pdo->prepare('SELECT * FROM inscrito_vigencias WHERE inscrito_id = ? ORDER BY id');
    $stmt->execute([$id]);
    return [$person, $stmt->fetchAll()];
}

expectRoster(rosterEligible($pdo, $activity, '2026-10-28') === [], 'La importación de noviembre no debe anticipar las altas.');
$pdo->exec("UPDATE actividades SET fecha_inicio = '2026-10-01' WHERE id = $activity");
expectRoster(rosterEligible($pdo, $activity, '2026-10-28') === [], 'Cambiar únicamente la actividad reproduce el listado vacío de la captura.');

$later = rosterInsert($pdo, $activity, 'Celia', 'Prueba', '2026-11-15');
$inactive = rosterInsert($pdo, $activity, 'Dora', 'Prueba', '2026-11-01');
rosterDeactivate($pdo, $inactive, '2026-11-20');
$reactivated = rosterInsert($pdo, $activity, 'Eva', 'Prueba', '2026-09-01');
rosterDeactivate($pdo, $reactivated, '2026-10-01');
rosterActivate($pdo, $reactivated, '2026-11-01');
$pdo->exec("INSERT INTO actividades (id, instalacion_id, edicion_id, fecha_inicio, fecha_fin) VALUES (99, 1, $edition, '2026-11-01', '2027-06-30')");
$other = rosterInsert($pdo, 99, 'Fina', 'Prueba', '2026-11-01');
$preserved = [];
foreach ([$later, $inactive, $reactivated, $other] as $id) $preserved[$id] = personState($pdo, $id);
$pdo->exec("INSERT INTO asistencias VALUES (1, $activity, {$initial[0]}, '2026-11-02', 1);
INSERT INTO evaluaciones VALUES (1, $activity, 'Resultado conservado');");
$attendance = $pdo->query('SELECT * FROM asistencias')->fetchAll();
$evaluations = $pdo->query('SELECT * FROM evaluaciones')->fetchAll();

try {
    rosterAdvanceActivityStart($pdo, $activity, '2026-11-01', '2026-10-01');
    throw new RuntimeException('Se aceptó un ajuste fuera de una transacción.');
} catch (LogicException $expected) {
    expectRoster(true, 'El ajuste exige una transacción.');
}
$pdo->beginTransaction();
expectRoster(rosterAdvanceActivityStart($pdo, $activity, '2026-11-01', '2026-10-01') === 2, 'Solo se adelantan las dos altas iniciales.');
editionRecalculate($pdo, $edition);
$pdo->commit();
expectRoster(rosterEligible($pdo, $activity, '2026-10-28') === $initial, 'El monitor debe mostrar la lista inicial en octubre.');
expectRoster(rosterEligible($pdo, $activity, '2026-09-30') === [$reactivated], 'No se anticipa la lista inicial a septiembre y se conserva la vigencia histórica.');
expectRoster(rosterEligible($pdo, $activity, '2026-10-01') === $initial, 'La nueva fecha de alta es inclusiva.');
foreach ($initial as $id) {
    [$person, $intervals] = personState($pdo, (int) $id);
    expectRoster($person['alta_at'] === '2026-10-01' && $intervals[0]['inicio'] === '2026-10-01', 'El alta y la vigencia se mantienen sincronizadas.');
    expectRoster(count($intervals) === 1 && $person['activo'] === 1, 'El ajuste conserva la identidad y no añade vigencias.');
}
foreach ($preserved as $id => $state) expectRoster(personState($pdo, (int) $id) === $state, 'Las incorporaciones posteriores, bajas, reactivaciones y otros grupos no cambian.');
expectRoster($pdo->query('SELECT * FROM asistencias')->fetchAll() === $attendance, 'No se modifica el historial de asistencia.');
expectRoster($pdo->query('SELECT * FROM evaluaciones')->fetchAll() === $evaluations, 'No se modifica el historial de evaluaciones.');
expectRoster($pdo->query('SELECT fecha_inicio FROM ediciones')->fetchColumn() === '2026-10-01', 'El período del centro refleja el nuevo inicio.');

$pdo->beginTransaction();
expectRoster(rosterAdvanceActivityStart($pdo, $activity, '2026-11-01', '2026-10-01') === 0, 'Repetir el ajuste no altera otros participantes.');
expectRoster(rosterAdvanceActivityStart($pdo, $activity, '2026-10-01', '2026-11-01') === 0, 'Retrasar la actividad no borra la elegibilidad histórica.');
expectRoster(rosterAdvanceActivityStart($pdo, $activity, '2026-10-01', '2026-10-01') === 0, 'Guardar la misma fecha no cambia las altas.');
expectRoster(rosterAdvanceActivityStart($pdo, $activity, null, '2026-10-01') === 0, 'Sin inicio anterior no se deduce una fecha de alta.');
expectRoster(rosterAdvanceActivityStart($pdo, $activity, '2026-10-01', '2026-09-01') === 2, 'Un segundo adelanto toma el inicio vigente.');
$pdo->rollBack();
expectRoster(rosterEligible($pdo, $activity, '2026-09-02') === [$reactivated], 'Un error revierte el ajuste completo.');

fwrite(STDOUT, "[OK] $checks comprobaciones del cambio de inicio, listado del monitor y conservación de altas, bajas e historial.\n");
