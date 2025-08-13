<?php
/**
 * Vista detalle de centro - Sistema Puri Admin
 */

require_once 'auth_middleware.php';
$admin_info = getAdminInfo();

// Obtener ID del centro de la URL
$centro_id = intval($_GET['id'] ?? 0);

if ($centro_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Verificar que el centro existe y el admin tiene acceso
require_once '../config/config.php';

try {
    // Si no es superadmin, verificar asignación
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("
            SELECT c.id, c.nombre, c.direccion 
            FROM centros c 
            INNER JOIN admin_asignaciones aa ON c.id = aa.centro_id 
            WHERE c.id = ? AND aa.admin_id = ?
        ");
        $stmt->execute([$centro_id, $admin_info['id']]);
    } else {
        $stmt = $pdo->prepare("SELECT id, nombre, direccion FROM centros WHERE id = ?");
        $stmt->execute([$centro_id]);
    }
    
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$centro) {
        header("Location: dashboard.php?error=centro_no_encontrado");
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error al cargar centro: " . $e->getMessage());
    header("Location: dashboard.php?error=error_sistema");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($centro['nombre']) ?> - Sistema Puri Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Sistema Puri</h2>
                <span class="admin-role"><?= ucfirst($admin_info['role']) ?></span>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="menu-item"><span class="icon">🏠</span> Dashboard</a></li>
                <li><a href="dashboard.php" class="menu-item active"><span class="icon">🏢</span> Centros</a></li>
                <?php if ($admin_info['role'] === 'superadmin'): ?>
                <li><a href="#" class="menu-item"><span class="icon">👥</span> Administradores</a></li>
                <li><a href="#" class="menu-item"><span class="icon">📊</span> Estadísticas</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="sidebar-footer">
                <div class="admin-info">
                    <span><?= htmlspecialchars($admin_info['username']) ?></span>
                </div>
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Breadcrumbs -->
            <nav class="breadcrumbs">
                <a href="dashboard.php">Dashboard</a>
                <span class="separator">/</span>
                <span class="current"><?= htmlspecialchars($centro['nombre']) ?></span>
            </nav>

            <!-- Center Header -->
            <div class="center-header">
                <div class="center-header-left">
                    <button onclick="history.back()" class="btn-back">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Volver
                    </button>
                </div>
                
                <div class="center-header-center">
                    <h1 class="center-title"><?= htmlspecialchars($centro['nombre']) ?></h1>
                    <p class="center-address">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <?= htmlspecialchars($centro['direccion']) ?>
                    </p>
                </div>
                
                <div class="center-header-right">
                    <button class="btn btn-secondary" onclick="showEditCenterModal(<?= $centro_id ?>)">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Configurar
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid" id="center-stats">
                <!-- Las estadísticas se cargarán dinámicamente -->
            </div>

            <!-- Installations Panel -->
            <div class="installations-panel">
                <div class="installations-header">
                    <h2 class="installations-title">Instalaciones del Centro</h2>
                    <div class="installations-actions">
                        <input type="text" id="search-installations" class="search-input" placeholder="Buscar instalaciones...">
                        <select id="sort-installations" class="sort-select">
                            <option value="nombre">Ordenar A-Z</option>
                            <option value="-nombre">Ordenar Z-A</option>
                        </select>
                        <button class="btn btn-primary" onclick="showCreateInstallationModal(<?= $centro_id ?>)">
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
        </main>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
        // Variables globales
        const CENTRO_ID = <?= $centro_id ?>;
        const CENTRO_NOMBRE = <?= json_encode($centro['nombre']) ?>;
        
        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadCenterStats();
            loadInstallations();
            setupEventListeners();
        });

        // Cargar estadísticas del centro
        async function loadCenterStats() {
            try {
                showLoading();
                const response = await fetch(`api/stats/center.php?centro_id=${CENTRO_ID}`);
                const data = await response.json();
                
                if (data.success) {
                    renderCenterStats(data.data);
                } else {
                    console.error('Error loading center stats:', data.error);
                    showError('Error al cargar estadísticas del centro');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error de conexión al cargar estadísticas');
            } finally {
                hideLoading();
            }
        }

        // Renderizar estadísticas
        function renderCenterStats(stats) {
            const statsContainer = document.getElementById('center-stats');
            statsContainer.innerHTML = `
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Asistencias Totales</div>
                        <div class="stat-value">${stats.total_asistencias || 0}</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Esta Semana</div>
                        <div class="stat-value">${stats.asistencias_semana || 0}</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Ocupación Media</div>
                        <div class="stat-value">${stats.ocupacion_media || 0}%</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Actividades</div>
                        <div class="stat-value">${stats.total_actividades || 0}</div>
                    </div>
                </div>
            `;
        }

        // Cargar instalaciones
        async function loadInstallations() {
            try {
                const response = await fetch(`api/instalaciones/list_by_center.php?centro_id=${CENTRO_ID}`);
                const data = await response.json();
                
                if (data.success) {
                    renderInstallations(data.instalaciones);
                } else {
                    console.error('Error loading installations:', data.error);
                    showError('Error al cargar instalaciones');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error de conexión al cargar instalaciones');
            }
        }

        // Renderizar instalaciones
        function renderInstallations(instalaciones) {
            const container = document.getElementById('installations-list');
            
            if (!instalaciones || instalaciones.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <p>No hay instalaciones en este centro</p>
                        <button class="btn btn-primary" onclick="showCreateInstallationModal(${CENTRO_ID})">
                            Crear primera instalación
                        </button>
                    </div>
                `;
                return;
            }

            container.innerHTML = instalaciones.map(instalacion => `
                <div class="installation-card" onclick="viewInstallation(${instalacion.id})" data-id="${instalacion.id}">
                    <div class="installation-content">
                        <h3 class="installation-name">${instalacion.nombre}</h3>
                        <div class="installation-stats">
                            <span class="stat-item">
                                <span class="stat-label">Activas:</span>
                                <span class="stat-value">${instalacion.actividades_activas || 0}</span>
                            </span>
                            <span class="stat-item">
                                <span class="stat-label">Programadas:</span>
                                <span class="stat-value">${instalacion.actividades_programadas || 0}</span>
                            </span>
                            <span class="stat-item">
                                <span class="stat-label">Finalizadas:</span>
                                <span class="stat-value">${instalacion.actividades_finalizadas || 0}</span>
                            </span>
                        </div>
                    </div>
                    <div class="installation-actions">
                        <button class="btn-menu" onclick="event.stopPropagation(); showInstallationMenu(${instalacion.id}, this)">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Event listeners
        function setupEventListeners() {
            // Búsqueda de instalaciones
            document.getElementById('search-installations').addEventListener('input', function(e) {
                filterInstallations(e.target.value);
            });

            // Ordenación de instalaciones
            document.getElementById('sort-installations').addEventListener('change', function(e) {
                sortInstallations(e.target.value);
            });
        }

        // Funciones de utilidad
        function showLoading() {
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        function showError(message) {
            // Implementar sistema de notificaciones
            alert(message);
        }

        function viewInstallation(installationId) {
            window.location.href = `instalacion.php?id=${installationId}`;
        }

        function showInstallationMenu(installationId, button) {
            // Implementar menú contextual
            console.log('Show menu for installation:', installationId);
        }

        function showCreateInstallationModal(centroId) {
            // Implementar modal de creación
            console.log('Create installation for center:', centroId);
        }

        function showEditCenterModal(centroId) {
            // Implementar modal de edición
            console.log('Edit center:', centroId);
        }

        function filterInstallations(searchTerm) {
            // Implementar filtrado
            console.log('Filter installations:', searchTerm);
        }

        function sortInstallations(sortBy) {
            // Implementar ordenación
            console.log('Sort installations:', sortBy);
        }
    </script>
</body>
</html>
