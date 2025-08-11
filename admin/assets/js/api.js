/**
 * Módulo de API para comunicación con el backend
 */

class AdminAPI {
    constructor() {
        this.baseURL = AdminConfig.API_BASE_URL;
    }

    /**
     * Realizar petición HTTP genérica
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        const config = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        
        return this.request(url);
    }

    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }

    // Métodos específicos de la API

    /**
     * Verificar autenticación
     */
    async checkAuth() {
        return this.get(AdminConfig.API_ENDPOINTS.auth.check);
    }

    /**
     * Cerrar sesión
     */
    async logout() {
        return this.post(AdminConfig.API_ENDPOINTS.auth.logout);
    }

    /**
     * Obtener lista de centros
     */
    async getCentros(params = {}) {
        return this.get(AdminConfig.API_ENDPOINTS.centros.list, params);
    }

    /**
     * Crear centro
     */
    async createCentro(data) {
        return this.post(AdminConfig.API_ENDPOINTS.centros.create, data);
    }

    /**
     * Actualizar centro
     */
    async updateCentro(id, data) {
        return this.put(`${AdminConfig.API_ENDPOINTS.centros.update}?id=${id}`, data);
    }

    /**
     * Eliminar centro
     */
    async deleteCentro(id) {
        return this.delete(`${AdminConfig.API_ENDPOINTS.centros.delete}?id=${id}`);
    }

    /**
     * Obtener lista de instalaciones
     */
    async getInstalaciones(params = {}) {
        return this.get(AdminConfig.API_ENDPOINTS.instalaciones.list, params);
    }

    /**
     * Crear instalación
     */
    async createInstalacion(data) {
        return this.post(AdminConfig.API_ENDPOINTS.instalaciones.create, data);
    }

    /**
     * Actualizar instalación
     */
    async updateInstalacion(id, data) {
        return this.put(`${AdminConfig.API_ENDPOINTS.instalaciones.update}?id=${id}`, data);
    }

    /**
     * Eliminar instalación
     */
    async deleteInstalacion(id) {
        return this.delete(`${AdminConfig.API_ENDPOINTS.instalaciones.delete}?id=${id}`);
    }

    /**
     * Obtener lista de actividades
     */
    async getActividades(params = {}) {
        return this.get(AdminConfig.API_ENDPOINTS.actividades.list, params);
    }

    /**
     * Crear actividad
     */
    async createActividad(data) {
        return this.post(AdminConfig.API_ENDPOINTS.actividades.create, data);
    }

    /**
     * Actualizar actividad
     */
    async updateActividad(id, data) {
        return this.put(`${AdminConfig.API_ENDPOINTS.actividades.update}?id=${id}`, data);
    }

    /**
     * Eliminar actividad
     */
    async deleteActividad(id) {
        return this.delete(`${AdminConfig.API_ENDPOINTS.actividades.delete}?id=${id}`);
    }

    /**
     * Obtener estadísticas del dashboard
     */
    async getDashboardStats() {
        return this.get(AdminConfig.API_ENDPOINTS.stats.dashboard);
    }

    /**
     * Obtener estadísticas de centros
     */
    async getCentrosStats() {
        return this.get(AdminConfig.API_ENDPOINTS.stats.centros);
    }

    /**
     * Exportar datos
     */
    async exportData(type, params = {}) {
        const queryString = new URLSearchParams({ type, ...params }).toString();
        const url = `${this.baseURL}${AdminConfig.API_ENDPOINTS.stats.export}?${queryString}`;
        
        // Para descargas, abrir en nueva ventana
        window.open(url, '_blank');
    }
}

// Instancia global de la API
window.api = new AdminAPI();
