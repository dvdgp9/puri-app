<?php
// Middleware de autenticación para API
session_start();

function requireAuth() {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
}

function requireSuperAdmin() {
    requireAuth();
    
    if ($_SESSION['admin_role'] !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }
}

function getCurrentAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role']
    ];
}

function getAssignedCentros($pdo, $admin_id, $admin_role) {
    if ($admin_role === 'superadmin') {
        // Superadmin puede ver todos los centros
        $stmt = $pdo->prepare("SELECT id, nombre FROM centros ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Admin normal solo puede ver centros asignados
        $stmt = $pdo->prepare("SELECT c.id, c.nombre FROM centros c JOIN admin_asignaciones aa ON c.id = aa.centro_id WHERE aa.admin_id = ? ORDER BY c.nombre");
        $stmt->execute([$admin_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
