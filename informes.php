<?php
require_once 'config/config.php';

// Requiere sesión de administrador: si no, guardar retorno y redirigir a login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['admin_return_to'] = '/informes.php';
    header('Location: admin/login.php');
    exit;
}

// Si hay una sesión activa de centro, la eliminamos para este módulo
if(isset($_SESSION['centro_id'])) {
    unset($_SESSION['centro_id']);
}

// Consultamos los centros según permisos del admin
if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin') {
    $sql = "SELECT id, nombre FROM centros ORDER BY nombre";
    $stmt = $pdo->query($sql);
} else {
    $sql = "SELECT c.id, c.nombre
            FROM centros c
            INNER JOIN admin_asignaciones aa ON aa.centro_id = c.id
            WHERE aa.admin_id = ?
            ORDER BY c.nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['admin_id']]);
}
$centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = "Generador de Informes";
// Cargar estilos del header admin dentro del <head>
$extraStyles = (
    '<link rel="stylesheet" href="admin/assets/css/admin.css">' .
    '<link href="https://fonts.googleapis.com/css2?family=GeistSans:wght@300;400;500;600;700&display=swap" rel="stylesheet">'
);
require_once 'includes/header.php';
?>
    <!-- Admin Header (reutilizado) -->
    <header class="admin-header">
        <div class="logo-section">
            <div class="logo">P</div>
            <div class="title">Puri: Gestión de centros deportivos</div>
        </div>
        <div class="actions">
            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin') { ?>
            <a class="btn btn-secondary" href="admin/dashboard.php#admins">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V4H2v16h5m10 0V10m0 10H7m10-10H7"/>
                </svg>
                Administradores
            </a>
            <?php } ?>
            <a class="btn btn-primary" href="admin/dashboard.php">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Ir al Dashboard
            </a>
            <div class="dropdown">
                <button class="btn btn-secondary" id="profile-dropdown-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                </button>
                <div class="dropdown-content" id="profile-dropdown">
                    <a href="admin/account.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756.426-1.756 2.924 0 3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Mi Cuenta
                    </a>
                    <a href="admin/logout.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>
    <div class="container">
        <img src="public/assets/images/logo.png" alt="Logo" class="logo">
        <h1>Generador de Informes</h1>
        <span class="subtitle">Selecciona los criterios para generar el informe</span>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form id="reportForm" action="generar_informe.php" method="post">
            <div class="form-group">
                <label for="centro">Centro:</label>
                <select id="centro" name="centro_id" required>
                    <option value="">Selecciona un centro</option>
                    <?php foreach($centros as $centro): ?>
                        <option value="<?php echo $centro['id']; ?>"><?php echo htmlspecialchars($centro['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="instalacion">Instalación:</label>
                <select id="instalacion" name="instalacion_id" required disabled>
                    <option value="">Primero selecciona un centro</option>
                </select>
            </div>

            <div class="form-group">
                <label for="actividad">Actividad:</label>
                <select id="actividad" name="actividad_id" required disabled>
                    <option value="">Primero selecciona una instalación</option>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha_inicio">Fecha inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" required>
            </div>

            <div class="form-group">
                <label for="fecha_fin">Fecha fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" required>
            </div>

            <button type="submit">Generar Informe</button>
        </form>
    </div>

    <script>
    // Dropdown perfil (comportamiento admin)
    (function(){
        const btn = document.getElementById('profile-dropdown-btn');
        const menu = document.getElementById('profile-dropdown');
        if (btn && menu) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.classList.toggle('active');
            });
            document.addEventListener('click', function () {
                menu.classList.remove('active');
            });
        }
    })();

    document.getElementById('centro').addEventListener('change', function() {
        const centroId = this.value;
        const instalacionSelect = document.getElementById('instalacion');
        const actividadSelect = document.getElementById('actividad');

        // Resetear y deshabilitar selectores dependientes
        instalacionSelect.innerHTML = '<option value="">Primero selecciona un centro</option>';
        actividadSelect.innerHTML = '<option value="">Primero selecciona una instalación</option>';
        instalacionSelect.disabled = !centroId;
        actividadSelect.disabled = true;

        if (centroId) {
            // Cargar instalaciones
            fetch('obtener_instalaciones.php?centro_id=' + centroId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        instalacionSelect.innerHTML = '<option value="">Selecciona una instalación</option>';
                        data.instalaciones.forEach(instalacion => {
                            instalacionSelect.innerHTML += `<option value="${instalacion.id}">${instalacion.nombre}</option>`;
                        });
                        instalacionSelect.disabled = false;
                    }
                });
        }
    });

    document.getElementById('instalacion').addEventListener('change', function() {
        const instalacionId = this.value;
        const actividadSelect = document.getElementById('actividad');

        // Resetear y deshabilitar selector de actividades
        actividadSelect.innerHTML = '<option value="">Primero selecciona una instalación</option>';
        actividadSelect.disabled = !instalacionId;

        if (instalacionId) {
            // Cargar actividades
            fetch('obtener_actividades.php?instalacion_id=' + instalacionId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        actividadSelect.innerHTML = '<option value="">Selecciona una actividad</option>';
                        data.actividades.forEach(actividad => {
                            actividadSelect.innerHTML += `<option value="${actividad.id}">${actividad.nombre} - ${actividad.horario}</option>`;
                        });
                        actividadSelect.disabled = false;
                    }
                });
        }
    });

    // Validar fechas
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
        const fechaFin = new Date(document.getElementById('fecha_fin').value);
        
        if (fechaFin < fechaInicio) {
            e.preventDefault();
            alert('La fecha de fin no puede ser anterior a la fecha de inicio');
        }
    });
    </script>
<?php require_once 'includes/footer.php'; ?> 