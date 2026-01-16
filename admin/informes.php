<?php
/**
 * Generador de Informes - Panel Admin
 * Soporta selección múltiple de actividades (hasta 10)
 */

require_once '../config/config.php';
require_once 'auth_middleware.php';

$admin_info = getAdminInfo();

// Obtener centros según permisos
if (isSuperAdmin()) {
    $stmt = $pdo->query("SELECT id, nombre FROM centros ORDER BY nombre");
} else {
    $stmt = $pdo->prepare("
        SELECT c.id, c.nombre
        FROM centros c
        INNER JOIN admin_asignaciones aa ON aa.centro_id = c.id
        WHERE aa.admin_id = ?
        ORDER BY c.nombre
    ");
    $stmt->execute([$_SESSION['admin_id']]);
}
$centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Informes - Admin Puri</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=GeistSans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .informes-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .step-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid var(--admin-border);
        }
        
        .step-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--admin-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .step-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .step-disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .step-disabled .step-number {
            background: #9ca3af;
        }
        
        /* Actividades grid */
        .actividades-search {
            margin-bottom: 16px;
        }
        
        .actividades-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 6px 14px;
            border: 1px solid var(--admin-border);
            border-radius: 20px;
            background: white;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-btn:hover {
            border-color: var(--admin-primary);
        }
        
        .filter-btn.active {
            background: var(--admin-primary);
            color: white;
            border-color: var(--admin-primary);
        }
        
        .actividades-grid {
            display: grid;
            gap: 12px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 8px;
        }
        
        .actividad-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border: 2px solid var(--admin-border);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }
        
        .actividad-card:hover {
            border-color: var(--admin-primary);
            background: #f8fafc;
        }
        
        .actividad-card.selected {
            border-color: var(--admin-primary);
            background: #eff6ff;
        }
        
        .actividad-card.finalizada {
            background: #fafafa;
        }
        
        .actividad-card.finalizada .actividad-nombre {
            color: #6b7280;
        }
        
        .actividad-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .actividad-card.selected .actividad-checkbox {
            background: var(--admin-primary);
            border-color: var(--admin-primary);
        }
        
        .actividad-checkbox svg {
            display: none;
            width: 14px;
            height: 14px;
            color: white;
        }
        
        .actividad-card.selected .actividad-checkbox svg {
            display: block;
        }
        
        .actividad-info {
            flex: 1;
            min-width: 0;
        }
        
        .actividad-nombre {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 4px;
        }
        
        .actividad-meta {
            font-size: 13px;
            color: var(--text-muted);
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .actividad-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .actividad-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .badge-activa {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-finalizada {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .actividad-ubicacion {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 4px;
        }
        
        .seleccion-counter {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
            margin-top: 16px;
        }
        
        .seleccion-text {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .seleccion-text strong {
            color: var(--text-color);
        }
        
        .btn-limpiar {
            font-size: 13px;
            color: var(--admin-primary);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 8px;
        }
        
        .btn-limpiar:hover {
            text-decoration: underline;
        }
        
        /* Fechas */
        .fechas-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        @media (max-width: 600px) {
            .fechas-row {
                grid-template-columns: 1fr;
            }
        }
        
        /* Empty state */
        .empty-actividades {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }
        
        .empty-actividades svg {
            width: 48px;
            height: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="logo-section">
            <a href="dashboard.php" class="back-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="logo">P</div>
            <div class="title">Generador de Informes</div>
        </div>
        <div class="actions">
            <div class="dropdown">
                <button class="btn btn-secondary" id="profile-dropdown-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?php echo htmlspecialchars($admin_info['username']); ?>
                </button>
                <div class="dropdown-content" id="profile-dropdown">
                    <a href="account.php" class="dropdown-item">Mi Cuenta</a>
                    <a href="logout.php" class="dropdown-item">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="informes-container">
            <!-- Paso 1: Seleccionar Centro -->
            <div class="step-section" id="step-centro">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <h2 class="step-title">Selecciona un centro</h2>
                </div>
                <div class="form-group">
                    <select id="centro-select" class="form-input">
                        <option value="">-- Selecciona un centro --</option>
                        <?php foreach ($centros as $centro): ?>
                            <option value="<?php echo $centro['id']; ?>"><?php echo htmlspecialchars($centro['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Paso 2: Seleccionar Instalación -->
            <div class="step-section step-disabled" id="step-instalacion">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <h2 class="step-title">Selecciona una instalación</h2>
                </div>
                <div class="form-group">
                    <select id="instalacion-select" class="form-input">
                        <option value="">-- Selecciona una instalación --</option>
                    </select>
                </div>
            </div>

            <!-- Paso 3: Seleccionar Actividades -->
            <div class="step-section step-disabled" id="step-actividades">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <h2 class="step-title">Selecciona actividades <span style="font-weight: 400; font-size: 14px; color: var(--text-muted);">(máximo 10)</span></h2>
                </div>
                
                <div class="actividades-search">
                    <input type="text" id="buscar-actividades" class="form-input" placeholder="Buscar actividades...">
                </div>
                
                <div class="actividades-filters">
                    <button class="filter-btn active" data-filter="todas">Todas</button>
                    <button class="filter-btn" data-filter="activas">Activas</button>
                    <button class="filter-btn" data-filter="finalizadas">Finalizadas</button>
                </div>
                
                <div class="actividades-grid" id="actividades-grid">
                    <div class="empty-actividades">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p>Selecciona una instalación para ver sus actividades</p>
                    </div>
                </div>
                
                <div class="seleccion-counter" id="seleccion-counter" style="display: none;">
                    <span class="seleccion-text"><strong id="count-selected">0</strong> de 10 actividades seleccionadas</span>
                    <button class="btn-limpiar" onclick="limpiarSeleccion()">Limpiar selección</button>
                </div>
            </div>

            <!-- Paso 4: Rango de fechas -->
            <div class="step-section step-disabled" id="step-fechas">
                <div class="step-header">
                    <div class="step-number">4</div>
                    <h2 class="step-title">Selecciona el rango de fechas</h2>
                </div>
                
                <div class="fechas-row">
                    <div class="form-group">
                        <label class="form-label">Fecha inicio</label>
                        <input type="date" id="fecha-inicio" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha fin</label>
                        <input type="date" id="fecha-fin" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Botón generar -->
            <div class="step-section" id="step-generar" style="text-align: center;">
                <button class="btn btn-primary btn-lg" id="btn-generar" disabled onclick="generarInformes()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Generar Informes
                </button>
                <p style="margin-top: 12px; font-size: 13px; color: var(--text-muted);">
                    Se descargará un archivo Excel por cada actividad seleccionada
                </p>
            </div>
        </div>
    </main>

    <!-- Notificaciones -->
    <div id="notification" class="notification"></div>

    <script>
    // Estado global
    const State = {
        centroId: null,
        instalacionId: null,
        instalaciones: [],
        actividades: [],
        selectedActividades: new Set(),
        filtroActual: 'todas',
        busqueda: ''
    };

    // ====== Inicialización ======
    document.addEventListener('DOMContentLoaded', () => {
        setupEventListeners();
        setupDropdowns();
        
        // Establecer fechas por defecto (último mes)
        const hoy = new Date();
        const hace1Mes = new Date();
        hace1Mes.setMonth(hace1Mes.getMonth() - 1);
        
        document.getElementById('fecha-fin').value = formatDateInput(hoy);
        document.getElementById('fecha-inicio').value = formatDateInput(hace1Mes);
    });

    function formatDateInput(date) {
        return date.toISOString().split('T')[0];
    }

    // ====== Event Listeners ======
    function setupEventListeners() {
        // Cambio de centro
        document.getElementById('centro-select').addEventListener('change', async function() {
            State.centroId = this.value;
            State.instalacionId = null;
            State.selectedActividades.clear();
            State.actividades = [];
            
            // Resetear selectores dependientes
            const instalacionSelect = document.getElementById('instalacion-select');
            instalacionSelect.innerHTML = '<option value="">-- Selecciona una instalación --</option>';
            document.getElementById('actividades-grid').innerHTML = `
                <div class="empty-actividades">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p>Selecciona una instalación para ver sus actividades</p>
                </div>
            `;
            
            if (State.centroId) {
                await cargarInstalaciones();
                document.getElementById('step-instalacion').classList.remove('step-disabled');
            } else {
                document.getElementById('step-instalacion').classList.add('step-disabled');
            }
            
            document.getElementById('step-actividades').classList.add('step-disabled');
            document.getElementById('step-fechas').classList.add('step-disabled');
            actualizarUI();
        });
        
        // Cambio de instalación
        document.getElementById('instalacion-select').addEventListener('change', async function() {
            State.instalacionId = this.value;
            State.selectedActividades.clear();
            
            if (State.instalacionId) {
                await cargarActividades();
                document.getElementById('step-actividades').classList.remove('step-disabled');
            } else {
                document.getElementById('step-actividades').classList.add('step-disabled');
                document.getElementById('step-fechas').classList.add('step-disabled');
            }
            
            actualizarUI();
        });
        
        // Búsqueda de actividades
        document.getElementById('buscar-actividades').addEventListener('input', function() {
            State.busqueda = this.value.toLowerCase();
            renderActividades();
        });
        
        // Filtros
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                State.filtroActual = this.dataset.filter;
                renderActividades();
            });
        });
        
        // Fechas
        document.getElementById('fecha-inicio').addEventListener('change', validarFormulario);
        document.getElementById('fecha-fin').addEventListener('change', validarFormulario);
    }

    // ====== Cargar instalaciones ======
    async function cargarInstalaciones() {
        const select = document.getElementById('instalacion-select');
        select.innerHTML = '<option value="">Cargando...</option>';
        
        try {
            const resp = await fetch(`api/informes/instalaciones.php?centro_id=${State.centroId}`);
            const data = await resp.json();
            
            if (data.success) {
                State.instalaciones = data.data || [];
                select.innerHTML = '<option value="">-- Selecciona una instalación --</option>';
                State.instalaciones.forEach(inst => {
                    select.innerHTML += `<option value="${inst.id}">${escapeHtml(inst.nombre)}</option>`;
                });
            } else {
                select.innerHTML = '<option value="">Error al cargar</option>';
            }
        } catch (err) {
            console.error(err);
            select.innerHTML = '<option value="">Error de conexión</option>';
        }
    }

    // ====== Cargar actividades ======
    async function cargarActividades() {
        const grid = document.getElementById('actividades-grid');
        grid.innerHTML = '<div class="empty-actividades"><p>Cargando actividades...</p></div>';
        
        try {
            const resp = await fetch(`api/informes/actividades.php?instalacion_id=${State.instalacionId}`);
            const data = await resp.json();
            
            if (data.success) {
                State.actividades = data.data || [];
                renderActividades();
            } else {
                grid.innerHTML = '<div class="empty-actividades"><p>Error al cargar actividades</p></div>';
            }
        } catch (err) {
            console.error(err);
            grid.innerHTML = '<div class="empty-actividades"><p>Error de conexión</p></div>';
        }
    }

    // ====== Renderizar actividades ======
    function renderActividades() {
        const grid = document.getElementById('actividades-grid');
        
        // Filtrar actividades
        let actividades = State.actividades.filter(a => {
            // Filtro de búsqueda
            if (State.busqueda) {
                const texto = `${a.nombre} ${a.grupo || ''} ${a.horario}`.toLowerCase();
                if (!texto.includes(State.busqueda)) return false;
            }
            
            // Filtro de estado
            if (State.filtroActual === 'activas' && a.finalizada) return false;
            if (State.filtroActual === 'finalizadas' && !a.finalizada) return false;
            
            return true;
        });
        
        if (actividades.length === 0) {
            grid.innerHTML = `
                <div class="empty-actividades">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>No se encontraron actividades</p>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = actividades.map(a => {
            const isSelected = State.selectedActividades.has(a.id);
            const fechaInfo = formatFechaActividad(a);
            
            return `
                <div class="actividad-card ${isSelected ? 'selected' : ''} ${a.finalizada ? 'finalizada' : ''}" 
                     data-id="${a.id}" onclick="toggleActividad(${a.id})">
                    <div class="actividad-checkbox">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="actividad-info">
                        <div class="actividad-nombre">${escapeHtml(a.nombre)}${a.grupo ? ' <span style="color: var(--admin-primary); font-weight: 500;">(' + escapeHtml(a.grupo) + ')</span>' : ''}</div>
                        <div class="actividad-meta">
                            <span>📅 ${fechaInfo}</span>
                            <span>🕐 ${escapeHtml(a.horario || 'Sin horario')}</span>
                            <span class="actividad-badge ${a.finalizada ? 'badge-finalizada' : 'badge-activa'}">
                                ${a.finalizada ? 'Finalizada' : 'Activa'}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function formatFechaActividad(a) {
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        
        let inicio = '';
        let fin = '';
        
        if (a.fecha_inicio) {
            const d = new Date(a.fecha_inicio);
            inicio = `${meses[d.getMonth()]} ${d.getFullYear()}`;
        }
        
        if (a.fecha_fin) {
            const d = new Date(a.fecha_fin);
            fin = `${meses[d.getMonth()]} ${d.getFullYear()}`;
        }
        
        if (inicio && fin) {
            return `${inicio} - ${fin}`;
        } else if (inicio) {
            return `Desde ${inicio}`;
        }
        return 'Sin fechas';
    }

    // ====== Toggle actividad ======
    function toggleActividad(id) {
        if (State.selectedActividades.has(id)) {
            State.selectedActividades.delete(id);
        } else {
            if (State.selectedActividades.size >= 10) {
                showNotification('Máximo 10 actividades permitidas', 'warning');
                return;
            }
            State.selectedActividades.add(id);
        }
        
        // Actualizar UI de la card
        const card = document.querySelector(`.actividad-card[data-id="${id}"]`);
        if (card) {
            card.classList.toggle('selected', State.selectedActividades.has(id));
        }
        
        actualizarUI();
    }

    function limpiarSeleccion() {
        State.selectedActividades.clear();
        document.querySelectorAll('.actividad-card.selected').forEach(card => {
            card.classList.remove('selected');
        });
        actualizarUI();
    }

    // ====== Actualizar UI ======
    function actualizarUI() {
        const count = State.selectedActividades.size;
        const counter = document.getElementById('seleccion-counter');
        const countEl = document.getElementById('count-selected');
        const stepFechas = document.getElementById('step-fechas');
        
        // Mostrar/ocultar contador
        counter.style.display = count > 0 ? 'flex' : 'none';
        countEl.textContent = count;
        
        // Habilitar/deshabilitar paso de fechas
        if (count > 0) {
            stepFechas.classList.remove('step-disabled');
        } else {
            stepFechas.classList.add('step-disabled');
        }
        
        validarFormulario();
    }

    function validarFormulario() {
        const btn = document.getElementById('btn-generar');
        const fechaInicio = document.getElementById('fecha-inicio').value;
        const fechaFin = document.getElementById('fecha-fin').value;
        const tieneActividades = State.selectedActividades.size > 0;
        
        const valido = tieneActividades && fechaInicio && fechaFin && fechaInicio <= fechaFin;
        btn.disabled = !valido;
    }

    // ====== Generar informes ======
    async function generarInformes() {
        const fechaInicio = document.getElementById('fecha-inicio').value;
        const fechaFin = document.getElementById('fecha-fin').value;
        const actividadIds = Array.from(State.selectedActividades);
        
        if (actividadIds.length === 0) {
            showNotification('Selecciona al menos una actividad', 'error');
            return;
        }
        
        const btn = document.getElementById('btn-generar');
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="loading-spinner" width="20" height="20" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="60" stroke-dashoffset="60"/>
            </svg>
            Generando...
        `;
        
        try {
            // Generar cada informe
            for (const actividadId of actividadIds) {
                await descargarInforme(actividadId, fechaInicio, fechaFin);
                // Pequeña pausa entre descargas para no saturar
                await new Promise(r => setTimeout(r, 500));
            }
            
            showNotification(`${actividadIds.length} informe(s) generado(s) correctamente`, 'success');
        } catch (err) {
            console.error(err);
            showNotification('Error al generar informes', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Generar Informes
            `;
            validarFormulario();
        }
    }

    async function descargarInforme(actividadId, fechaInicio, fechaFin) {
        // Crear formulario oculto para la descarga
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'api/informes/generar.php';
        form.style.display = 'none';
        
        const campos = {
            actividad_id: actividadId,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin
        };
        
        for (const [key, value] of Object.entries(campos)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // ====== Utilidades ======
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showNotification(message, type = 'info') {
        const notification = document.getElementById('notification');
        if (!notification) return;
        
        notification.textContent = message;
        notification.className = `notification ${type} show`;
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 4000);
    }

    function setupDropdowns() {
        const profileBtn = document.getElementById('profile-dropdown-btn');
        const profileDropdown = document.getElementById('profile-dropdown');
        
        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });
            
            document.addEventListener('click', () => {
                profileDropdown.classList.remove('show');
            });
        }
    }
    </script>
</body>
</html>
