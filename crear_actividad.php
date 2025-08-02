<?php
require_once 'config/config.php';

// Verifica que se haya autenticado el centro
if(!isset($_SESSION['centro_id'])){
    header("Location: index.php");
    exit;
}

$centro_id = $_SESSION['centro_id'];
$instalacion_id = filter_input(INPUT_GET, 'instalacion_id', FILTER_SANITIZE_NUMBER_INT);

// Verificar que la instalación pertenece al centro
$stmt = $pdo->prepare("SELECT i.*, c.nombre as centro_nombre 
                       FROM instalaciones i 
                       JOIN centros c ON i.centro_id = c.id 
                       WHERE i.id = ? AND i.centro_id = ?");
$stmt->execute([$instalacion_id, $centro_id]);
$instalacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$instalacion) {
    header("Location: instalaciones.php");
    exit;
}

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $horario = filter_input(INPUT_POST, 'horario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $dias_semana = $_POST['dias_semana'] ?? [];
    $hora_inicio = filter_input(INPUT_POST, 'hora_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $hora_fin = filter_input(INPUT_POST, 'hora_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $fecha_fin = filter_input(INPUT_POST, 'fecha_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  
    // Validar que se haya proporcionado al menos un día si se usan los nuevos campos
    if (!empty($dias_semana) && (!empty($hora_inicio) || !empty($hora_fin))) {
        // Usar los nuevos campos
        $dias_semana_str = implode(',', $dias_semana);
        
        // Validar que se proporcionen ambos campos de hora si se usa uno
        if ((!empty($hora_inicio) && empty($hora_fin)) || (empty($hora_inicio) && !empty($hora_fin))) {
            $error = "Debe proporcionar tanto la hora de inicio como la hora de fin, o dejar ambos campos vacíos.";
        } elseif (empty($nombre) || empty($fecha_inicio)) {
            $error = "El nombre, los días de la semana, las horas y la fecha de inicio son obligatorios.";
        } else {
            // Mantener compatibilidad con el campo horario existente
            if (empty($horario)) {
                $horario = $dias_semana_str . ' ' . ($hora_inicio ?? '') . '-' . ($hora_fin ?? '');
            }
            
            $stmt = $pdo->prepare("INSERT INTO actividades (nombre, horario, dias_semana, hora_inicio, hora_fin, instalacion_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$nombre, $horario, $dias_semana_str, $hora_inicio, $hora_fin, $instalacion_id, $fecha_inicio, $fecha_fin ?: null]);

            if ($result) {
                header("Location: actividades.php?instalacion_id=" . $instalacion_id);
                exit;
            } else {
                $error = "Error al crear la actividad.";
            }
        }
    } else {
        // Usar el campo horario existente (modo retrocompatible)
        if (empty($nombre) || empty($horario) || empty($fecha_inicio)) {
            $error = "El nombre, el horario y la fecha de inicio son obligatorios.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO actividades (nombre, horario, instalacion_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$nombre, $horario, $instalacion_id, $fecha_inicio, $fecha_fin ?: null]);

            if ($result) {
                header("Location: actividades.php?instalacion_id=" . $instalacion_id);
                exit;
            } else {
                $error = "Error al crear la actividad.";
            }
        }
    }
}
$pageTitle = "Crear Actividad";
require_once 'includes/header.php';
?>
    <div class="container">
        <h1><i class="fas fa-plus-circle"></i> Crear Actividad</h1>
        
        <div class="breadcrumbs">
            <a href="instalaciones.php">Instalaciones</a>
            <span class="separator">></span>
            <a href="actividades.php?instalacion_id=<?php echo $instalacion_id; ?>"><?php echo htmlspecialchars($instalacion['nombre']); ?></a>
            <span class="separator">></span>
            <span class="current">Nueva Actividad</span>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="form">
            <div class="form-group">
                <label for="nombre">
                    <i class="fas fa-tasks"></i> Nombre de la Actividad
                </label>
                <input type="text" 
                       id="nombre" 
                       name="nombre" 
                       required 
                       placeholder="Ejemplo: Taller de Pintura"
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>

            <!-- Campo oculto para compatibilidad, se generará automáticamente si se usan los nuevos campos -->
            <input type="hidden" 
                   id="horario" 
                   name="horario" 
                   value="<?php echo isset($_POST['horario']) ? htmlspecialchars($_POST['horario']) : ''; ?>">
            
            <!-- Selector de días de la semana -->
            <div class="form-group">
                <label>
                    <i class="fas fa-calendar-days"></i> Días de la semana
                </label>
                <div class="days-selector">
                    <label><input type="checkbox" name="dias_semana[]" value="Lunes" <?php echo (isset($_POST['dias_semana']) && in_array('Lunes', $_POST['dias_semana'])) ? 'checked' : ''; ?>> Lunes</label>
                    <label><input type="checkbox" name="dias_semana[]" value="Martes" <?php echo (isset($_POST['dias_semana']) && in_array('Martes', $_POST['dias_semana'])) ? 'checked' : ''; ?>> Martes</label>
                    <label><input type="checkbox" name="dias_semana[]" value="Miércoles" <?php echo (isset($_POST['dias_semana']) && in_array('Miércoles', $_POST['dias_semana'])) ? 'checked' : ''; ?>> Miércoles</label>
                    <label><input type="checkbox" name="dias_semana[]" value="Jueves" <?php echo (isset($_POST['dias_semana']) && in_array('Jueves', $_POST['dias_semana'])) ? 'checked' : ''; ?>> Jueves</label>
                    <label><input type="checkbox" name="dias_semana[]" value="Viernes" <?php echo (isset($_POST['dias_semana']) && in_array('Viernes', $_POST['dias_semana'])) ? 'checked' : ''; ?>> Viernes</label>
                    <label><input type="checkbox" name="dias_semana[]" value="Sábado" <?php echo (isset($_POST['dias_semana']) && in_array('Sábado', $_POST['dias_semana'])) ? 'checked' : ''; ?>> Sábado</label>
                    <label><input type="checkbox" name="dias_semana[]" value="Domingo" <?php echo (isset($_POST['dias_semana']) && in_array('Domingo', $_POST['dias_semana'])) ? 'checked' : ''; ?>> Domingo</label>
                </div>
            </div>

            <!-- Selectores de hora -->
            <div class="form-row">
                <div class="form-group">
                    <label for="hora_inicio">
                        <i class="fas fa-hourglass-start"></i> Hora de inicio
                    </label>
                    <input type="time" 
                           id="hora_inicio" 
                           name="hora_inicio"
                           value="<?php echo isset($_POST['hora_inicio']) ? htmlspecialchars($_POST['hora_inicio']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="hora_fin">
                        <i class="fas fa-hourglass-end"></i> Hora de fin
                    </label>
                    <input type="time" 
                           id="hora_fin" 
                           name="hora_fin"
                           value="<?php echo isset($_POST['hora_fin']) ? htmlspecialchars($_POST['hora_fin']) : ''; ?>">
                </div>
            </div>
            
            <!-- Campo visible para modo retrocompatible -->
            <div class="form-group" id="horario-text-container" style="display: none;">
                <label for="horario-text">
                    <i class="fas fa-clock"></i> Horario (texto)
                </label>
                <input type="text" 
                       id="horario-text" 
                       name="horario-text" 
                       placeholder="Ejemplo: Lunes y Miércoles 16:00-17:30"
                       value="<?php echo isset($_POST['horario']) ? htmlspecialchars($_POST['horario']) : ''; ?>">
                <small class="form-text">Este campo se usa solo si no se seleccionan días de la semana.</small>
            </div>
            
            <script>
                // Mostrar/ocultar campos según el modo de entrada
                document.addEventListener('DOMContentLoaded', function() {
                    const diasCheckboxes = document.querySelectorAll('input[name="dias_semana[]"]');
                    const horarioTextContainer = document.getElementById('horario-text-container');
                    const horarioHidden = document.getElementById('horario');
                    
                    function updateVisibility() {
                        let checked = false;
                        diasCheckboxes.forEach(checkbox => {
                            if (checkbox.checked) checked = true;
                        });
                        
                        if (checked) {
                            horarioTextContainer.style.display = 'none';
                            // Generar valor para el campo oculto
                            updateHorarioValue();
                        } else {
                            horarioTextContainer.style.display = 'block';
                            // Usar valor del campo de texto
                            horarioHidden.value = document.getElementById('horario-text').value;
                        }
                    }
                    
                    function updateHorarioValue() {
                        const selectedDays = [];
                        diasCheckboxes.forEach(checkbox => {
                            if (checkbox.checked) selectedDays.push(checkbox.value);
                        });
                        
                        const horaInicio = document.getElementById('hora_inicio').value;
                        const horaFin = document.getElementById('hora_fin').value;
                        
                        if (selectedDays.length > 0 && horaInicio && horaFin) {
                            horarioHidden.value = selectedDays.join(', ') + ' ' + horaInicio + '-' + horaFin;
                        } else {
                            horarioHidden.value = '';
                        }
                    }
                    
                    // Añadir event listeners
                    diasCheckboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', updateVisibility);
                    });
                    
                    document.getElementById('hora_inicio').addEventListener('change', updateHorarioValue);
                    document.getElementById('hora_fin').addEventListener('change', updateHorarioValue);
                    document.getElementById('horario-text').addEventListener('input', function() {
                        horarioHidden.value = this.value;
                    });
                    
                    // Inicializar
                    updateVisibility();
                });
            </script>

            <div class="form-group">
                <label for="fecha_inicio">
                    <i class="fas fa-calendar-alt"></i> Fecha de inicio
                </label>
                <input type="date" 
                       id="fecha_inicio" 
                       name="fecha_inicio" 
                       required
                       value="<?php echo isset($_POST['fecha_inicio']) ? htmlspecialchars($_POST['fecha_inicio']) : date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="fecha_fin">
                    <i class="fas fa-calendar-check"></i> Fecha de finalización
                </label>
                <input type="date" 
                       id="fecha_fin" 
                       name="fecha_fin" 
                       value="<?php echo isset($_POST['fecha_fin']) ? htmlspecialchars($_POST['fecha_fin']) : ''; ?>">
                <small class="form-text">Opcional. Dejar en blanco si la actividad no tiene fecha de finalización definida.</small>
            </div>

            <div class="button-group">
                <button type="submit" class="button btn-primary">
                    <i class="fas fa-save"></i> Guardar Actividad
                </button>
                <a href="actividades.php?instalacion_id=<?php echo $instalacion_id; ?>" class="button btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
<?php require_once 'includes/footer.php'; ?> 