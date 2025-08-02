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
    $horario = filter_input(INPUT_POST, 'horario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $instalacion_id = filter_input(INPUT_POST, 'instalacion_id', FILTER_VALIDATE_INT); // IMPORTANT
    $fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $fecha_fin = filter_input(INPUT_POST, 'fecha_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (empty($nombre) || empty($horario) || !$instalacion_id || empty($fecha_inicio)) {
        $error = "El nombre, el horario, la instalación y la fecha de inicio son obligatorios.";
    } else {
        $stmt = $pdo->prepare("UPDATE actividades SET nombre = ?, horario = ?, instalacion_id = ?, fecha_inicio = ?, fecha_fin = ? WHERE id = ?");
        $result = $stmt->execute([$nombre, $horario, $instalacion_id, $fecha_inicio, $fecha_fin ?: null, $id]);

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
        <label for="horario">Horario:</label>
        <input type="text" id="horario" name="horario" value="<?php echo htmlspecialchars($actividad['horario']); ?>" required>
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