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
        const response = await fetch('api/centros/list.php');
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
    const centersGrid = document.getElementById('centers-grid');
    if (!centersGrid) return;
    
    if (!Dashboard.centers || Dashboard.centers.length === 0) {
        centersGrid.innerHTML = `
            <div class="empty-state">
                <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3>No hay centros disponibles</h3>
                <p>Comienza creando tu primer centro deportivo</p>
                <button class="btn btn-primary" onclick="openModal('centro')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Crear primer centro
                </button>
            </div>
        `;
        return;
    }
    
    const centersHTML = Dashboard.centers.map(center => `
        <div class="center-card" data-center-id="${center.id}">
            <div class="center-header">
                <div class="center-name">${escapeHtml(center.nombre)}</div>
                <div class="center-actions">
                    <button class="btn-icon" onclick="showCenterMenu(${center.id}, event)" title="Más opciones">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="center-info">
                <div class="center-address">${escapeHtml(center.direccion || 'Sin dirección')}</div>
            </div>
            <div class="center-footer">
                <button class="btn btn-outline" onclick="viewCenter(${center.id})">Ver detalles</button>
            </div>
        </div>
    `).join('');
    
    centersGrid.innerHTML = centersHTML;
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
 * Escapar HTML para prevenir XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Hacer funciones globales para uso en HTML
window.openModal = openModal;
window.viewCenter = viewCenter;
window.showCenterMenu = showCenterMenu;
