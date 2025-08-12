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
        if (routeConfig.requireSuperAdmin) {
            const currentUser = window.AdminApp?.currentUser;
            if (!currentUser || !currentUser.isSuperAdmin) {
                this.navigate('dashboard');
                Utils.showNotification('No tienes permisos para acceder a esta sección', 'error');
                return;
            }
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
        document.title = `${config.title} - Admin Panel`;
        
        // En la nueva estructura, no hay elementos fijos de UI para actualizar
        // El componente se encarga de renderizar todo su contenido
    }

    /**
     * Cargar componente dinámicamente
     */
    async loadComponent(componentName) {
        const contentArea = document.getElementById('admin-app');
        
        // Mostrar loading
        Utils.showLoading();

        try {
            // Verificar si el componente existe
            if (typeof window[componentName] === 'function') {
                const component = new window[componentName](contentArea);
                await component.load();
                component.renderHTML();
            } else {
                throw new Error(`Componente ${componentName} no encontrado`);
            }
        } catch (error) {
            console.error('Error cargando componente:', error);
            contentArea.innerHTML = `
                <div class="error-message" style="padding: 40px; text-align: center;">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 16px; color: var(--error-color);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3>Error al cargar la página</h3>
                    <p>Ha ocurrido un error al cargar el contenido. Por favor, inténtalo de nuevo.</p>
                    <button onclick="location.reload()" class="btn btn-primary" style="margin-top: 16px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Recargar
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
