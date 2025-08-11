/**
 * Componentes de la SPA Admin
 */

/**
 * Componente base para todos los componentes
 */
class BaseComponent {
    constructor() {
        this.container = null;
        this.data = null;
        this.isLoading = false;
    }

    async render(container) {
        this.container = container;
        await this.load();
        this.renderHTML();
        this.bindEvents();
    }

    async load() {
        // Override en componentes hijos para cargar datos
    }

    renderHTML() {
        // Override en componentes hijos para renderizar HTML
        this.container.innerHTML = '<p>Componente base</p>';
    }

    bindEvents() {
        // Override en componentes hijos para bind de eventos
    }

    showLoading() {
        this.isLoading = true;
        if (this.container) {
            this.container.innerHTML = `
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Cargando...</p>
                </div>
            `;
        }
    }

    showError(message) {
        if (this.container) {
            this.container.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error</h3>
                    <p>${message}</p>
                    <button onclick="location.reload()" class="btn btn-primary">
                        <i class="fas fa-refresh"></i> Recargar
                    </button>
                </div>
            `;
        }
    }
}

/**
 * Componente Dashboard
 */
class DashboardComponent extends BaseComponent {
    async load() {
        this.showLoading();
        try {
            // Usar endpoint de prueba para debug
            const response = await fetch('api/test_stats.php');
            this.data = await response.json();
            
            if (!this.data.success) {
                throw new Error(this.data.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error cargando dashboard:', error);
            this.showError('Error al cargar las estadísticas del dashboard: ' + error.message);
            return;
        }
    }

    renderHTML() {
        if (!this.data || !this.data.success) {
            this.showError('No se pudieron cargar las estadísticas');
            return;
        }

        const stats = this.data.data;
        
        this.container.innerHTML = `
            <div class="dashboard-container">
                <div class="dashboard-header">
                    <h2>Dashboard de Administración</h2>
                    <p>Resumen general del sistema</p>
                </div>

                <!-- Métricas principales -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="metric-content">
                            <h3>${stats.total_centros || 0}</h3>
                            <p>Centros</p>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="metric-content">
                            <h3>${stats.total_instalaciones || 0}</h3>
                            <p>Instalaciones</p>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="metric-content">
                            <h3>${stats.total_actividades || 0}</h3>
                            <p>Actividades</p>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="metric-content">
                            <h3>${stats.actividades_por_estado?.Activa || 0}</h3>
                            <p>Activas</p>
                        </div>
                    </div>
                </div>

                <!-- Actividades por estado -->
                <div class="dashboard-row">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Actividades por Estado</h3>
                        </div>
                        <div class="card-body">
                            <div class="status-grid">
                                <div class="status-item status-programada">
                                    <span class="status-count">${stats.actividades_por_estado?.Programada || 0}</span>
                                    <span class="status-label">Programadas</span>
                                </div>
                                <div class="status-item status-activa">
                                    <span class="status-count">${stats.actividades_por_estado?.Activa || 0}</span>
                                    <span class="status-label">Activas</span>
                                </div>
                                <div class="status-item status-finalizada">
                                    <span class="status-count">${stats.actividades_por_estado?.Finalizada || 0}</span>
                                    <span class="status-label">Finalizadas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Centros con más Actividades</h3>
                        </div>
                        <div class="card-body">
                            ${this.renderTopCentros(stats.top_centros || [])}
                        </div>
                    </div>
                </div>

                <!-- Actividades recientes -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Actividades Recientes</h3>
                    </div>
                    <div class="card-body">
                        ${this.renderActividadesRecientes(stats.actividades_recientes || [])}
                    </div>
                </div>
            </div>
        `;
    }

    renderTopCentros(centros) {
        if (!centros.length) {
            return '<p class="empty-state">No hay datos disponibles</p>';
        }

        return `
            <div class="top-centros-list">
                ${centros.map(centro => `
                    <div class="top-centro-item">
                        <span class="centro-name">${centro.nombre}</span>
                        <span class="centro-count">${centro.total_actividades} actividades</span>
                    </div>
                `).join('')}
            </div>
        `;
    }

    renderActividadesRecientes(actividades) {
        if (!actividades.length) {
            return '<p class="empty-state">No hay actividades recientes</p>';
        }

        return `
            <div class="actividades-recientes-list">
                ${actividades.map(actividad => `
                    <div class="actividad-reciente-item">
                        <div class="actividad-info">
                            <h4>${actividad.nombre}</h4>
                            <p>${actividad.centro_nombre} - ${actividad.instalacion_nombre}</p>
                            <small>${Utils.formatDate(actividad.fecha_inicio)} - ${Utils.formatDate(actividad.fecha_fin)}</small>
                        </div>
                        <div class="actividad-estado">
                            <span class="badge badge-${actividad.estado.toLowerCase()}">${actividad.estado}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    bindEvents() {
        // Eventos específicos del dashboard si son necesarios
    }
}

/**
 * Componente Centros (placeholder)
 */
class CentrosComponent extends BaseComponent {
    async load() {
        this.showLoading();
        try {
            this.data = await api.getCentros();
        } catch (error) {
            console.error('Error cargando centros:', error);
            this.showError('Error al cargar los centros');
            return;
        }
    }

    renderHTML() {
        this.container.innerHTML = `
            <div class="centros-container">
                <div class="page-header">
                    <h2>Gestión de Centros</h2>
                    <button class="btn btn-admin-primary">
                        <i class="fas fa-plus"></i> Nuevo Centro
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <p>Componente de centros en desarrollo...</p>
                        <p>Aquí se mostrará la lista de centros con funcionalidades CRUD.</p>
                    </div>
                </div>
            </div>
        `;
    }
}

/**
 * Componente Instalaciones (placeholder)
 */
class InstalacionesComponent extends BaseComponent {
    renderHTML() {
        this.container.innerHTML = `
            <div class="instalaciones-container">
                <div class="page-header">
                    <h2>Gestión de Instalaciones</h2>
                    <button class="btn btn-admin-primary">
                        <i class="fas fa-plus"></i> Nueva Instalación
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <p>Componente de instalaciones en desarrollo...</p>
                        <p>Aquí se mostrará la lista de instalaciones con funcionalidades CRUD.</p>
                    </div>
                </div>
            </div>
        `;
    }
}

/**
 * Componente Actividades (placeholder)
 */
class ActividadesComponent extends BaseComponent {
    renderHTML() {
        this.container.innerHTML = `
            <div class="actividades-container">
                <div class="page-header">
                    <h2>Gestión de Actividades</h2>
                    <button class="btn btn-admin-primary">
                        <i class="fas fa-plus"></i> Nueva Actividad
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <p>Componente de actividades en desarrollo...</p>
                        <p>Aquí se mostrará la lista de actividades con funcionalidades CRUD.</p>
                    </div>
                </div>
            </div>
        `;
    }
}

/**
 * Componente Estadísticas (placeholder)
 */
class EstadisticasComponent extends BaseComponent {
    renderHTML() {
        this.container.innerHTML = `
            <div class="estadisticas-container">
                <div class="page-header">
                    <h2>Estadísticas</h2>
                    <button class="btn btn-admin-primary">
                        <i class="fas fa-download"></i> Exportar Datos
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <p>Componente de estadísticas en desarrollo...</p>
                        <p>Aquí se mostrarán gráficos y reportes detallados.</p>
                    </div>
                </div>
            </div>
        `;
    }
}

/**
 * Componente Superadmin (placeholder)
 */
class SuperadminComponent extends BaseComponent {
    renderHTML() {
        this.container.innerHTML = `
            <div class="superadmin-container">
                <div class="page-header">
                    <h2>Panel de Superadministrador</h2>
                    <button class="btn btn-admin-primary">
                        <i class="fas fa-user-plus"></i> Nuevo Admin
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <p>Componente de superadmin en desarrollo...</p>
                        <p>Aquí se gestionarán administradores y asignaciones.</p>
                    </div>
                </div>
            </div>
        `;
    }
}

// Hacer los componentes globales
window.DashboardComponent = DashboardComponent;
window.CentrosComponent = CentrosComponent;
window.InstalacionesComponent = InstalacionesComponent;
window.ActividadesComponent = ActividadesComponent;
window.EstadisticasComponent = EstadisticasComponent;
window.SuperadminComponent = SuperadminComponent;
