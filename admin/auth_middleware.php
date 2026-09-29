<?php
/**
 * Middleware de autenticación para administradores
 * Incluir este archivo en todas las páginas que requieran autenticación de admin
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function restoreAdminSessionFromRememberCookie() {
    global $pdo;
    if (empty($_COOKIE['admin_remember_token'])) {
        return false;
    }

    try {
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            require_once __DIR__ . '/../config/config.php';
        }

        $stmt = $pdo->prepare("
            SELECT s.admin_id, a.username, a.role
            FROM admin_sessions s
            JOIN admins a ON s.admin_id = a.id
            WHERE s.token = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$_COOKIE['admin_remember_token']]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            setcookie('admin_remember_token', '', time() - 3600, '/');
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_id'] = $session['admin_id'];
        $_SESSION['admin_username'] = $session['username'];
        $_SESSION['admin_role'] = $session['role'];
        $_SESSION['admin_logged_in'] = true;

        return true;
    } catch (PDOException $e) {
        error_log("Error al verificar token de recordarme: " . $e->getMessage());
        return false;
    }
}

function requireAdminAuth() {
    // Verificar si el administrador está logueado
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        if (!restoreAdminSessionFromRememberCookie()) {
            header("Location: login.php");
            exit;
        }
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

// Auto-ejecutar la verificación salvo en las entradas públicas del directorio admin.
$current_script = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
$public_scripts = [
    __DIR__ . '/login.php',
    __DIR__ . '/process_login.php',
    __DIR__ . '/index.php',
    __DIR__ . '/check_session.php'
];
if (!in_array($current_script, $public_scripts, true)) {
    requireAdminAuth();
}
?>
