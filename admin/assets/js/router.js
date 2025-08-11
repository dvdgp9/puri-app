/**
 * Sistema de enrutamiento client-side para la SPA
 */

class AdminRouter {
    constructor() {
        this.routes = new Map();
        this.currentRoute = null;
        this.defaultRoute = 'dashboard';
        
        // Inicializar el router
        this.init();
    }

    /**
     * Inicializar el router
     */
    init() {
        // Registrar rutas
        this.registerRoutes();
        
        // Escuchar cambios en el hash
        window.addEventListener('hashchange', () => this.handleRouteChange());
        
        // Manejar la ruta inicial
        this.handleRouteChange();
    }

    /**
     * Registrar todas las rutas de la aplicación
     */
    registerRoutes() {
        this.addRoute('dashboard', {
            title: 'Dashboard',
            component: 'DashboardComponent',
            breadcrumb: ['Dashboard']
        });

        this.addRoute('centros', {
            title: 'Centros',
            component: 'CentrosComponent',
            breadcrumb: ['Dashboard', 'Centros']
        });

        this.addRoute('instalaciones', {
            title: 'Instalaciones',
            component: 'InstalacionesComponent',
            breadcrumb: ['Dashboard', 'Instalaciones']
        });

        this.addRoute('actividades', {
            title: 'Actividades',
            component: 'ActividadesComponent',
            breadcrumb: ['Dashboard', 'Actividades']
        });

        this.addRoute('estadisticas', {
            title: 'Estadísticas',
            component: 'EstadisticasComponent',
            breadcrumb: ['Dashboard', 'Estadísticas']
        });

        this.addRoute('superadmin', {
            title: 'Superadmin',
            component: 'SuperadminComponent',
            breadcrumb: ['Dashboard', 'Superadmin'],
            requireSuperAdmin: true
        });
    }

    /**
     * Agregar una ruta
     */
    addRoute(path, config) {
        this.routes.set(path, config);
    }

    /**
     * Obtener la ruta actual del hash
     */
    getCurrentRoute() {
        const hash = window.location.hash.slice(1); // Remover el #
        return hash || this.defaultRoute;
    }

    /**
     * Manejar cambio de ruta
     */
    async handleRouteChange() {
        const route = this.getCurrentRoute();
        const routeConfig = this.routes.get(route);

        // Si la ruta no existe, redirigir al dashboard
        if (!routeConfig) {
            this.navigate(this.defaultRoute);
            return;
        }

        // Verificar permisos si es necesario
        if (routeConfig.requireSuperAdmin && !AdminApp.currentUser?.isSuperAdmin) {
            this.navigate('dashboard');
            Utils.showNotification('No tienes permisos para acceder a esta sección', 'error');
            return;
        }

        // Actualizar la ruta actual
        this.currentRoute = route;

        // Actualizar la UI
        this.updateUI(route, routeConfig);

        // Cargar el componente
        await this.loadComponent(routeConfig.component);
    }

    /**
     * Actualizar la interfaz de usuario
     */
    updateUI(route, config) {
        // Actualizar el título de la página
        document.getElementById('page-title').textContent = config.title;
        document.title = `${config.title} - Admin Panel`;

        // Actualizar breadcrumb
        const breadcrumbEl = document.getElementById('breadcrumb');
        breadcrumbEl.innerHTML = config.breadcrumb
            .map((item, index) => {
                if (index === config.breadcrumb.length - 1) {
                    return `<span class="breadcrumb-current">${item}</span>`;
                }
                return `<span class="breadcrumb-item">${item}</span>`;
            })
            .join(' <i class="fas fa-chevron-right"></i> ');

        // Actualizar navegación activa
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        const activeLink = document.querySelector(`[data-route="${route}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }

    /**
     * Cargar componente dinámicamente
     */
    async loadComponent(componentName) {
        const contentArea = document.getElementById('content-area');
        
        // Mostrar loading
        Utils.showLoading();

        try {
            // Verificar si el componente existe
            if (typeof window[componentName] === 'function') {
                const component = new window[componentName]();
                await component.render(contentArea);
            } else {
                throw new Error(`Componente ${componentName} no encontrado`);
            }
        } catch (error) {
            console.error('Error cargando componente:', error);
            contentArea.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error al cargar la página</h3>
                    <p>Ha ocurrido un error al cargar el contenido. Por favor, inténtalo de nuevo.</p>
                    <button onclick="location.reload()" class="btn btn-primary">
                        <i class="fas fa-refresh"></i> Recargar
                    </button>
                </div>
            `;
        } finally {
            Utils.hideLoading();
        }
    }

    /**
     * Navegar a una ruta
     */
    navigate(route, params = {}) {
        const queryString = Object.keys(params).length > 0 
            ? '?' + new URLSearchParams(params).toString() 
            : '';
        
        window.location.hash = route + queryString;
    }

    /**
     * Obtener parámetros de la URL
     */
    getParams() {
        const hash = window.location.hash;
        const queryStart = hash.indexOf('?');
        
        if (queryStart === -1) return {};
        
        const queryString = hash.slice(queryStart + 1);
        return Object.fromEntries(new URLSearchParams(queryString));
    }

    /**
     * Ir hacia atrás en el historial
     */
    goBack() {
        window.history.back();
    }
}

// Instancia global del router
window.router = new AdminRouter();
