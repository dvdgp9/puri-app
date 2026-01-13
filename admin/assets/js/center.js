/**
 * JavaScript para la página de detalle de centro
 * Reutiliza patrones del dashboard para consistencia
 */

// Estado global del centro
const Center = {
    id: null,
    data: null,
    stats: null,
    installations: []
};

/**
 * Inicialización
 */
document.addEventListener('DOMContentLoaded', function() {
    // Obtener ID del centro de la URL
    const urlParams = new URLSearchParams(window.location.search);
    Center.id = parseInt(urlParams.get('id'));
    
    if (!Center.id) {
        window.location.href = 'dashboard.php';
        return;
    }

    // Cargar datos
    loadCenterStats();
    loadInstallations();
    
    // Configurar búsqueda y ordenación
    setupSearch();
    setupInstallationForm();
    setupEditInstallationForm();

    // Prefijar valores del modal de edición con datos iniciales si existen en el header
    const nameEl = document.querySelector('.center-title');
    const addrEl = document.querySelector('.center-address');
    if (nameEl && document.getElementById('editCenterName')) {
        document.getElementById('editCenterName').value = (nameEl.textContent || '').trim();
    }
    if (addrEl && document.getElementById('editCenterAddress')) {
        const txt = (addrEl.textContent || '').replace(/^\s*\n?\s*/, '').trim();
        document.getElementById('editCenterAddress').value = txt.replace(/^\s*$/, '');
    }

    // Submit editar centro
    const editCenterForm = document.getElementById('editCenterForm');
    if (editCenterForm) {
        editCenterForm.addEventListener('submit', handleEditCenterSubmit);
    }
});

/**
 * Manejar edición de instalación
 */
async function handleEditInstallation(e) {
    e.preventDefault();

    const id = document.getElementById('editInstallationId').value;
    const nombre = document.getElementById('editInstallationName').value.trim();

    const errorSpan = document.getElementById('editInstallationName-error');
    if (errorSpan) errorSpan.textContent = '';

    if (!nombre) {
        if (errorSpan) errorSpan.textContent = 'El nombre es obligatorio';
        return;
    }

    try {
        const resp = await fetch('api/instalaciones/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: Number(id), nombre })
        });
        const result = await resp.json();
        if (result.success) {
            closeModal('editInstallationModal');
            await loadInstallations();
            await loadCenterStats();
            showNotification('Instalación actualizada', 'success');
        } else {
            showNotification(result.message || 'No se pudo actualizar la instalación', 'error');
        }
    } catch (err) {
        console.error(err);
        showNotification('Error actualizando la instalación', 'error');
    }
}

/**
 * Cargar estadísticas del centro
 */
async function loadCenterStats() {
    try {
        const response = await fetch(`api/stats/center.php?id=${Center.id}`);
        const data = await response.json();
        
        if (data.success) {
            Center.stats = data.data;
            renderStats();
        } else {
            throw new Error(data.error || 'Error cargando estadísticas');
        }
        
    } catch (error) {
        console.error('Error cargando estadísticas del centro:', error);
        showStatsError();
    }
}

/**
 * Cargar instalaciones del centro
 */
async function loadInstallations() {
    try {
        const response = await fetch(`api/instalaciones/list_by_center.php?centro_id=${Center.id}`);
        const data = await response.json();
        
        if (data.success) {
            Center.installations = data.instalaciones || [];
            renderInstallations();
        } else {
            throw new Error(data.error || 'Error cargando instalaciones');
        }
        
    } catch (error) {
        console.error('Error cargando instalaciones:', error);
        showInstallationsError();
    }
}

/**
 * Renderizar estadísticas
 */
function renderStats() {
    const statsGrid = document.getElementById('stats-grid');
    if (!statsGrid || !Center.stats) return;

    const s = Center.stats;
    statsGrid.innerHTML = `
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Instalaciones</div>
                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                </svg>
            </div>
            <div class="stat-value">${s.total_instalaciones || 0}</div>
            <div class="stat-change">Total disponibles</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Actividades</div>
                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-value">${s.actividades_activas || 0}</div>
            <div class="stat-change">Activas • ${s.actividades_programadas || 0} programadas</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Participantes</div>
                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div class="stat-value">${s.total_participantes || 0}</div>
            <div class="stat-change">Acumulado</div>
        </div>
    `;
}

/**
 * Renderizar instalaciones - usando clases del dashboard
 */
function renderInstallations() {
    const container = document.getElementById('installations-list');
    if (!container) return;

    if (!Center.installations || Center.installations.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <svg width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.495v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5z"/>
                </svg>
                <h3>No hay instalaciones</h3>
                <p>Crea la primera instalación para este centro</p>
                <button class="btn btn-primary" onclick="showCreateInstallationModal()">+ Nueva Instalación</button>
            </div>
        `;
        return;
    }

    const installationsHTML = Center.installations.map(installation => `
        <div class="center-item" onclick="goToInstallation(${installation.id})" style="cursor: pointer;">
            <div class="center-main">
                <div class="center-header">
                    <h3 class="center-name">${escapeHtml(decodeHtml(installation.nombre || ''))}</h3>
                    <span class="center-status ${installation.activo ? 'active' : 'inactive'}">${installation.activo ? 'Activa' : 'Inactiva'}</span>
                </div>
                <div class="center-details">
                    <span class="center-stat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        Actividades: ${installation.actividades_activas ?? installation.total_activas ?? 0} activas, ${installation.actividades_programadas ?? installation.total_programadas ?? 0} programadas, ${installation.actividades_finalizadas ?? installation.total_finalizadas ?? 0} finalizadas
                    </span>
                    <span class="center-stat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                        </svg>
                        ${installation.total_inscritos || 0} inscritos
                    </span>
                    <span class="center-stat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm6.5 4.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3a.5.5 0 0 1 1 0z"/>
                        </svg>
                        ${installation.total_asistencias || 0} asistencias
                    </span>
                </div>
            </div>
            <div class="center-actions">
                <div class="dropdown" onclick="event.stopPropagation();">
                    <button class="more-btn" onclick="toggleInstallationDropdown(event, ${installation.id}); return false;">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3z"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu" id="installation-dropdown-${installation.id}" onclick="event.stopPropagation();">
                        <a href="#" onclick="event.preventDefault(); editInstallation(${installation.id});">Editar</a>
                        <a href="#" onclick="event.preventDefault(); toggleActiveInstallation(${installation.id}, ${installation.activo ? 1 : 0});">${installation.activo ? 'Desactivar' : 'Activar'}</a>
                        ${window.isSuperAdmin ? `<a href="#" class="dropdown-item-danger" onclick="event.preventDefault(); deleteInstallation(${installation.id}, '${escapeHtml(installation.nombre || '')}');">Eliminar</a>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    container.innerHTML = installationsHTML;
}

/**
 * Mostrar errores
 */
function showStatsError() {
    const statsGrid = document.getElementById('stats-grid');
    if (statsGrid) {
        statsGrid.innerHTML = `
            <div class="error-card">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Error cargando estadísticas
            </div>
        `;
    }
}

function showInstallationsError() {
    const container = document.getElementById('installations-list');
    if (container) {
        container.innerHTML = `
            <div class="error-state">
                <p>Error cargando instalaciones</p>
                <button class="btn btn-secondary" onclick="loadInstallations()">Reintentar</button>
            </div>
        `;
    }
}

/**
 * Configurar búsqueda
 */
function setupSearch() {
    const searchInput = document.getElementById('search-installations');
    if (searchInput) {
        searchInput.addEventListener('input', filterInstallations);
    }
    
    const sortSelect = document.getElementById('sort-installations');
    if (sortSelect) {
        sortSelect.addEventListener('change', sortInstallations);
    }
}

/**
 * Filtrar instalaciones - usando clases del dashboard
 */
function filterInstallations() {
    const searchTerm = document.getElementById('search-installations').value.toLowerCase();
    const installationItems = document.querySelectorAll('.center-item');
    
    installationItems.forEach(item => {
        const name = item.querySelector('.center-name').textContent.toLowerCase();
        if (name.includes(searchTerm)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Ordenar instalaciones - usando clases del dashboard
 */
function sortInstallations() {
    const sortValue = document.getElementById('sort-installations').value;
    const container = document.getElementById('installations-list');
    const items = Array.from(container.querySelectorAll('.center-item'));
    
    items.sort((a, b) => {
        const nameA = a.querySelector('.center-name').textContent;
        const nameB = b.querySelector('.center-name').textContent;
        
        if (sortValue === '-nombre') {
            return nameB.localeCompare(nameA);
        } else {
            return nameA.localeCompare(nameB);
        }
    });
    
    items.forEach(item => container.appendChild(item));
}

/**
 * Configurar formulario de instalación
 */
function setupInstallationForm() {
    const form = document.getElementById('createInstallationForm');
    if (form) {
        form.addEventListener('submit', handleCreateInstallation);
    }
}

/**
 * Configurar formulario de edición de instalación
 */
function setupEditInstallationForm() {
    const form = document.getElementById('editInstallationForm');
    if (form) {
        form.addEventListener('submit', handleEditInstallation);
    }
}

/**
 * Manejar creación de instalación
 */
async function handleCreateInstallation(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        nombre: formData.get('nombre'),
        centro_id: Center.id
    };
    
    try {
        const response = await fetch('api/instalaciones/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal('createInstallationModal');
            loadInstallations(); // Recargar lista
            loadCenterStats(); // Actualizar estadísticas
            showNotification('Instalación creada exitosamente', 'success');
        } else {
            throw new Error(result.message || 'Error creando instalación');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    }
}

/**
 * Funciones de navegación y acciones
 */
function goBack() {
    window.location.href = 'dashboard.php';
}

function goToInstallation(installationId) {
    window.location.href = `installation.php?id=${installationId}`;
}

function showCreateInstallationModal() {
    openModal('createInstallationModal');
}

function editCenter(centerId) {
    // Prefijar datos desde la cabecera si están
    const title = document.querySelector('.center-title');
    const addr = document.querySelector('.center-address');
    const idInput = document.getElementById('editCenterId');
    const nameInput = document.getElementById('editCenterName');
    const addrInput = document.getElementById('editCenterAddress');
    if (idInput) idInput.value = String(centerId || Center.id || '');
    if (nameInput && title) nameInput.value = (title.textContent || '').trim();
    if (addrInput && addr) addrInput.value = (addr.textContent || '').trim().replace(/^Sin dirección$/i, '');
    showEditCenterModal();
}

function showEditCenterModal() {
    const modal = document.getElementById('editCenterModal');
    if (modal) {
        modal.classList.add('show');
        setTimeout(() => {
            const firstInput = modal.querySelector('input[type="text"]');
            if (firstInput) firstInput.focus();
        }, 100);
    }
}

function closeEditCenterModal() {
    const modal = document.getElementById('editCenterModal');
    if (modal) modal.classList.remove('show');
}

async function handleEditCenterSubmit(e) {
    e.preventDefault();
    // limpiar errores
    const errName = document.getElementById('editCenterName-error');
    if (errName) errName.textContent = '';
    const id = Number(document.getElementById('editCenterId').value);
    const nombre = String(document.getElementById('editCenterName').value || '').trim();
    const direccion = String(document.getElementById('editCenterAddress').value || '').trim();
    if (!nombre) {
        if (errName) errName.textContent = 'El nombre del centro es obligatorio';
        return;
    }
    const btn = document.getElementById('saveEditCenterBtn');
    if (btn) { btn.classList.add('loading'); btn.disabled = true; }
    try {
        const resp = await fetch('api/centros/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre, direccion })
        });
        const result = await resp.json();
        if (resp.ok && result.success) {
            // actualizar cabecera
            const title = document.querySelector('.center-title');
            const address = document.querySelector('.center-address');
            if (title) title.textContent = nombre;
            if (address) address.innerHTML = address.innerHTML.replace(/>([^<]*)$/, `>${direccion || 'Sin dirección'}`);
            closeEditCenterModal();
            showNotification('Centro actualizado correctamente', 'success');
        } else {
            showNotification(result.error || result.message || 'No se pudo actualizar el centro', 'error');
            if ((result.error || '').toLowerCase().includes('nombre')) {
                if (errName) errName.textContent = result.error;
            }
        }
    } catch (err) {
        console.error(err);
        showNotification('Error al actualizar el centro', 'error');
    } finally {
        if (btn) { btn.classList.remove('loading'); btn.disabled = false; }
    }
}

function toggleInstallationDropdown(event, installationId) {
    event.stopPropagation();
    toggleDropdown(installationId, 'installation-dropdown-');
}

function viewActivities(installationId) {
    window.location.href = `installation.php?id=${installationId}`;
}

function editInstallation(installationId) {
    const inst = (Center.installations || []).find(i => i.id === installationId);
    if (!inst) return;
    document.getElementById('editInstallationId').value = installationId;
    document.getElementById('editInstallationName').value = decodeHtml(inst.nombre || '');
    // cerrar el dropdown de esta instalación si está abierto
    const menu = document.getElementById(`installation-dropdown-${installationId}`);
    if (menu) {
        menu.classList.remove('show', 'open', 'dropup');
        menu.style.top = '';
        menu.style.left = '';
        menu.style.right = '';
        menu.style.bottom = '';
    }
    openModal('editInstallationModal');
}

async function toggleActiveInstallation(installationId, currentActive) {
    try {
        const next = currentActive ? 0 : 1;
        const resp = await fetch('api/instalaciones/set_active.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: installationId, activo: next })
        });
        const result = await resp.json();
        if (result.success) {
            showNotification(next ? 'Instalación activada' : 'Instalación desactivada', 'success');
            await loadInstallations();
            await loadCenterStats();
        } else {
            showNotification(result.message || 'No se pudo actualizar el estado', 'error');
        }
    } catch (e) {
        console.error(e);
        showNotification('Error actualizando el estado', 'error');
    }
}

/**
 * Utilidades reutilizadas del dashboard
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Decodifica entidades HTML si el backend o los datos ya vienen codificados (e.g. &iacute;)
function decodeHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        
        // Limpiar formulario si existe
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            // Limpiar errores
            const errors = form.querySelectorAll('.field-error');
            errors.forEach(error => error.textContent = '');
        }
    }
}

function toggleDropdown(id, prefix = 'dropdown-') {
    const dropdown = document.getElementById(prefix + id);
    if (!dropdown) return;
    
    const wasOpen = dropdown.classList.contains('open');
    
    // Cerrar otros dropdowns
    document.querySelectorAll('.dropdown-menu.open').forEach(menu => {
        menu.classList.remove('open');
        menu.classList.remove('dropup');
        menu.style.display = 'none';
        menu.style.top = '';
        menu.style.left = '';
        menu.style.right = '';
        menu.style.bottom = '';
    });
    
    if (wasOpen) return; // Si estaba abierto, ya lo cerramos arriba
    
    // Obtener posición del contenedor dropdown
    const dropdownContainer = dropdown.closest('.dropdown');
    if (dropdownContainer) {
        const containerRect = dropdownContainer.getBoundingClientRect();
        const viewportH = window.innerHeight || document.documentElement.clientHeight;
        const viewportW = window.innerWidth || document.documentElement.clientWidth;
        
        // Calcular posición
        const isInBottomHalf = containerRect.bottom > (viewportH / 2);
        const isNearRightEdge = containerRect.right > (viewportW - 180); // 180px = ancho aprox del menu
        
        if (isInBottomHalf) {
            dropdown.classList.add('dropup');
            dropdown.style.bottom = (viewportH - containerRect.top) + 'px';
            dropdown.style.top = 'auto';
        } else {
            dropdown.style.top = containerRect.bottom + 'px';
            dropdown.style.bottom = 'auto';
        }
        
        if (isNearRightEdge) {
            dropdown.style.right = (viewportW - containerRect.right) + 'px';
            dropdown.style.left = 'auto';
        } else {
            dropdown.style.left = containerRect.left + 'px';
            dropdown.style.right = 'auto';
        }
    }
    
    // Abrir dropdown
    dropdown.classList.add('open');
    dropdown.style.display = 'block';
}

function showNotification(message, type = 'info') {
    // Crear elemento de notificación (estructura unificada con dashboard)
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;
    
    // Añadir al DOM
    document.body.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Auto-remover después de 5 segundos (igual que dashboard)
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Cerrar dropdowns al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.open').forEach(menu => {
            menu.classList.remove('open');
            menu.classList.remove('dropup');
            menu.style.top = '';
            menu.style.left = '';
            menu.style.right = '';
            menu.style.bottom = '';
        });
    }
});

// (Eliminado) Botón + Añadir y modales de actividad/participante

// Configurar dropdown del perfil
document.addEventListener('DOMContentLoaded', function() {
    const profileBtn = document.getElementById('profile-dropdown-btn');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });
        
        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function() {
            profileDropdown.classList.remove('active');
        });
    }
});

/**
 * Eliminar instalación en cascada (solo superadmin)
 */
async function deleteInstallation(id, nombre) {
    if (!window.isSuperAdmin) {
        showNotification('Solo superadmin puede eliminar instalaciones', 'error');
        return;
    }
    
    const confirmMsg = `¿Estás seguro de eliminar la instalación "${nombre}"?\n\nEsto eliminará PERMANENTEMENTE:\n- Todas las actividades de esta instalación\n- Todos los participantes inscritos\n- Todo el historial de asistencias\n\nEsta acción NO se puede deshacer.`;
    
    if (!confirm(confirmMsg)) return;
    
    try {
        const response = await fetch('api/instalaciones/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            // Recargar instalaciones y estadísticas
            await loadInstallations();
            await loadCenterStats();
        } else {
            showNotification(result.message || 'Error al eliminar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Hacer funciones globales para uso en HTML
window.goBack = goBack;
window.goToInstallation = goToInstallation;
window.showCreateInstallationModal = showCreateInstallationModal;
window.editCenter = editCenter;
window.toggleInstallationDropdown = toggleInstallationDropdown;
window.viewActivities = viewActivities;
window.editInstallation = editInstallation;
window.toggleActiveInstallation = toggleActiveInstallation;
window.closeModal = closeModal;
window.deleteInstallation = deleteInstallation;
