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

// Funciones auxiliares para el header admin
function getAdminInfo() {
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role']
    ];
}

function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin';
}

$admin_info = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Informes - Admin Puri</title>
    <link rel="stylesheet" href="admin/assets/css/admin.css">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=GeistSans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Solo para esta página: forzar header admin a ocupar todo el ancho de la ventana */
        .admin-header.fullbleed {
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <!-- Header Admin -->
    <header class="admin-header fullbleed">
        <div class="logo-section">
            <div class="logo">P</div>
            <div class="title">Puri: Gestión de centros deportivos</div>
        </div>
        <div class="actions">
            <a href="admin/dashboard.php" class="btn btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v0M8 5a2 2 0 012 2h4a2 2 0 012-2v0"/>
                </svg>
                Dashboard
            </a>
            <div class="dropdown">
                <button class="btn btn-secondary" id="profile-dropdown-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?php echo htmlspecialchars($admin_info['username']); ?>
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

    <!-- Main Content -->
    <main class="admin-content">
        <div class="container" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
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

    <script>
    // Dropdown functionality
    document.getElementById('profile-dropdown-btn').addEventListener('click', function(e) {
        e.preventDefault();
        const dropdown = document.getElementById('profile-dropdown');
        dropdown.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('profile-dropdown');
        const button = document.getElementById('profile-dropdown-btn');
        
        if (!button.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
    </script>
    </main>
</body>
</html> 