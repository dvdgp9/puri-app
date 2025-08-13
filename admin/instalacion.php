<?php
/**
 * Detalle de Instalación - Panel de Administración
 */

// Incluir middleware de autenticación
require_once 'auth_middleware.php';
requireAdminAuth();

// Obtener ID de la instalación desde la URL
$instalacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($instalacion_id <= 0) {
    header('Location: dashboard.php?error=invalid_installation');
    exit;
}

// Cargar configuración de la base de datos
require_once '../config/config.php';

try {
    // Verificar que la instalación existe y obtener sus datos
    $stmt = $pdo->prepare(
        "SELECT i.id, i.nombre as instalacion_nombre, 
                c.id as centro_id, c.nombre as centro_nombre, c.direccion
         FROM instalaciones i
         INNER JOIN centros c ON i.centro_id = c.id
         WHERE i.id = ?"
    );
    $stmt->execute([$instalacion_id]);
    $instalacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instalacion) {
        header('Location: dashboard.php?error=installation_not_found');
        exit;
    }
    
    // Verificar permisos del administrador
    $admin_info = getAdminInfo();
    
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare(
            "SELECT 1
             FROM instalaciones i
             INNER JOIN admin_asignaciones aa ON aa.centro_id = i.centro_id
             WHERE i.id = ? AND aa.admin_id = ?"
        );
        $stmt->execute([$instalacion_id, $admin_info['id']]);
        
        if (!$stmt->fetch()) {
            header('Location: dashboard.php?error=access_denied');
            exit;
        }
    }
    
} catch (Exception $e) {
    error_log("Error loading installation details: " . $e->getMessage());
    header('Location: dashboard.php?error=system_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($instalacion['instalacion_nombre']) ?> - Panel de Administración</title>
    
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
                        <a href="centro.php?id=<?= $instalacion['centro_id'] ?>"><?= htmlspecialchars($instalacion['centro_nombre']) ?></a>
                        <span>/</span>
                        <span><?= htmlspecialchars($instalacion['instalacion_nombre']) ?></span>
                    </nav>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-secondary" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                    <h1 class="page-title"><?= htmlspecialchars($instalacion['instalacion_nombre']) ?></h1>
                    <p class="page-subtitle"><?= htmlspecialchars($instalacion['centro_nombre']) ?> - <?= htmlspecialchars($instalacion['direccion']) ?></p>
                    <button class="btn btn-primary" onclick="showEditInstallationModal(<?= $instalacion_id ?>)">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                </div>
            </header>
            
            <!-- Stats Cards -->
            <div class="stats-grid" id="statsContainer">
                <!-- Stats will be loaded via JS -->
            </div>
            
            <!-- Activities Section -->
            <div class="activities-panel">
                <div class="activities-header">
                    <h2 class="activities-title">Actividades</h2>
                    <div class="activities-actions">
                        <input type="text" id="search-activities" class="search-input" placeholder="Buscar actividades...">
                        <select id="sort-activities" class="sort-select">
                            <option value="nombre">Ordenar A-Z</option>
                            <option value="-nombre">Ordenar Z-A</option>
                        </select>
                        <button class="btn btn-primary" onclick="showCreateActivityModal(<?= $instalacion_id ?>)">
                            <i class="fas fa-plus"></i> Nueva Actividad
                        </button>
                    </div>
                </div>
                
                <div class="activities-content">
                    <div id="activities-list" class="activities-list">
                        <!-- Activities will be loaded via JS -->
                    </div>
                </div>
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
                    <input type="hidden" id="editInstallationId" value="<?= $instalacion_id ?>">
                    <div class="form-group">
                        <label for="editInstallationName">Nombre de la Instalación:</label>
                        <input type="text" id="editInstallationName" class="form-input" value="<?= htmlspecialchars($instalacion['instalacion_nombre']) ?>" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditInstallationModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Create Activity Modal -->
    <div id="createActivityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Crear Nueva Actividad</h2>
                <span class="close" onclick="closeCreateActivityModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="createActivityForm">
                    <input type="hidden" id="installationId" value="<?= $instalacion_id ?>">
                    <div class="form-group">
                        <label for="activityName">Nombre de la Actividad:</label>
                        <input type="text" id="activityName" class="form-input" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="activityStartDate">Fecha de Inicio:</label>
                            <input type="date" id="activityStartDate" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="activityEndDate">Fecha de Fin (Opcional):</label>
                            <input type="date" id="activityEndDate" class="form-input">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="activityDays">Días de la semana:</label>
                            <select id="activityDays" class="form-input" multiple required>
                                <option value="Lunes">Lunes</option>
                                <option value="Martes">Martes</option>
                                <option value="Miércoles">Miércoles</option>
                                <option value="Jueves">Jueves</option>
                                <option value="Viernes">Viernes</option>
                                <option value="Sábado">Sábado</option>
                                <option value="Domingo">Domingo</option>
                            </select>
                            <small class="form-text">Mantenga presionada la tecla Ctrl (Cmd en Mac) para seleccionar múltiples días</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="activityStartTime">Hora de Inicio:</label>
                            <input type="time" id="activityStartTime" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="activityEndTime">Hora de Fin:</label>
                            <input type="time" id="activityEndTime" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeCreateActivityModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Actividad</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Activity Options Modal -->
    <div id="activityOptionsModal" class="modal">
        <div class="modal-content small">
            <div class="modal-header">
                <h3>Opciones de Actividad</h3>
                <span class="close" onclick="closeActivityOptionsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selectedActivityId">
                <ul class="modal-options-list">
                    <li onclick="viewActivityDetails()">
                        <i class="fas fa-info-circle"></i>
                        <span>Ver detalles</span>
                    </li>
                    <li onclick="editActivity()">
                        <i class="fas fa-edit"></i>
                        <span>Editar actividad</span>
                    </li>
                    <li onclick="viewParticipants()">
                        <i class="fas fa-users"></i>
                        <span>Ver participantes</span>
                    </li>
                    <li onclick="deactivateActivity()" class="danger">
                        <i class="fas fa-times-circle"></i>
                        <span>Desactivar</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Edit Activity Modal -->
    <div id="editActivityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Actividad</h2>
                <span class="close" onclick="closeEditActivityModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editActivityForm">
                    <input type="hidden" id="editActivityId">
                    <div class="form-group">
                        <label for="editActivityName">Nombre de la Actividad:</label>
                        <input type="text" id="editActivityName" class="form-input" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editActivityStartDate">Fecha de Inicio:</label>
                            <input type="date" id="editActivityStartDate" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="editActivityEndDate">Fecha de Fin (Opcional):</label>
                            <input type="date" id="editActivityEndDate" class="form-input">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editActivityDays">Días de la semana:</label>
                            <select id="editActivityDays" class="form-input" multiple required>
                                <option value="Lunes">Lunes</option>
                                <option value="Martes">Martes</option>
                                <option value="Miércoles">Miércoles</option>
                                <option value="Jueves">Jueves</option>
                                <option value="Viernes">Viernes</option>
                                <option value="Sábado">Sábado</option>
                                <option value="Domingo">Domingo</option>
                            </select>
                            <small class="form-text">Mantenga presionada la tecla Ctrl (Cmd en Mac) para seleccionar múltiples días</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editActivityStartTime">Hora de Inicio:</label>
                            <input type="time" id="editActivityStartTime" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="editActivityEndTime">Hora de Fin:</label>
                            <input type="time" id="editActivityEndTime" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditActivityModal()">Cancelar</button>
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
        // Initialize page with installation data
        document.addEventListener('DOMContentLoaded', function() {
            loadInstallationStats(<?= $instalacion_id ?>);
            loadActivities(<?= $instalacion_id ?>);
            
            // Set up search and sort listeners
            document.getElementById('search-activities').addEventListener('input', function() {
                loadActivities(<?= $instalacion_id ?>);
            });
            
            document.getElementById('sort-activities').addEventListener('change', function() {
                loadActivities(<?= $instalacion_id ?>);
            });
            
            // Set up form submission handlers
            document.getElementById('editInstallationForm').addEventListener('submit', handleEditInstallation);
            document.getElementById('createActivityForm').addEventListener('submit', handleCreateActivity);
            document.getElementById('editActivityForm').addEventListener('submit', handleEditActivity);
        });
        
        // Load installation statistics
        function loadInstallationStats(installationId) {
            fetch(`api/stats/installation.php?id=${installationId}`)
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
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h3>${stats.total_actividades}</h3>
                        <p>Total Actividades</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>${stats.actividades_activas}</h3>
                        <p>Actividades Activas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>${stats.actividades_programadas}</h3>
                        <p>Actividades Programadas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-purple">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-info">
                        <h3>${stats.total_participantes}</h3>
                        <p>Total Participantes</p>
                    </div>
                </div>
            `;
        }
        
        // Load activities for this installation
        function loadActivities(installationId) {
            const search = document.getElementById('search-activities').value;
            const sort = document.getElementById('sort-activities').value;
            
            fetch(`api/actividades/list_by_installation.php?instalacion_id=${installationId}&search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderActivities(data.data);
                    } else {
                        showToast('Error al cargar actividades', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error de conexión', 'error');
                });
        }
        
        // Render activities list
        function renderActivities(activities) {
            const container = document.getElementById('activities-list');
            
            if (activities.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-plus fa-3x"></i>
                        <h3>No hay actividades</h3>
                        <p>Cree su primera actividad para comenzar</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="activities-grid">';
            
            activities.forEach(activity => {
                // Format days
                let daysStr = activity.dias_semana.split(',').join(', ');
                
                // Format time
                let timeStr = `${activity.hora_inicio} - ${activity.hora_fin}`;
                
                html += `
                    <div class="activity-card" onclick="viewActivityDetails(${activity.id})">
                        <div class="activity-header">
                            <h3>${htmlspecialchars(activity.nombre)}</h3>
                            <button class="btn-icon" onclick="showActivityOptions(event, ${activity.id})">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                        <div class="activity-details">
                            <div class="activity-info">
                                <i class="fas fa-calendar-day"></i>
                                <span>${daysStr}</span>
                            </div>
                            <div class="activity-info">
                                <i class="fas fa-clock"></i>
                                <span>${timeStr}</span>
                            </div>
                            <div class="activity-info">
                                <i class="fas fa-users"></i>
                                <span>${activity.total_participantes} participantes</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        // Show activity options menu
        function showActivityOptions(event, activityId) {
            event.stopPropagation();
            document.getElementById('selectedActivityId').value = activityId;
            document.getElementById('activityOptionsModal').style.display = 'block';
        }
        
        // View activity details option
        function viewActivityDetails() {
            const activityId = document.getElementById('selectedActivityId').value;
            closeActivityOptionsModal();
            // For now, we'll just show a toast - in a real implementation, this would navigate to an activity detail page
            showToast('Funcionalidad de detalles de actividad en desarrollo', 'info');
        }
        
        // Edit activity option
        function editActivity() {
            const activityId = document.getElementById('selectedActivityId').value;
            closeActivityOptionsModal();
            
            // Load activity data and show edit modal
            fetch(`api/actividades/get.php?id=${activityId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const activity = data.data;
                        
                        document.getElementById('editActivityId').value = activity.id;
                        document.getElementById('editActivityName').value = activity.nombre;
                        document.getElementById('editActivityStartDate').value = activity.fecha_inicio;
                        document.getElementById('editActivityEndDate').value = activity.fecha_fin || '';
                        document.getElementById('editActivityStartTime').value = activity.hora_inicio;
                        document.getElementById('editActivityEndTime').value = activity.hora_fin;
                        
                        // Set days
                        const daysSelect = document.getElementById('editActivityDays');
                        const activityDays = activity.dias_semana.split(',');
                        
                        for (let i = 0; i < daysSelect.options.length; i++) {
                            const option = daysSelect.options[i];
                            option.selected = activityDays.includes(option.value);
                        }
                        
                        document.getElementById('editActivityModal').style.display = 'block';
                    } else {
                        showToast('Error al cargar datos de la actividad', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error de conexión', 'error');
                });
        }
        
        // View participants option
        function viewParticipants() {
            const activityId = document.getElementById('selectedActivityId').value;
            closeActivityOptionsModal();
            // For now, we'll just show a toast - in a real implementation, this would navigate to a participants page
            showToast('Funcionalidad de ver participantes en desarrollo', 'info');
        }
        
        // Deactivate activity option
        function deactivateActivity() {
            const activityId = document.getElementById('selectedActivityId').value;
            closeActivityOptionsModal();
            
            if (confirm('¿Está seguro de que desea desactivar esta actividad?')) {
                fetch('api/actividades/deactivate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: activityId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Actividad desactivada correctamente', 'success');
                        loadActivities(<?= $instalacion_id ?>);
                    } else {
                        showToast(data.message || 'Error al desactivar actividad', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error de conexión', 'error');
                });
            }
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
                    // Reload page to show updated data
                    location.reload();
                } else {
                    showToast(data.message || 'Error al actualizar instalación', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            });
        }
        
        // Handle create activity form submission
        function handleCreateActivity(event) {
            event.preventDefault();
            
            // Get selected days
            const daysSelect = document.getElementById('activityDays');
            const selectedDays = Array.from(daysSelect.selectedOptions).map(option => option.value);
            
            if (selectedDays.length === 0) {
                showToast('Debe seleccionar al menos un día', 'error');
                return;
            }
            
            const formData = {
                instalacion_id: document.getElementById('installationId').value,
                nombre: document.getElementById('activityName').value,
                fecha_inicio: document.getElementById('activityStartDate').value,
                fecha_fin: document.getElementById('activityEndDate').value || null,
                dias_semana: selectedDays.join(','),
                hora_inicio: document.getElementById('activityStartTime').value,
                hora_fin: document.getElementById('activityEndTime').value
            };
            
            fetch('api/actividades/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Actividad creada correctamente', 'success');
                    closeCreateActivityModal();
                    // Reset form
                    document.getElementById('activityName').value = '';
                    document.getElementById('activityStartDate').value = '';
                    document.getElementById('activityEndDate').value = '';
                    document.getElementById('activityStartTime').value = '';
                    document.getElementById('activityEndTime').value = '';
                    
                    // Clear days selection
                    for (let i = 0; i < daysSelect.options.length; i++) {
                        daysSelect.options[i].selected = false;
                    }
                    
                    loadActivities(<?= $instalacion_id ?>);
                } else {
                    showToast(data.message || 'Error al crear actividad', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            });
        }
        
        // Handle edit activity form submission
        function handleEditActivity(event) {
            event.preventDefault();
            
            // Get selected days
            const daysSelect = document.getElementById('editActivityDays');
            const selectedDays = Array.from(daysSelect.selectedOptions).map(option => option.value);
            
            if (selectedDays.length === 0) {
                showToast('Debe seleccionar al menos un día', 'error');
                return;
            }
            
            const formData = {
                id: document.getElementById('editActivityId').value,
                nombre: document.getElementById('editActivityName').value,
                fecha_inicio: document.getElementById('editActivityStartDate').value,
                fecha_fin: document.getElementById('editActivityEndDate').value || null,
                dias_semana: selectedDays.join(','),
                hora_inicio: document.getElementById('editActivityStartTime').value,
                hora_fin: document.getElementById('editActivityEndTime').value
            };
            
            fetch('api/actividades/update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Actividad actualizada correctamente', 'success');
                    closeEditActivityModal();
                    loadActivities(<?= $instalacion_id ?>);
                } else {
                    showToast(data.message || 'Error al actualizar actividad', 'error');
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
