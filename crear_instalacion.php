<?php
require_once 'config/config.php';

// Verifica que se haya autenticado el centro
if(!isset($_SESSION['centro_id'])){
    header("Location: index.php");
    exit;
}

$centro_id = $_SESSION['centro_id'];

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  
    if (empty($nombre)) {  // Validación básica
        $error = "El nombre es obligatorio.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO instalaciones (nombre, centro_id) VALUES (?, ?)");
        $result = $stmt->execute([$nombre, $centro_id]);

        if ($result) {
            header("Location: instalaciones.php"); // Redirigir al listado
            exit;
        } else {
            $error = "Error al crear la instalacion.";
        }
    }
}

$pageTitle = "Crear Instalación";
require_once 'includes/header.php';
?>
    <div class="container">
        <h1><i class="fas fa-plus-circle"></i> Crear Instalación</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="form">
            <div class="form-group">
                <label for="nombre">
                    <i class="fas fa-building"></i> Nombre de la Instalación
                </label>
                <input type="text" 
                       id="nombre" 
                       name="nombre" 
                       required 
                       placeholder="Ejemplo: Sala de Informática"
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>

            <div class="button-group">
                <button type="submit" class="button btn-primary">
                    <i class="fas fa-save"></i> Guardar Instalación
                </button>
                <a href="instalaciones.php" class="button btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
<?php require_once 'includes/footer.php'; ?> 