<?php
/**
 * Verificar sesión de administrador sin redirecciones
 * Para uso de la SPA
 */

session_start();

header('Content-Type: application/json');

// Verificar si el administrador está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Intentar login por cookie "Recordarme"
    if (isset($_COOKIE['admin_remember_token'])) {
        require_once '../config/config.php';
        $token = $_COOKIE['admin_remember_token'];
        
        $stmt = $pdo->prepare("
            SELECT s.*, a.username, a.role 
            FROM admin_sessions s 
            JOIN admins a ON s.admin_id = a.id 
            WHERE s.token = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $session = $stmt->fetch();

        if ($session) {
            // Restaurar sesión
            $_SESSION['admin_id'] = $session['admin_id'];
            $_SESSION['admin_username'] = $session['username'];
            $_SESSION['admin_role'] = $session['role'];
            $_SESSION['admin_logged_in'] = true;
            
            // Actualizar fecha de último uso
            $pdo->prepare("UPDATE admin_sessions SET last_used_at = NOW() WHERE id = ?")
                ->execute([$session['id']]);

            echo json_encode([
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['admin_id'],
                    'username' => $_SESSION['admin_username'],
                    'role' => $_SESSION['admin_role'],
                    'isSuperAdmin' => $_SESSION['admin_role'] === 'superadmin'
                ]
            ]);
            exit;
        } else {
            // Token inválido o expirado, limpiar cookie
            setcookie('admin_remember_token', '', time() - 3600, '/');
        }
    }

    echo json_encode([
        'authenticated' => false,
        'redirect' => 'login.php'
    ]);
    exit;
}

// Verificar que la sesión tenga los datos necesarios
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username']) || !isset($_SESSION['admin_role'])) {
    // Limpiar sesión corrupta
    session_unset();
    session_destroy();
    echo json_encode([
        'authenticated' => false,
        'redirect' => 'login.php'
    ]);
    exit;
}

// Usuario autenticado correctamente
echo json_encode([
    'authenticated' => true,
    'user' => [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role'],
        'isSuperAdmin' => $_SESSION['admin_role'] === 'superadmin'
    ]
]);
?>
