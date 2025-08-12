/**
 * Componentes SPA para el panel de administración
 */

// Componente base
class BaseComponent {
    constructor(container) {
        this.container = container;
        this.data = null;
    }

    showLoading() {
        this.container.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Cargando...</p>
            </div>
        `;
    }

    showError(message) {
        this.container.innerHTML = `
            <div class="error-message">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3>Error</h3>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Recargar
                </button>
            </div>
        `;
    }

    async load() {
        // Implementar en subclases
    }

    render() {
        // Implementar en subclases
    }
}

// Componente Dashboard
class DashboardComponent extends BaseComponent {
    constructor(container) {
        super(container);
        this.centers = [];
    }

    async load() {
        this.showLoading();
        try {
            // Cargar estadísticas
            const response = await fetch('api/stats/dashboard.php');
            this.data = await response.json();
            
            if (!this.data.success) {
                throw new Error(this.data.error || 'Error desconocido');
            }

            // Cargar centros
            await this.loadCenters();
            
        } catch (error) {
            console.error('Error cargando dashboard:', error);
            this.showError('Error al cargar las estadísticas del dashboard: ' + error.message);
            return;
        }
    }

    async loadCenters() {
        try {
            const response = await fetch('api/centros/list.php');
            const centersData = await response.json();
            
            if (centersData.success) {
                this.centers = centersData.data || [];
            }
        } catch (error) {
            console.error('Error cargando centros:', error);
            this.centers = [];
        }
    }

    renderHTML() {
        if (!this.data || !this.data.success) {
            this.showError('No se pudieron cargar las estadísticas');
            return;
        }

        const stats = this.data.data;
        
        this.container.innerHTML = `
            <!-- Barra superior -->
            <div class="admin-header">
                <div class="logo-section">
                    <div class="logo">P</div>
                    <div class="title">Puri: Gestión de centros deportivos</div>
                </div>
                <div class="actions">
                    <div class="dropdown">
                        <button class="btn btn-primary" onclick="toggleDropdown(this)">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Añadir
                        </button>
                        <div class="dropdown-content">
                            <a href="#" class="dropdown-item" onclick="openModal('centro')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                Nuevo centro
                            </a>
                            <a href="#" class="dropdown-item" onclick="openModal('instalacion')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                                </svg>
                                Nueva instalación
                            </a>
                            <a href="#" class="dropdown-item" onclick="openModal('actividad')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Nueva actividad
                            </a>
                        </div>
                    </div>
                    <button class="btn btn-secondary" onclick="showProfile()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Mi cuenta
                    </button>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="admin-content">
                <!-- Tarjetas de estadísticas -->
                <div class="stats-grid">
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
                </div>

                <!-- Panel principal de centros -->
                <div class="main-panel">
                    <div class="panel-header">
                        <div class="panel-title">Centros Deportivos</div>
                        <div class="panel-actions">
                            <div class="search-bar">
                                <input type="text" class="search-input" placeholder="Buscar centros..." id="searchCenters">
                                <select class="btn btn-secondary" id="sortCenters">
                                    <option value="name">Ordenar A-Z</option>
                                    <option value="activities">Por actividades</option>
                                    <option value="installations">Por instalaciones</option>
                                </select>
                            </div>
                            <button class="btn btn-primary" onclick="openModal('centro')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Añadir centro
                            </button>
                        </div>
                    </div>
                    <div class="panel-content">
                        <div class="centers-grid" id="centersGrid">
                            ${this.renderCenters()}
                        </div>
                    </div>
                </div>

                <!-- Panel de acciones rápidas -->
                <div class="quick-actions">
                    <h3>Acciones Rápidas</h3>
                    <div class="actions-grid">
                        <a href="#" class="action-btn" onclick="openModal('centro')">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Crear nuevo centro
                        </a>
                        <a href="#" class="action-btn" onclick="openModal('instalacion')">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                            </svg>
                            Nueva instalación
                        </a>
                        <a href="#" class="action-btn" onclick="openModal('actividad')">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Nueva actividad
                        </a>
                        <a href="#" class="action-btn" onclick="openModal('participante')">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Añadir participantes
                        </a>
                    </div>
                </div>
            </div>
        `;

        // Configurar eventos después de renderizar
        this.setupEvents();
    }

    renderCenters() {
        if (!this.centers || this.centers.length === 0) {
            return `
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <h3>No hay centros disponibles</h3>
                    <p>Comienza creando tu primer centro deportivo</p>
                    <button class="btn btn-primary" onclick="openModal('centro')" style="margin-top: 16px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Crear primer centro
                    </button>
                </div>
            `;
        }

        return this.centers.map(center => `
            <div class="center-card" onclick="viewCenter(${center.id})" data-center-id="${center.id}">
                <div class="card-header">
                    <div>
                        <div class="center-name">${center.nombre}</div>
                        <span class="center-status active">Activo</span>
                    </div>
                </div>
                <div class="center-address">${center.direccion || 'Sin dirección especificada'}</div>
                <div class="center-stats">
                    <span>${center.total_instalaciones || 0} instalaciones</span>
                    <span>${center.total_actividades || 0} actividades</span>
                </div>
                <button class="more-options" onclick="event.stopPropagation(); showCenterOptions(${center.id})" title="Más opciones">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                </button>
            </div>
        `).join('');
    }

    setupEvents() {
        // Configurar búsqueda de centros
        const searchInput = document.getElementById('searchCenters');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterCenters(e.target.value);
            });
        }

        // Configurar ordenación
        const sortSelect = document.getElementById('sortCenters');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.sortCenters(e.target.value);
            });
        }
    }

    filterCenters(searchTerm) {
        const cards = document.querySelectorAll('.center-card');
        cards.forEach(card => {
            const centerName = card.querySelector('.center-name').textContent.toLowerCase();
            const centerAddress = card.querySelector('.center-address').textContent.toLowerCase();
            
            if (centerName.includes(searchTerm.toLowerCase()) || 
                centerAddress.includes(searchTerm.toLowerCase())) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    sortCenters(sortBy) {
        // Implementar ordenación de centros
        console.log('Ordenar por:', sortBy);
    }
}

// Placeholders para otros componentes
class CentrosComponent extends BaseComponent {
    render() {
        return '<div class="placeholder">Vista de Centros - En desarrollo</div>';
    }
}

class InstalacionesComponent extends BaseComponent {
    render() {
        return '<div class="placeholder">Vista de Instalaciones - En desarrollo</div>';
    }
}

class ActividadesComponent extends BaseComponent {
    render() {
        return '<div class="placeholder">Vista de Actividades - En desarrollo</div>';
    }
}

class EstadisticasComponent extends BaseComponent {
    render() {
        return '<div class="placeholder">Vista de Estadísticas - En desarrollo</div>';
    }
}

class SuperadminComponent extends BaseComponent {
    render() {
        return '<div class="placeholder">Vista de Superadmin - En desarrollo</div>';
    }
}

// Funciones globales para interacciones
window.toggleDropdown = function(button) {
    const dropdown = button.closest('.dropdown');
    dropdown.classList.toggle('active');
    
    // Cerrar otros dropdowns
    document.querySelectorAll('.dropdown').forEach(d => {
        if (d !== dropdown) d.classList.remove('active');
    });
};

window.openModal = function(type) {
    console.log('Abrir modal para:', type);
    // TODO: Implementar modales
};

window.showProfile = function() {
    console.log('Mostrar perfil');
    // TODO: Implementar perfil
};

window.viewCenter = function(centerId) {
    console.log('Ver centro:', centerId);
    // TODO: Navegar a vista de centro
};

window.showCenterOptions = function(centerId) {
    console.log('Opciones para centro:', centerId);
    // TODO: Mostrar menú contextual
};

// Cerrar dropdowns al hacer click fuera
document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown').forEach(d => {
            d.classList.remove('active');
        });
    }
});
