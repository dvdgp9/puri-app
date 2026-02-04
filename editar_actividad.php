<?php
require_once 'config/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: actividades.php"); // O mostrar un error, o redirigir a una página 404
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM actividades WHERE id = ?");
$stmt->execute([$id]);
$actividad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actividad) {
    header("Location: actividades.php"); // Actividad no encontrada
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $grupo = filter_input(INPUT_POST, 'grupo', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: null;
    // Procesar días de la semana
    $dias_semana = isset($_POST['dias_semana']) ? implode(',', $_POST['dias_semana']) : '';
    $hora_inicio = filter_input(INPUT_POST, 'hora_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $hora_fin = filter_input(INPUT_POST, 'hora_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $instalacion_id = filter_input(INPUT_POST, 'instalacion_id', FILTER_VALIDATE_INT); // IMPORTANT
    
    // Mantener compatibilidad con campo legacy
    $horario = '';
    if (!empty($dias_semana) && !empty($hora_inicio) && !empty($hora_fin)) {
        $horario = "$dias_semana $hora_inicio-$hora_fin";
    }
    
    $fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $fecha_fin = filter_input(INPUT_POST, 'fecha_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (empty($nombre) || (empty($dias_semana) && empty($_POST['horario'])) || !$instalacion_id || empty($fecha_inicio)) {
        $error = "El nombre, los días de la semana, la instalación y la fecha de inicio son obligatorios.";
    } else {
        $stmt = $pdo->prepare("UPDATE actividades SET nombre = ?, grupo = ?, horario = ?, dias_semana = ?, hora_inicio = ?, hora_fin = ?, instalacion_id = ?, fecha_inicio = ?, fecha_fin = ? WHERE id = ?");
        $result = $stmt->execute([$nombre, $grupo, $horario, $dias_semana, $hora_inicio, $hora_fin, $instalacion_id, $fecha_inicio, $fecha_fin ?: null, $id]);

        if ($result) {
             // Redirigir a la lista de actividades, *incluyendo el instalacion_id*
            header("Location: actividades.php?instalacion_id=" . $actividad['instalacion_id']);
            exit;
        } else {
            $error = "Error al actualizar la actividad.";
        }
    }
}
$pageTitle = "Editar Actividad";
require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Editar Actividad</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <h1>Editar Actividad</h1>
      <?php if (isset($error)): ?>
        <p class="error-message"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($actividad['nombre']); ?>" required>
        <br>
        <label for="grupo">Grupo:</label>
        <input type="text" id="grupo" name="grupo" value="<?php echo htmlspecialchars($actividad['grupo'] ?? ''); ?>" placeholder="Ejemplo: 1, A, Avanzado (opcional)">
        <small>Identificador opcional para diferenciar grupos de la misma actividad.</small>
        <br>
        <!-- Selector de días de la semana -->
        <label>
            <i class="fas fa-calendar-days"></i> Días de la semana
        </label>
        <div class="checkbox-group">
            <?php 
            // Convertir el valor de dias_semana en un array
            $dias_seleccionados = !empty($actividad['dias_semana']) ? 
                                  explode(',', $actividad['dias_semana']) : [];
            $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            
            foreach ($dias_semana as $dia): 
            ?>
                <label class="checkbox-inline">
                    <input type="checkbox" 
                           name="dias_semana[]" 
                           value="<?php echo $dia; ?>" 
                           <?php echo in_array($dia, $dias_seleccionados) ? 'checked' : ''; ?>>
                    <?php echo $dia; ?>
                </label>
            <?php endforeach; ?>
        </div>
        <br>

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
                       value="<?php echo htmlspecialchars($actividad['hora_inicio'] ?? ''); ?>">
            </div>
            
            <div class="form-group col-md-6">
                <label for="hora_fin">
                    <i class="fas fa-clock"></i> Hora de finalización
                </label>
                <input type="time" 
                       id="hora_fin" 
                       name="hora_fin" 
                       required
                       value="<?php echo htmlspecialchars($actividad['hora_fin'] ?? ''); ?>">
            </div>
        </div>
        <br>
         <label for="instalacion_id">Instalacion ID:</label>
        <input type="number" id="instalacion_id" name="instalacion_id" value="<?php echo htmlspecialchars($actividad['instalacion_id']); ?>" required>
        <br>
        <label for="fecha_inicio">Fecha de inicio:</label>
        <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($actividad['fecha_inicio'] ?? date('Y-m-d')); ?>" required>
        <br>
        <label for="fecha_fin">Fecha de finalización:</label>
        <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($actividad['fecha_fin'] ?? ''); ?>">
        <small>Opcional. Dejar en blanco si la actividad no tiene fecha de finalización definida.</small>
        <br>
        <button type="submit">Guardar Cambios</button>
    </form>
</body>
</html>
<?php require_once 'includes/footer.php'; ?> 