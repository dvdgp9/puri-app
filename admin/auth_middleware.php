<?php
/**
 * Middleware de autenticación para administradores
 * Incluir este archivo en todas las páginas que requieran autenticación de admin
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function requireAdminAuth() {
    // Verificar si el administrador está logueado
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Intentar restaurar sesión desde cookie "Recordarme"
        if (isset($_COOKIE['admin_remember_token'])) {
            require_once __DIR__ . '/../config/config.php';
            $token = $_COOKIE['admin_remember_token'];
            
            try {
                $stmt = $pdo->prepare("
                    SELECT s.*, a.username, a.role 
                    FROM admin_sessions s 
                    JOIN admins a ON s.admin_id = a.id 
                    WHERE s.token = ? AND s.expires_at > NOW()
                ");
                $stmt->execute([$token]);
                $session = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($session) {
                    // Restaurar sesión
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $session['admin_id'];
                    $_SESSION['admin_username'] = $session['username'];
                    $_SESSION['admin_role'] = $session['role'];
                    $_SESSION['admin_logged_in'] = true;
                    
                    // Actualizar fecha de último uso
                    $pdo->prepare("UPDATE admin_sessions SET last_used_at = NOW() WHERE id = ?")
                        ->execute([$session['id']]);
                    
                    // Sesión restaurada exitosamente, continuar
                    return;
                } else {
                    // Token inválido o expirado, limpiar cookie
                    setcookie('admin_remember_token', '', time() - 3600, '/');
                }
            } catch (PDOException $e) {
                error_log("Error al verificar token de recordarme: " . $e->getMessage());
                // Si hay error, limpiar cookie y continuar al login
                setcookie('admin_remember_token', '', time() - 3600, '/');
            }
        }
        
        // Redirigir al login si no está autenticado
        header("Location: login.php");
        exit;
    }
    
    // Verificar que la sesión tenga los datos necesarios
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username']) || !isset($_SESSION['admin_role'])) {
        // Limpiar sesión corrupta
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

function requireSuperAdmin() {
    requireAdminAuth();
    
    if ($_SESSION['admin_role'] !== 'superadmin') {
        // Redirigir al dashboard si no es superadmin
        header("Location: dashboard.php?error=access_denied");
        exit;
    }
}

function getAdminInfo() {
    requireAdminAuth();
    
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role']
    ];
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['admin_role'] === 'superadmin';
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Auto-ejecutar verificación de autenticación si se incluye este archivo
// (excepto en login.php y process_login.php)
$current_file = basename($_SERVER['PHP_SELF']);
if (!in_array($current_file, ['login.php', 'process_login.php', 'index.html'])) {
    requireAdminAuth();
}
?>
