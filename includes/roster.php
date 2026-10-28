<?php

function rosterKey(string $name, string $surname): string
{
    $clean = static function (string $value): string {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)), 'UTF-8');
    };
    return $clean($name) . "\x1f" . $clean($surname);
}

function rosterActivate(PDO $pdo, int $id, string $date): void
{
    $pdo->prepare('UPDATE inscritos SET activo = 1, baja_at = NULL WHERE id = ?')->execute([$id]);
    $pdo->prepare('INSERT INTO inscrito_vigencias (inscrito_id, inicio) VALUES (?, ?)')->execute([$id, $date]);
}

function rosterDeactivate(PDO $pdo, int $id, string $date): void
{
    $pdo->prepare('UPDATE inscritos SET activo = 0, baja_at = ? WHERE id = ?')->execute([$date, $id]);
    $pdo->prepare('UPDATE inscrito_vigencias SET fin = ? WHERE inscrito_id = ? AND fin IS NULL')->execute([$date, $id]);
}

function rosterInsert(PDO $pdo, int $activityId, string $name, string $surname, string $date): int
{
    $pdo->prepare('INSERT INTO inscritos (actividad_id, nombre, apellidos, activo, alta_at) VALUES (?, ?, ?, 1, ?)')
        ->execute([$activityId, $name, $surname, $date]);
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO inscrito_vigencias (inscrito_id, inicio) VALUES (?, ?)')->execute([$id, $date]);
    return $id;
}

/**
 * Advance only the initial roster tied to the activity's previous start.
 * Later admissions, withdrawals and reactivations keep their own dates.
 * The caller owns the transaction and locks the activity before changing it.
 */
function rosterAdvanceActivityStart(PDO $pdo, int $activityId, ?string $oldStart, ?string $newStart): int
{
    if (!$oldStart || !$newStart || $newStart >= $oldStart) return 0;
    if (!$pdo->inTransaction()) throw new LogicException('El cambio de inicio requiere una transacción.');

    $sql = 'SELECT i.id, v.id AS vigencia_id FROM inscritos i
            JOIN inscrito_vigencias v ON v.inscrito_id = i.id
            WHERE i.actividad_id = ? AND i.activo = 1 AND i.alta_at = ?
              AND v.inicio = ? AND v.fin IS NULL
              AND NOT EXISTS (SELECT 1 FROM inscrito_vigencias other
                              WHERE other.inscrito_id = i.id AND other.id <> v.id)
            ORDER BY i.id';
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') $sql .= ' FOR UPDATE';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$activityId, $oldStart, $oldStart]);
    $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $interval = $pdo->prepare('UPDATE inscrito_vigencias SET inicio = ? WHERE id = ?');
    $admission = $pdo->prepare('UPDATE inscritos SET alta_at = ? WHERE id = ?');
    foreach ($people as $person) {
        $interval->execute([$newStart, $person['vigencia_id']]);
        $admission->execute([$newStart, $person['id']]);
    }
    return count($people);
}

function rosterEnsureActive(PDO $pdo, int $activityId, string $name, string $surname, string $date): array
{
    $stmt = $pdo->prepare('SELECT id, nombre, apellidos, activo FROM inscritos WHERE actividad_id = ?');
    $stmt->execute([$activityId]);
    $matches = [];
    $key = rosterKey($name, $surname);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (rosterKey($row['nombre'], $row['apellidos']) === $key) $matches[] = $row;
    }
    if (count($matches) > 1) throw new DomainException('Hay participantes duplicados con este nombre en la actividad.');
    if ($matches) {
        $id = (int) $matches[0]['id'];
        if ((int) $matches[0]['activo'] === 1) return ['id' => $id, 'accion' => 'mantenido'];
        rosterActivate($pdo, $id, $date);
        return ['id' => $id, 'accion' => 'reactivado'];
    }
    return ['id' => rosterInsert($pdo, $activityId, $name, $surname, $date), 'accion' => 'nuevo'];
}
