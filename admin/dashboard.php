<?php
session_start();
require_once '../config/database.php';

// Verificar si el admin está logueado
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'];

// Obtener información básica del admin
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_centros FROM admin_asignaciones WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $asignaciones = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si es superadmin, obtener todos los centros
    if ($admin_role === 'superadmin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_centros FROM centros");
        $stmt->execute();
        $asignaciones = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $total_centros = $asignaciones['total_centros'];
} catch (Exception $e) {
    $total_centros = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel de Administración</title>
    <link rel="stylesheet" href="../public/assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="#dashboard" class="active" data-view="dashboard">Dashboard</a></li>
                    <li><a href="#centros" data-view="centros">Centros</a></li>
                    <li><a href="#instalaciones" data-view="instalaciones">Instalaciones</a></li>
                    <li><a href="#actividades" data-view="actividades">Actividades</a></li>
                    <li><a href="#estadisticas" data-view="estadisticas">Estadísticas</a></li>
                    <?php if ($admin_role === 'superadmin'): ?>
                        <li><a href="#superadmin" data-view="superadmin">Superadmin</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Bienvenido, <span id="admin-username"><?php echo htmlspecialchars($admin_username); ?></span></h1>
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
            
            <div id="content-area">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </main>
    </div>
    
    <script src="assets/js/app.js"></script>
</body>
</html>
