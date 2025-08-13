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
});

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
    
    const stats = [
        {
            title: 'Total Instalaciones',
            value: Center.stats.total_instalaciones || 0,
            icon: `<svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.495v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5z"/>
            </svg>`,
            color: 'primary'
        },
        {
            title: 'Actividades Activas',
            value: Center.stats.actividades_activas || 0,
            icon: `<svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
            </svg>`,
            color: 'success'
        },
        {
            title: 'Actividades Programadas',
            value: Center.stats.actividades_programadas || 0,
            icon: `<svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
            </svg>`,
            color: 'warning'
        },
        {
            title: 'Total Participantes',
            value: Center.stats.total_participantes || 0,
            icon: `<svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
            </svg>`,
            color: 'info'
        }
    ];

    const statsHTML = stats.map(stat => `
        <div class="stat-card stat-card-${stat.color}">
            <div class="stat-icon">
                ${stat.icon}
            </div>
            <div class="stat-content">
                <div class="stat-value">${stat.value}</div>
                <div class="stat-title">${stat.title}</div>
            </div>
        </div>
    `).join('');

    statsGrid.innerHTML = statsHTML;
}

/**
 * Renderizar instalaciones
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
        <div class="installation-item" onclick="goToInstallation(${installation.id})" style="cursor: pointer;">
            <div class="installation-main">
                <div class="installation-header">
                    <h3 class="installation-name">${escapeHtml(installation.nombre)}</h3>
                    <span class="installation-status active">Activa</span>
                </div>
                <div class="installation-details">
                    <span class="installation-stat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        ${installation.total_actividades || 0} actividades
                    </span>
                </div>
            </div>
            <div class="installation-actions">
                <div class="dropdown">
                    <button class="more-btn" onclick="toggleInstallationDropdown(event, ${installation.id})">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu" id="installation-dropdown-${installation.id}">
                        <a href="#" onclick="viewActivities(${installation.id})">Ver actividades</a>
                        <a href="#" onclick="editInstallation(${installation.id})">Editar instalación</a>
                        <a href="#" onclick="deactivateInstallation(${installation.id})">Desactivar</a>
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
            <div class="error-state">
                <p>Error cargando estadísticas</p>
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
 * Filtrar instalaciones
 */
function filterInstallations() {
    const searchTerm = document.getElementById('search-installations').value.toLowerCase();
    const installationItems = document.querySelectorAll('.installation-item');
    
    installationItems.forEach(item => {
        const name = item.querySelector('.installation-name').textContent.toLowerCase();
        if (name.includes(searchTerm)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Ordenar instalaciones
 */
function sortInstallations() {
    const sortValue = document.getElementById('sort-installations').value;
    const container = document.getElementById('installations-list');
    const items = Array.from(container.querySelectorAll('.installation-item'));
    
    items.sort((a, b) => {
        const nameA = a.querySelector('.installation-name').textContent;
        const nameB = b.querySelector('.installation-name').textContent;
        
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
    // TODO: Implementar modal de edición de centro
    console.log('Editar centro:', centerId);
}

function toggleInstallationDropdown(event, installationId) {
    event.stopPropagation();
    toggleDropdown(installationId, 'installation-dropdown-');
}

function viewActivities(installationId) {
    window.location.href = `installation.php?id=${installationId}`;
}

function editInstallation(installationId) {
    // TODO: Implementar modal de edición de instalación
    console.log('Editar instalación:', installationId);
}

function deactivateInstallation(installationId) {
    // TODO: Implementar desactivación de instalación
    console.log('Desactivar instalación:', installationId);
}

/**
 * Utilidades reutilizadas del dashboard
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
    
    // Cerrar otros dropdowns
    document.querySelectorAll('.dropdown-menu.open').forEach(menu => {
        if (menu !== dropdown) {
            menu.classList.remove('open');
        }
    });
    
    dropdown.classList.toggle('open');
}

function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Añadir al DOM
    document.body.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Cerrar dropdowns al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.open').forEach(menu => {
            menu.classList.remove('open');
        });
    }
});

/**
 * Funciones para el header (reutilizadas del dashboard)
 */
function showAddOptionsModal() {
    // TODO: Implementar modal de opciones de añadir
    console.log('Mostrar opciones de añadir');
}

// Configurar dropdown del perfil
document.addEventListener('DOMContentLoaded', function() {
    const profileBtn = document.getElementById('profile-dropdown-btn');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        
        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function() {
            profileDropdown.classList.remove('show');
        });
    }
});

// Hacer funciones globales para uso en HTML
window.goBack = goBack;
window.goToInstallation = goToInstallation;
window.showCreateInstallationModal = showCreateInstallationModal;
window.showAddOptionsModal = showAddOptionsModal;
window.editCenter = editCenter;
window.toggleInstallationDropdown = toggleInstallationDropdown;
window.viewActivities = viewActivities;
window.editInstallation = editInstallation;
window.deactivateInstallation = deactivateInstallation;
window.closeModal = closeModal;
