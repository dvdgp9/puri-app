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
    $grupo = filter_input(INPUT_POST, 'grupo', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: null;
    // Procesar días de la semana
    $dias_semana = isset($_POST['dias_semana']) ? implode(',', $_POST['dias_semana']) : '';
    $hora_inicio = filter_input(INPUT_POST, 'hora_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $hora_fin = filter_input(INPUT_POST, 'hora_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $fecha_fin = filter_input(INPUT_POST, 'fecha_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Mantener compatibilidad con campo legacy
    $horario = '';
    if (!empty($dias_semana) && !empty($hora_inicio) && !empty($hora_fin)) {
        $horario = "$dias_semana $hora_inicio-$hora_fin";
    }
  
    if (empty($nombre) || empty($dias_semana) || empty($fecha_inicio)) {
        $error = "El nombre, los días de la semana y la fecha de inicio son obligatorios.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO actividades (nombre, grupo, horario, dias_semana, hora_inicio, hora_fin, instalacion_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$nombre, $grupo, $horario, $dias_semana, $hora_inicio, $hora_fin, $instalacion_id, $fecha_inicio, $fecha_fin ?: null]);

        if ($result) {
            header("Location: actividades.php?instalacion_id=" . $instalacion_id);
            exit;
        } else {
            $error = "Error al crear la actividad.";
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

            <div class="form-group">
                <label for="grupo">
                    <i class="fas fa-users"></i> Grupo
                </label>
                <input type="text" 
                       id="grupo" 
                       name="grupo" 
                       placeholder="Ejemplo: 1, A, Avanzado (opcional)"
                       value="<?php echo isset($_POST['grupo']) ? htmlspecialchars($_POST['grupo']) : ''; ?>">
                <small class="form-text">Identificador opcional para diferenciar grupos de la misma actividad.</small>
            </div>

            <!-- Selector de días de la semana -->
            <div class="form-group">
                <label>
                    <i class="fas fa-calendar-days"></i> Días de la semana
                </label>
                <div class="checkbox-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="dias_semana[]" value="Lunes" 
                               <?php echo isset($_POST['dias_semana']) && in_array('Lunes', $_POST['dias_semana']) ? 'checked' : ''; ?>>
                        Lunes
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="dias_semana[]" value="Martes" 
                               <?php echo isset($_POST['dias_semana']) && in_array('Martes', $_POST['dias_semana']) ? 'checked' : ''; ?>>
                        Martes
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="dias_semana[]" value="Miércoles" 
                               <?php echo isset($_POST['dias_semana']) && in_array('Miércoles', $_POST['dias_semana']) ? 'checked' : ''; ?>>
                        Miércoles
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="dias_semana[]" value="Jueves" 
                               <?php echo isset($_POST['dias_semana']) && in_array('Jueves', $_POST['dias_semana']) ? 'checked' : ''; ?>>
                        Jueves
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="dias_semana[]" value="Viernes" 
                               <?php echo isset($_POST['dias_semana']) && in_array('Viernes', $_POST['dias_semana']) ? 'checked' : ''; ?>>
                        Viernes
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="dias_semana[]" value="Sábado" 
                               <?php echo isset($_POST['dias_semana']) && in_array('Sábado', $_POST['dias_semana']) ? 'checked' : ''; ?>>
                        Sábado
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="dias_semana[]" value="Domingo" 
                               <?php echo isset($_POST['dias_semana']) && in_array('Domingo', $_POST['dias_semana']) ? 'checked' : ''; ?>>
                        Domingo
                    </label>
                </div>
            </div>

            <!-- Selectores de hora de inicio y fin -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="hora_inicio">
                        <i class="fas fa-clock"></i> Hora de inicio
                    </label>
                    <input type="time" 
                           id="hora_inicio" 
                           name="hora_inicio" 
                           required
                           value="<?php echo isset($_POST['hora_inicio']) ? htmlspecialchars($_POST['hora_inicio']) : ''; ?>">
                </div>
                
                <div class="form-group col-md-6">
                    <label for="hora_fin">
                        <i class="fas fa-clock"></i> Hora de finalización
                    </label>
                    <input type="time" 
                           id="hora_fin" 
                           name="hora_fin" 
                           required
                           value="<?php echo isset($_POST['hora_fin']) ? htmlspecialchars($_POST['hora_fin']) : ''; ?>">
                </div>
            </div>

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