<?php
require_once 'config/config.php';  // config.php ya incluye session_start()

// Verificar que se haya pasado el actividad_id
if (!isset($_GET['actividad_id'])) {
    header("Location: actividades.php"); // Redirigir a actividades si no hay ID
    exit;
}

$actividad_id = $_GET['actividad_id'];

// Obtener el instalacion_id para poder volver a la lista de actividades correctamente
$stmt = $pdo->prepare("SELECT instalacion_id FROM actividades WHERE id = ?");
$stmt->execute([$actividad_id]);
$actividad = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar que la actividad existe
if (!$actividad) {
    header("Location: actividades.php"); // Redirigir si la actividad no existe
    exit;
}

$instalacion_id = $actividad['instalacion_id'];
$pageTitle = "Confirmación";
require_once 'includes/header.php';
?>

  <button class="menu-button" onclick="showModal()">Volver a...</button>

    <div class="modal-backdrop" id="menuModal">
        <div class="modal">
            <button class="modal-close" onclick="hideModal()">×</button>
            <h3>Navegación</h3>
            <ul class="nav-list">
                <li class="nav-item" onclick="window.location='index.php'">Inicio</li>
                <li class="nav-item" onclick="window.location='instalaciones.php'">Instalaciones</li>
                 <li class="nav-item" onclick="window.location='actividades.php?instalacion_id=<?php echo $instalacion_id; ?>'">Actividades</li>
            </ul>
        </div>
    </div>

    <script>
        function showModal() {
            document.getElementById('menuModal').style.display = 'flex';
        }

        function hideModal() {
            document.getElementById('menuModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('menuModal').addEventListener('click', function (e) {
            if (e.target === this) {
                hideModal();
            }
        });
    </script>
  <h1>Asistencias Confirmadas</h1>
  <p>La asistencia se ha registrado correctamente.</p>
  <a href="asistencia.php?actividad_id=<?php echo $actividad_id; ?>">Volver al listado de Asistencia</a>
</body>
</html>
<?php require_once 'includes/footer.php'; ?>
