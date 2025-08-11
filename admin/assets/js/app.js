// Sistema de enrutamiento client-side
const Router = {
    routes: {},
    currentRoute: '',
    
    addRoute(path, callback) {
        this.routes[path] = callback;
    },
    
    navigate(path) {
        this.currentRoute = path;
        window.location.hash = path;
        this.handleRoute();
    },
    
    handleRoute() {
        const path = window.location.hash.substr(1) || 'dashboard';
        const route = this.routes[path];
        
        if (route) {
            // Mostrar loader mientras se carga el contenido
            loadContent(Components.loader());
            route();
        } else {
            loadContent(Components.error('Ruta no encontrada'));
        }
        
        // Actualizar navegación activa
        this.updateActiveNav(path);
    },
    
    updateActiveNav(activePath) {
        const navLinks = document.querySelectorAll('.sidebar nav a[data-view]');
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('data-view') === activePath) {
                link.classList.add('active');
            }
        });
    }
};

// Sistema de API para llamadas AJAX con manejo de errores
const API = {
    baseUrl: '/admin/api',
    
    async request(endpoint, options = {}) {
        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, {
                headers: {
                    'Content-Type': 'application/json',
                },
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },
    
    async get(endpoint) {
        return this.request(endpoint);
    },
    
    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },
    
    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }
};

// Sistema de componentes
const Components = {
    loader() {
        return `
            <div class="loader">
                <div class="spinner"></div>
                <p>Cargando...</p>
            </div>
        `;
    },
    
    error(message) {
        return `
            <div class="error-message">
                <p>${message}</p>
            </div>
        `;
    },
    
    success(message) {
        return `
            <div class="success-message">
                <p>${message}</p>
            </div>
        `;
    },
    
    // Componente para confirmación de eliminación
    confirmModal(title, message, onConfirm) {
        const modal = document.createElement('div');
        modal.className = 'modal-backdrop';
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3>${title}</h3>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="cancel-btn">Cancelar</button>
                    <button class="btn btn-danger" id="confirm-btn">Confirmar</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Eventos
        document.getElementById('cancel-btn').addEventListener('click', () => {
            document.body.removeChild(modal);
        });
        
        document.getElementById('confirm-btn').addEventListener('click', () => {
            document.body.removeChild(modal);
            onConfirm();
        });
        
        return modal;
    }
};

// Sistema de estado (simplificado)
const State = {
    data: {},
    
    set(key, value) {
        this.data[key] = value;
        // Opcional: guardar en localStorage para persistencia
        // localStorage.setItem(`admin_${key}`, JSON.stringify(value));
    },
    
    get(key) {
        // Opcional: cargar desde localStorage
        // if (this.data[key] === undefined) {
        //     const item = localStorage.getItem(`admin_${key}`);
        //     return item ? JSON.parse(item) : undefined;
        // }
        return this.data[key];
    },
    
    clear() {
        this.data = {};
        // localStorage.clear();
    }
};

// Función para cargar contenido dinámico
function loadContent(content) {
    const contentArea = document.getElementById('content-area');
    if (contentArea) {
        contentArea.innerHTML = content;
    }
}

// Función para mostrar notificaciones
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `<p>${message}</p>`;
    
    document.body.appendChild(notification);
    
    // Eliminar después de 3 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}

// Inicializar la aplicación cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Configurar enrutamiento
    setupRouting();
    
    // Manejar el hash change
    window.addEventListener('hashchange', () => Router.handleRoute());
    
    // Cargar la ruta inicial
    Router.handleRoute();
});

// Definir rutas básicas
Router.addRoute('dashboard', async function() {
    try {
        // Obtener centros asignados para el filtro
        const centrosResponse = await API.get('/centros.php');
        const centros = centrosResponse.success ? centrosResponse.data : [];
        
        // Obtener estadísticas iniciales
        const statsResponse = await API.get('/estadisticas.php');
        
        if (statsResponse.success) {
            const stats = statsResponse.data.stats;
            
            loadContent(`
                <h2>Dashboard</h2>
                <div class="filters">
                    <select id="centro-filter" class="form-control">
                        <option value="">Todos los centros</option>
                        ${centros.map(centro => `
                            <option value="${centro.id}">${centro.nombre}</option>
                        `).join('')}
                    </select>
                    <button id="download-report" class="btn btn-secondary">Descargar Informe</button>
                </div>
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Centros Asignados</h3>
                        <div class="number">${stats.centros}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Instalaciones</h3>
                        <div class="number">${stats.instalaciones}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Actividades</h3>
                        <div class="number">${stats.actividades}</div>
                    </div>
                </div>
                
                <h3>Distribución de Actividades por Estado</h3>
                <div class="stats-container">
                    ${stats.estados && stats.estados.length > 0 ? 
                        stats.estados.map(estado => `
                            <div class="stat-card">
                                <h3>${estado.estado.charAt(0).toUpperCase() + estado.estado.slice(1)}s</h3>
                                <div class="number">${estado.count}</div>
                            </div>
                        `).join('') : 
                        '<p>No hay actividades registradas.</p>'
                    }
                </div>
                
                <h3>Gráficos de Actividades</h3>
                <div class="chart-container">
                    <canvas id="actividadesChart" width="400" height="200"></canvas>
                </div>
                
                <h3>Estadísticas en Tiempo Real</h3>
                <div class="stats-container" id="realtime-stats">
                    <div class="stat-card">
                        <h3>Actividades Nuevas (última hora)</h3>
                        <div class="number" id="new-activities-count">0</div>
                    </div>
                    <div class="stat-card">
                        <h3>Actividades Completadas (última hora)</h3>
                        <div class="number" id="completed-activities-count">0</div>
                    </div>
                </div>
                
                <h3>Mis Centros</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${centros.map(centro => `
                                <tr>
                                    <td>${centro.nombre}</td>
                                    <td>${centro.descripcion || ''}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `);
            
            // Generar gráfico inicial
            generateChart(stats.estados);
            
            // Iniciar actualización en tiempo real
            startRealtimeStats();
            
            // Añadir evento al botón de descarga de informe
            const downloadReportBtn = document.getElementById('download-report');
            if (downloadReportBtn) {
                downloadReportBtn.addEventListener('click', async function() {
                    const centroId = document.getElementById('centro-filter').value;
                    
                    try {
                        // Obtener datos para el informe
                        const reportData = await API.get(`/estadisticas.php${centroId ? `?centro_id=${centroId}` : ''}`);
                        
                        if (reportData.success) {
                            // Generar y descargar informe
                            generateAndDownloadReport(reportData.data, centroId);
                        }
                    } catch (error) {
                        showNotification('Error al generar el informe', 'error');
                    }
                });
            }
            
            // Añadir evento al filtro de centro
            const centroFilter = document.getElementById('centro-filter');
            if (centroFilter) {
                centroFilter.addEventListener('change', async function() {
                    const centroId = this.value;
                    
                    // Mostrar loader mientras se cargan las estadísticas
                    const statsContainer = document.querySelector('.stats-container');
                    if (statsContainer) {
                        statsContainer.innerHTML = '<div class="loader"></div>';
                    }
                    
                    try {
                        // Obtener estadísticas filtradas
                        const filteredStatsResponse = await API.get(`/estadisticas.php${centroId ? `?centro_id=${centroId}` : ''}`);
                        
                        if (filteredStatsResponse.success) {
                            const filteredStats = filteredStatsResponse.data.stats;
                            
                            // Actualizar las estadísticas
                            const statCards = document.querySelectorAll('.stat-card');
                            if (statCards.length >= 3) {
                                statCards[0].querySelector('.number').textContent = filteredStats.centros;
                                statCards[1].querySelector('.number').textContent = filteredStats.instalaciones;
                                statCards[2].querySelector('.number').textContent = filteredStats.actividades;
                            }
                            
                            // Actualizar la distribución de actividades por estado
                            const estadosContainer = document.querySelectorAll('.stats-container')[1];
                            if (estadosContainer) {
                                if (filteredStats.estados && filteredStats.estados.length > 0) {
                                    estadosContainer.innerHTML = filteredStats.estados.map(estado => `
                                        <div class="stat-card">
                                            <h3>${estado.estado.charAt(0).toUpperCase() + estado.estado.slice(1)}s</h3>
                                            <div class="number">${estado.count}</div>
                                        </div>
                                    `).join('');
                                } else {
                                    estadosContainer.innerHTML = '<p>No hay actividades registradas.</p>';
                                }
                            }
                            
                            // Actualizar gráfico
                            generateChart(filteredStats.estados);
                        }
                    } catch (error) {
                        showNotification('Error al cargar las estadísticas filtradas', 'error');
                    }
                });
            }
        } else {
            loadContent(Components.error('Error al cargar el dashboard'));
        }
    } catch (error) {
        loadContent(Components.error('Error de conexión al cargar el dashboard'));
    }
});

// Ruta para gestión de administradores (solo superadmin)
Router.addRoute('admins', async function() {
    try {
        // Verificar si el usuario es superadmin
        const adminResponse = await API.get('/admin.php');
        
        if (adminResponse.success && adminResponse.data.role === 'superadmin') {
            // Obtener lista de administradores
            const adminsResponse = await API.get('/admins.php');
            
            if (adminsResponse.success) {
                const admins = adminsResponse.data;
                
                loadContent(`
                    <h2>Gestión de Administradores</h2>
                    <div class="actions-bar">
                        <button id="add-admin" class="btn btn-primary">Añadir Administrador</button>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Centros Asignados</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${admins.map(admin => `
                                    <tr data-id="${admin.id}">
                                        <td>${admin.nombre}</td>
                                        <td>${admin.email}</td>
                                        <td>${admin.role === 'superadmin' ? 'Superadmin' : 'Admin'}</td>
                                        <td>${admin.centros_asignados || 0}</td>
                                        <td>
                                            <button class="btn btn-small btn-secondary edit-admin" data-id="${admin.id}">Editar</button>
                                            ${admin.role !== 'superadmin' ? `<button class="btn btn-small btn-danger delete-admin" data-id="${admin.id}">Eliminar</button>` : ''}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `);
                
                // Añadir evento al botón de añadir administrador
                document.getElementById('add-admin').addEventListener('click', showAddAdminModal);
                
                // Añadir eventos a botones de editar y eliminar
                document.querySelectorAll('.edit-admin').forEach(button => {
                    button.addEventListener('click', function() {
                        const adminId = this.getAttribute('data-id');
                        showEditAdminModal(adminId);
                    });
                });
                
                document.querySelectorAll('.delete-admin').forEach(button => {
                    button.addEventListener('click', function() {
                        const adminId = this.getAttribute('data-id');
                        deleteAdmin(adminId);
                    });
                });
            } else {
                loadContent(Components.error('Error al cargar la lista de administradores'));
            }
        } else {
            loadContent(Components.error('Acceso denegado. Solo los superadministradores pueden acceder a esta sección.'));
        }
    } catch (error) {
        loadContent(Components.error('Error de conexión al cargar la gestión de administradores'));
    }
});

// Ruta para asignación de centros a administradores (solo superadmin)
Router.addRoute('asignar-centros', async function() {
    try {
        // Verificar si el usuario es superadmin
        const adminResponse = await API.get('/admin.php');
        
        if (adminResponse.success && adminResponse.data.role === 'superadmin') {
            // Obtener lista de administradores
            const adminsResponse = await API.get('/admins.php');
            
            // Obtener lista de centros
            const centrosResponse = await API.get('/centros.php');
            
            if (adminsResponse.success && centrosResponse.success) {
                const admins = adminsResponse.data;
                const centros = centrosResponse.data;
                
                loadContent(`
                    <h2>Asignación de Centros a Administradores</h2>
                    <div class="asignacion-container">
                        <div class="admins-list">
                            <h3>Administradores</h3>
                            <ul id="admins-container" class="draggable-list">
                                ${admins.filter(admin => admin.role !== 'superadmin').map(admin => `
                                    <li class="draggable-item admin-item" data-id="${admin.id}" data-type="admin">
                                        <div class="item-content">
                                            <span class="item-name">${admin.nombre}</span>
                                            <span class="item-email">${admin.email}</span>
                                        </div>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        
                        <div class="centros-list">
                            <h3>Centros</h3>
                            <ul id="centros-container" class="draggable-list">
                                ${centros.map(centro => `
                                    <li class="draggable-item centro-item" data-id="${centro.id}" data-type="centro">
                                        <div class="item-content">
                                            <span class="item-name">${centro.nombre}</span>
                                        </div>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        
                        <div class="asignaciones-list">
                            <h3>Asignaciones Actuales</h3>
                            <div id="asignaciones-container" class="asignaciones-container">
                                <!-- Las asignaciones se cargarán aquí dinámicamente -->
                            </div>
                        </div>
                    </div>
                `);
                
                // Inicializar funcionalidad de drag & drop
                initDragAndDrop();
                
                // Cargar asignaciones actuales
                loadCurrentAsignaciones();
            } else {
                loadContent(Components.error('Error al cargar los datos para la asignación'));
            }
        } else {
            loadContent(Components.error('Acceso denegado. Solo los superadministradores pueden acceder a esta sección.'));
        }
    } catch (error) {
        loadContent(Components.error('Error de conexión al cargar la asignación de centros'));
    }
});

// Ruta para vista global sin restricciones (solo superadmin)
Router.addRoute('vista-global', async function() {
    try {
        // Verificar si el usuario es superadmin
        const adminResponse = await API.get('/admin.php');
        
        if (adminResponse.success && adminResponse.data.role === 'superadmin') {
            // Obtener estadísticas globales
            const statsResponse = await API.get('/estadisticas.php?global=true');
            
            // Obtener lista de centros
            const centrosResponse = await API.get('/centros.php?all=true');
            
            // Obtener lista de instalaciones
            const instalacionesResponse = await API.get('/instalaciones.php?all=true');
            
            // Obtener lista de actividades
            const actividadesResponse = await API.get('/actividades.php?all=true');
            
            if (statsResponse.success && centrosResponse.success && instalacionesResponse.success && actividadesResponse.success) {
                const stats = statsResponse.data.stats;
                const centros = centrosResponse.data;
                const instalaciones = instalacionesResponse.data;
                const actividades = actividadesResponse.data;
                
                loadContent(`
                    <h2>Vista Global del Sistema</h2>
                    
                    <div class="stats-container">
                        <div class="stat-card">
                            <h3>Total de Centros</h3>
                            <div class="number">${stats.centros}</div>
                        </div>
                        <div class="stat-card">
                            <h3>Total de Instalaciones</h3>
                            <div class="number">${stats.instalaciones}</div>
                        </div>
                        <div class="stat-card">
                            <h3>Total de Actividades</h3>
                            <div class="number">${stats.actividades}</div>
                        </div>
                    </div>
                    
                    <h3>Distribución de Actividades por Estado</h3>
                    <div class="stats-container">
                        ${stats.estados && stats.estados.length > 0 ? 
                            stats.estados.map(estado => `
                                <div class="stat-card">
                                    <h3>${estado.estado.charAt(0).toUpperCase() + estado.estado.slice(1)}s</h3>
                                    <div class="number">${estado.count}</div>
                                </div>
                            `).join('') : 
                            '<p>No hay actividades registradas.</p>'
                        }
                    </div>
                    
                    <h3>Gráficos Globales</h3>
                    <div class="chart-container">
                        <canvas id="actividadesChart" width="400" height="200"></canvas>
                    </div>
                    
                    <h3>Todos los Centros</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Instalaciones</th>
                                    <th>Actividades</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${centros.map(centro => `
                                    <tr>
                                        <td>${centro.nombre}</td>
                                        <td>${centro.descripcion || ''}</td>
                                        <td>${centro.instalaciones_count || 0}</td>
                                        <td>${centro.actividades_count || 0}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    <h3>Todas las Instalaciones</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Centro</th>
                                    <th>Descripción</th>
                                    <th>Actividades</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${instalaciones.map(instalacion => `
                                    <tr>
                                        <td>${instalacion.nombre}</td>
                                        <td>${instalacion.centro_nombre}</td>
                                        <td>${instalacion.descripcion || ''}</td>
                                        <td>${instalacion.actividades_count || 0}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    <h3>Todas las Actividades</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Instalación</th>
                                    <th>Centro</th>
                                    <th>Estado</th>
                                    <th>Horario</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${actividades.map(actividad => `
                                    <tr>
                                        <td>${actividad.nombre}</td>
                                        <td>${actividad.instalacion_nombre}</td>
                                        <td>${actividad.centro_nombre}</td>
                                        <td>${actividad.estado}</td>
                                        <td>${actividad.horario}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `);
                
                // Generar gráfico
                generateChart(stats.estados);
            } else {
                loadContent(Components.error('Error al cargar los datos globales'));
            }
        } else {
            loadContent(Components.error('Acceso denegado. Solo los superadministradores pueden acceder a esta sección.'));
        }
    } catch (error) {
        loadContent(Components.error('Error de conexión al cargar la vista global'));
    }
});

// Ruta para panel de logs de actividad en tiempo real (solo superadmin)
Router.addRoute('logs-actividad', async function() {
    try {
        // Verificar si el usuario es superadmin
        const adminResponse = await API.get('/admin.php');
        
        if (adminResponse.success && adminResponse.data.role === 'superadmin') {
            loadContent(`
                <h2>Logs de Actividad en Tiempo Real</h2>
                <div class="logs-container">
                    <div id="logs-content" class="logs-content">
                        <p class="no-logs">Cargando logs...</p>
                    </div>
                    <div class="logs-controls">
                        <button id="clear-logs" class="btn btn-secondary">Limpiar Logs</button>
                        <button id="pause-logs" class="btn btn-secondary">Pausar</button>
                    </div>
                </div>
            `);
            
            // Iniciar la actualización en tiempo real de logs
            startRealtimeLogs();
        } else {
            loadContent(Components.error('Acceso denegado. Solo los superadministradores pueden acceder a esta sección.'));
        }
    } catch (error) {
        loadContent(Components.error('Error de conexión al cargar los logs de actividad'));
    }
});

Router.addRoute('centros', async function() {
    loadContent(Components.loader());
    
    try {
        const response = await API.get('/centros.php');
        
        if (response.success) {
            const centros = response.data;
            
            loadContent(`
                <div class="header-with-actions">
                    <h2>Gestión de Centros</h2>
                    <button class="btn btn-primary" id="add-centro">Añadir Centro</button>
                </div>
                <div class="table-container">
                    <table class="data-table" id="centros-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${centros.map(centro => `
                                <tr data-id="${centro.id}">
                                    <td class="editable" data-field="nombre" data-id="${centro.id}">${centro.nombre}</td>
                                    <td class="editable" data-field="descripcion" data-id="${centro.id}">${centro.descripcion || ''}</td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary edit-btn" data-id="${centro.id}">Editar</button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="${centro.id}">Eliminar</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `);
            
            // Añadir evento al botón de añadir
            const addCentroBtn = document.getElementById('add-centro');
            if (addCentroBtn) {
                addCentroBtn.addEventListener('click', function() {
                    showAddCentroModal();
                });
            }
            
            // Añadir eventos a botones de editar
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    // Aquí iría la lógica para editar el centro
                    alert('Editar centro con ID: ' + id);
                });
            });
            
            // Añadir eventos a botones de eliminar
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nombre = document.querySelector(`tr[data-id="${id}"] .editable[data-field="nombre"]`).textContent;
                    
                    Components.confirmModal(
                        'Eliminar Centro',
                        `¿Estás seguro de que quieres eliminar el centro "${nombre}"? Esta acción no se puede deshacer.`,
                        async () => {
                            try {
                                const response = await API.delete('/centros.php', {
                                    body: JSON.stringify({id: id})
                                });
                                
                                if (response.success) {
                                    // Eliminar fila de la tabla
                                    document.querySelector(`tr[data-id="${id}"]`).remove();
                                    showNotification('Centro eliminado correctamente', 'success');
                                } else {
                                    showNotification(response.error || 'Error al eliminar el centro', 'error');
                                }
                            } catch (error) {
                                showNotification('Error de conexión al eliminar el centro', 'error');
                            }
                        }
                    );
                });
            });
            
            // Añadir eventos a celdas editables
            document.querySelectorAll('.editable').forEach(cell => {
                cell.addEventListener('click', function() {
                    const field = this.getAttribute('data-field');
                    const id = this.getAttribute('data-id');
                    const currentValue = this.textContent;
                    
                    // Crear input para edición
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.value = currentValue;
                    input.className = 'editable-input';
                    
                    // Reemplazar contenido con input
                    this.innerHTML = '';
                    this.appendChild(input);
                    input.focus();
                    
                    // Guardar al perder el foco o presionar Enter
                    const save = async () => {
                        const newValue = input.value;
                        this.innerHTML = newValue;
                        
                        // Llamar a la API para guardar
                        await saveCentroField(id, field, newValue);
                    };
                    
                    input.addEventListener('blur', save);
                    input.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            save();
                        }
                    });
                });
            });
        } else {
            loadContent(Components.error('Error al cargar los centros'));
        }
    } catch (error) {
        loadContent(Components.error('Error de conexión al cargar los centros'));
    }
});

Router.addRoute('instalaciones', async function() {
    loadContent(Components.loader());
    
    try {
        // Obtener centros asignados para el filtro
        const centrosResponse = await API.get('/centros.php');
        const centros = centrosResponse.success ? centrosResponse.data : [];
        
        // Obtener instalaciones
        const instalacionesResponse = await API.get('/instalaciones.php');
        const instalaciones = instalacionesResponse.success ? instalacionesResponse.data : [];
        
        loadContent(`
            <div class="header-with-actions">
                <div>
                    <h2>Gestión de Instalaciones</h2>
                    <div class="filters">
                        <select id="centro-filter" class="form-control">
                            <option value="">Todos los centros</option>
                            ${centros.map(centro => `
                                <option value="${centro.id}">${centro.nombre}</option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" id="add-instalacion">Añadir Instalación</button>
            </div>
            <div class="table-container">
                <table class="data-table" id="instalaciones-table">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Nombre</th>
                            <th>Centro</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${instalaciones.map(instalacion => `
                            <tr data-id="${instalacion.id}" data-centro-id="${instalacion.centro_id}" class="draggable">
                                <td class="drag-handle">&#x2630;</td>
                                <td class="editable" data-field="nombre" data-id="${instalacion.id}">${instalacion.nombre}</td>
                                <td>${instalacion.centro_nombre}</td>
                                <td class="editable" data-field="descripcion" data-id="${instalacion.id}">${instalacion.descripcion || ''}</td>
                                <td>
                                    <button class="btn btn-sm btn-secondary edit-btn" data-id="${instalacion.id}">Editar</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${instalacion.id}">Eliminar</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `);
        
        // Añadir evento al filtro de centro
        const centroFilter = document.getElementById('centro-filter');
        if (centroFilter) {
            centroFilter.addEventListener('change', function() {
                const centroId = this.value;
                const rows = document.querySelectorAll('#instalaciones-table tbody tr');
                
                rows.forEach(row => {
                    if (centroId === '' || row.getAttribute('data-centro-id') === centroId) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Añadir evento al botón de añadir
        const addInstalacionBtn = document.getElementById('add-instalacion');
        if (addInstalacionBtn) {
            addInstalacionBtn.addEventListener('click', function() {
                showAddInstalacionModal(centros);
            });
        }
        
        // Añadir eventos a botones de editar
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                // Aquí iría la lógica para editar la instalación
                alert('Editar instalación con ID: ' + id);
            });
        });
        
        // Añadir eventos a botones de eliminar
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nombre = document.querySelector(`tr[data-id="${id}"] .editable[data-field="nombre"]`).textContent;
                
                Components.confirmModal(
                    'Eliminar Instalación',
                    `¿Estás seguro de que quieres eliminar la instalación "${nombre}"? Esta acción no se puede deshacer.`,
                    async () => {
                        try {
                            const response = await API.delete('/instalaciones.php', {
                                body: JSON.stringify({id: id})
                            });
                            
                            if (response.success) {
                                // Eliminar fila de la tabla
                                document.querySelector(`tr[data-id="${id}"]`).remove();
                                showNotification('Instalación eliminada correctamente', 'success');
                            } else {
                                showNotification(response.error || 'Error al eliminar la instalación', 'error');
                            }
                        } catch (error) {
                            showNotification('Error de conexión al eliminar la instalación', 'error');
                        }
                    }
                );
            });
        });
        
        // Añadir eventos a celdas editables
        document.querySelectorAll('.editable').forEach(cell => {
            cell.addEventListener('click', function() {
                const field = this.getAttribute('data-field');
                const id = this.getAttribute('data-id');
                const currentValue = this.textContent;
                
                // Crear input para edición
                const input = document.createElement('input');
                input.type = 'text';
                input.value = currentValue;
                input.className = 'editable-input';
                
                // Reemplazar contenido con input
                this.innerHTML = '';
                this.appendChild(input);
                input.focus();
                
                // Guardar al perder el foco o presionar Enter
                const save = async () => {
                    const newValue = input.value;
                    this.innerHTML = newValue;
                    
                    // Llamar a la API para guardar
                    await saveInstalacionField(id, field, newValue);
                };
                
                input.addEventListener('blur', save);
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        save();
                    }
                });
            });
        });
        
        // Implementar drag & drop
        implementDragAndDrop();
    } catch (error) {
        loadContent(Components.error('Error de conexión al cargar las instalaciones'));
    }
});

Router.addRoute('actividades', async function() {
    loadContent(Components.loader());
    
    try {
        // Obtener centros para el filtro
        const centrosResponse = await API.get('/centros.php');
        const centros = centrosResponse.success ? centrosResponse.data : [];
        
        // Obtener instalaciones para el filtro
        const instalacionesResponse = await API.get('/instalaciones.php');
        const instalaciones = instalacionesResponse.success ? instalacionesResponse.data : [];
        
        // Obtener actividades
        const actividadesResponse = await API.get('/actividades.php');
        const actividades = actividadesResponse.success ? actividadesResponse.data : [];
        
        // Separar actividades por estado
        const programadas = actividades.filter(a => a.estado === 'programada');
        const activas = actividades.filter(a => a.estado === 'activa');
        const finalizadas = actividades.filter(a => a.estado === 'finalizada');
        
        loadContent(`
            <div class="header-with-actions">
                <div>
                    <h2>Gestión de Actividades</h2>
                    <div class="filters">
                        <select id="centro-filter" class="form-control">
                            <option value="">Todos los centros</option>
                            ${centros.map(centro => `
                                <option value="${centro.id}">${centro.nombre}</option>
                            `).join('')}
                        </select>
                        <select id="instalacion-filter" class="form-control">
                            <option value="">Todas las instalaciones</option>
                            ${instalaciones.map(instalacion => `
                                <option value="${instalacion.id}" data-centro-id="${instalacion.centro_id}">${instalacion.nombre}</option>
                            `).join('')}
                        </select>
                        <select id="estado-filter" class="form-control">
                            <option value="">Todos los estados</option>
                            <option value="programada">Programada</option>
                            <option value="activa">Activa</option>
                            <option value="finalizada">Finalizada</option>
                        </select>
                    </div>
                </div>
                <div>
                    <button class="btn btn-secondary" id="import-csv">Importar CSV</button>
                    <button class="btn btn-primary" id="add-actividad">Añadir Actividad</button>
                </div>
            </div>
            
            <!-- Actividades Programadas -->
            ${programadas.length > 0 ? `
            <div class="section">
                <h3>Actividades Programadas (${programadas.length})</h3>
                <div class="table-container">
                    <table class="data-table" id="programadas-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Instalación</th>
                                <th>Horario</th>
                                <th>Fecha Inicio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${programadas.map(actividad => `
                                <tr data-id="${actividad.id}" data-instalacion-id="${actividad.instalacion_id}" data-centro-id="${actividad.centro_id}" data-estado="${actividad.estado}">
                                    <td class="editable" data-field="nombre" data-id="${actividad.id}">${actividad.nombre}</td>
                                    <td>${actividad.instalacion_nombre}</td>
                                    <td class="editable" data-field="horario" data-id="${actividad.id}">${actividad.horario}</td>
                                    <td>${actividad.fecha_inicio}</td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary edit-btn" data-id="${actividad.id}">Editar</button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="${actividad.id}">Eliminar</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            ` : ''}
            
            <!-- Actividades Activas -->
            ${activas.length > 0 ? `
            <div class="section">
                <h3>Actividades Activas (${activas.length})</h3>
                <div class="table-container">
                    <table class="data-table" id="activas-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Instalación</th>
                                <th>Horario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${activas.map(actividad => `
                                <tr data-id="${actividad.id}" data-instalacion-id="${actividad.instalacion_id}" data-centro-id="${actividad.centro_id}" data-estado="${actividad.estado}">
                                    <td class="editable" data-field="nombre" data-id="${actividad.id}">${actividad.nombre}</td>
                                    <td>${actividad.instalacion_nombre}</td>
                                    <td class="editable" data-field="horario" data-id="${actividad.id}">${actividad.horario}</td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary edit-btn" data-id="${actividad.id}">Editar</button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="${actividad.id}">Eliminar</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            ` : ''}
            
            <!-- Actividades Finalizadas -->
            ${finalizadas.length > 0 ? `
            <div class="section">
                <h3>Actividades Finalizadas (${finalizadas.length})</h3>
                <div class="table-container">
                    <table class="data-table" id="finalizadas-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Instalación</th>
                                <th>Horario</th>
                                <th>Fecha Fin</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${finalizadas.map(actividad => `
                                <tr data-id="${actividad.id}" data-instalacion-id="${actividad.instalacion_id}" data-centro-id="${actividad.centro_id}" data-estado="${actividad.estado}">
                                    <td class="editable" data-field="nombre" data-id="${actividad.id}">${actividad.nombre}</td>
                                    <td>${actividad.instalacion_nombre}</td>
                                    <td class="editable" data-field="horario" data-id="${actividad.id}">${actividad.horario}</td>
                                    <td>${actividad.fecha_fin}</td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary edit-btn" data-id="${actividad.id}">Editar</button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="${actividad.id}">Eliminar</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            ` : ''}
            
            ${actividades.length === 0 ? '<p>No hay actividades registradas.</p>' : ''}
        `);
        
        // Añadir evento al filtro de centro
        const centroFilter = document.getElementById('centro-filter');
        if (centroFilter) {
            centroFilter.addEventListener('change', function() {
                const centroId = this.value;
                const instalacionFilter = document.getElementById('instalacion-filter');
                const estadoFilter = document.getElementById('estado-filter');
                
                // Filtrar instalaciones por centro
                if (instalacionFilter) {
                    const instalacionOptions = instalacionFilter.querySelectorAll('option');
                    instalacionOptions.forEach(option => {
                        if (option.value === '') {
                            option.style.display = '';
                            return;
                        }
                        
                        const optionCentroId = option.getAttribute('data-centro-id');
                        if (centroId === '' || optionCentroId === centroId) {
                            option.style.display = '';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                    
                    // Resetear filtro de instalación
                    instalacionFilter.value = '';
                }
                
                // Filtrar todas las tablas por centro
                filterAllTables();
            });
        }
        
        // Añadir evento al filtro de instalación
        const instalacionFilter = document.getElementById('instalacion-filter');
        if (instalacionFilter) {
            instalacionFilter.addEventListener('change', function() {
                // Filtrar todas las tablas por instalación
                filterAllTables();
            });
        }
        
        // Añadir evento al filtro de estado
        const estadoFilter = document.getElementById('estado-filter');
        if (estadoFilter) {
            estadoFilter.addEventListener('change', function() {
                // Filtrar todas las tablas por estado
                filterAllTables();
            });
        }
        
        // Función para filtrar todas las tablas
        function filterAllTables() {
            const centroId = document.getElementById('centro-filter').value;
            const instalacionId = document.getElementById('instalacion-filter').value;
            const estado = document.getElementById('estado-filter').value;
            
            // Filtrar cada tabla
            ['programadas', 'activas', 'finalizadas'].forEach(tableName => {
                const table = document.getElementById(`${tableName}-table`);
                if (table) {
                    const rows = table.querySelectorAll('tbody tr');
                    let visibleCount = 0;
                    
                    rows.forEach(row => {
                        const rowCentroId = row.getAttribute('data-centro-id');
                        const rowInstalacionId = row.getAttribute('data-instalacion-id');
                        const rowEstado = row.getAttribute('data-estado');
                        
                        const centroMatch = centroId === '' || rowCentroId === centroId;
                        const instalacionMatch = instalacionId === '' || rowInstalacionId === instalacionId;
                        const estadoMatch = estado === '' || rowEstado === estado;
                        
                        if (centroMatch && instalacionMatch && estadoMatch) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Ocultar sección si no hay filas visibles
                    const section = table.closest('.section');
                    if (section) {
                        section.style.display = visibleCount > 0 ? '' : 'none';
                    }
                }
            });
        }
        
        // Añadir evento al botón de añadir
        const addActividadBtn = document.getElementById('add-actividad');
        if (addActividadBtn) {
            addActividadBtn.addEventListener('click', function() {
                showAddActividadModal(centros, instalaciones);
            });
        }
        
        // Añadir evento al botón de importación de CSV
        const importCsvBtn = document.getElementById('import-csv');
        if (importCsvBtn) {
            importCsvBtn.addEventListener('click', function() {
                showImportCsvModal();
            });
        }
        
        // Añadir eventos a botones de editar
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const actividad = actividades.find(a => a.id == id);
                if (actividad) {
                    showEditActividadModal(actividad, centros, instalaciones);
                }
            });
        });
        
        // Añadir eventos a botones de eliminar
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nombre = document.querySelector(`tr[data-id="${id}"] .editable[data-field="nombre"]`).textContent;
                
                Components.confirmModal(
                    'Eliminar Actividad',
                    `¿Estás seguro de que quieres eliminar la actividad "${nombre}"? Esta acción no se puede deshacer.`,
                    async () => {
                        try {
                            const response = await API.delete('/actividades.php', {
                                body: JSON.stringify({id: id})
                            });
                            
                            if (response.success) {
                                // Eliminar fila de la tabla
                                const row = document.querySelector(`tr[data-id="${id}"]`);
                                if (row) {
                                    const section = row.closest('.section');
                                    row.remove();
                                    
                                    // Ocultar sección si no quedan filas
                                    if (section && section.querySelectorAll('tbody tr').length === 0) {
                                        section.style.display = 'none';
                                    }
                                }
                                
                                showNotification('Actividad eliminada correctamente', 'success');
                            } else {
                                showNotification(response.error || 'Error al eliminar la actividad', 'error');
                            }
                        } catch (error) {
                            showNotification('Error de conexión al eliminar la actividad', 'error');
                        }
                    }
                );
            });
        });
        
        // Añadir eventos a celdas editables
        document.querySelectorAll('.editable').forEach(cell => {
            cell.addEventListener('click', function() {
                const field = this.getAttribute('data-field');
                const id = this.getAttribute('data-id');
                const currentValue = this.textContent;
                
                // Crear input para edición
                const input = document.createElement('input');
                input.type = 'text';
                input.value = currentValue;
                input.className = 'editable-input';
                
                // Reemplazar contenido con input
                this.innerHTML = '';
                this.appendChild(input);
                input.focus();
                
                // Guardar al perder el foco o presionar Enter
                const save = async () => {
                    const newValue = input.value;
                    this.innerHTML = newValue;
                    
                    // Llamar a la API para guardar
                    await saveActividadField(id, field, newValue);
                };
                
                input.addEventListener('blur', save);
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        save();
                    }
                });
            });
        });
    } catch (error) {
        loadContent(Components.error('Error de conexión al cargar las actividades'));
    }
});

Router.addRoute('estadisticas', function() {
    loadContent(`
        <h2>Estadísticas</h2>
        <p>Aquí se mostrarán las estadísticas del sistema.</p>
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Centros</h3>
                <div class="number">0</div>
            </div>
            <div class="stat-card">
                <h3>Total Instalaciones</h3>
                <div class="number">0</div>
            </div>
            <div class="stat-card">
                <h3>Total Actividades</h3>
                <div class="number">0</div>
            </div>
        </div>
    `);
});

// Función para mostrar modal de añadir actividad
function showAddActividadModal(centros, instalaciones) {
    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.innerHTML = `
        <div class="modal" style="width: 90%; max-width: 800px;">
            <div class="modal-header">
                <h3>Añadir Nueva Actividad</h3>
                <button class="close-btn" id="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="centro_id">Centro:</label>
                    <select id="centro_id" class="form-control" required>
                        <option value="">Selecciona un centro</option>
                        ${centros.map(centro => `
                            <option value="${centro.id}">${centro.nombre}</option>
                        `).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="instalacion_id">Instalación:</label>
                    <select id="instalacion_id" class="form-control" required>
                        <option value="">Selecciona una instalación</option>
                        ${instalaciones.map(instalacion => `
                            <option value="${instalacion.id}" data-centro-id="${instalacion.centro_id}">${instalacion.nombre}</option>
                        `).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="horario">Horario:</label>
                    <textarea id="horario" class="form-control" rows="3" placeholder="Ej: Lunes y Miércoles 10:00-11:30"></textarea>
                </div>
                <div class="form-group">
                    <label for="fecha_inicio">Fecha de Inicio:</label>
                    <input type="date" id="fecha_inicio" class="form-control">
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha de Fin:</label>
                    <input type="date" id="fecha_fin" class="form-control">
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="activo" checked> Activa
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-btn">Cancelar</button>
                <button class="btn btn-primary" id="save-btn">Guardar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Filtrar instalaciones por centro
    const centroSelect = document.getElementById('centro_id');
    const instalacionSelect = document.getElementById('instalacion_id');
    
    if (centroSelect && instalacionSelect) {
        centroSelect.addEventListener('change', function() {
            const centroId = this.value;
            const instalacionOptions = instalacionSelect.querySelectorAll('option');
            
            instalacionOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                
                const optionCentroId = option.getAttribute('data-centro-id');
                if (centroId === '' || optionCentroId === centroId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Resetear selección de instalación
            instalacionSelect.value = '';
        });
    }
    
    // Eventos
    document.getElementById('close-modal').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('cancel-btn').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('save-btn').addEventListener('click', async () => {
        const nombre = document.getElementById('nombre').value.trim();
        const centroId = document.getElementById('centro_id').value;
        const instalacionId = document.getElementById('instalacion_id').value;
        const horario = document.getElementById('horario').value.trim();
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        const descripcion = document.getElementById('descripcion').value.trim();
        const activo = document.getElementById('activo').checked;
        
        if (!nombre) {
            showNotification('El nombre es obligatorio', 'error');
            return;
        }
        
        if (!centroId) {
            showNotification('Debes seleccionar un centro', 'error');
            return;
        }
        
        if (!instalacionId) {
            showNotification('Debes seleccionar una instalación', 'error');
            return;
        }
        
        try {
            const response = await API.post('/actividades.php', {
                nombre: nombre,
                centro_id: centroId,
                instalacion_id: instalacionId,
                horario: horario,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
                descripcion: descripcion,
                activo: activo
            });
            
            if (response.success) {
                // Cerrar modal
                document.body.removeChild(modal);
                
                // Mostrar notificación
                showNotification('Actividad creada correctamente', 'success');
                
                // Recargar la vista de actividades
                Router.navigate('actividades');
            } else {
                showNotification(response.error || 'Error al crear la actividad', 'error');
            }
        } catch (error) {
            showNotification('Error de conexión al crear la actividad', 'error');
        }
    });
}

// Función para mostrar modal de importación de CSV
function showImportCsvModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.innerHTML = `
        <div class="modal" style="width: 90%; max-width: 800px;">
            <div class="modal-header">
                <h3>Importar Actividades desde CSV</h3>
                <button class="close-btn" id="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="csv-file">Selecciona un archivo CSV:</label>
                    <div class="drop-zone" id="drop-zone">
                        <p>Arrastra y suelta un archivo CSV aquí o haz clic para seleccionarlo</p>
                        <input type="file" id="csv-file" accept=".csv" style="display: none;">
                    </div>
                </div>
                <div id="preview-container" style="display: none;">
                    <h4>Vista previa:</h4>
                    <div class="table-container">
                        <table class="data-table" id="csv-preview">
                            <thead>
                                <tr id="preview-header"></tr>
                            </thead>
                            <tbody id="preview-body"></tbody>
                        </table>
                    </div>
                </div>
                <div id="import-status" style="margin-top: 15px;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-btn">Cancelar</button>
                <button class="btn btn-primary" id="import-btn" disabled>Importar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('csv-file');
    const previewContainer = document.getElementById('preview-container');
    const previewHeader = document.getElementById('preview-header');
    const previewBody = document.getElementById('preview-body');
    const importBtn = document.getElementById('import-btn');
    const importStatus = document.getElementById('import-status');
    
    let csvData = [];
    
    // Eventos para el drop zone
    dropZone.addEventListener('click', () => {
        fileInput.click();
    });
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        
        if (e.dataTransfer.files.length) {
            handleFile(e.dataTransfer.files[0]);
        }
    });
    
    // Evento para seleccionar archivo
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            handleFile(e.target.files[0]);
        }
    });
    
    // Función para manejar el archivo
    function handleFile(file) {
        if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
            showNotification('Por favor, selecciona un archivo CSV válido', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const csv = e.target.result;
                csvData = parseCSV(csv);
                
                if (csvData.length > 0) {
                    showPreview(csvData);
                    importBtn.disabled = false;
                } else {
                    showNotification('El archivo CSV está vacío o no es válido', 'error');
                }
            } catch (error) {
                showNotification('Error al procesar el archivo CSV: ' + error.message, 'error');
            }
        };
        reader.readAsText(file);
    }
    
    // Función para parsear CSV
    function parseCSV(csv) {
        const lines = csv.split('\n');
        const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
        const data = [];
        
        for (let i = 1; i < lines.length; i++) {
            if (lines[i].trim() === '') continue;
            
            const values = lines[i].split(',').map(v => v.trim().replace(/"/g, ''));
            if (values.length !== headers.length) continue;
            
            const row = {};
            headers.forEach((header, index) => {
                row[header] = values[index];
            });
            data.push(row);
        }
        
        return data;
    }
    
    // Función para mostrar vista previa
    function showPreview(data) {
        previewContainer.style.display = 'block';
        
        // Limpiar contenido previo
        previewHeader.innerHTML = '';
        previewBody.innerHTML = '';
        
        // Mostrar encabezados
        const headers = Object.keys(data[0]);
        headers.forEach(header => {
            const th = document.createElement('th');
            th.textContent = header;
            previewHeader.appendChild(th);
        });
        
        // Mostrar datos (máximo 10 filas)
        const rowsToShow = Math.min(data.length, 10);
        for (let i = 0; i < rowsToShow; i++) {
            const tr = document.createElement('tr');
            headers.forEach(header => {
                const td = document.createElement('td');
                td.textContent = data[i][header] || '';
                tr.appendChild(td);
            });
            previewBody.appendChild(tr);
        }
        
        if (data.length > 10) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = headers.length;
            td.textContent = `... y ${data.length - 10} filas más`;
            td.style.textAlign = 'center';
            td.style.fontStyle = 'italic';
            tr.appendChild(td);
            previewBody.appendChild(tr);
        }
    }
    
    // Eventos
    document.getElementById('close-modal').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('cancel-btn').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    importBtn.addEventListener('click', async () => {
        if (csvData.length === 0) return;
        
        importBtn.disabled = true;
        importBtn.textContent = 'Importando...';
        
        try {
            // Enviar datos al servidor
            const response = await API.post('/actividades.php', {
                action: 'import_csv',
                data: csvData
            });
            
            if (response.success) {
                // Cerrar modal
                document.body.removeChild(modal);
                
                // Mostrar notificación
                showNotification(`Se importaron ${response.imported} actividades correctamente`, 'success');
                
                // Recargar la vista de actividades
                Router.navigate('actividades');
            } else {
                showNotification(response.error || 'Error al importar las actividades', 'error');
                importBtn.disabled = false;
                importBtn.textContent = 'Importar';
            }
        } catch (error) {
            showNotification('Error de conexión al importar las actividades', 'error');
            importBtn.disabled = false;
            importBtn.textContent = 'Importar';
        }
    });
}

// Función para mostrar modal de editar actividad
function showEditActividadModal(actividad, centros, instalaciones) {
    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.innerHTML = `
        <div class="modal" style="width: 90%; max-width: 800px;">
            <div class="modal-header">
                <h3>Editar Actividad</h3>
                <button class="close-btn" id="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" class="form-control" value="${actividad.nombre}" required>
                </div>
                <div class="form-group">
                    <label for="centro_id">Centro:</label>
                    <select id="centro_id" class="form-control" required>
                        <option value="">Selecciona un centro</option>
                        ${centros.map(centro => `
                            <option value="${centro.id}" ${centro.id == actividad.centro_id ? 'selected' : ''}>${centro.nombre}</option>
                        `).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="instalacion_id">Instalación:</label>
                    <select id="instalacion_id" class="form-control" required>
                        <option value="">Selecciona una instalación</option>
                        ${instalaciones.map(instalacion => `
                            <option value="${instalacion.id}" data-centro-id="${instalacion.centro_id}" ${instalacion.id == actividad.instalacion_id ? 'selected' : ''}>${instalacion.nombre}</option>
                        `).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="horario">Horario:</label>
                    <textarea id="horario" class="form-control" rows="3">${actividad.horario}</textarea>
                </div>
                <div class="form-group">
                    <label for="fecha_inicio">Fecha de Inicio:</label>
                    <input type="date" id="fecha_inicio" class="form-control" value="${actividad.fecha_inicio || ''}">
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha de Fin:</label>
                    <input type="date" id="fecha_fin" class="form-control" value="${actividad.fecha_fin || ''}">
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" class="form-control" rows="3">${actividad.descripcion || ''}</textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="activo" ${actividad.activo ? 'checked' : ''}> Activa
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-btn">Cancelar</button>
                <button class="btn btn-primary" id="save-btn">Guardar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Filtrar instalaciones por centro
    const centroSelect = document.getElementById('centro_id');
    const instalacionSelect = document.getElementById('instalacion_id');
    
    if (centroSelect && instalacionSelect) {
        centroSelect.addEventListener('change', function() {
            const centroId = this.value;
            const instalacionOptions = instalacionSelect.querySelectorAll('option');
            
            instalacionOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                
                const optionCentroId = option.getAttribute('data-centro-id');
                if (centroId === '' || optionCentroId === centroId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Resetear selección de instalación
            instalacionSelect.value = '';
        });
        
        // Inicializar filtro de instalaciones
        centroSelect.dispatchEvent(new Event('change'));
    }
    
    // Eventos
    document.getElementById('close-modal').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('cancel-btn').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('save-btn').addEventListener('click', async () => {
        const nombre = document.getElementById('nombre').value.trim();
        const centroId = document.getElementById('centro_id').value;
        const instalacionId = document.getElementById('instalacion_id').value;
        const horario = document.getElementById('horario').value.trim();
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        const descripcion = document.getElementById('descripcion').value.trim();
        const activo = document.getElementById('activo').checked;
        
        if (!nombre) {
            showNotification('El nombre es obligatorio', 'error');
            return;
        }
        
        if (!centroId) {
            showNotification('Debes seleccionar un centro', 'error');
            return;
        }
        
        if (!instalacionId) {
            showNotification('Debes seleccionar una instalación', 'error');
            return;
        }
        
        try {
            const response = await API.put('/actividades.php', {
                id: actividad.id,
                nombre: nombre,
                centro_id: centroId,
                instalacion_id: instalacionId,
                horario: horario,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
                descripcion: descripcion,
                activo: activo
            });
            
            if (response.success) {
                // Cerrar modal
                document.body.removeChild(modal);
                
                // Mostrar notificación
                showNotification('Actividad actualizada correctamente', 'success');
                
                // Recargar la vista de actividades
                Router.navigate('actividades');
            } else {
                showNotification(response.error || 'Error al actualizar la actividad', 'error');
            }
        } catch (error) {
            showNotification('Error de conexión al actualizar la actividad', 'error');
        }
    });
}

// Función para guardar cambios en campos de actividades
async function saveActividadField(id, field, value) {
    try {
        const response = await API.put('/actividades.php', {
            id: id,
            [field]: value
        });
        
        if (response.success) {
            showNotification('Campo actualizado correctamente', 'success');
        } else {
            showNotification(response.error || 'Error al actualizar el campo', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al actualizar el campo', 'error');
    }
}

// Función para guardar cambios en campos de instalaciones
async function saveInstalacionField(id, field, value) {
    try {
        const response = await API.put('/instalaciones.php', {
            id: id,
            [field]: value
        });
        
        if (response.success) {
            showNotification('Campo actualizado correctamente', 'success');
        } else {
            showNotification(response.error || 'Error al actualizar el campo', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al actualizar el campo', 'error');
    }
}

// Función para implementar drag & drop
function implementDragAndDrop() {
    const draggableElements = document.querySelectorAll('.draggable');
    let draggedItem = null;
    
    draggableElements.forEach(item => {
        const dragHandle = item.querySelector('.drag-handle');
        
        if (dragHandle) {
            dragHandle.addEventListener('mousedown', (e) => {
                draggedItem = item;
                setTimeout(() => {
                    item.classList.add('dragging');
                }, 0);
            });
            
            dragHandle.addEventListener('mouseup', (e) => {
                if (draggedItem) {
                    draggedItem.classList.remove('dragging');
                    draggedItem = null;
                }
            });
        }
        
        item.addEventListener('dragstart', (e) => {
            draggedItem = item;
            setTimeout(() => {
                item.classList.add('dragging');
            }, 0);
        });
        
        item.addEventListener('dragend', (e) => {
            if (draggedItem) {
                draggedItem.classList.remove('dragging');
                draggedItem = null;
            }
        });
    });
    
    const tableBody = document.querySelector('#instalaciones-table tbody');
    
    if (tableBody) {
        tableBody.addEventListener('dragover', (e) => {
            e.preventDefault();
            const afterElement = getDragAfterElement(tableBody, e.clientY);
            const draggable = document.querySelector('.dragging');
            
            if (afterElement == null) {
                tableBody.appendChild(draggable);
            } else {
                tableBody.insertBefore(draggable, afterElement);
            }
        });
        
        tableBody.addEventListener('drop', (e) => {
            e.preventDefault();
            const draggable = document.querySelector('.dragging');
            if (draggable) {
                draggable.classList.remove('dragging');
                
                // Actualizar el orden en la base de datos
                updateInstallationOrder();
            }
        });
    }
}

// Función para obtener el elemento después del cual se debe insertar el elemento arrastrado
function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.draggable:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// Función para actualizar el orden de las instalaciones en la base de datos
async function updateInstallationOrder() {
    const rows = document.querySelectorAll('#instalaciones-table tbody tr');
    const order = [];
    
    rows.forEach((row, index) => {
        const id = row.getAttribute('data-id');
        order.push({ id: id, orden: index });
    });
    
    try {
        const response = await API.put('/instalaciones.php', {
            action: 'reorder',
            order: order
        });
        
        if (response.success) {
            showNotification('Orden actualizado correctamente', 'success');
        } else {
            showNotification(response.error || 'Error al actualizar el orden', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al actualizar el orden', 'error');
    }
}

// Función para mostrar modal de añadir instalación
function showAddInstalacionModal(centros) {
    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3>Añadir Nueva Instalación</h3>
                <button class="close-btn" id="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="centro_id">Centro:</label>
                    <select id="centro_id" class="form-control" required>
                        <option value="">Selecciona un centro</option>
                        ${centros.map(centro => `
                            <option value="${centro.id}">${centro.nombre}</option>
                        `).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-btn">Cancelar</button>
                <button class="btn btn-primary" id="save-btn">Guardar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Eventos
    document.getElementById('close-modal').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('cancel-btn').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('save-btn').addEventListener('click', async () => {
        const nombre = document.getElementById('nombre').value.trim();
        const centroId = document.getElementById('centro_id').value;
        const descripcion = document.getElementById('descripcion').value.trim();
        
        if (!nombre) {
            showNotification('El nombre es obligatorio', 'error');
            return;
        }
        
        if (!centroId) {
            showNotification('Debes seleccionar un centro', 'error');
            return;
        }
        
        try {
            const response = await API.post('/instalaciones.php', {
                nombre: nombre,
                centro_id: centroId,
                descripcion: descripcion
            });
            
            if (response.success) {
                // Cerrar modal
                document.body.removeChild(modal);
                
                // Mostrar notificación
                showNotification('Instalación creada correctamente', 'success');
                
                // Recargar la vista de instalaciones
                Router.navigate('instalaciones');
            } else {
                showNotification(response.error || 'Error al crear la instalación', 'error');
            }
        } catch (error) {
            showNotification('Error de conexión al crear la instalación', 'error');
        }
    });
}

// Función para guardar cambios en campos de centros
async function saveCentroField(id, field, value) {
    try {
        const response = await API.put('/centros.php', {
            id: id,
            [field]: value
        });
        
        if (response.success) {
            showNotification('Campo actualizado correctamente', 'success');
        } else {
            showNotification(response.error || 'Error al actualizar el campo', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al actualizar el campo', 'error');
    }
}

// Función para mostrar modal de añadir centro
function showAddCentroModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3>Añadir Nuevo Centro</h3>
                <button class="close-btn" id="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-btn">Cancelar</button>
                <button class="btn btn-primary" id="save-btn">Guardar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Eventos
    document.getElementById('close-modal').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('cancel-btn').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('save-btn').addEventListener('click', async () => {
        const nombre = document.getElementById('nombre').value.trim();
        const descripcion = document.getElementById('descripcion').value.trim();
        
        if (!nombre) {
            showNotification('El nombre es obligatorio', 'error');
            return;
        }
        
        try {
            const response = await API.post('/centros.php', {
                nombre: nombre,
                descripcion: descripcion
            });
            
            if (response.success) {
                // Cerrar modal
                document.body.removeChild(modal);
                
                // Mostrar notificación
                showNotification('Centro creado correctamente', 'success');
                
                // Recargar la vista de centros
                Router.navigate('centros');
            } else {
                showNotification(response.error || 'Error al crear el centro', 'error');
            }
        } catch (error) {
            showNotification('Error de conexión al crear el centro', 'error');
        }
    });
}

// Función para generar gráficos dinámicos
function generateChart(estados) {
    const ctx = document.getElementById('actividadesChart');
    if (!ctx) return;
    
    // Destruir gráfico existente si hay uno
    if (window.actividadesChart) {
        window.actividadesChart.destroy();
    }
    
    // Preparar datos
    const labels = estados && estados.length > 0 ? 
        estados.map(estado => estado.estado.charAt(0).toUpperCase() + estado.estado.slice(1) + 's') : 
        ['Sin datos'];
    const data = estados && estados.length > 0 ? 
        estados.map(estado => estado.count) : 
        [0];
    
    // Crear gráfico
    window.actividadesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Número de Actividades',
                data: data,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Función para iniciar actualización en tiempo real de estadísticas
function startRealtimeStats() {
    // Actualizar estadísticas cada 30 segundos
    setInterval(async () => {
        try {
            const response = await API.get('/estadisticas.php?realtime=true');
            
            if (response.success && response.data.stats.realtime) {
                const realtimeStats = response.data.stats.realtime;
                
                // Actualizar contador de actividades nuevas
                const newActivitiesCount = document.getElementById('new-activities-count');
                if (newActivitiesCount) {
                    newActivitiesCount.textContent = realtimeStats.new_activities || 0;
                }
                
                // Actualizar contador de actividades completadas
                const completedActivitiesCount = document.getElementById('completed-activities-count');
                if (completedActivitiesCount) {
                    completedActivitiesCount.textContent = realtimeStats.completed_activities || 0;
                }
            }
        } catch (error) {
            console.error('Error al obtener estadísticas en tiempo real:', error);
        }
    }, 30000); // 30 segundos
}

// Función para generar y descargar informe en CSV
function generateAndDownloadReport(data, centroId) {
    // Crear contenido CSV
    let csvContent = 'Informe de Estadísticas\n';
    csvContent += '=====================\n\n';
    
    // Información del filtro
    if (centroId) {
        csvContent += `Filtro: Centro ID ${centroId}\n\n`;
    } else {
        csvContent += 'Filtro: Todos los centros\n\n';
    }
    
    // Estadísticas generales
    csvContent += 'Estadísticas Generales:\n';
    csvContent += `Centros Asignados,${data.stats.centros}\n`;
    csvContent += `Instalaciones,${data.stats.instalaciones}\n`;
    csvContent += `Actividades,${data.stats.actividades}\n\n`;
    
    // Distribución de actividades por estado
    csvContent += 'Distribución de Actividades por Estado:\n';
    csvContent += 'Estado,Cantidad\n';
    
    if (data.stats.estados && data.stats.estados.length > 0) {
        data.stats.estados.forEach(estado => {
            csvContent += `${estado.estado},${estado.count}\n`;
        });
    } else {
        csvContent += 'No hay actividades registradas,0\n';
    }
    
    csvContent += '\n';
    
    // Estadísticas en tiempo real (si están disponibles)
    if (data.stats.realtime) {
        csvContent += 'Estadísticas en Tiempo Real (última hora):\n';
        csvContent += `Actividades Nuevas,${data.stats.realtime.new_activities || 0}\n`;
        csvContent += `Actividades Completadas,${data.stats.realtime.completed_activities || 0}\n\n`;
    }
    
    // Generar y descargar archivo
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
    
    link.setAttribute('href', url);
    link.setAttribute('download', `informe-estadisticas-${timestamp}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Función para mostrar modal de añadir administrador
function showAddAdminModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3>Añadir Nuevo Administrador</h3>
                <button class="close-btn" id="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="role">Rol:</label>
                    <select id="role" class="form-control">
                        <option value="admin">Administrador</option>
                        <option value="superadmin">Superadministrador</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-btn">Cancelar</button>
                <button class="btn btn-primary" id="save-btn">Guardar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Eventos
    document.getElementById('close-modal').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('cancel-btn').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    document.getElementById('save-btn').addEventListener('click', async () => {
        const nombre = document.getElementById('nombre').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const role = document.getElementById('role').value;
        
        if (!nombre || !email || !password) {
            showNotification('Todos los campos son obligatorios', 'error');
            return;
        }
        
        try {
            const response = await API.post('/admins.php', {
                nombre: nombre,
                email: email,
                password: password,
                role: role
            });
            
            if (response.success) {
                // Cerrar modal
                document.body.removeChild(modal);
                
                // Mostrar notificación
                showNotification('Administrador creado correctamente', 'success');
                
                // Recargar la vista de administradores
                Router.navigate('admins');
            } else {
                showNotification(response.error || 'Error al crear el administrador', 'error');
            }
        } catch (error) {
            showNotification('Error de conexión al crear el administrador', 'error');
        }
    });
}

// Función para mostrar modal de editar administrador
async function showEditAdminModal(adminId) {
    try {
        // Obtener datos del administrador
        const response = await API.get(`/admin.php?id=${adminId}`);
        
        if (response.success) {
            const admin = response.data;
            
            const modal = document.createElement('div');
            modal.className = 'modal-backdrop';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3>Editar Administrador</h3>
                        <button class="close-btn" id="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="nombre">Nombre:</label>
                            <input type="text" id="nombre" class="form-control" value="${admin.nombre}" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" class="form-control" value="${admin.email}" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Rol:</label>
                            <select id="role" class="form-control">
                                <option value="admin" ${admin.role === 'admin' ? 'selected' : ''}>Administrador</option>
                                <option value="superadmin" ${admin.role === 'superadmin' ? 'selected' : ''}>Superadministrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" id="cancel-btn">Cancelar</button>
                        <button class="btn btn-primary" id="save-btn">Guardar</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Eventos
            document.getElementById('close-modal').addEventListener('click', () => {
                document.body.removeChild(modal);
            });
            
            document.getElementById('cancel-btn').addEventListener('click', () => {
                document.body.removeChild(modal);
            });
            
            document.getElementById('save-btn').addEventListener('click', async () => {
                const nombre = document.getElementById('nombre').value.trim();
                const email = document.getElementById('email').value.trim();
                const role = document.getElementById('role').value;
                
                if (!nombre || !email) {
                    showNotification('Nombre y email son obligatorios', 'error');
                    return;
                }
                
                try {
                    const updateResponse = await API.put('/admins.php', {
                        id: adminId,
                        nombre: nombre,
                        email: email,
                        role: role
                    });
                    
                    if (updateResponse.success) {
                        // Cerrar modal
                        document.body.removeChild(modal);
                        
                        // Mostrar notificación
                        showNotification('Administrador actualizado correctamente', 'success');
                        
                        // Recargar la vista de administradores
                        Router.navigate('admins');
                    } else {
                        showNotification(updateResponse.error || 'Error al actualizar el administrador', 'error');
                    }
                } catch (error) {
                    showNotification('Error de conexión al actualizar el administrador', 'error');
                }
            });
        } else {
            showNotification('Error al obtener datos del administrador', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al obtener datos del administrador', 'error');
    }
}

// Función para eliminar administrador
async function deleteAdmin(adminId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este administrador?')) {
        return;
    }
    
    try {
        const response = await API.delete('/admins.php', {
            id: adminId
        });
        
        if (response.success) {
            showNotification('Administrador eliminado correctamente', 'success');
            
            // Recargar la vista de administradores
            Router.navigate('admins');
        } else {
            showNotification(response.error || 'Error al eliminar el administrador', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al eliminar el administrador', 'error');
    }
}

// Función para inicializar funcionalidad de drag & drop
function initDragAndDrop() {
    let draggedItem = null;
    
    // Configurar elementos arrastrables
    const draggableItems = document.querySelectorAll('.draggable-item');
    
    draggableItems.forEach(item => {
        // Hacer el elemento arrastrable
        item.setAttribute('draggable', true);
        
        // Evento cuando comienza a arrastrar
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            setTimeout(() => {
                this.classList.add('dragging');
            }, 0);
        });
        
        // Evento cuando termina de arrastrar
        item.addEventListener('dragend', function() {
            setTimeout(() => {
                this.classList.remove('dragging');
                draggedItem = null;
            }, 0);
        });
    });
    
    // Configurar zonas donde se pueden soltar los elementos
    const dropZones = document.querySelectorAll('.draggable-list, .asignaciones-container');
    
    dropZones.forEach(zone => {
        // Evento cuando un elemento arrastrado está sobre la zona
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        // Evento cuando un elemento arrastrado entra en la zona
        zone.addEventListener('dragenter', function(e) {
            e.preventDefault();
        });
        
        // Evento cuando un elemento arrastrado sale de la zona
        zone.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });
        
        // Evento cuando se suelta un elemento en la zona
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            // Solo permitir soltar en la zona de asignaciones
            if (this.classList.contains('asignaciones-container') && draggedItem) {
                const adminId = draggedItem.dataset.type === 'admin' ? draggedItem.dataset.id : null;
                const centroId = draggedItem.dataset.type === 'centro' ? draggedItem.dataset.id : null;
                
                // Si se están arrastrando un admin y un centro, crear asignación
                if (adminId && centroId) {
                    createAsignacion(adminId, centroId);
                }
            }
        });
    });
}

// Función para cargar asignaciones actuales
async function loadCurrentAsignaciones() {
    try {
        const response = await API.get('/asignaciones.php');
        
        if (response.success) {
            const asignaciones = response.data;
            const container = document.getElementById('asignaciones-container');
            
            if (container) {
                if (asignaciones.length > 0) {
                    container.innerHTML = `
                        <ul class="asignaciones-list">
                            ${asignaciones.map(asignacion => `
                                <li class="asignacion-item">
                                    <span class="admin-name">${asignacion.admin_nombre}</span>
                                    <span class="arrow">→</span>
                                    <span class="centro-name">${asignacion.centro_nombre}</span>
                                    <button class="btn btn-small btn-danger remove-asignacion" 
                                            data-admin-id="${asignacion.admin_id}" 
                                            data-centro-id="${asignacion.centro_id}">
                                        Eliminar
                                    </button>
                                </li>
                            `).join('')}
                        </ul>
                    `;
                    
                    // Añadir eventos a botones de eliminar asignación
                    document.querySelectorAll('.remove-asignacion').forEach(button => {
                        button.addEventListener('click', function() {
                            const adminId = this.getAttribute('data-admin-id');
                            const centroId = this.getAttribute('data-centro-id');
                            removeAsignacion(adminId, centroId);
                        });
                    });
                } else {
                    container.innerHTML = '<p class="no-asignaciones">No hay asignaciones actualmente.</p>';
                }
            }
        }
    } catch (error) {
        console.error('Error al cargar asignaciones:', error);
    }
}

// Función para crear una nueva asignación
async function createAsignacion(adminId, centroId) {
    try {
        const response = await API.post('/asignaciones.php', {
            admin_id: adminId,
            centro_id: centroId
        });
        
        if (response.success) {
            showNotification('Asignación creada correctamente', 'success');
            loadCurrentAsignaciones();
        } else {
            showNotification(response.error || 'Error al crear la asignación', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al crear la asignación', 'error');
    }
}

// Función para eliminar una asignación
async function removeAsignacion(adminId, centroId) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta asignación?')) {
        return;
    }
    
    try {
        const response = await API.delete('/asignaciones.php', {
            admin_id: adminId,
            centro_id: centroId
        });
        
        if (response.success) {
            showNotification('Asignación eliminada correctamente', 'success');
            loadCurrentAsignaciones();
        } else {
            showNotification(response.error || 'Error al eliminar la asignación', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al eliminar la asignación', 'error');
    }
}

// Función para iniciar la actualización en tiempo real de logs
let logsInterval;
let logsPaused = false;

function startRealtimeLogs() {
    // Limpiar intervalo existente si hay uno
    if (logsInterval) {
        clearInterval(logsInterval);
    }
    
    // Obtener elementos del DOM
    const logsContent = document.getElementById('logs-content');
    const clearLogsBtn = document.getElementById('clear-logs');
    const pauseLogsBtn = document.getElementById('pause-logs');
    
    // Añadir evento al botón de limpiar logs
    if (clearLogsBtn) {
        clearLogsBtn.addEventListener('click', function() {
            if (logsContent) {
                logsContent.innerHTML = '<p class="no-logs">No hay logs disponibles.</p>';
            }
        });
    }
    
    // Añadir evento al botón de pausar logs
    if (pauseLogsBtn) {
        pauseLogsBtn.addEventListener('click', function() {
            logsPaused = !logsPaused;
            this.textContent = logsPaused ? 'Reanudar' : 'Pausar';
        });
    }
    
    // Iniciar actualización periódica de logs
    logsInterval = setInterval(async function() {
        if (logsPaused) return;
        
        try {
            // Obtener logs del endpoint de API
            const response = await API.get('/logs.php');
            
            if (response.success && response.data && logsContent) {
                // Remover mensaje de "cargando" si es la primera vez
                if (logsContent.querySelector('.no-logs')) {
                    logsContent.innerHTML = '';
                }
                
                // Añadir nuevos logs
                response.data.forEach(log => {
                    // Verificar si el log ya existe para evitar duplicados
                    if (!document.querySelector(`.log-entry[data-log-id="${log.id}"]`)) {
                        const logElement = document.createElement('div');
                        logElement.className = `log-entry log-${log.type}`;
                        logElement.setAttribute('data-log-id', log.id);
                        logElement.innerHTML = `
                            <span class="log-timestamp">[${log.timestamp}]</span>
                            <span class="log-message">${log.message}</span>
                            <span class="log-user">por ${log.user_name}</span>
                        `;
                        
                        logsContent.insertBefore(logElement, logsContent.firstChild);
                    }
                });
                
                // Limitar a 100 logs para evitar problemas de rendimiento
                while (logsContent.children.length > 100) {
                    logsContent.removeChild(logsContent.lastChild);
                }
            }
        } catch (error) {
            console.error('Error al cargar logs:', error);
        }
    }, 5000); // Actualizar cada 5 segundos
}
