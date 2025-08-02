<?php
require_once 'config/config.php';

// Verifica que se haya autenticado el centro
if(!isset($_SESSION['centro_id'])){
    header("Location: index.php");
    exit;
}

$actividad_id = filter_input(INPUT_GET, 'actividad_id', FILTER_SANITIZE_NUMBER_INT);

// Verificar que la actividad existe y pertenece al centro
$stmt = $pdo->prepare("
    SELECT a.*, i.nombre as instalacion_nombre, c.nombre as centro_nombre 
    FROM actividades a 
    JOIN instalaciones i ON a.instalacion_id = i.id 
    JOIN centros c ON i.centro_id = c.id 
    WHERE a.id = ? AND c.id = ?
");
$stmt->execute([$actividad_id, $_SESSION['centro_id']]);
$actividad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actividad) {
    header("Location: instalaciones.php");
    exit;
}

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $apellidos = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  
    if (empty($nombre) || empty($apellidos)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO inscritos (nombre, apellidos, actividad_id) VALUES (?, ?, ?)");
        $result = $stmt->execute([$nombre, $apellidos, $actividad_id]);

        if ($result) {
            header("Location: asistencia.php?actividad_id=" . $actividad_id);
            exit;
        } else {
            $error = "Error al crear el inscrito.";
        }
    }
}
$pageTitle = "Añadir Inscrito/a";
require_once 'includes/header.php';
?>
    <div class="container">
        <h1><i class="fas fa-user-plus"></i> Añadir Inscrito/a</h1>
        
        <div class="breadcrumbs">
            <a href="instalaciones.php"><?php echo htmlspecialchars($actividad['centro_nombre']); ?></a>
            <span class="separator">></span>
            <a href="actividades.php?instalacion_id=<?php echo $actividad['instalacion_id']; ?>"><?php echo htmlspecialchars($actividad['instalacion_nombre']); ?></a>
            <span class="separator">></span>
            <a href="asistencia.php?actividad_id=<?php echo $actividad_id; ?>"><?php echo htmlspecialchars($actividad['nombre']); ?></a>
            <span class="separator">></span>
            <span class="current">Nuevo/a Inscrito/a</span>
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
                    <i class="fas fa-user"></i> Nombre
                </label>
                <input type="text" 
                       id="nombre" 
                       name="nombre" 
                       required 
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="apellidos">
                    <i class="fas fa-user"></i> Apellidos
                </label>
                <input type="text" 
                       id="apellidos" 
                       name="apellidos" 
                       required
                       value="<?php echo isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''; ?>">
            </div>

            <div class="button-group">
                <button type="submit" class="button btn-primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <a href="asistencia.php?actividad_id=<?php echo $actividad_id; ?>" class="button btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
<?php require_once 'includes/footer.php'; ?>