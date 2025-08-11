<?php
/**
 * Script para crear administradores
 * Ejecutar una vez para crear el primer superadmin
 */

require_once '../config/config.php';

// Datos del superadmin
$username = 'superadmin';
$password = 'admin123'; // Cambiar por una contraseña segura
$role = 'superadmin';

// Generar hash de la contraseña
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Verificar si ya existe el usuario
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        echo "El usuario '$username' ya existe.\n";
    } else {
        // Crear el superadmin
        $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password_hash, $role]);
        
        echo "Superadmin creado exitosamente:\n";
        echo "Usuario: $username\n";
        echo "Contraseña: $password\n";
        echo "Rol: $role\n";
        echo "\n¡IMPORTANTE: Cambie la contraseña después del primer login!\n";
    }
    
} catch (PDOException $e) {
    echo "Error al crear el administrador: " . $e->getMessage() . "\n";
}
?>
