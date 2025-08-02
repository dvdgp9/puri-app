<?php
require_once 'config/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: instalaciones.php"); // O mostrar un error
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM instalaciones WHERE id = ?");
$stmt->execute([$id]);
$instalacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$instalacion) {
    header("Location: instalaciones.php"); // Instalación no encontrada
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $centro_id = filter_input(INPUT_POST, 'centro_id', FILTER_VALIDATE_INT); // IMPORTANT

    if (empty($nombre) || !$centro_id) {
        $error = "Todos los campos son obligatorios.";
    } else {
        $stmt = $pdo->prepare("UPDATE instalaciones SET nombre = ?, centro_id = ? WHERE id = ?");
        $result = $stmt->execute([$nombre, $centro_id, $id]);

        if ($result) {
            header("Location: instalaciones.php"); // O a donde sea apropiado
            exit;
        } else {
            $error = "Error al actualizar la instalación.";
        }
    }
}
$pageTitle = "Editar Instalación";
require_once 'includes/header.php';
?>
    <h1>Editar Instalación</h1>
    <?php if (isset($error)): ?>
        <p class="error-message"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($instalacion['nombre']); ?>" required>
        <br>
        <label for="centro_id">Centro ID:</label>
        <input type="number" id="centro_id" name="centro_id" value="<?php echo htmlspecialchars($instalacion['centro_id']); ?>" required>
        <br>

        <button type="submit">Guardar Cambios</button>
    </form>
<?php require_once 'includes/footer.php'; ?> 