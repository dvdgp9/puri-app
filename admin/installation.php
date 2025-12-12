<?php
/**
 * Página de detalle de instalación
 */
require_once 'auth_middleware.php';
require_once '../config/config.php';

// Obtener ID de la instalación
$instalacion_id = intval($_GET['id'] ?? 0);

if ($instalacion_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Verificar que la instalación existe y el admin tiene acceso
try {
    $admin_info = getAdminInfo();

    // Obtener instalación con su centro
    $query = "SELECT i.id, i.nombre, i.centro_id, c.nombre AS centro_nombre, c.direccion AS centro_direccion
              FROM instalaciones i
              INNER JOIN centros c ON c.id = i.centro_id
              WHERE i.id = ?";
    $params = [$instalacion_id];

    // Si no es superadmin, verificar asignación al centro
    if ($admin_info['role'] !== 'superadmin') {
        $query .= " AND c.id IN (SELECT centro_id FROM admin_asignaciones WHERE admin_id = ?)";
        $params[] = $admin_info['id'];
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $instalacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instalacion) {
        header("Location: dashboard.php?error=instalacion_no_encontrada");
        exit;
    }

} catch (Exception $e) {
    error_log("Error en installation.php: " . $e->getMessage());
    header("Location: dashboard.php?error=error_sistema");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($instalacion['nombre']) ?> - Sistema Puri</title>
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
            <a href="../informes.php" class="btn btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17h6l3 3v-3h2V9h-2M4 4h11v8H9l-3 3v-3H4V4z"/>
                </svg>
                Informes
            </a>
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
            <a href="center.php?id=<?= (int)$instalacion['centro_id'] ?>"><?= htmlspecialchars($instalacion['centro_nombre']) ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= htmlspecialchars($instalacion['nombre']) ?></span>
        </nav>

        <!-- Installation Header -->
        <div class="center-header-section">
            <div class="center-header-left">
                <button onclick="goBackToCenter(<?= (int)$instalacion['centro_id'] ?>)" class="btn btn-secondary">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Volver
                </button>
            </div>
            <div class="center-header-center">
                <h1 class="center-title installation-title"><?= htmlspecialchars($instalacion['nombre']) ?></h1>
                <p class="center-address">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                    <?= htmlspecialchars($instalacion['centro_nombre']) ?> · <?= htmlspecialchars($instalacion['centro_direccion'] ?: 'Sin dirección') ?>
                </p>
            </div>
            <div class="center-header-right">
                <button onclick="editInstallationHeader(<?= (int)$instalacion['id'] ?>)" class="btn btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 16 16">
                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L10.5 8.207l-3-3L12.146.146zM11.207 9l-3-3L2.5 11.707V14.5h2.793L11.207 9z"/>
                    </svg>
                    Editar
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid" id="stats-grid">
            <div class="loading-card">Cargando estadísticas...</div>
        </div>

        <!-- Panel de Actividades -->
        <div class="centers-panel">
            <div class="centers-header">
                <h2 class="centers-title">Actividades</h2>
                <div class="centers-actions">
                    <input type="text" id="search-activities" class="search-input" placeholder="Buscar actividades...">
                    <select id="sort-activities" class="sort-select">
                        <option value="nombre">Ordenar A-Z</option>
                        <option value="-nombre">Ordenar Z-A</option>
                    </select>
                    <button class="btn btn-primary" onclick="showCreateActivityModal()">
                        + Nueva Actividad
                    </button>
                </div>
            </div>
            <div class="centers-content">
                <div id="activities-list" class="centers-list">
                    <!-- Las actividades se cargarán aquí dinámicamente -->
                </div>
            </div>
        </div>
    </main>

    <!-- Modal: Crear Actividad -->
    <div id="createActivityModal" class="modal-overlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="createActivityTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="createActivityTitle">Nueva Actividad</h3>
                <button class="modal-close" onclick="closeModal('createActivityModal')" aria-label="Cerrar modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createActivityForm">
                    <div class="form-group">
                        <label for="activityName">Nombre de la Actividad *</label>
                        <input type="text" id="activityName" name="nombre" required placeholder="Ejemplo: Natación Infantil">
                        <span class="field-error" id="activityName-error"></span>
                    </div>
                    <!-- Días de la semana -->
                    <div class="form-group">
                        <label>Días de la semana *</label>
                        <div class="checkbox-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dias_semana[]" value="Lunes"> Lunes
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dias_semana[]" value="Martes"> Martes
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dias_semana[]" value="Miércoles"> Miércoles
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dias_semana[]" value="Jueves"> Jueves
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dias_semana[]" value="Viernes"> Viernes
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dias_semana[]" value="Sábado"> Sábado
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dias_semana[]" value="Domingo"> Domingo
                            </label>
                        </div>
                        <span class="field-error" id="dias_semana-error"></span>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="activityStart">Hora inicio</label>
                            <input type="time" id="activityStart" name="hora_inicio">
                        </div>
                        <div class="form-group">
                            <label for="activityEnd">Hora fin</label>
                            <input type="time" id="activityEnd" name="hora_fin">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="activityDateStart">Fecha inicio *</label>
                            <input type="date" id="activityDateStart" name="fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label for="activityDateEnd">Fecha fin (opcional)</label>
                            <input type="date" id="activityDateEnd" name="fecha_fin">
                        </div>
                    </div>
                    <input type="hidden" name="instalacion_id" value="<?= (int)$instalacion['id'] ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createActivityModal')">Cancelar</button>
                <button type="submit" form="createActivityForm" class="btn btn-primary">Crear Actividad</button>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Actividad -->
    <div id="editActivityModal" class="modal-overlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="editActivityTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="editActivityTitle">Editar Actividad</h3>
                <button class="modal-close" onclick="closeModal('editActivityModal')" aria-label="Cerrar modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editActivityForm">
                    <input type="hidden" id="editActivityId" name="id" />
                    <div class="form-group">
                        <label for="editActivityName">Nombre *</label>
                        <input type="text" id="editActivityName" name="nombre" required>
                        <span class="field-error" id="editActivityName-error"></span>
                    </div>
                    <!-- Días de la semana -->
                    <div class="form-group">
                        <label>Días de la semana *</label>
                        <div class="checkbox-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="edit_dias_semana[]" value="Lunes"> Lunes
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="edit_dias_semana[]" value="Martes"> Martes
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="edit_dias_semana[]" value="Miércoles"> Miércoles
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="edit_dias_semana[]" value="Jueves"> Jueves
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="edit_dias_semana[]" value="Viernes"> Viernes
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="edit_dias_semana[]" value="Sábado"> Sábado
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="edit_dias_semana[]" value="Domingo"> Domingo
                            </label>
                        </div>
                        <span class="field-error" id="edit_dias_semana-error"></span>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="editActivityStart">Hora inicio</label>
                            <input type="time" id="editActivityStart" name="hora_inicio">
                        </div>
                        <div class="form-group">
                            <label for="editActivityEnd">Hora fin</label>
                            <input type="time" id="editActivityEnd" name="hora_fin">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="editActivityDateStart">Fecha inicio *</label>
                            <input type="date" id="editActivityDateStart" name="fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label for="editActivityDateEnd">Fecha fin (opcional)</label>
                            <input type="date" id="editActivityDateEnd" name="fecha_fin">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">Cancelar</button>
                <button type="submit" form="editActivityForm" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
    </div>

    <script>
        window.__INSTALLATION_CTX__ = {
            id: <?= (int)$instalacion['id'] ?>,
            centro_id: <?= (int)$instalacion['centro_id'] ?>
        };
    </script>
    
    <!-- Modal: Editar nombre instalación (cabecera) -->
    <div id="editInstallationHeaderModal" class="modal-overlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="editInstallationHeaderTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="editInstallationHeaderTitle">Editar Instalación</h3>
                <button class="modal-close" onclick="closeModal('editInstallationHeaderModal')" aria-label="Cerrar modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editInstallationHeaderForm">
                    <input type="hidden" id="editInstallationHeaderId" name="id" />
                    <div class="form-group">
                        <label for="editInstallationHeaderName">Nombre *</label>
                        <input type="text" id="editInstallationHeaderName" name="nombre" required>
                        <span class="field-error" id="editInstallationHeaderName-error"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editInstallationHeaderModal')">Cancelar</button>
                <button type="submit" form="editInstallationHeaderForm" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
    </div>
    <script>
        window.AdminRole = '<?= $admin_info['role'] ?>';
        window.isSuperAdmin = <?= $admin_info['role'] === 'superadmin' ? 'true' : 'false' ?>;
    </script>
    <script src="assets/js/installation.js"></script>
</body>
</html>
