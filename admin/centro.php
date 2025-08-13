<?php
/**
 * Detalle de Centro - Panel de Administración
 */

// Incluir middleware de autenticación
require_once 'auth_middleware.php';
requireAdminAuth();

// Obtener ID del centro desde la URL
$centro_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($centro_id <= 0) {
    header('Location: dashboard.php?error=invalid_center');
    exit;
}

// Cargar configuración de la base de datos
require_once '../config/config.php';

try {
    // Verificar que el centro existe y obtener sus datos
    $stmt = $pdo->prepare("SELECT id, nombre, direccion FROM centros WHERE id = ?");
    $stmt->execute([$centro_id]);
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$centro) {
        header('Location: dashboard.php?error=center_not_found');
        exit;
    }
    
    // Verificar permisos del administrador
    $admin_info = getAdminInfo();
    
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_info['id'], $centro_id]);
        
        if (!$stmt->fetch()) {
            header('Location: dashboard.php?error=access_denied');
            exit;
        }
    }
    
} catch (Exception $e) {
    error_log("Error loading center details: " . $e->getMessage());
    header('Location: dashboard.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($centro['nombre']) ?> - Panel de Administración</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <nav class="breadcrumb">
                        <a href="dashboard.php">Escritorio</a>
                        <span>/</span>
                        <span><?= htmlspecialchars($centro['nombre']) ?></span>
                    </nav>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-secondary" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                    <h1 class="page-title"><?= htmlspecialchars($centro['nombre']) ?></h1>
                    <p class="page-subtitle"><?= htmlspecialchars($centro['direccion']) ?></p>
                    <button class="btn btn-primary" onclick="showEditCenterModal(<?= $centro_id ?>)">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                </div>
            </header>
            
            <!-- Stats Cards -->
            <div class="stats-grid" id="statsContainer">
                <!-- Stats will be loaded via JS -->
            </div>
            
            <!-- Installations Section -->
            <div class="installations-panel">
                <div class="installations-header">
                    <h2 class="installations-title">Instalaciones</h2>
                    <div class="installations-actions">
                        <input type="text" id="search-installations" class="search-input" placeholder="Buscar instalaciones...">
                        <select id="sort-installations" class="sort-select">
                            <option value="nombre">Ordenar A-Z</option>
                            <option value="-nombre">Ordenar Z-A</option>
                        </select>
                        <button class="btn btn-primary" onclick="showCreateInstallationModal(<?= $centro_id ?>)">
                            <i class="fas fa-plus"></i> Nueva Instalación
                        </button>
                    </div>
                </div>
                
                <div class="installations-content">
                    <div id="installations-list" class="installations-list">
                        <!-- Installations will be loaded via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Center Modal -->
    <div id="editCenterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Centro</h2>
                <span class="close" onclick="closeEditCenterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editCenterForm">
                    <input type="hidden" id="editCenterId" value="<?= $centro_id ?>">
                    <div class="form-group">
                        <label for="editCenterName">Nombre del Centro:</label>
                        <input type="text" id="editCenterName" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="editCenterAddress">Dirección:</label>
                        <input type="text" id="editCenterAddress" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="editCenterPassword">Contraseña de Acceso:</label>
                        <input type="password" id="editCenterPassword" class="form-input" placeholder="Dejar en blanco para no cambiar">
                        <small class="form-text">Deje en blanco si no desea cambiar la contraseña</small>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditCenterModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Create Installation Modal -->
    <div id="createInstallationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Crear Nueva Instalación</h2>
                <span class="close" onclick="closeCreateInstallationModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="createInstallationForm">
                    <input type="hidden" id="centerId" value="<?= $centro_id ?>">
                    <div class="form-group">
                        <label for="installationName">Nombre de la Instalación:</label>
                        <input type="text" id="installationName" class="form-input" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeCreateInstallationModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Instalación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Installation Options Modal -->
    <div id="installationOptionsModal" class="modal">
        <div class="modal-content small">
            <div class="modal-header">
                <h3>Opciones de Instalación</h3>
                <span class="close" onclick="closeInstallationOptionsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selectedInstallationId">
                <ul class="modal-options-list">
                    <li onclick="viewActivities()">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Ver actividades</span>
                    </li>
                    <li onclick="editInstallation()">
                        <i class="fas fa-edit"></i>
                        <span>Editar instalación</span>
                    </li>
                    <li onclick="deactivateInstallation()" class="danger">
                        <i class="fas fa-times-circle"></i>
                        <span>Desactivar</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Edit Installation Modal -->
    <div id="editInstallationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Instalación</h2>
                <span class="close" onclick="closeEditInstallationModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editInstallationForm">
                    <input type="hidden" id="editInstallationId">
                    <div class="form-group">
                        <label for="editInstallationName">Nombre de la Instalación:</label>
                        <input type="text" id="editInstallationName" class="form-input" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditInstallationModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>
    
    <!-- JavaScript -->
    <script src="assets/js/dashboard.js"></script>
    <script>
        // Initialize page with center data
        document.addEventListener('DOMContentLoaded', function() {
            loadCenterStats(<?= $centro_id ?>);
            loadInstallations(<?= $centro_id ?>);
            
            // Set up search and sort listeners
            document.getElementById('search-installations').addEventListener('input', function() {
                loadInstallations(<?= $centro_id ?>);
            });
            
            document.getElementById('sort-installations').addEventListener('change', function() {
                loadInstallations(<?= $centro_id ?>);
            });
            
            // Set up form submission handlers
            document.getElementById('editCenterForm').addEventListener('submit', handleEditCenter);
            document.getElementById('createInstallationForm').addEventListener('submit', handleCreateInstallation);
            document.getElementById('editInstallationForm').addEventListener('submit', handleEditInstallation);
        });
        
        // Load center statistics
        function loadCenterStats(centerId) {
            fetch(`api/stats/center.php?id=${centerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderStats(data.data);
                    } else {
                        showToast('Error al cargar estadísticas', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error de conexión', 'error');
                });
        }
        
        // Render statistics cards
        function renderStats(stats) {
            const container = document.getElementById('statsContainer');
            
            container.innerHTML = `
                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-info">
                        <h3>${stats.total_instalaciones}</h3>
                        <p>Instalaciones</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>${stats.total_actividades_activas}</h3>
                        <p>Actividades Activas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>${stats.total_actividades_programadas}</h3>
                        <p>Actividades Programadas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-purple">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>${stats.porcentaje_asistencia}%</h3>
                        <p>Asistencia Promedio</p>
                    </div>
                </div>
            `;
        }
        
        // Load installations for this center
        function loadInstallations(centerId) {
            const search = document.getElementById('search-installations').value;
            const sort = document.getElementById('sort-installations').value;
            
            fetch(`api/instalaciones/list_by_center.php?centro_id=${centerId}&search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderInstallations(data.data);
                    } else {
                        showToast('Error al cargar instalaciones', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error de conexión', 'error');
                });
        }
        
        // Render installations list
        function renderInstallations(installations) {
            const container = document.getElementById('installations-list');
            
            if (installations.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-warehouse fa-3x"></i>
                        <h3>No hay instalaciones</h3>
                        <p>Cree su primera instalación para comenzar</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="installations-grid">';
            
            installations.forEach(installation => {
                html += `
                    <div class="installation-card" onclick="viewInstallationActivities(${installation.id})">
                        <div class="installation-header">
                            <h3>${htmlspecialchars(installation.nombre)}</h3>
                            <button class="btn-icon" onclick="showInstallationOptions(event, ${installation.id})">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                        <div class="installation-stats">
                            <div class="stat-item">
                                <span class="stat-value">${installation.actividades_activas}</span>
                                <span class="stat-label">Activas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">${installation.actividades_programadas}</span>
                                <span class="stat-label">Programadas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">${installation.actividades_finalizadas}</span>
                                <span class="stat-label">Finalizadas</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        // View activities for an installation
        function viewInstallationActivities(installationId) {
            window.location.href = `instalacion.php?id=${installationId}`;
        }
        
        // Show installation options menu
        function showInstallationOptions(event, installationId) {
            event.stopPropagation();
            document.getElementById('selectedInstallationId').value = installationId;
            document.getElementById('installationOptionsModal').style.display = 'block';
        }
        
        // View activities option
        function viewActivities() {
            const installationId = document.getElementById('selectedInstallationId').value;
            closeInstallationOptionsModal();
            viewInstallationActivities(installationId);
        }
        
        // Edit installation option
        function editInstallation() {
            const installationId = document.getElementById('selectedInstallationId').value;
            closeInstallationOptionsModal();
            
            // Load installation data and show edit modal
            fetch(`api/instalaciones/get.php?id=${installationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editInstallationId').value = data.data.id;
                        document.getElementById('editInstallationName').value = data.data.nombre;
                        document.getElementById('editInstallationModal').style.display = 'block';
                    } else {
                        showToast('Error al cargar datos de la instalación', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error de conexión', 'error');
                });
        }
        
        // Deactivate installation option
        function deactivateInstallation() {
            const installationId = document.getElementById('selectedInstallationId').value;
            closeInstallationOptionsModal();
            
            if (confirm('¿Está seguro de que desea desactivar esta instalación?')) {
                fetch('api/instalaciones/deactivate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: installationId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Instalación desactivada correctamente', 'success');
                        loadInstallations(<?= $centro_id ?>);
                    } else {
                        showToast(data.message || 'Error al desactivar instalación', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error de conexión', 'error');
                });
            }
        }
        
        // Handle edit center form submission
        function handleEditCenter(event) {
            event.preventDefault();
            
            const formData = {
                id: document.getElementById('editCenterId').value,
                nombre: document.getElementById('editCenterName').value,
                direccion: document.getElementById('editCenterAddress').value,
                password: document.getElementById('editCenterPassword').value
            };
            
            fetch('api/centros/update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Centro actualizado correctamente', 'success');
                    closeEditCenterModal();
                    // Reload page to show updated data
                    location.reload();
                } else {
                    showToast(data.message || 'Error al actualizar centro', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            });
        }
        
        // Handle create installation form submission
        function handleCreateInstallation(event) {
            event.preventDefault();
            
            const formData = {
                centro_id: document.getElementById('centerId').value,
                nombre: document.getElementById('installationName').value
            };
            
            fetch('api/instalaciones/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Instalación creada correctamente', 'success');
                    closeCreateInstallationModal();
                    document.getElementById('installationName').value = '';
                    loadInstallations(<?= $centro_id ?>);
                } else {
                    showToast(data.message || 'Error al crear instalación', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            });
        }
        
        // Handle edit installation form submission
        function handleEditInstallation(event) {
            event.preventDefault();
            
            const formData = {
                id: document.getElementById('editInstallationId').value,
                nombre: document.getElementById('editInstallationName').value
            };
            
            fetch('api/instalaciones/update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Instalación actualizada correctamente', 'success');
                    closeEditInstallationModal();
                    loadInstallations(<?= $centro_id ?>);
                } else {
                    showToast(data.message || 'Error al actualizar instalación', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            });
        }
        
        // Utility function for HTML escaping
        function htmlspecialchars(str) {
            return str.replace(/&/g, "&amp;")
                     .replace(/</g, "&lt;")
                     .replace(/>/g, "&gt;")
                     .replace(/"/g, "&quot;")
                     .replace(/'/g, "&#039;");
        }
        
        // Go back to previous page
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>
