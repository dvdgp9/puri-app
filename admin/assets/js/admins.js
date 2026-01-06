/**
 * Gestión de Administradores - JavaScript
 * Página dedicada para superadmins
 */

// Estado global
const AdminsPage = {
    admins: [],
    centers: [],
    deleteTargetId: null
};

// ====== API ======
const AdminAPI = {
    async list(params = {}) {
        const { search = '' } = params;
        const qs = search ? `?search=${encodeURIComponent(search)}` : '';
        const resp = await fetch(`api/superadmin/admins/list.php${qs}`);
        return resp.json();
    },
    async create(data) {
        const resp = await fetch('api/superadmin/admins/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return resp.json();
    },
    async update(data) {
        const resp = await fetch('api/superadmin/admins/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return resp.json();
    },
    async remove(id) {
        const resp = await fetch('api/superadmin/admins/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        return resp.json();
    },
    async centersList(admin_id) {
        const resp = await fetch(`api/superadmin/admins/centers_list.php?admin_id=${encodeURIComponent(admin_id)}`);
        return resp.json();
    },
    async centersSave(admin_id, center_ids) {
        const resp = await fetch('api/superadmin/admins/centers_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_id, center_ids })
        });
        return resp.json();
    },
    async getAllCenters() {
        const resp = await fetch('api/centros/list.php');
        return resp.json();
    }
};

// ====== Inicialización ======
document.addEventListener('DOMContentLoaded', () => {
    loadAdmins();
    loadAllCenters();
    setupEventListeners();
    setupDropdowns();
});

// ====== Cargar datos ======
async function loadAdmins() {
    const searchValue = document.getElementById('search-admins')?.value || '';
    const sortValue = document.getElementById('sort-admins')?.value || 'created_at_desc';
    
    try {
        const data = await AdminAPI.list({ search: searchValue });
        if (data.success) {
            AdminsPage.admins = sortAdminsList(data.data || [], sortValue);
            renderAdmins();
            updateStats();
        } else {
            showNotification('Error al cargar administradores: ' + (data.error || 'Desconocido'), 'error');
            renderAdminsError();
        }
    } catch (err) {
        console.error(err);
        showNotification('Error de conexión al cargar administradores', 'error');
        renderAdminsError();
    }
}

async function loadAllCenters() {
    try {
        const data = await AdminAPI.getAllCenters();
        if (data.success) {
            AdminsPage.centers = data.data || [];
        }
    } catch (err) {
        console.error('Error cargando centros:', err);
    }
}

// ====== Ordenación ======
function sortAdminsList(items, sort) {
    const arr = [...items];
    switch (sort) {
        case 'created_at_asc':
            return arr.sort((a, b) => (a.created_at > b.created_at ? 1 : -1));
        case 'nombre_asc':
            return arr.sort((a, b) => {
                const nameA = (a.nombre || a.username || '').toLowerCase();
                const nameB = (b.nombre || b.username || '').toLowerCase();
                return nameA.localeCompare(nameB);
            });
        case 'nombre_desc':
            return arr.sort((a, b) => {
                const nameA = (a.nombre || a.username || '').toLowerCase();
                const nameB = (b.nombre || b.username || '').toLowerCase();
                return nameB.localeCompare(nameA);
            });
        case 'username_asc':
            return arr.sort((a, b) => String(a.username).localeCompare(String(b.username)));
        case 'username_desc':
            return arr.sort((a, b) => String(b.username).localeCompare(String(a.username)));
        case 'created_at_desc':
        default:
            return arr.sort((a, b) => (a.created_at < b.created_at ? 1 : -1));
    }
}

// ====== Renderizado ======
function renderAdmins() {
    const tbody = document.getElementById('admins-tbody');
    if (!tbody) return;
    
    const admins = AdminsPage.admins || [];
    
    if (admins.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="empty-cell">
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <h3>No hay administradores</h3>
                        <p>Crea el primer administrador para comenzar</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = admins.map(admin => {
        const fullName = [admin.nombre, admin.apellidos].filter(Boolean).join(' ') || '-';
        const roleClass = admin.role === 'superadmin' ? 'badge-primary' : 'badge-secondary';
        const roleLabel = admin.role === 'superadmin' ? 'Superadmin' : 'Admin';
        const centersCount = admin.role === 'superadmin' ? 'Todos' : (admin.centers_count || 0);
        const createdDate = formatDate(admin.created_at);
        
        return `
            <tr>
                <td>
                    <div class="admin-name-cell">
                        <div class="admin-avatar">${getInitials(admin.nombre, admin.apellidos, admin.username)}</div>
                        <div class="admin-name-info">
                            <span class="admin-fullname">${escapeHtml(fullName)}</span>
                        </div>
                    </div>
                </td>
                <td><code>${escapeHtml(admin.username)}</code></td>
                <td><span class="badge ${roleClass}">${roleLabel}</span></td>
                <td>${centersCount}</td>
                <td>${createdDate}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" title="Editar" onclick="openEditAdmin(${admin.id})">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button class="btn-icon btn-icon-danger" title="Eliminar" onclick="confirmDeleteAdmin(${admin.id})">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderAdminsError() {
    const tbody = document.getElementById('admins-tbody');
    if (!tbody) return;
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="error-cell">
                <div class="error-state">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Error cargando administradores
                </div>
            </td>
        </tr>
    `;
}

function updateStats() {
    const admins = AdminsPage.admins || [];
    const total = admins.length;
    const superadmins = admins.filter(a => a.role === 'superadmin').length;
    const regularAdmins = admins.filter(a => a.role === 'admin').length;
    
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-superadmins').textContent = superadmins;
    document.getElementById('stat-admins').textContent = regularAdmins;
}

// ====== Modales ======
function showCreateAdminModal() {
    const modal = document.getElementById('createAdminModal');
    if (modal) {
        modal.classList.add('show');
        document.getElementById('createAdminForm')?.reset();
        clearFormErrors();
        updateCreateRoleUI();
        loadCentersForCreate();
        setTimeout(() => {
            document.getElementById('adminNombre')?.focus();
        }, 50);
    }
}

function closeCreateAdminModal() {
    const modal = document.getElementById('createAdminModal');
    if (modal) modal.classList.remove('show');
}

function showEditAdminModal() {
    const modal = document.getElementById('editAdminModal');
    if (modal) modal.classList.add('show');
}

function closeEditAdminModal() {
    const modal = document.getElementById('editAdminModal');
    if (modal) modal.classList.remove('show');
}

function closeConfirmDeleteModal() {
    const modal = document.getElementById('confirmDeleteModal');
    if (modal) modal.classList.remove('show');
    AdminsPage.deleteTargetId = null;
}

async function openEditAdmin(id) {
    const admin = AdminsPage.admins.find(a => String(a.id) === String(id));
    if (!admin) {
        showNotification('No se encontraron datos del administrador', 'error');
        return;
    }
    
    document.getElementById('editAdminId').value = admin.id;
    document.getElementById('editAdminNombre').value = admin.nombre || '';
    document.getElementById('editAdminApellidos').value = admin.apellidos || '';
    document.getElementById('editAdminUsername').value = admin.username || '';
    document.getElementById('editAdminRole').value = admin.role || 'admin';
    document.getElementById('editAdminNewPassword').value = '';
    
    clearFormErrors();
    updateEditRoleUI();
    await loadCentersForEdit(admin.id);
    showEditAdminModal();
}

function confirmDeleteAdmin(id) {
    const admin = AdminsPage.admins.find(a => String(a.id) === String(id));
    if (!admin) return;
    
    AdminsPage.deleteTargetId = id;
    const fullName = [admin.nombre, admin.apellidos].filter(Boolean).join(' ') || admin.username;
    document.getElementById('deleteAdminName').textContent = fullName;
    document.getElementById('confirmDeleteModal').classList.add('show');
}

async function executeDeleteAdmin() {
    const id = AdminsPage.deleteTargetId;
    if (!id) return;
    
    const btn = document.getElementById('confirmDeleteBtn');
    btn?.classList.add('loading');
    
    try {
        const result = await AdminAPI.remove(id);
        if (result.success) {
            showNotification('Administrador eliminado correctamente', 'success');
            closeConfirmDeleteModal();
            await loadAdmins();
        } else {
            showNotification(result.error || 'No se pudo eliminar', 'error');
        }
    } catch (err) {
        console.error(err);
        showNotification('Error de conexión', 'error');
    } finally {
        btn?.classList.remove('loading');
    }
}

// ====== Centros UI ======
function loadCentersForCreate() {
    const container = document.getElementById('createAdminCentersList');
    if (!container) return;
    
    const centers = AdminsPage.centers || [];
    if (centers.length === 0) {
        container.innerHTML = '<p class="text-muted">No hay centros disponibles</p>';
        return;
    }
    
    container.innerHTML = centers.map(c => `
        <label class="checkbox-inline" data-name="${escapeHtml(c.nombre.toLowerCase())}">
            <input type="checkbox" name="centers[]" value="${c.id}">
            <span>${escapeHtml(c.nombre)}</span>
        </label>
    `).join('');
}

async function loadCentersForEdit(adminId) {
    const container = document.getElementById('editAdminCentersList');
    if (!container) return;
    
    container.innerHTML = '<p class="text-muted">Cargando centros...</p>';
    
    try {
        const data = await AdminAPI.centersList(adminId);
        if (data.success) {
            const centers = data.data || [];
            if (centers.length === 0) {
                container.innerHTML = '<p class="text-muted">No hay centros disponibles</p>';
                return;
            }
            
            container.innerHTML = centers.map(c => `
                <label class="checkbox-inline" data-name="${escapeHtml(c.nombre.toLowerCase())}">
                    <input type="checkbox" name="centers[]" value="${c.id}" ${c.asignado ? 'checked' : ''}>
                    <span>${escapeHtml(c.nombre)}</span>
                </label>
            `).join('');
        }
    } catch (err) {
        console.error(err);
        container.innerHTML = '<p class="text-muted text-error">Error cargando centros</p>';
    }
}

function updateCreateRoleUI() {
    const role = document.getElementById('adminRole')?.value;
    const centersGroup = document.getElementById('createAdminCentersGroup');
    const superadminInfo = document.getElementById('createAdminSuperadminInfo');
    
    if (role === 'superadmin') {
        centersGroup?.style.setProperty('display', 'none');
        superadminInfo?.style.setProperty('display', 'flex');
    } else {
        centersGroup?.style.setProperty('display', 'block');
        superadminInfo?.style.setProperty('display', 'none');
    }
}

function updateEditRoleUI() {
    const role = document.getElementById('editAdminRole')?.value;
    const centersGroup = document.getElementById('editAdminCentersGroup');
    const superadminInfo = document.getElementById('editAdminSuperadminInfo');
    
    if (role === 'superadmin') {
        centersGroup?.style.setProperty('display', 'none');
        superadminInfo?.style.setProperty('display', 'flex');
    } else {
        centersGroup?.style.setProperty('display', 'block');
        superadminInfo?.style.setProperty('display', 'none');
    }
}

function filterCentersList(listId, noResultsId, term) {
    const container = document.getElementById(listId);
    const noResults = document.getElementById(noResultsId);
    if (!container) return;
    
    const t = (term || '').toLowerCase().trim();
    const items = Array.from(container.querySelectorAll('label.checkbox-inline'));
    let visible = 0;
    
    items.forEach(label => {
        const name = label.getAttribute('data-name') || '';
        const show = !t || name.includes(t);
        label.style.display = show ? 'flex' : 'none';
        if (show) visible++;
    });
    
    if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
}

// ====== Event Listeners ======
function setupEventListeners() {
    // Búsqueda
    const searchInput = document.getElementById('search-admins');
    if (searchInput) {
        let searchTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadAdmins, 300);
        });
    }
    
    // Ordenación
    const sortSelect = document.getElementById('sort-admins');
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            const sortValue = sortSelect.value;
            AdminsPage.admins = sortAdminsList(AdminsPage.admins, sortValue);
            renderAdmins();
        });
    }
    
    // Cambio de rol en crear
    const createRoleSelect = document.getElementById('adminRole');
    if (createRoleSelect) {
        createRoleSelect.addEventListener('change', updateCreateRoleUI);
    }
    
    // Cambio de rol en editar
    const editRoleSelect = document.getElementById('editAdminRole');
    if (editRoleSelect) {
        editRoleSelect.addEventListener('change', updateEditRoleUI);
    }
    
    // Formulario crear
    const createForm = document.getElementById('createAdminForm');
    if (createForm) {
        createForm.addEventListener('submit', handleCreateAdmin);
    }
    
    // Formulario editar
    const editForm = document.getElementById('editAdminForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditAdmin);
    }
    
    // Cerrar modales con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCreateAdminModal();
            closeEditAdminModal();
            closeConfirmDeleteModal();
        }
    });
    
    // Cerrar modales al hacer clic fuera
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('show');
            }
        });
    });
}

async function handleCreateAdmin(e) {
    e.preventDefault();
    clearFormErrors();
    
    const formData = new FormData(e.target);
    const data = {
        nombre: formData.get('nombre')?.trim() || '',
        apellidos: formData.get('apellidos')?.trim() || '',
        username: formData.get('username')?.trim() || '',
        password: formData.get('password') || '',
        role: formData.get('role') || 'admin'
    };
    
    // Validaciones
    if (!data.username) {
        showFieldError('adminUsername', 'El usuario es obligatorio');
        return;
    }
    if (!data.password || data.password.length < 8) {
        showFieldError('adminPassword', 'La contraseña debe tener al menos 8 caracteres');
        return;
    }
    
    const btn = document.getElementById('createAdminBtn');
    btn?.classList.add('loading');
    if (btn) btn.disabled = true;
    
    try {
        const result = await AdminAPI.create(data);
        if (result.success) {
            const newId = result.data?.id;
            
            // Guardar centros si no es superadmin
            if (data.role !== 'superadmin' && newId) {
                const centersContainer = document.getElementById('createAdminCentersList');
                if (centersContainer) {
                    const selectedCenters = Array.from(centersContainer.querySelectorAll('input:checked'))
                        .map(ch => parseInt(ch.value, 10))
                        .filter(n => n > 0);
                    
                    if (selectedCenters.length > 0) {
                        await AdminAPI.centersSave(newId, selectedCenters);
                    }
                }
            }
            
            showNotification('Administrador creado correctamente', 'success');
            closeCreateAdminModal();
            await loadAdmins();
        } else {
            if (/usuario/i.test(result.error || '')) {
                showFieldError('adminUsername', result.error);
            } else {
                showNotification(result.error || 'No se pudo crear el administrador', 'error');
            }
        }
    } catch (err) {
        console.error(err);
        showNotification('Error de conexión', 'error');
    } finally {
        btn?.classList.remove('loading');
        if (btn) btn.disabled = false;
    }
}

async function handleEditAdmin(e) {
    e.preventDefault();
    clearFormErrors();
    
    const formData = new FormData(e.target);
    const id = parseInt(formData.get('id'), 10);
    const newPassword = formData.get('new_password')?.trim() || '';
    
    const data = {
        id,
        nombre: formData.get('nombre')?.trim() || '',
        apellidos: formData.get('apellidos')?.trim() || '',
        role: formData.get('role') || 'admin'
    };
    
    if (newPassword) {
        if (newPassword.length < 8) {
            showFieldError('editAdminNewPassword', 'La contraseña debe tener al menos 8 caracteres');
            return;
        }
        data.new_password = newPassword;
    }
    
    const btn = document.getElementById('saveEditAdminBtn');
    btn?.classList.add('loading');
    if (btn) btn.disabled = true;
    
    try {
        const result = await AdminAPI.update(data);
        
        // Guardar centros si no es superadmin
        if (data.role !== 'superadmin') {
            const centersContainer = document.getElementById('editAdminCentersList');
            if (centersContainer) {
                const selectedCenters = Array.from(centersContainer.querySelectorAll('input:checked'))
                    .map(ch => parseInt(ch.value, 10))
                    .filter(n => n > 0);
                
                await AdminAPI.centersSave(id, selectedCenters);
            }
        }
        
        if (result.success) {
            showNotification('Administrador actualizado correctamente', 'success');
            closeEditAdminModal();
            await loadAdmins();
        } else {
            showNotification(result.error || 'No se pudo actualizar', 'error');
        }
    } catch (err) {
        console.error(err);
        showNotification('Error de conexión', 'error');
    } finally {
        btn?.classList.remove('loading');
        if (btn) btn.disabled = false;
    }
}

// ====== Utilidades ======
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getInitials(nombre, apellidos, username) {
    if (nombre && apellidos) {
        return (nombre[0] + apellidos[0]).toUpperCase();
    } else if (nombre) {
        return nombre.substring(0, 2).toUpperCase();
    } else if (username) {
        return username.substring(0, 2).toUpperCase();
    }
    return '??';
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    if (!notification) return;
    
    notification.textContent = message;
    notification.className = `notification ${type} show`;
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 4000);
}

function clearFormErrors() {
    document.querySelectorAll('.form-error').forEach(el => {
        el.textContent = '';
    });
}

function showFieldError(fieldId, message) {
    const errorEl = document.getElementById(fieldId + '-error');
    if (errorEl) errorEl.textContent = message;
}

function setupDropdowns() {
    const profileBtn = document.getElementById('profile-dropdown-btn');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', () => {
            profileDropdown.classList.remove('show');
        });
    }
}
