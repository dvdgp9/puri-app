/**
 * Dashboard JavaScript - Arquitectura simple
 * Sin frameworks complejos, solo vanilla JS + AJAX
 */

// Estado global simple
const Dashboard = {
    stats: null,
    centers: [],
    currentUser: null
};

// Inicializar dashboard cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando dashboard...');
    
    // Configurar event listeners
    setupEventListeners();
    
    // Cargar datos iniciales
    loadDashboardData();
});

/**
 * Configurar todos los event listeners
 */
function setupEventListeners() {
    // Dropdowns
    setupDropdowns();
    
    // Configurar modal de actividades
    setupActivityModal();
    
    // Configurar modal de participantes
    setupParticipantModal();
    
    // Búsqueda de centros
    const searchInput = document.getElementById('search-centers');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            filterCenters(e.target.value);
        });
    }
    
    // Ordenación de centros
    const sortSelect = document.getElementById('sort-centers');
    if (sortSelect) {
        sortSelect.addEventListener('change', function(e) {
            sortCenters(e.target.value);
        });
    }
    
    // Formulario crear centro
    const createCenterForm = document.getElementById('createCenterForm');
    if (createCenterForm) {
        createCenterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Limpiar errores previos
            clearFormErrors();
            
            // Obtener datos del formulario
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Validación básica
            if (!data.nombre.trim()) {
                showFieldError('centerName', 'El nombre del centro es obligatorio');
                return;
            }
            
            // Mostrar loading
            const submitBtn = document.getElementById('createCenterBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            try {
                await createCenter(data);
            } finally {
                // Quitar loading
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    }
    
    // Formulario crear instalación
    const createInstallationForm = document.getElementById('createInstallationForm');
    if (createInstallationForm) {
        createInstallationForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Limpiar errores previos
            clearFormErrors();
            
            // Obtener datos del formulario
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Validación básica
            if (!data.centro_id) {
                showFieldError('installationCenter', 'Debe seleccionar un centro');
                return;
            }
            
            if (!data.nombre.trim()) {
                showFieldError('installationName', 'El nombre de la instalación es obligatorio');
                return;
            }
            
            // Mostrar loading
            const submitBtn = document.getElementById('createInstallationBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            try {
                await createInstallation(data);
            } finally {
                // Quitar loading
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    }
}

/**
 * Configurar dropdowns
 */
function setupDropdowns() {
    // Dropdown "Añadir"
    const addBtn = document.getElementById('add-dropdown-btn');
    const addDropdown = document.getElementById('add-dropdown');
    
    if (addBtn && addDropdown) {
        addBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown(addDropdown);
        });
    }
    
    // Dropdown "Perfil"
    const profileBtn = document.getElementById('profile-dropdown-btn');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown(profileDropdown);
        });
    }
    
    // Cerrar dropdowns al hacer click fuera
    document.addEventListener('click', function() {
        closeAllDropdowns();
    });
}

/**
 * Toggle dropdown
 */
function toggleDropdown(dropdown) {
    // Cerrar otros dropdowns
    closeAllDropdowns();
    
    // Toggle el dropdown actual
    dropdown.classList.toggle('active');
}

/**
 * Cerrar todos los dropdowns
 */
function closeAllDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown-content');
    dropdowns.forEach(dropdown => {
        dropdown.classList.remove('active');
    });
}

/**
 * Cargar todos los datos del dashboard
 */
async function loadDashboardData() {
    console.log('📊 Cargando datos del dashboard...');
    
    try {
        // Cargar estadísticas y centros en paralelo
        await Promise.all([
            loadStats(),
            loadCenters()
        ]);
        
        console.log('✅ Datos cargados correctamente');
        
    } catch (error) {
        console.error('❌ Error cargando datos:', error);
        showError('Error al cargar los datos del dashboard');
    }
}

/**
 * Cargar estadísticas
 */
async function loadStats() {
    try {
        const response = await fetch('api/stats/dashboard.php');
        const data = await response.json();
        
        if (data.success) {
            Dashboard.stats = data.data;
            renderStats();
        } else {
            throw new Error(data.error || 'Error cargando estadísticas');
        }
        
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        showStatsError();
    }
}

/**
 * Cargar centros
 */
async function loadCenters() {
    try {
        // Usar el endpoint corregido
        const response = await fetch('api/centros/list_new.php');
        const data = await response.json();
        
        if (data.success) {
            Dashboard.centers = data.data || [];
            renderCenters();
        } else {
            throw new Error(data.error || 'Error cargando centros');
        }
        
    } catch (error) {
        console.error('Error cargando centros:', error);
        showCentersError();
    }
}

/**
 * Renderizar estadísticas
 */
function renderStats() {
    const statsGrid = document.getElementById('stats-grid');
    if (!statsGrid || !Dashboard.stats) return;
    
    const stats = Dashboard.stats;
    
    statsGrid.innerHTML = `
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Centros</div>
                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div class="stat-value">${stats.total_centros || 0}</div>
            <div class="stat-change">Total activos</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Instalaciones</div>
                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                </svg>
            </div>
            <div class="stat-value">${stats.total_instalaciones || 0}</div>
            <div class="stat-change">Total disponibles</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Actividades</div>
                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-value">${stats.total_actividades_activas || 0}</div>
            <div class="stat-change">Activas • ${stats.total_actividades_programadas || 0} programadas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">% Asistencia</div>
                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="stat-value">${stats.porcentaje_asistencia || 0}%</div>
            <div class="stat-change">${stats.total_asistencias || 0} asistencias totales</div>
        </div>
    `;
}

/**
 * Renderizar centros
 */
function renderCenters() {
    const container = document.getElementById('centers-list');
    
    if (!Dashboard.centers || Dashboard.centers.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3>No hay centros disponibles</h3>
                <p>Comienza creando tu primer centro deportivo</p>
                <button class="btn btn-primary" onclick="showCreateCenterModal()">
                    + Crear primer centro
                </button>
            </div>
        `;
        return;
    }

    const centersHTML = Dashboard.centers.map(center => `
        <div class="center-item">
            <div class="center-main">
                <div class="center-header">
                    <h3 class="center-name">${escapeHtml(center.nombre)}</h3>
                    <span class="center-status active">Activo</span>
                </div>
                <div class="center-details">
                    <span class="center-address">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                        </svg>
                        ${escapeHtml(center.direccion || 'Sin dirección')}
                    </span>
                    <span class="center-stat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.495v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5z"/>
                        </svg>
                        ${center.total_instalaciones || 0} instalaciones
                    </span>
                    <span class="center-stat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        ${center.total_actividades || 0} actividades
                    </span>
                </div>
            </div>
            <div class="center-actions">
                <div class="dropdown">
                    <button class="more-btn" onclick="toggleDropdown(${center.id})">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu" id="dropdown-${center.id}">
                        <a href="#" onclick="viewActivities(${center.id})">Ver actividades</a>
                        <a href="#" onclick="editCenter(${center.id})">Editar centro</a>
                        <a href="#" onclick="deactivateCenter(${center.id})">Desactivar</a>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    container.innerHTML = centersHTML;
}

/**
 * Mostrar error en estadísticas
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

/**
 * Mostrar error en centros
 */
function showCentersError() {
    const centersGrid = document.getElementById('centers-grid');
    if (centersGrid) {
        centersGrid.innerHTML = `
            <div class="error-card">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Error cargando centros
            </div>
        `;
    }
}

/**
 * Filtrar centros por búsqueda
 */
function filterCenters(searchTerm) {
    const centerCards = document.querySelectorAll('.center-card');
    const term = searchTerm.toLowerCase();
    
    centerCards.forEach(card => {
        const name = card.querySelector('.center-name').textContent.toLowerCase();
        const address = card.querySelector('.center-address').textContent.toLowerCase();
        
        if (name.includes(term) || address.includes(term)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Ordenar centros
 */
function sortCenters(sortBy) {
    // TODO: Implementar ordenación
    console.log('Ordenar centros por:', sortBy);
}

/**
 * Abrir modal (placeholder)
 */
function openModal(type) {
    console.log('Abrir modal para:', type);
    alert(`Modal para crear ${type} - En desarrollo`);
}

/**
 * Ver centro (placeholder)
 */
function viewCenter(centerId) {
    console.log('Ver centro:', centerId);
    alert(`Ver detalles del centro ${centerId} - En desarrollo`);
}

/**
 * Mostrar menú de centro (placeholder)
 */
function showCenterMenu(centerId, event) {
    event.stopPropagation();
    console.log('Menú del centro:', centerId);
    alert(`Menú del centro ${centerId} - En desarrollo`);
}

/**
 * Mostrar error general
 */
function showError(message) {
    console.error(message);
    // TODO: Implementar notificaciones toast
    alert('Error: ' + message);
}

/**
 * Función auxiliar para escapar HTML
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
}

/**
 * Toggle dropdown menu
 */
function toggleDropdown(centerId) {
    const dropdown = document.getElementById(`dropdown-${centerId}`);
    const isVisible = dropdown.classList.contains('show');
    
    // Cerrar todos los dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.remove('show');
    });
    
    // Toggle el dropdown actual
    if (!isVisible) {
        dropdown.classList.add('show');
    }
}

/**
 * Cerrar dropdowns al hacer click fuera
 */
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

/**
 * Funciones del dropdown
 */
function viewActivities(centerId) {
    console.log('Ver actividades del centro:', centerId);
    // TODO: Implementar navegación a actividades
}

function editCenter(centerId) {
    console.log('Editar centro:', centerId);
    // TODO: Implementar modal de edición
}

function deactivateCenter(centerId) {
    console.log('Desactivar centro:', centerId);
    // TODO: Implementar confirmación y desactivación
}

/**
 * Mostrar modal de crear centro
 */
function showCreateCenterModal() {
    const modal = document.getElementById('createCenterModal');
    if (modal) {
        modal.classList.add('show');
        // Focus en el primer campo
        setTimeout(() => {
            const firstInput = modal.querySelector('input[type="text"]');
            if (firstInput) firstInput.focus();
        }, 100);
    }
}

/**
 * Mostrar modal de opciones de añadir
 */
function showAddOptionsModal() {
    const modal = document.getElementById('addOptionsModal');
    if (modal) {
        modal.classList.add('show');
    }
}

/**
 * Cerrar modal de opciones de añadir
 */
function closeAddOptionsModal() {
    const modal = document.getElementById('addOptionsModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

/**
 * Seleccionar opción de creación
 */
function selectCreateOption(type) {
    // Cerrar modal de opciones
    closeAddOptionsModal();
    
    // Abrir modal correspondiente
    switch(type) {
        case 'centro':
            showCreateCenterModal();
            break;
        case 'instalacion':
            showCreateInstallationModal();
            break;
        case 'actividad':
            showCreateActivityModal();
            break;
        case 'participante':
            showCreateParticipantModal();
            break;
        default:
            showNotification('Funcionalidad en desarrollo', 'info');
    }
}

/**
 * Mostrar modal de crear instalación
 */
async function showCreateInstallationModal() {
    const modal = document.getElementById('createInstallationModal');
    if (modal) {
        // Cargar centros en el selector
        await loadCentersForSelector();
        
        modal.classList.add('show');
        // Focus en el selector de centro
        setTimeout(() => {
            const centerSelect = document.getElementById('installationCenter');
            if (centerSelect) centerSelect.focus();
        }, 100);
    }
}

/**
 * Cerrar modal de crear instalación
 */
function closeCreateInstallationModal() {
    const modal = document.getElementById('createInstallationModal');
    if (modal) {
        modal.classList.remove('show');
    }
    
    // Limpiar formulario
    document.getElementById('createInstallationForm').reset();
    clearFormErrors();
    
    // Limpiar selector personalizado
    const wrapper = document.querySelector('.custom-select-wrapper');
    const input = document.getElementById('installationCenterSearch');
    const hiddenInput = document.getElementById('installationCenter');
    
    if (wrapper && input && hiddenInput) {
        wrapper.classList.remove('open');
        input.value = '';
        input.classList.remove('has-value');
        hiddenInput.value = '';
        
        // Limpiar selecciones
        document.querySelectorAll('.custom-select-option').forEach(opt => {
            opt.classList.remove('selected');
        });
    }
}

/**
 * Cargar centros para el selector
 */
async function loadCentersForSelector() {
    try {
        const response = await fetch('api/centros/list_for_selector.php');
        const data = await response.json();
        
        if (data.success) {
            // Guardar centros globalmente para filtrado
            window.availableCenters = data.centros;
            
            // Inicializar selector personalizado
            initCustomCenterSelector();
            
            // Mostrar todos los centros inicialmente
            renderCenterOptions(data.centros);
            
        } else {
            showNotification('Error al cargar centros: ' + data.message, 'error');
            showCenterSelectorError('Error al cargar centros');
        }
    } catch (error) {
        console.error('Error loading centers:', error);
        showNotification('Error al cargar centros', 'error');
        showCenterSelectorError('Error de conexión');
    }
}

/**
 * Inicializar selector personalizado de centros
 */
function initCustomCenterSelector() {
    const wrapper = document.querySelector('.custom-select-wrapper');
    const input = document.getElementById('installationCenterSearch');
    const dropdown = document.getElementById('installationCenterDropdown');
    const hiddenInput = document.getElementById('installationCenter');
    
    if (!wrapper || !input || !dropdown || !hiddenInput) return;
    
    // Evento click en input para abrir/cerrar
    input.addEventListener('click', function() {
        wrapper.classList.toggle('open');
    });
    
    // Evento input para filtrar
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const filtered = window.availableCenters.filter(centro => 
            centro.nombre.toLowerCase().includes(query)
        );
        renderCenterOptions(filtered);
        
        if (!wrapper.classList.contains('open')) {
            wrapper.classList.add('open');
        }
    });
    
    // Cerrar al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            wrapper.classList.remove('open');
        }
    });
    
    // Manejar teclas
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            wrapper.classList.remove('open');
        }
    });
}

/**
 * Renderizar opciones de centros
 */
function renderCenterOptions(centros) {
    const dropdown = document.getElementById('installationCenterDropdown');
    
    if (centros.length === 0) {
        dropdown.innerHTML = '<div class="custom-select-no-results">No se encontraron centros</div>';
        return;
    }
    
    dropdown.innerHTML = centros.map(centro => 
        `<div class="custom-select-option" data-value="${centro.id}" data-name="${escapeHtml(centro.nombre)}">
            ${escapeHtml(centro.nombre)}
        </div>`
    ).join('');
    
    // Añadir eventos click a las opciones
    dropdown.querySelectorAll('.custom-select-option').forEach(option => {
        option.addEventListener('click', function() {
            selectCenterOption(this.dataset.value, this.dataset.name);
        });
    });
}

/**
 * Seleccionar opción de centro
 */
function selectCenterOption(value, name) {
    const wrapper = document.querySelector('.custom-select-wrapper');
    const input = document.getElementById('installationCenterSearch');
    const hiddenInput = document.getElementById('installationCenter');
    
    // Actualizar valores
    input.value = name;
    input.classList.add('has-value');
    hiddenInput.value = value;
    
    // Cerrar dropdown
    wrapper.classList.remove('open');
    
    // Limpiar error si existe
    clearFieldError('installationCenter');
    
    // Marcar opción como seleccionada
    document.querySelectorAll('.custom-select-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    document.querySelector(`[data-value="${value}"]`)?.classList.add('selected');
}

/**
 * Mostrar error en selector de centros
 */
function showCenterSelectorError(message) {
    const dropdown = document.getElementById('installationCenterDropdown');
    dropdown.innerHTML = `<div class="custom-select-loading" style="color: var(--color-red-500);">${message}</div>`;
}

/**
 * Cerrar modal de crear centro
 */
function closeCreateCenterModal() {
    const modal = document.getElementById('createCenterModal');
    if (modal) {
        modal.classList.remove('show');
    }
    
    // Limpiar formulario
    document.getElementById('createCenterForm').reset();
    clearFormErrors();
}

/**
 * Limpiar errores del formulario
 */
function clearFormErrors() {
    document.querySelectorAll('.form-error').forEach(error => {
        error.textContent = '';
    });
    document.querySelectorAll('.form-input').forEach(input => {
        input.classList.remove('error');
    });
}

/**
 * Mostrar error en campo específico
 */
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + '-error');
    
    if (field) field.classList.add('error');
    if (errorDiv) errorDiv.textContent = message;
}

/**
 * Limpiar error en campo específico
 */
function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + '-error');
    
    if (field) field.classList.remove('error');
    if (errorDiv) errorDiv.textContent = '';
}

/**
 * Crear centro
 */
async function createCenter(data) {
    try {
        const response = await fetch('api/centros/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Centro creado exitosamente', 'success');
            closeCreateCenterModal();
            
            // Recargar la lista de centros
            await loadCenters();
            
            // Actualizar estadísticas
            await loadStats();
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error creating center:', error);
        showNotification('Error al crear el centro', 'error');
    }
}

/**
 * Crear instalación
 */
async function createInstallation(data) {
    try {
        const response = await fetch('api/instalaciones/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Instalación creada exitosamente', 'success');
            closeCreateInstallationModal();
            
            // Recargar la lista de centros para actualizar contadores
            await loadCenters();
            
            // Actualizar estadísticas
            await loadStats();
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error creating installation:', error);
        showNotification('Error al crear la instalación', 'error');
    }
}

/**
 * Configurar modal de actividades
 */
function setupActivityModal() {
    // Configurar selectores cascada
    setupActivityCenterSelector();
    
    // Configurar formulario de actividades
    const activityForm = document.getElementById('createActivityForm');
    if (activityForm) {
        activityForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await createActivity();
        });
    }
}

/**
 * Configurar selector de centros para actividades
 */
function setupActivityCenterSelector() {
    // Usar el mismo patrón que el modal de instalaciones que funciona
    initActivityCenterSelector();
}

/**
 * Inicializar selector de centros para actividades
 */
function initActivityCenterSelector() {
    const wrapper = document.querySelector('#createActivityModal .custom-select-wrapper');
    const input = document.getElementById('activityCenterSearch');
    const dropdown = document.getElementById('activityCenterDropdown');
    const hiddenInput = document.getElementById('activityCenter');
    
    if (!wrapper || !input || !dropdown || !hiddenInput) return;
    
    // Evento click en input para abrir/cerrar
    input.addEventListener('click', function() {
        wrapper.classList.toggle('open');
        if (wrapper.classList.contains('open') && !window.activityCenters) {
            loadActivityCenters();
        }
    });
    
    // Evento input para filtrar
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        if (window.activityCenters) {
            const filtered = window.activityCenters.filter(centro => 
                centro.nombre && centro.nombre.toLowerCase().includes(query)
            );
            renderActivityCenterOptions(filtered);
        }
        
        if (!wrapper.classList.contains('open')) {
            wrapper.classList.add('open');
        }
    });
    
    // Cerrar al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            wrapper.classList.remove('open');
        }
    });
    
    // Manejar teclas
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            wrapper.classList.remove('open');
        }
    });
}

/**
 * Cargar centros para actividades
 */
async function loadActivityCenters() {
    try {
        const response = await fetch('api/centros/list_for_selector.php');
        const data = await response.json();
        
        if (data.success) {
            // Guardar centros globalmente para filtrado
            window.activityCenters = data.centros;
            
            // Mostrar todos los centros inicialmente
            renderActivityCenterOptions(data.centros);
            
        } else {
            showNotification('Error al cargar centros: ' + data.message, 'error');
            showActivityCenterSelectorError('Error al cargar centros');
        }
    } catch (error) {
        console.error('Error loading centers:', error);
        showNotification('Error al cargar centros', 'error');
        showActivityCenterSelectorError('Error de conexión');
    }
}

/**
 * Renderizar opciones de centros para actividades
 */
function renderActivityCenterOptions(centros) {
    const dropdown = document.getElementById('activityCenterDropdown');
    
    if (centros.length === 0) {
        dropdown.innerHTML = '<div class="custom-select-no-results">No se encontraron centros</div>';
        return;
    }
    
    dropdown.innerHTML = centros.map(centro => 
        `<div class="custom-select-option" data-value="${centro.id}" data-name="${escapeHtml(centro.nombre)}">
            <div class="option-content">
                <div class="option-title">${centro.nombre}</div>
                <div class="option-subtitle">${centro.direccion || ''}</div>
            </div>
        </div>`
    ).join('');
    
    // Agregar eventos click a las opciones
    dropdown.querySelectorAll('.custom-select-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.dataset.value;
            const name = this.dataset.name;
            
            // Actualizar input y valor oculto
            document.getElementById('activityCenterSearch').value = name;
            document.getElementById('activityCenter').value = value;
            
            // Cerrar dropdown
            const wrapper = document.querySelector('#createActivityModal .custom-select-wrapper');
            wrapper.classList.remove('open');
            
            // Cargar instalaciones para este centro
            loadActivityInstallations(value);
            
            // Limpiar error si existe
            clearFieldError('activityCenter');
        });
    });
}

/**
 * Mostrar error en selector de centros para actividades
 */
function showActivityCenterSelectorError(message) {
    const dropdown = document.getElementById('activityCenterDropdown');
    dropdown.innerHTML = `<div class="custom-select-no-results">${message}</div>`;
}

/**
 * Cargar instalaciones por centro
 */
async function loadActivityInstallations(centroId) {
    const installationInput = document.getElementById('activityInstallationSearch');
    const installationDropdown = document.getElementById('activityInstallationDropdown');
    const installationHidden = document.getElementById('activityInstallation');
    
    if (!installationInput || !installationDropdown || !installationHidden) return;
    
    try {
        // Habilitar selector de instalaciones
        installationInput.disabled = false;
        installationInput.placeholder = 'Buscar instalación...';
        installationDropdown.innerHTML = '<div class="custom-select-loading">Cargando instalaciones...</div>';
        
        const response = await fetch(`api/instalaciones/list_by_center.php?centro_id=${centroId}`);
        const data = await response.json();
        
        if (data.success) {
            const installations = data.instalaciones;
            setupActivityInstallationSelector(installations);
        } else {
            installationDropdown.innerHTML = '<div class="custom-select-no-results">Error cargando instalaciones</div>';
        }
    } catch (error) {
        console.error('Error loading installations:', error);
        installationDropdown.innerHTML = '<div class="custom-select-no-results">Error cargando instalaciones</div>';
    }
}

/**
 * Configurar selector de instalaciones para actividades
 */
function setupActivityInstallationSelector(installations) {
    const wrapper = document.querySelector('#createActivityModal .form-group:nth-child(2) .custom-select-wrapper');
    const input = document.getElementById('activityInstallationSearch');
    const dropdown = document.getElementById('activityInstallationDropdown');
    const hiddenInput = document.getElementById('activityInstallation');
    
    if (!wrapper || !input || !dropdown || !hiddenInput) return;
    
    // Guardar instalaciones globalmente
    window.activityInstallations = installations;
    
    // Evento click en input para abrir/cerrar
    input.addEventListener('click', function() {
        if (!input.disabled) {
            wrapper.classList.toggle('open');
        }
    });
    
    // Evento input para filtrar
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const filtered = installations.filter(instalacion => 
            instalacion.nombre && instalacion.nombre.toLowerCase().includes(query)
        );
        renderActivityInstallationOptions(filtered);
        
        if (!wrapper.classList.contains('open') && !input.disabled) {
            wrapper.classList.add('open');
        }
    });
    
    // Cerrar al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            wrapper.classList.remove('open');
        }
    });
    
    // Manejar teclas
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            wrapper.classList.remove('open');
        }
    });
    
    // Renderizar inicialmente
    renderActivityInstallationOptions(installations);
}

/**
 * Renderizar opciones de instalaciones para actividades
 */
function renderActivityInstallationOptions(instalaciones) {
    const dropdown = document.getElementById('activityInstallationDropdown');
    
    if (instalaciones.length === 0) {
        dropdown.innerHTML = '<div class="custom-select-no-results">No se encontraron instalaciones</div>';
        return;
    }
    
    dropdown.innerHTML = instalaciones.map(instalacion => 
        `<div class="custom-select-option" data-value="${instalacion.id}" data-name="${escapeHtml(instalacion.nombre)}">
            <div class="option-content">
                <div class="option-title">${instalacion.nombre}</div>
            </div>
        </div>`
    ).join('');
    
    // Agregar eventos click a las opciones
    dropdown.querySelectorAll('.custom-select-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.dataset.value;
            const name = this.dataset.name;
            
            // Actualizar input y valor oculto
            document.getElementById('activityInstallationSearch').value = name;
            document.getElementById('activityInstallation').value = value;
            
            // Cerrar dropdown
            const wrapper = document.querySelector('#createActivityModal .form-group:nth-child(2) .custom-select-wrapper');
            wrapper.classList.remove('open');
            
            // Limpiar error si existe
            clearFieldError('activityInstallation');
        });
    });
}

/**
 * Mostrar modal crear actividad
 */
function showCreateActivityModal() {
    const modal = document.getElementById('createActivityModal');
    if (modal) {
        modal.classList.add('show');
        
        // Limpiar formulario
        const form = document.getElementById('createActivityForm');
        if (form) {
            form.reset();
            clearFormErrors();
        }
        
        // Resetear selectores
        document.getElementById('activityCenterSearch').value = '';
        document.getElementById('activityCenter').value = '';
        document.getElementById('activityInstallationSearch').value = '';
        document.getElementById('activityInstallationSearch').disabled = true;
        document.getElementById('activityInstallationSearch').placeholder = 'Primero selecciona un centro';
        document.getElementById('activityInstallation').value = '';
        
        // Establecer fecha de inicio por defecto
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('activityStartDate').value = today;
    }
}

/**
 * Cerrar modal crear actividad
 */
function closeCreateActivityModal() {
    const modal = document.getElementById('createActivityModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

/**
 * Crear actividad
 */
async function createActivity() {
    const btn = document.getElementById('createActivityBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoading = btn.querySelector('.btn-loading');
    
    try {
        // Mostrar loading
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        
        // Limpiar errores previos
        clearFormErrors();
        
        // Obtener datos del formulario
        const form = document.getElementById('createActivityForm');
        const formData = new FormData(form);
        
        // Obtener días seleccionados
        const diasSemana = [];
        form.querySelectorAll('input[name="dias_semana[]"]:checked').forEach(checkbox => {
            diasSemana.push(checkbox.value);
        });
        
        // Preparar datos
        const data = {
            nombre: formData.get('nombre'),
            instalacion_id: formData.get('instalacion_id'),
            dias_semana: diasSemana,
            hora_inicio: formData.get('hora_inicio'),
            hora_fin: formData.get('hora_fin'),
            fecha_inicio: formData.get('fecha_inicio'),
            fecha_fin: formData.get('fecha_fin') || null
        };
        
        // Validaciones básicas
        if (!data.nombre.trim()) {
            showFieldError('activityName', 'El nombre es obligatorio');
            return;
        }
        
        if (!data.instalacion_id) {
            showFieldError('activityInstallation', 'Debe seleccionar una instalación');
            return;
        }
        
        if (diasSemana.length === 0) {
            showFieldError('dias_semana', 'Debe seleccionar al menos un día');
            return;
        }
        
        if (!data.hora_inicio || !data.hora_fin) {
            showFieldError('activityStartTime', 'Las horas son obligatorias');
            return;
        }
        
        if (!data.fecha_inicio) {
            showFieldError('activityStartDate', 'La fecha de inicio es obligatoria');
            return;
        }
        
        // Enviar datos
        const response = await fetch('api/actividades/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Actividad creada exitosamente', 'success');
            closeCreateActivityModal();
            
            // Actualizar estadísticas
            await loadStats();
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error creating activity:', error);
        showNotification('Error al crear la actividad', 'error');
    } finally {
        // Ocultar loading
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }
}

/**
 * Configurar modal de participantes
 */
function setupParticipantModal() {
    // Configurar pestañas
    setupParticipantTabs();
    
    // Configurar selectores cascada para pestaña manual
    setupParticipantCenterSelector();
    
    // Configurar selectores cascada para pestaña CSV
    setupCsvParticipantCenterSelector();
    
    // Configurar formularios
    const manualForm = document.getElementById('createParticipantForm');
    if (manualForm) {
        manualForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await createParticipant();
        });
    }
    
    const csvForm = document.getElementById('uploadParticipantCsvForm');
    if (csvForm) {
        csvForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await uploadParticipantCsv();
        });
    }
    
    // Configurar selector de archivo CSV
    const csvFileInput = document.getElementById('participantCsvFile');
    if (csvFileInput) {
        csvFileInput.addEventListener('change', function() {
            handleCsvFileSelection(this);
        });
    }
}

/**
 * Configurar pestañas del modal de participantes
 */
function setupParticipantTabs() {
    // No necesita configuración adicional, se maneja con onclick
}

/**
 * Cambiar pestaña del modal de participantes
 */
function switchParticipantTab(tabType) {
    // Cambiar botones de pestaña
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Cambiar contenido de pestañas
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    if (tabType === 'manual') {
        document.getElementById('manualTab').classList.add('active');
        document.getElementById('createParticipantBtn').style.display = 'inline-flex';
        document.getElementById('uploadCsvBtn').style.display = 'none';
    } else if (tabType === 'csv') {
        document.getElementById('csvTab').classList.add('active');
        document.getElementById('createParticipantBtn').style.display = 'none';
        document.getElementById('uploadCsvBtn').style.display = 'inline-flex';
    }
}

/**
 * Configurar selectores para pestaña manual de participantes
 */
function setupManualParticipantSelectors() {
    setupManualParticipantCenterSelector();
}

/**
 * Configurar selectores para pestaña CSV de participantes  
 */
function setupCsvParticipantSelectors() {
    setupCsvParticipantCenterSelector();
}

/**
 * Configurar selector de centros para pestaña manual
 */
function setupManualParticipantCenterSelector() {
    loadManualParticipantCenters();
    
    const input = document.getElementById('participantCenterSearch');
    const wrapper = input?.closest('.custom-select-wrapper');
    const dropdown = document.getElementById('participantCenterDropdown');
    
    if (!input || !wrapper || !dropdown) return;
    
    // Manejar búsqueda
    input.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const filteredCenters = window.manualParticipantCenters?.filter(centro => 
            centro.nombre.toLowerCase().includes(searchTerm)
        ) || [];
        
        renderManualParticipantCenterOptions(filteredCenters);
        wrapper.classList.add('open');
    });
    
    // Manejar focus
    input.addEventListener('focus', function() {
        if (window.manualParticipantCenters) {
            renderManualParticipantCenterOptions(window.manualParticipantCenters);
            wrapper.classList.add('open');
        }
    });
    
    // Cerrar al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            wrapper.classList.remove('open');
        }
    });
    
    // Manejar teclas
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            wrapper.classList.remove('open');
        }
    });
}

/**
 * Configurar selector de centros para pestaña CSV
 */
function setupCsvParticipantCenterSelector() {
    loadCsvParticipantCenters();
    
    const input = document.getElementById('csvParticipantCenterSearch');
    const wrapper = input?.closest('.custom-select-wrapper');
    const dropdown = document.getElementById('csvParticipantCenterDropdown');
    
    if (!input || !wrapper || !dropdown) return;
    
    // Manejar búsqueda
    input.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const filteredCenters = window.csvParticipantCenters?.filter(centro => 
            centro.nombre.toLowerCase().includes(searchTerm)
        ) || [];
        
        renderCsvParticipantCenterOptions(filteredCenters);
        wrapper.classList.add('open');
    });
    
    // Manejar focus
    input.addEventListener('focus', function() {
        if (window.csvParticipantCenters) {
            renderCsvParticipantCenterOptions(window.csvParticipantCenters);
            wrapper.classList.add('open');
        }
    });
    
    // Cerrar al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            wrapper.classList.remove('open');
        }
    });
    
    // Manejar teclas
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            wrapper.classList.remove('open');
        }
    });
}

/**
 * Cargar centros para pestaña manual
 */
async function loadManualParticipantCenters() {
    try {
        const response = await fetch('api/centros/list_for_selector.php');
        const data = await response.json();
        
        if (data.success) {
            window.manualParticipantCenters = data.centros;
            renderManualParticipantCenterOptions(data.centros);
        } else {
            showManualParticipantCenterSelectorError('Error al cargar centros');
        }
    } catch (error) {
        console.error('Error loading centers:', error);
        showManualParticipantCenterSelectorError('Error de conexión');
    }
}

/**
 * Cargar centros para pestaña CSV
 */
async function loadCsvParticipantCenters() {
    try {
        const response = await fetch('api/centros/list_for_selector.php');
        const data = await response.json();
        
        if (data.success) {
            window.csvParticipantCenters = data.centros;
            renderCsvParticipantCenterOptions(data.centros);
        } else {
            showCsvParticipantCenterSelectorError('Error al cargar centros');
        }
    } catch (error) {
        console.error('Error loading centers:', error);
        showCsvParticipantCenterSelectorError('Error de conexión');
    }
}

/**
 * Renderizar opciones de centros para pestaña manual
 */
function renderManualParticipantCenterOptions(centros) {
    const dropdown = document.getElementById('participantCenterDropdown');
    
    if (centros.length === 0) {
        dropdown.innerHTML = '<div class="custom-select-no-results">No se encontraron centros</div>';
        return;
    }
    
    dropdown.innerHTML = centros.map(centro => 
        `<div class="custom-select-option" data-value="${centro.id}" data-name="${escapeHtml(centro.nombre)}">
            <div class="option-content">
                <div class="option-title">${centro.nombre}</div>
                <div class="option-subtitle">${centro.direccion || ''}</div>
            </div>
        </div>`
    ).join('');
    
    // Agregar eventos click a las opciones
    dropdown.querySelectorAll('.custom-select-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.dataset.value;
            const name = this.dataset.name;
            
            // Actualizar input y valor oculto
            document.getElementById('participantCenterSearch').value = name;
            document.getElementById('participantCenter').value = value;
            
            // Cerrar dropdown
            const wrapper = document.querySelector('#participantCenterSearch').closest('.custom-select-wrapper');
            wrapper.classList.remove('open');
            
            // Cargar instalaciones para este centro
            loadManualParticipantInstallations(value);
            
            // Limpiar error si existe
            clearFieldError('participantCenter');
        });
    });
}

/**
 * Renderizar opciones de centros para pestaña CSV
 */
function renderCsvParticipantCenterOptions(centros) {
    const dropdown = document.getElementById('csvParticipantCenterDropdown');
    
    if (centros.length === 0) {
        dropdown.innerHTML = '<div class="custom-select-no-results">No se encontraron centros</div>';
        return;
    }
    
    dropdown.innerHTML = centros.map(centro => 
        `<div class="custom-select-option" data-value="${centro.id}" data-name="${escapeHtml(centro.nombre)}">
            <div class="option-content">
                <div class="option-title">${centro.nombre}</div>
                <div class="option-subtitle">${centro.direccion || ''}</div>
            </div>
        </div>`
    ).join('');
    
    // Agregar eventos click a las opciones
    dropdown.querySelectorAll('.custom-select-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.dataset.value;
            const name = this.dataset.name;
            
            // Actualizar input y valor oculto
            document.getElementById('csvParticipantCenterSearch').value = name;
            document.getElementById('csvParticipantCenter').value = value;
            
            // Cerrar dropdown
            const wrapper = document.querySelector('#csvParticipantCenterSearch').closest('.custom-select-wrapper');
            wrapper.classList.remove('open');
            
            // Cargar instalaciones para este centro
            loadCsvParticipantInstallations(value);
            
            // Limpiar error si existe
            clearFieldError('csvParticipantCenter');
        });
    });
}

/**
 * Cargar instalaciones para pestaña manual
 */
async function loadManualParticipantInstallations(centroId) {
    const input = document.getElementById('participantInstallationSearch');
    const dropdown = document.getElementById('participantInstallationDropdown');
    const hidden = document.getElementById('participantInstallation');
    
    if (!input || !dropdown || !hidden) {
        console.error('Elementos del selector de instalaciones no encontrados');
        return;
    }
    
    try {
        // Habilitar selector de instalaciones
        input.disabled = false;
        input.placeholder = 'Buscar instalación...';
        dropdown.innerHTML = '<div class="custom-select-loading">Cargando instalaciones...</div>';
        
        const response = await fetch(`api/instalaciones/list_by_center.php?centro_id=${centroId}`);
        const data = await response.json();
        
        if (data.success) {
            renderManualParticipantInstallationOptions(data.instalaciones);
        } else {
            dropdown.innerHTML = '<div class="custom-select-no-results">Error cargando instalaciones</div>';
        }
    } catch (error) {
        console.error('Error loading installations:', error);
        dropdown.innerHTML = '<div class="custom-select-no-results">Error cargando instalaciones</div>';
    }
}

/**
 * Cargar instalaciones para pestaña CSV
 */
async function loadCsvParticipantInstallations(centroId) {
    const input = document.getElementById('csvParticipantInstallationSearch');
    const dropdown = document.getElementById('csvParticipantInstallationDropdown');
    const hidden = document.getElementById('csvParticipantInstallation');
    
    if (!input || !dropdown || !hidden) {
        console.error('Elementos del selector de instalaciones no encontrados');
        return;
    }
    
    try {
        // Habilitar selector de instalaciones
        input.disabled = false;
        input.placeholder = 'Buscar instalación...';
        dropdown.innerHTML = '<div class="custom-select-loading">Cargando instalaciones...</div>';
        
        const response = await fetch(`api/instalaciones/list_by_center.php?centro_id=${centroId}`);
        const data = await response.json();
        
        if (data.success) {
            renderCsvParticipantInstallationOptions(data.instalaciones);
        } else {
            dropdown.innerHTML = '<div class="custom-select-no-results">Error cargando instalaciones</div>';
        }
    } catch (error) {
        console.error('Error loading installations:', error);
        dropdown.innerHTML = '<div class="custom-select-no-results">Error cargando instalaciones</div>';
    }
}

/**
 * Configurar selector de instalaciones para participantes
 */
function setupParticipantInstallationSelector(installations, installationPrefix, activityPrefix) {
    const wrapper = document.querySelector(`#${installationPrefix}Search`).closest('.custom-select-wrapper');
    const input = document.getElementById(`${installationPrefix}Search`);
    const dropdown = document.getElementById(`${installationPrefix}Dropdown`);
    const hiddenInput = document.getElementById(installationPrefix);
    
    // Evento click en input para abrir/cerrar
    input.addEventListener('click', function() {
        if (!input.disabled) {
            wrapper.classList.toggle('open');
        }
    });
    
    // Evento input para filtrar
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const filtered = installations.filter(instalacion => 
            instalacion.nombre && instalacion.nombre.toLowerCase().includes(query)
        );
        renderParticipantInstallationOptions(filtered, installationPrefix, activityPrefix);
        
        if (!wrapper.classList.contains('open') && !input.disabled) {
            wrapper.classList.add('open');
        }
    });
    
    // Renderizar inicialmente
    renderParticipantInstallationOptions(installations, installationPrefix, activityPrefix);
}

/**
 * Renderizar opciones de instalaciones para participantes
 */
function renderParticipantInstallationOptions(instalaciones, installationPrefix, activityPrefix) {
    const dropdown = document.getElementById(`${installationPrefix}Dropdown`);
    
    if (instalaciones.length === 0) {
        dropdown.innerHTML = '<div class="custom-select-no-results">No se encontraron instalaciones</div>';
        return;
    }
    
    dropdown.innerHTML = instalaciones.map(instalacion => 
        `<div class="custom-select-option" data-value="${instalacion.id}" data-name="${escapeHtml(instalacion.nombre)}">
            <div class="option-content">
                <div class="option-title">${instalacion.nombre}</div>
            </div>
        </div>`
    ).join('');
    
    // Agregar eventos click a las opciones
    dropdown.querySelectorAll('.custom-select-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.dataset.value;
            const name = this.dataset.name;
            
            // Actualizar input y valor oculto
            document.getElementById(`${installationPrefix}Search`).value = name;
            document.getElementById(installationPrefix).value = value;
            
            // Cerrar dropdown
            const wrapper = document.querySelector(`#${installationPrefix}Search`).closest('.custom-select-wrapper');
            wrapper.classList.remove('open');
            
            // Cargar actividades para esta instalación
            loadParticipantActivities(value, activityPrefix);
            
            // Limpiar error si existe
            clearFieldError(installationPrefix);
        });
    });
}

/**
 * Cargar actividades para participantes
 */
async function loadParticipantActivities(instalacionId, activityPrefix) {
    const input = document.getElementById(`${activityPrefix}Search`);
    const dropdown = document.getElementById(`${activityPrefix}Dropdown`);
    const hidden = document.getElementById(activityPrefix);
    
    // Validación de elementos (como en el modal de actividades)
    if (!input || !dropdown || !hidden) {
        console.error('Elementos del selector de actividades no encontrados:', {
            input: !!input,
            dropdown: !!dropdown,
            hidden: !!hidden,
            activityPrefix
        });
        return;
    }
    
    try {
        // Habilitar selector de actividades
        input.disabled = false;
        input.placeholder = 'Buscar actividad...';
        dropdown.innerHTML = '<div class="custom-select-loading">Cargando actividades...</div>';
        
        const response = await fetch(`api/actividades/list_by_installation.php?instalacion_id=${instalacionId}`);
        const data = await response.json();
        
        if (data.success) {
            setupParticipantActivitySelector(data.actividades, activityPrefix);
        } else {
            dropdown.innerHTML = '<div class="custom-select-no-results">Error cargando actividades</div>';
        }
    } catch (error) {
        console.error('Error loading activities:', error);
        dropdown.innerHTML = '<div class="custom-select-no-results">Error cargando actividades</div>';
    }
}

/**
 * Configurar selector de actividades para participantes
 */
function setupParticipantActivitySelector(activities, activityPrefix) {
    const wrapper = document.querySelector(`#${activityPrefix}Search`).closest('.custom-select-wrapper');
    const input = document.getElementById(`${activityPrefix}Search`);
    const dropdown = document.getElementById(`${activityPrefix}Dropdown`);
    const hiddenInput = document.getElementById(activityPrefix);
    
    // Evento click en input para abrir/cerrar
    input.addEventListener('click', function() {
        if (!input.disabled) {
            wrapper.classList.toggle('open');
        }
    });
    
    // Evento input para filtrar
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const filtered = activities.filter(actividad => 
            actividad.nombre && actividad.nombre.toLowerCase().includes(query)
        );
        renderParticipantActivityOptions(filtered, activityPrefix);
        
        if (!wrapper.classList.contains('open') && !input.disabled) {
            wrapper.classList.add('open');
        }
    });
    
    // Renderizar inicialmente
    renderParticipantActivityOptions(activities, activityPrefix);
}

/**
 * Renderizar opciones de actividades para participantes
 */
function renderParticipantActivityOptions(actividades, activityPrefix) {
    const dropdown = document.getElementById(`${activityPrefix}Dropdown`);
    
    if (actividades.length === 0) {
        dropdown.innerHTML = '<div class="custom-select-no-results">No se encontraron actividades</div>';
        return;
    }
    
    dropdown.innerHTML = actividades.map(actividad => 
        `<div class="custom-select-option" data-value="${actividad.id}" data-name="${escapeHtml(actividad.nombre)}">
            <div class="option-content">
                <div class="option-title">${actividad.nombre}</div>
                <div class="option-subtitle">${actividad.dias_semana} • ${actividad.hora_inicio}-${actividad.hora_fin}</div>
            </div>
        </div>`
    ).join('');
    
    // Agregar eventos click a las opciones
    dropdown.querySelectorAll('.custom-select-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.dataset.value;
            const name = this.dataset.name;
            
            // Actualizar input y valor oculto
            document.getElementById(`${activityPrefix}Search`).value = name;
            document.getElementById(activityPrefix).value = value;
            
            // Cerrar dropdown
            const wrapper = document.querySelector(`#${activityPrefix}Search`).closest('.custom-select-wrapper');
            wrapper.classList.remove('open');
            
            // Limpiar error si existe
            clearFieldError(activityPrefix);
        });
    });
}

/**
 * Mostrar error en selector de centros para participantes
 */
function showParticipantCenterSelectorError(message, centerPrefix) {
    const dropdown = document.getElementById(`${centerPrefix}Dropdown`);
    dropdown.innerHTML = `<div class="custom-select-no-results">${message}</div>`;
}

/**
 * Mostrar modal crear participante
 */
function showCreateParticipantModal() {
    const modal = document.getElementById('createParticipantModal');
    if (modal) {
        modal.classList.add('show');
        
        // Limpiar formularios
        const manualForm = document.getElementById('createParticipantForm');
        const csvForm = document.getElementById('uploadParticipantCsvForm');
        
        if (manualForm) {
            manualForm.reset();
        }
        if (csvForm) {
            csvForm.reset();
        }
        
        clearFormErrors();
        
        // Resetear selectores manuales
        resetParticipantSelectors('participantCenter', 'participantInstallation', 'participantActivity');
        
        // Resetear selectores CSV
        resetParticipantSelectors('csvParticipantCenter', 'csvParticipantInstallation', 'csvParticipantActivity');
        
        // Inicializar selectores cascada para pestaña manual
        setupManualParticipantSelectors();
        
        // Inicializar selectores cascada para pestaña CSV
        setupCsvParticipantSelectors();
        
        // Activar pestaña manual por defecto
        switchParticipantTab('manual');
        
        // Ocultar info de archivo CSV
        document.getElementById('csvFileInfo').style.display = 'none';
    }
}

/**
 * Resetear selectores de participantes
 */
function resetParticipantSelectors(centerPrefix, installationPrefix, activityPrefix) {
    // Resetear centro
    document.getElementById(`${centerPrefix}Search`).value = '';
    document.getElementById(centerPrefix).value = '';
    
    // Resetear instalación
    const installationInput = document.getElementById(`${installationPrefix}Search`);
    installationInput.value = '';
    installationInput.disabled = true;
    installationInput.placeholder = 'Selecciona un centro';
    document.getElementById(installationPrefix).value = '';
    
    // Resetear actividad
    const activityInput = document.getElementById(`${activityPrefix}Search`);
    activityInput.value = '';
    activityInput.disabled = true;
    activityInput.placeholder = 'Selecciona una instalación';
    document.getElementById(activityPrefix).value = '';
}

/**
 * Cerrar modal crear participante
 */
function closeCreateParticipantModal() {
    const modal = document.getElementById('createParticipantModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

/**
 * Manejar selección de archivo CSV
 */
function handleCsvFileSelection(input) {
    const fileInfo = document.getElementById('csvFileInfo');
    const fileName = document.getElementById('csvFileName');
    
    if (input.files.length > 0) {
        const file = input.files[0];
        
        // Verificar que sea CSV
        if (!file.name.toLowerCase().endsWith('.csv')) {
            showNotification('Por favor, selecciona un archivo CSV', 'error');
            input.value = '';
            return;
        }
        
        // Mostrar información del archivo
        fileName.textContent = file.name;
        fileInfo.style.display = 'flex';
        
    } else {
        fileInfo.style.display = 'none';
    }
}

/**
 * Remover archivo CSV seleccionado
 */
function removeCsvFile() {
    document.getElementById('participantCsvFile').value = '';
    document.getElementById('csvFileInfo').style.display = 'none';
}

/**
 * Crear participante individual
 */
async function createParticipant() {
    const btn = document.getElementById('createParticipantBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoading = btn.querySelector('.btn-loading');
    
    try {
        // Mostrar loading
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        
        // Limpiar errores previos
        clearFormErrors();
        
        // Obtener datos del formulario
        const form = document.getElementById('createParticipantForm');
        const formData = new FormData(form);
        
        // Preparar datos
        const data = {
            nombre: formData.get('nombre'),
            apellidos: formData.get('apellidos'),
            actividad_id: formData.get('actividad_id')
        };
        
        // Validaciones básicas
        if (!data.nombre.trim()) {
            showFieldError('participantName', 'El nombre es obligatorio');
            return;
        }
        
        if (!data.apellidos.trim()) {
            showFieldError('participantLastName', 'Los apellidos son obligatorios');
            return;
        }
        
        if (!data.actividad_id) {
            showFieldError('participantActivity', 'Debe seleccionar una actividad');
            return;
        }
        
        // Enviar datos
        const response = await fetch('api/participantes/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Participante inscrito exitosamente', 'success');
            closeCreateParticipantModal();
            
            // Actualizar estadísticas
            await loadStats();
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error creating participant:', error);
        showNotification('Error al inscribir el participante', 'error');
    } finally {
        // Ocultar loading
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }
}

/**
 * Subir CSV de participantes
 */
async function uploadParticipantCsv() {
    const btn = document.getElementById('uploadCsvBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoading = btn.querySelector('.btn-loading');
    
    try {
        // Mostrar loading
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        
        // Limpiar errores previos
        clearFormErrors();
        
        // Verificar que se seleccionó archivo
        const fileInput = document.getElementById('participantCsvFile');
        if (!fileInput.files.length) {
            showNotification('Debe seleccionar un archivo CSV', 'error');
            return;
        }
        
        // Verificar que se seleccionó actividad
        const activityId = document.getElementById('csvParticipantActivity').value;
        if (!activityId) {
            showFieldError('csvParticipantActivity', 'Debe seleccionar una actividad');
            return;
        }
        
        // Preparar FormData
        const formData = new FormData();
        formData.append('csv', fileInput.files[0]);
        formData.append('actividad_id', activityId);
        
        // Enviar archivo
        const response = await fetch('api/participantes/upload_csv.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeCreateParticipantModal();
            
            // Actualizar estadísticas
            await loadStats();
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error uploading CSV:', error);
        showNotification('Error al subir el archivo CSV', 'error');
    } finally {
        // Ocultar loading
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
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
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

/**
 * Filtrar centros por búsqueda
 */
function filterCenters(searchTerm) {
    const filteredCenters = Dashboard.centers.filter(center => {
        const searchLower = searchTerm.toLowerCase();
        return center.nombre.toLowerCase().includes(searchLower) ||
               (center.direccion && center.direccion.toLowerCase().includes(searchLower));
    });
    
    renderFilteredCenters(filteredCenters);
}

/**
 * Ordenar centros
 */
function sortCenters(sortBy) {
    let sortedCenters = [...Dashboard.centers];
    
    switch(sortBy) {
        case 'nombre':
            sortedCenters.sort((a, b) => a.nombre.localeCompare(b.nombre));
            break;
        case '-nombre':
            sortedCenters.sort((a, b) => b.nombre.localeCompare(a.nombre));
            break;
        default:
            // No sorting
            break;
    }
    
    renderFilteredCenters(sortedCenters);
}

/**
 * Renderizar centros filtrados
 */
function renderFilteredCenters(centers) {
    const container = document.getElementById('centers-list');
    
    if (!centers || centers.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3>No se encontraron centros</h3>
                <p>Intenta con otros términos de búsqueda</p>
            </div>
        `;
        return;
    }

    const centersHTML = centers.map(center => `
        <div class="center-item">
            <div class="center-main">
                <div class="center-header">
                    <h3 class="center-name">${escapeHtml(center.nombre)}</h3>
                    <span class="center-status active">Activo</span>
                </div>
                <div class="center-details">
                    <span class="center-address">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                        </svg>
                        ${escapeHtml(center.direccion || 'Sin dirección')}
                    </span>
                    <span class="center-stat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.495v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5z"/>
                        </svg>
                        ${center.total_instalaciones || 0} instalaciones
                    </span>
                    <span class="center-stat">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        ${center.total_actividades || 0} actividades
                    </span>
                </div>
            </div>
            <div class="center-actions">
                <div class="dropdown">
                    <button class="more-btn" onclick="toggleDropdown(${center.id})">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu" id="dropdown-${center.id}">
                        <a href="#" onclick="viewActivities(${center.id})">Ver actividades</a>
                        <a href="#" onclick="editCenter(${center.id})">Editar centro</a>
                        <a href="#" onclick="deactivateCenter(${center.id})">Desactivar</a>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    container.innerHTML = centersHTML;
}

// Hacer funciones globales para uso en HTML
window.openModal = openModal;
window.viewCenter = viewCenter;
window.showCenterMenu = showCenterMenu;
