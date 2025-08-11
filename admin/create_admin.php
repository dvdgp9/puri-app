<?php
/**
 * Script para crear administradores
 * Ejecutar una vez para crear el primer superadmin
 */

// Configuración de la base de datos (copia de config.php para evitar problemas)
define('DB_HOST', 'localhost');
define('DB_USER', 'pasarusr');
define('DB_PASS', 'pasarcontr');
define('DB_NAME', 'pasarlistabdd');

// Datos del superadmin
$username = 'superadmin';
$password = 'admin123'; // Cambiar por una contraseña segura
$role = 'superadmin';

// Generar hash de la contraseña
$password_hash = password_hash($password, PASSWORD_DEFAULT);

echo "Intentando conectar a la base de datos...\n";
echo "Host: " . DB_HOST . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ Conexión a la base de datos exitosa!\n\n";
    
    // Verificar si la tabla admins existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() == 0) {
        echo "❌ La tabla 'admins' no existe. Por favor, créala primero.\n";
        echo "SQL para crear la tabla:\n";
        echo "CREATE TABLE admins (\n";
        echo "    id INT PRIMARY KEY AUTO_INCREMENT,\n";
        echo "    username VARCHAR(50) UNIQUE NOT NULL,\n";
        echo "    password_hash VARCHAR(255) NOT NULL,\n";
        echo "    role ENUM('admin', 'superadmin') DEFAULT 'admin',\n";
        echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n";
        echo ");\n";
        exit;
    }
    
    echo "✅ Tabla 'admins' encontrada.\n\n";
    
    // Verificar si ya existe el usuario
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        echo "⚠️  El usuario '$username' ya existe.\n";
        echo "Si quieres recrearlo, elimínalo primero con:\n";
        echo "DELETE FROM admins WHERE username = '$username';\n";
    } else {
        // Crear el superadmin
        $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password_hash, $role]);
        
        echo "✅ Superadmin creado exitosamente!\n\n";
        echo "=== CREDENCIALES DE ACCESO ===\n";
        echo "Usuario: $username\n";
        echo "Contraseña: $password\n";
        echo "Rol: $role\n";
        echo "Hash generado: " . substr($password_hash, 0, 20) . "...\n\n";
        echo "🔐 IMPORTANTE: Cambie la contraseña después del primer login!\n";
        echo "🌐 Accede en: /admin/login.php\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error de conexión a la base de datos:\n";
    echo $e->getMessage() . "\n\n";
    echo "Posibles soluciones:\n";
    echo "1. Verifica que MySQL esté ejecutándose\n";
    echo "2. Verifica las credenciales de la base de datos\n";
    echo "3. Verifica que la base de datos '$DB_NAME' exista\n";
}
?>
