<?php
/**
 * Gestión de Administradores
 * Página completa para superadmins
 */

require_once '../config/config.php';
require_once 'auth_middleware.php';

// Solo superadmins pueden acceder
if (!isSuperAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$admin_info = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Administradores - Admin Puri</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=GeistSans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="logo-section">
            <a href="dashboard.php" class="back-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="logo">P</div>
            <div class="title">Gestión de Administradores</div>
        </div>
        <div class="actions">
            <div class="dropdown">
                <button class="btn btn-secondary" id="profile-dropdown-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?php echo htmlspecialchars($admin_info['username']); ?>
                </button>
                <div class="dropdown-content" id="profile-dropdown">
                    <a href="account.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Mi Cuenta
                    </a>
                    <a href="logout.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-content">
        <!-- Panel de Administradores -->
        <div class="centers-panel">
            <div class="centers-header">
                <h2 class="centers-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Administradores del Sistema
                </h2>
                <div class="centers-actions">
                    <input type="text" id="search-admins" class="search-input" placeholder="Buscar por nombre, usuario...">
                    <select id="sort-admins" class="sort-select">
                        <option value="created_at_desc">Más recientes</option>
                        <option value="created_at_asc">Más antiguos</option>
                        <option value="nombre_asc">Nombre A-Z</option>
                        <option value="nombre_desc">Nombre Z-A</option>
                        <option value="username_asc">Usuario A-Z</option>
                        <option value="username_desc">Usuario Z-A</option>
                    </select>
                    <button class="btn btn-primary" onclick="showCreateAdminModal()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Nuevo Administrador
                    </button>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="admin-stats-bar">
                <div class="admin-stat">
                    <span class="admin-stat-value" id="stat-total">-</span>
                    <span class="admin-stat-label">Total</span>
                </div>
                <div class="admin-stat">
                    <span class="admin-stat-value" id="stat-superadmins">-</span>
                    <span class="admin-stat-label">Superadmins</span>
                </div>
                <div class="admin-stat">
                    <span class="admin-stat-value" id="stat-admins">-</span>
                    <span class="admin-stat-label">Admins</span>
                </div>
            </div>

            <!-- Tabla de administradores -->
            <div class="table-container">
                <table class="data-table" id="admins-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Centros asignados</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="admins-tbody">
                        <tr>
                            <td colspan="6" class="loading-cell">Cargando administradores...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Crear Admin -->
    <div class="modal-overlay" id="createAdminModal">
        <div class="modal modal-medium">
            <div class="modal-header">
                <h2 class="modal-title">Nuevo Administrador</h2>
                <button class="modal-close" onclick="closeCreateAdminModal()">&times;</button>
            </div>
            <form id="createAdminForm">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="adminNombre">Nombre</label>
                            <input type="text" id="adminNombre" name="nombre" class="form-input" placeholder="Nombre">
                            <div class="form-error" id="adminNombre-error"></div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label" for="adminApellidos">Apellidos</label>
                            <input type="text" id="adminApellidos" name="apellidos" class="form-input" placeholder="Apellidos">
                            <div class="form-error" id="adminApellidos-error"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="adminUsername">Usuario <span class="required">*</span></label>
                        <input type="text" id="adminUsername" name="username" class="form-input" placeholder="nombre.usuario" required>
                        <div class="form-error" id="adminUsername-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="adminPassword">Contraseña <span class="required">*</span></label>
                        <input type="password" id="adminPassword" name="password" class="form-input" placeholder="Mínimo 8 caracteres" required>
                        <div class="form-error" id="adminPassword-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="adminRole">Rol <span class="required">*</span></label>
                        <select id="adminRole" name="role" class="form-input" required>
                            <option value="admin">Admin - Acceso limitado a centros asignados</option>
                            <option value="superadmin">Superadmin - Acceso total al sistema</option>
                        </select>
                        <div class="form-error" id="adminRole-error"></div>
                    </div>
                    <div class="form-group" id="createAdminCentersGroup">
                        <label class="form-label">Centros asignados</label>
                        <p class="form-hint">Selecciona los centros a los que este administrador tendrá acceso.</p>
                        <input type="text" id="createAdminCentersSearch" class="form-input" placeholder="Buscar centros..." oninput="filterCentersList('createAdminCentersList', 'createAdminCentersNoResults', this.value)">
                        <div id="createAdminCentersList" class="checkbox-group" style="max-height: 200px; overflow: auto; margin-top: 8px;">
                            <!-- Centros se cargan dinámicamente -->
                        </div>
                        <div id="createAdminCentersNoResults" class="empty-state small" style="display:none;">No hay centros que coincidan</div>
                    </div>
                    <div id="createAdminSuperadminInfo" class="info-box info-box-warning" style="display:none;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <strong>Atención</strong>
                            <p>Los superadmins tienen acceso completo a todos los centros, instalaciones y actividades del sistema.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateAdminModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="createAdminBtn">
                        <span class="btn-text">Crear Administrador</span>
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

    <!-- Modal Editar Admin -->
    <div class="modal-overlay" id="editAdminModal">
        <div class="modal modal-medium">
            <div class="modal-header">
                <h2 class="modal-title">Editar Administrador</h2>
                <button class="modal-close" onclick="closeEditAdminModal()">&times;</button>
            </div>
            <form id="editAdminForm">
                <input type="hidden" id="editAdminId" name="id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="editAdminNombre">Nombre</label>
                            <input type="text" id="editAdminNombre" name="nombre" class="form-input" placeholder="Nombre">
                            <div class="form-error" id="editAdminNombre-error"></div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label" for="editAdminApellidos">Apellidos</label>
                            <input type="text" id="editAdminApellidos" name="apellidos" class="form-input" placeholder="Apellidos">
                            <div class="form-error" id="editAdminApellidos-error"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Usuario</label>
                        <input type="text" id="editAdminUsername" class="form-input" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editAdminRole">Rol</label>
                        <select id="editAdminRole" name="role" class="form-input">
                            <option value="admin">Admin - Acceso limitado a centros asignados</option>
                            <option value="superadmin">Superadmin - Acceso total al sistema</option>
                        </select>
                        <div class="form-error" id="editAdminRole-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editAdminNewPassword">Nueva Contraseña</label>
                        <input type="password" id="editAdminNewPassword" name="new_password" class="form-input" placeholder="Dejar vacío para no cambiar">
                        <div class="form-error" id="editAdminNewPassword-error"></div>
                    </div>
                    <div class="form-group" id="editAdminCentersGroup">
                        <label class="form-label">Centros asignados</label>
                        <p class="form-hint">Selecciona los centros a los que este administrador tendrá acceso.</p>
                        <input type="text" id="editAdminCentersSearch" class="form-input" placeholder="Buscar centros..." oninput="filterCentersList('editAdminCentersList', 'editAdminCentersNoResults', this.value)">
                        <div id="editAdminCentersList" class="checkbox-group" style="max-height: 200px; overflow: auto; margin-top: 8px;">
                            <!-- Centros se cargan dinámicamente -->
                        </div>
                        <div id="editAdminCentersNoResults" class="empty-state small" style="display:none;">No hay centros que coincidan</div>
                    </div>
                    <div id="editAdminSuperadminInfo" class="info-box info-box-warning" style="display:none;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <strong>Atención</strong>
                            <p>Los superadmins tienen acceso completo a todos los centros, instalaciones y actividades del sistema.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditAdminModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="saveEditAdminBtn">
                        <span class="btn-text">Guardar Cambios</span>
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

    <!-- Modal Confirmar Eliminación -->
    <div class="modal-overlay" id="confirmDeleteModal">
        <div class="modal modal-small">
            <div class="modal-header">
                <h2 class="modal-title">Confirmar Eliminación</h2>
                <button class="modal-close" onclick="closeConfirmDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirm-delete-content">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48" class="warning-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p>¿Estás seguro de que deseas eliminar a <strong id="deleteAdminName"></strong>?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmDeleteModal()">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="executeDeleteAdmin()">
                    <span class="btn-text">Eliminar</span>
                    <span class="btn-loading">
                        <svg class="loading-spinner" width="16" height="16" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="60" stroke-dashoffset="60"/>
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Notificaciones -->
    <div id="notification" class="notification"></div>

    <script src="assets/js/admins.js"></script>
</body>
</html>
