<?php
/**
 * Página de detalle de centro
 */
require_once 'auth_middleware.php';
require_once '../config/config.php';

// Obtener ID del centro
$centro_id = intval($_GET['id'] ?? 0);

if ($centro_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Verificar que el centro existe y el admin tiene acceso
try {
    $admin_info = getAdminInfo();
    
    // Query base para obtener datos del centro
    $query = "SELECT id, nombre, direccion FROM centros WHERE id = ?";
    $params = [$centro_id];
    
    // Si no es superadmin, verificar asignación
    if ($admin_info['role'] !== 'superadmin') {
        $query .= " AND id IN (SELECT centro_id FROM admin_asignaciones WHERE admin_id = ?)";
        $params[] = $admin_info['id'];
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$centro) {
        header("Location: dashboard.php?error=centro_no_encontrado");
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error en center.php: " . $e->getMessage());
    header("Location: dashboard.php?error=error_sistema");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($centro['nombre']) ?> - Sistema Puri</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Sistema Puri</h2>
                <p>Panel de Administración</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                    Escritorio
                </a>
                <a href="#" class="nav-item active">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.495v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5z"/>
                    </svg>
                    <?= htmlspecialchars($centro['nombre']) ?>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                    <span class="user-role"><?= htmlspecialchars($_SESSION['admin_role']) ?></span>
                </div>
                <a href="logout.php" class="logout-btn">Cerrar sesión</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <!-- Breadcrumbs -->
                <nav class="breadcrumbs">
                    <a href="dashboard.php">Escritorio</a>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-current"><?= htmlspecialchars($centro['nombre']) ?></span>
                </nav>
                
                <!-- Center Header -->
                <div class="center-header-section">
                    <div class="center-header-left">
                        <button onclick="goBack()" class="btn btn-secondary">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                            </svg>
                            Volver
                        </button>
                    </div>
                    <div class="center-header-center">
                        <h1 class="center-title"><?= htmlspecialchars($centro['nombre']) ?></h1>
                        <p class="center-address">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                            </svg>
                            <?= htmlspecialchars($centro['direccion'] ?: 'Sin dirección') ?>
                        </p>
                    </div>
                    <div class="center-header-right">
                        <button onclick="editCenter(<?= $centro['id'] ?>)" class="btn btn-primary">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L10.5 8.207l-3-3L12.146.146zM11.207 9l-3-3L2.5 11.707V14.5h2.793L11.207 9z"/>
                            </svg>
                            Editar
                        </button>
                    </div>
                </div>
            </header>

            <!-- Stats Grid -->
            <section class="stats-section">
                <div class="stats-grid" id="stats-grid">
                    <!-- Las estadísticas se cargarán aquí dinámicamente -->
                </div>
            </section>

            <!-- Installations Panel -->
            <section class="installations-section">
                <div class="installations-panel">
                    <div class="installations-header">
                        <h2 class="installations-title">Instalaciones</h2>
                        <div class="installations-actions">
                            <input type="text" id="search-installations" class="search-input" placeholder="Buscar instalaciones...">
                            <select id="sort-installations" class="sort-select">
                                <option value="nombre">Ordenar A-Z</option>
                                <option value="-nombre">Ordenar Z-A</option>
                            </select>
                            <button class="btn btn-primary" onclick="showCreateInstallationModal()">
                                + Nueva Instalación
                            </button>
                        </div>
                    </div>
                    <div class="installations-content">
                        <div id="installations-list" class="installations-list">
                            <!-- Las instalaciones se cargarán aquí dinámicamente -->
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal para crear instalación -->
    <div id="createInstallationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nueva Instalación</h3>
                <button class="modal-close" onclick="closeModal('createInstallationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createInstallationForm">
                    <div class="form-group">
                        <label for="installationName">Nombre de la Instalación *</label>
                        <input type="text" id="installationName" name="nombre" required 
                               placeholder="Ejemplo: Piscina Olímpica, Cancha de Baloncesto">
                        <span class="field-error" id="installationName-error"></span>
                    </div>
                    <input type="hidden" name="centro_id" value="<?= $centro['id'] ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createInstallationModal')">Cancelar</button>
                <button type="submit" form="createInstallationForm" class="btn btn-primary">Crear Instalación</button>
            </div>
        </div>
    </div>

    <script src="assets/js/center.js"></script>
</body>
</html>
