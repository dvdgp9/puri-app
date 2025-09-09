<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['username']) || !isset($_POST['password'])) {
    $_SESSION['admin_error'] = "Por favor, complete todos los campos.";
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

if (empty($username) || empty($password)) {
    $_SESSION['admin_error'] = "Por favor, complete todos los campos.";
    header("Location: login.php");
    exit;
}

try {
    // Buscar el administrador en la base de datos
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Regenerar el ID de sesión para evitar fijación y asegurar persistencia
        session_regenerate_id(true);
        // Login exitoso
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_logged_in'] = true;
        
        // Redirigir a la ruta de retorno si existe; si no, al dashboard
        if (isset($_SESSION['admin_return_to']) && is_string($_SESSION['admin_return_to'])) {
            $returnTo = $_SESSION['admin_return_to'];
            unset($_SESSION['admin_return_to']);
            // Saneado básico: permitir solo rutas internas
            if (strpos($returnTo, 'http://') === 0 || strpos($returnTo, 'https://') === 0) {
                $returnTo = 'dashboard.php';
            }
            header("Location: " . $returnTo);
            exit;
        } else {
            header("Location: dashboard.php");
            exit;
        }
    } else {
        // Credenciales incorrectas
        $_SESSION['admin_error'] = "Usuario o contraseña incorrectos.";
        header("Location: login.php");
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Error en login de admin: " . $e->getMessage());
    $_SESSION['admin_error'] = "Error del sistema. Por favor, inténtelo más tarde.";
    header("Location: login.php");
    exit;
}
?>
