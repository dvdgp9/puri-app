<?php
/**
 * Vista detalle de centro
 * Reutiliza estilos y estructura del dashboard principal
 */

require_once '../config/config.php';
require_once 'auth_middleware.php';

// Verificar autenticación
$admin_info = getAdminInfo();

// Obtener ID del centro desde URL
$centro_id = intval($_GET['id'] ?? 0);

if ($centro_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Verificar que el centro existe y el admin tiene acceso
try {
    if ($admin_info['role'] === 'superadmin') {
        $stmt = $pdo->prepare("SELECT id, nombre, direccion FROM centros WHERE id = ?");
        $stmt->execute([$centro_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT c.id, c.nombre, c.direccion 
            FROM centros c 
            INNER JOIN admin_asignaciones aa ON c.id = aa.centro_id 
            WHERE c.id = ? AND aa.admin_id = ?
        ");
        $stmt->execute([$centro_id, $admin_info['id']]);
    }
    
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$centro) {
        header("Location: dashboard.php");
        exit;
    }
} catch (Exception $e) {
    error_log("Error al obtener centro: " . $e->getMessage());
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($centro['nombre']); ?> - Admin Puri</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=GeistSans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="logo-section">
            <div class="logo">P</div>
            <div class="title">Puri: Gestión de centros deportivos</div>
        </div>
        <div class="actions">
            <button class="btn btn-primary" onclick="showAddOptionsModal()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Añadir
            </button>
            <div class="dropdown">
                <button class="btn btn-secondary" id="profile-dropdown-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?php echo htmlspecialchars($admin_info['username']); ?>
                </button>
                <div class="dropdown-content" id="profile-dropdown">
                    <a href="#" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configuración
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

    <div class="admin-container">
        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php" class="breadcrumb-item">Escritorio</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($centro['nombre']); ?></span>
        </div>

        <!-- Header del centro -->
        <div class="center-header">
            <button class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </button>
            <div class="center-info">
                <h1 class="center-title"><?php echo htmlspecialchars($centro['nombre']); ?></h1>
                <p class="center-address"><?php echo htmlspecialchars($centro['direccion']); ?></p>
            </div>
            <button class="btn btn-primary" onclick="showEditCenterModal(<?php echo $centro_id; ?>)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </button>
        </div>

        <!-- Estadísticas del centro -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number" id="total-instalaciones">-</div>
                    <div class="stat-label">Instalaciones</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number" id="total-actividades-activas">-</div>
                    <div class="stat-label">Actividades Activas</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number" id="total-actividades-programadas">-</div>
                    <div class="stat-label">Actividades Programadas</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number" id="total-participantes">-</div>
                    <div class="stat-label">Participantes</div>
                </div>
            </div>
        </div>

        <!-- Panel de instalaciones -->
        <div class="installations-panel">
            <div class="installations-header">
                <h2 class="installations-title">Instalaciones</h2>
                <div class="installations-actions">
                    <input type="text" id="search-installations" class="search-input" placeholder="Buscar instalaciones...">
                    <select id="sort-installations" class="sort-select">
                        <option value="nombre">Ordenar A-Z</option>
                        <option value="-nombre">Ordenar Z-A</option>
                    </select>
                    <button class="btn btn-primary" onclick="showCreateInstallationModal(<?php echo $centro_id; ?>)">
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
    </div>

    <!-- Incluir modales del dashboard -->
    <?php include 'modals/add_options.php'; ?>
    <?php include 'modals/create_center.php'; ?>
    <?php include 'modals/create_installation.php'; ?>
    <?php include 'modals/create_activity.php'; ?>
    <?php include 'modals/create_participant.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/dashboard.js"></script>
    <script>
        // Configuración específica del centro
        const CURRENT_CENTER_ID = <?php echo $centro_id; ?>;
        
        // Inicializar página del centro
        document.addEventListener('DOMContentLoaded', function() {
            initCenterPage();
        });

        function initCenterPage() {
            loadCenterStats();
            loadInstallations();
            setupInstallationSearch();
            setupDropdowns();
        }

        function loadCenterStats() {
            fetch(`api/stats/center.php?centro_id=${CURRENT_CENTER_ID}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-instalaciones').textContent = data.data.total_instalaciones || 0;
                        document.getElementById('total-actividades-activas').textContent = data.data.total_actividades_activas || 0;
                        document.getElementById('total-actividades-programadas').textContent = data.data.total_actividades_programadas || 0;
                        document.getElementById('total-participantes').textContent = data.data.total_participantes || 0;
                    }
                })
                .catch(error => {
                    console.error('Error loading center stats:', error);
                });
        }

        function loadInstallations() {
            const search = document.getElementById('search-installations').value;
            const sort = document.getElementById('sort-installations').value;
            
            fetch(`api/instalaciones/list_by_center.php?centro_id=${CURRENT_CENTER_ID}&search=${encodeURIComponent(search)}&sort=${sort}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderInstallations(data.instalaciones || []);
                    } else {
                        console.error('Error loading installations:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading installations:', error);
                });
        }

        function renderInstallations(installations) {
            const container = document.getElementById('installations-list');
            
            if (installations.length === 0) {
                container.innerHTML = '<div class="empty-state">No hay instalaciones en este centro</div>';
                return;
            }

            container.innerHTML = installations.map(installation => `
                <div class="installation-card" onclick="goToInstallation(${installation.id})">
                    <div class="installation-info">
                        <h3 class="installation-name">${escapeHtml(installation.nombre)}</h3>
                        <div class="installation-stats">
                            <span class="stat-item">
                                <span class="stat-value">${installation.total_actividades_activas || 0}</span>
                                <span class="stat-label">Activas</span>
                            </span>
                            <span class="stat-item">
                                <span class="stat-value">${installation.total_actividades_programadas || 0}</span>
                                <span class="stat-label">Programadas</span>
                            </span>
                            <span class="stat-item">
                                <span class="stat-value">${installation.total_actividades_finalizadas || 0}</span>
                                <span class="stat-label">Finalizadas</span>
                            </span>
                        </div>
                    </div>
                    <div class="installation-actions">
                        <button class="btn-options" onclick="event.stopPropagation(); showInstallationOptions(${installation.id}, '${escapeHtml(installation.nombre)}')">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function setupInstallationSearch() {
            const searchInput = document.getElementById('search-installations');
            const sortSelect = document.getElementById('sort-installations');
            
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(loadInstallations, 300);
            });
            
            sortSelect.addEventListener('change', loadInstallations);
        }

        function goToInstallation(installationId) {
            window.location.href = `instalacion.php?id=${installationId}`;
        }

        function showInstallationOptions(installationId, installationName) {
            // Implementar menú contextual
            console.log('Show options for installation:', installationId, installationName);
        }

        function showCreateInstallationModal(centroId) {
            // Reutilizar modal existente pero preseleccionar el centro
            showCreateInstallationModalForCenter(centroId);
        }

        function showEditCenterModal(centroId) {
            // Implementar modal de edición de centro
            console.log('Edit center:', centroId);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
