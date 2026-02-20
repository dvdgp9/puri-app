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
    <link href="https://fonts.googleapis.com/css2?family=GeistSans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header igual que dashboard -->
    <header class="admin-header">
        <div class="logo-section">
            <div class="logo">P</div>
            <div class="title">Puri: Gestión de centros deportivos</div>
        </div>
        <div class="actions">
            <a href="informes.php" class="btn btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17h6l3 3v-3h2V9h-2M4 4h11v8H9l-3 3v-3H4V4z"/>
                </svg>
                Informes
            </a>
            <button class="btn btn-secondary" onclick="openModal('createInstallationModal')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nueva Instalación
            </button>
            <div class="dropdown">
                <button class="btn btn-secondary" id="profile-dropdown-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?= htmlspecialchars($_SESSION['admin_username']) ?>
                </button>
                <div class="dropdown-content" id="profile-dropdown">
                    <a href="account.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756.426-1.756 2.924 0 3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Mi Cuenta
                    </a>
                    <a href="logout.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-content">
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
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 16 16">
                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L10.5 8.207l-3-3L12.146.146zM11.207 9l-3-3L2.5 11.707V14.5h2.793L11.207 9z"/>
                    </svg>
                    Editar
                </button>
            </div>
        </div>

        <!-- Stats Grid - copiado exacto del dashboard -->
        <div class="stats-grid" id="stats-grid">
            <div class="loading-card">Cargando estadísticas...</div>
        </div>

        <!-- Panel de Instalaciones - copiado del dashboard -->
        <div class="centers-panel">
            <div class="centers-header">
                <h2 class="centers-title">Instalaciones</h2>
                <div class="centers-actions">
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
            <div class="centers-content">
                <div id="installations-list" class="centers-list">
                    <!-- Las instalaciones se cargarán aquí dinámicamente -->
                </div>
            </div>
        </div>
    </main>

    

    <!-- Modal para crear instalación -->
    <div id="createInstallationModal" class="modal-overlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="createInstallationTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="createInstallationTitle">Nueva Instalación</h3>
                <button class="modal-close" onclick="closeModal('createInstallationModal')" aria-label="Cerrar modal">&times;</button>
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

    <!-- Modal para editar instalación -->
    <div id="editInstallationModal" class="modal-overlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="editInstallationTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="editInstallationTitle">Editar Instalación</h3>
                <button class="modal-close" onclick="closeModal('editInstallationModal')" aria-label="Cerrar modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editInstallationForm">
                    <input type="hidden" id="editInstallationId" name="id" />
                    <div class="form-group">
                        <label for="editInstallationName">Nombre de la Instalación *</label>
                        <input type="text" id="editInstallationName" name="nombre" required placeholder="Nombre de la instalación">
                        <span class="field-error" id="editInstallationName-error"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editInstallationModal')">Cancelar</button>
                <button type="submit" form="editInstallationForm" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
    </div>


    <!-- Modal Editar Centro -->
    <div class="modal-overlay" id="editCenterModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Editar Centro</h2>
                <button class="modal-close" onclick="closeEditCenterModal()">&times;</button>
            </div>
            <form id="editCenterForm">
                <input type="hidden" id="editCenterId" name="id" value="<?= (int)$centro['id'] ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="editCenterName">Nombre del Centro</label>
                        <input type="text" id="editCenterName" name="nombre" class="form-input" required>
                        <div class="form-error" id="editCenterName-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editCenterAddress">Dirección</label>
                        <input type="text" id="editCenterAddress" name="direccion" class="form-input">
                        <div class="form-error" id="editCenterAddress-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditCenterModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="saveEditCenterBtn">
                        <span class="btn-text">Guardar cambios</span>
                        <span class="btn-loading">
                            <svg class="loading-spinner" width="16" height="16" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="60" stroke-dashoffset="60"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Pasar info de rol al JS
        window.AdminRole = '<?= $admin_info['role'] ?>';
        window.isSuperAdmin = <?= $admin_info['role'] === 'superadmin' ? 'true' : 'false' ?>;
    </script>
    <script src="assets/js/center.js"></script>
</body>
</html>
