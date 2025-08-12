/**
 * Aplicación principal SPA Admin
 */

class AdminApp {
    constructor() {
        this.isInitialized = false;
        this.currentUser = null;
    }

    /**
     * Inicializar la aplicación
     */
    async init() {
        if (this.isInitialized) return;

        try {
            // Verificar autenticación
            await this.checkAuthentication();
            
            // Configurar event listeners
            this.setupEventListeners();
            
            // Inicializar utilidades
            this.initUtils();
            
            // Mostrar la aplicación
            this.showApp();
            
            this.isInitialized = true;
            
        } catch (error) {
            console.error('Error inicializando la aplicación:', error);
            this.redirectToLogin();
        }
    }

    /**
     * Verificar autenticación del usuario
     */
    async checkAuthentication() {
        try {
            const response = await fetch('check_session.php');
            const data = await response.json();
            
            if (data.authenticated && data.user) {
                this.currentUser = data.user;
                window.AdminApp.currentUser = data.user;
                
                // Actualizar información del usuario en la UI
                this.updateUserInfo();
                
                return true;
            } else {
                // Redirigir al login si no está autenticado
                window.location.href = data.redirect || 'login.php';
                throw new Error('Usuario no autenticado');
            }
        } catch (error) {
            console.error('Error verificando autenticación:', error);
            // En caso de error, redirigir al login
            window.location.href = 'login.php';
            throw error;
        }
    }

    /**
     * Actualizar información del usuario en la interfaz
     */
    updateUserInfo() {
        // En la nueva estructura, la información del usuario se maneja en el componente
        // No hay elementos fijos en el DOM para actualizar
        if (this.currentUser) {
            console.log('Usuario autenticado:', this.currentUser.username, this.currentUser.role);
        }
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // En la nueva estructura, los event listeners se manejan en los componentes
        // No hay elementos fijos para configurar aquí
        console.log('Event listeners configurados para la nueva estructura SPA');
    }

    /**
     * Configurar sidebar responsivo - OBSOLETO en nueva estructura
     */
    setupResponsiveSidebar() {
        // Ya no hay sidebar en la nueva estructura
    }

    /**
     * Toggle sidebar - OBSOLETO en nueva estructura
     */
    toggleSidebar() {
        // Ya no hay sidebar en la nueva estructura
    }

    /**
     * Cerrar sesión
     */
    async logout() {
        if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) {
            return;
        }

        try {
            Utils.showLoading();
            await api.logout();
            this.redirectToLogin();
        } catch (error) {
            console.error('Error cerrando sesión:', error);
            Utils.showNotification('Error al cerrar sesión', 'error');
        } finally {
            Utils.hideLoading();
        }
    }

    /**
     * Redirigir al login
     */
    redirectToLogin() {
        window.location.href = 'login.php';
    }

    /**
     * Inicializar utilidades
     */
    initUtils() {
        // Configurar tooltips si es necesario
        // Configurar otros plugins o utilidades
    }

    /**
     * Mostrar la aplicación
     */
    showApp() {
        const loadingSpinner = document.getElementById('loading-spinner');
        const adminApp = document.getElementById('admin-app');

        loadingSpinner.style.display = 'none';
        adminApp.style.display = 'flex';
    }
}

/**
 * Utilidades globales
 */
class Utils {
    /**
     * Mostrar loading spinner
     */
    static showLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'flex';
        }
        window.AdminApp.isLoading = true;
    }

    /**
     * Ocultar loading spinner
     */
    static hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'none';
        }
        window.AdminApp.isLoading = false;
    }

    /**
     * Mostrar notificación
     */
    static showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        // Agregar al DOM
        document.body.appendChild(notification);

        // Mostrar con animación
        setTimeout(() => notification.classList.add('show'), 10);

        // Auto-remover
        setTimeout(() => this.removeNotification(notification), duration);

        // Click para cerrar
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.removeNotification(notification);
        });
    }

    /**
     * Remover notificación
     */
    static removeNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    /**
     * Obtener icono para notificación
     */
    static getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    /**
     * Formatear fecha
     */
    static formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    /**
     * Formatear hora
     */
    static formatTime(timeString) {
        if (!timeString) return '';
        return timeString.slice(0, 5) + 'h';
    }

    /**
     * Formatear días de la semana
     */
    static formatDays(daysString) {
        if (!daysString) return '';
        
        const days = daysString.split(',');
        return days.map(day => AdminConfig.DAYS_OF_WEEK[day.trim()] || day.trim()).join(' ');
    }

    /**
     * Debounce function
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Confirmar acción
     */
    static confirm(message, title = 'Confirmar') {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal-backdrop';
            modal.innerHTML = `
                <div class="modal confirm-modal">
                    <div class="modal-header">
                        <h3>${title}</h3>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline cancel-btn">Cancelar</button>
                        <button class="btn btn-primary confirm-btn">Confirmar</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);

            modal.querySelector('.cancel-btn').addEventListener('click', () => {
                this.closeModal(modal);
                resolve(false);
            });

            modal.querySelector('.confirm-btn').addEventListener('click', () => {
                this.closeModal(modal);
                resolve(true);
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                    resolve(false);
                }
            });
        });
    }

    /**
     * Cerrar modal
     */
    static closeModal(modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
    }
}

// Hacer Utils global
window.Utils = Utils;

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', async () => {
    const app = new AdminApp();
    await app.init();
});
