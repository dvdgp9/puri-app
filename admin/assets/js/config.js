/**
 * Configuración global de la aplicación SPA Admin
 */

const AdminConfig = {
    // URLs base
    API_BASE_URL: '/admin/api',
    BASE_URL: '/admin',
    
    // Rutas de la aplicación
    ROUTES: {
        dashboard: 'dashboard',
        centros: 'centros',
        instalaciones: 'instalaciones',
        actividades: 'actividades',
        estadisticas: 'estadisticas',
        superadmin: 'superadmin'
    },
    
    // Configuración de la API
    API_ENDPOINTS: {
        // Autenticación
        auth: {
            check: '/auth/check.php',
            logout: '/auth/logout.php'
        },
        
        // Centros
        centros: {
            list: '/centros/list.php',
            create: '/centros/create.php',
            update: '/centros/update.php',
            delete: '/centros/delete.php',
            get: '/centros/get.php'
        },
        
        // Instalaciones
        instalaciones: {
            list: '/instalaciones/list.php',
            create: '/instalaciones/create.php',
            update: '/instalaciones/update.php',
            delete: '/instalaciones/delete.php',
            get: '/instalaciones/get.php'
        },
        
        // Actividades
        actividades: {
            list: '/actividades/list.php',
            create: '/actividades/create.php',
            update: '/actividades/update.php',
            delete: '/actividades/delete.php',
            get: '/actividades/get.php'
        },
        
        // Estadísticas
        stats: {
            dashboard: '/stats/dashboard.php',
            centros: '/stats/centros.php',
            export: '/stats/export.php'
        },
        
        // Superadmin
        superadmin: {
            admins: '/superadmin/admins.php',
            assignments: '/superadmin/assignments.php'
        }
    },
    
    // Configuración de UI
    UI: {
        ITEMS_PER_PAGE: 20,
        DEBOUNCE_DELAY: 300,
        ANIMATION_DURATION: 300
    },
    
    // Estados de actividades
    ACTIVITY_STATES: {
        PROGRAMADA: 'Programada',
        ACTIVA: 'Activa',
        FINALIZADA: 'Finalizada'
    },
    
    // Días de la semana
    DAYS_OF_WEEK: {
        'Lunes': 'L',
        'Martes': 'M',
        'Miércoles': 'X',
        'Jueves': 'J',
        'Viernes': 'V',
        'Sábado': 'S',
        'Domingo': 'D'
    }
};

// Variables globales de la aplicación
window.AdminApp = {
    currentUser: null,
    currentRoute: 'dashboard',
    cache: new Map(),
    isLoading: false
};
