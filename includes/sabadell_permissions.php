<?php

function sabadellIsSuperAdmin(PDO $pdo, int $adminId): bool
{
    $stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ?');
    $stmt->execute([$adminId]);
    return $stmt->fetchColumn() === 'superadmin';
}

/** Read current privileges, never cache this capability in the login session. */
function sabadellCanImport(PDO $pdo, int $adminId): bool
{
    try {
        $stmt = $pdo->prepare('SELECT role, puede_importar_sabadell FROM admins WHERE id = ?');
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        return $admin && ($admin['role'] === 'superadmin' || (int) $admin['puede_importar_sabadell'] === 1);
    } catch (PDOException $e) {
        // Hide the entry point until its migration is deployed.
        return false;
    }
}

function sabadellRequireAccess(PDO $pdo, int $adminId, int $centerId): void
{
    $stmt = $pdo->prepare('SELECT role, puede_importar_sabadell FROM admins WHERE id = ?');
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin || ($admin['role'] !== 'superadmin' && (int) $admin['puede_importar_sabadell'] !== 1)) {
        throw new DomainException('No tienes permiso para importar listados de Sabadell.', 403);
    }
    $stmt = $pdo->prepare('SELECT id FROM centros WHERE id = ? AND activo = 1');
    $stmt->execute([$centerId]);
    if (!$stmt->fetchColumn()) throw new DomainException('Centro no disponible.', 404);
    if ($admin['role'] !== 'superadmin') {
        $stmt = $pdo->prepare('SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?');
        $stmt->execute([$adminId, $centerId]);
        if (!$stmt->fetchColumn()) throw new DomainException('No tienes acceso a este centro.', 403);
    }
}

function sabadellPermissionValue(array $payload, ?bool $default = null): ?bool
{
    if (!array_key_exists('puede_importar_sabadell', $payload)) return $default;
    if (!is_bool($payload['puede_importar_sabadell'])) throw new DomainException('El permiso de Sabadell debe ser verdadero o falso.');
    return $payload['puede_importar_sabadell'];
}
