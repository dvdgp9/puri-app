<?php
require_once 'config/config.php';

// Si hay una sesión activa de centro, la eliminamos para este módulo
if(isset($_SESSION['centro_id'])) {
    unset($_SESSION['centro_id']);
}

// Consultamos los centros desde la tabla
$sql = "SELECT id, nombre FROM centros";
$stmt = $pdo->query($sql);
$centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = "Generador de Informes";
require_once 'includes/header.php';
?>
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

            <div id="passwordSection" style="display: none;" class="form-group">
                <label for="password">Contraseña del centro:</label>
                <input type="password" id="password" name="password" required>
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
        const passwordSection = document.getElementById('passwordSection');
        const instalacionSelect = document.getElementById('instalacion');
        const actividadSelect = document.getElementById('actividad');

        // Mostrar/ocultar sección de contraseña
        passwordSection.style.display = centroId ? 'block' : 'none';
        
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