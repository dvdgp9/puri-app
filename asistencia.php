<?php
require_once 'config/config.php';

// Primero, validar parámetro de actividad
if(!isset($_GET['actividad_id'])){
    header("Location: actividades.php");
    exit;
}
$actividad_id = $_GET['actividad_id'];

// Si no hay sesión, redirigir a login con centro preseleccionado (si se puede resolver)
if(!isset($_SESSION['centro_id'])){
    // Resolver centro_id de la actividad para preseleccionar en login
    $centroIdForLogin = null;
    try {
        $stmtCentro = $pdo->prepare("SELECT c.id AS centro_id\n                                     FROM actividades a\n                                     JOIN instalaciones i ON a.instalacion_id = i.id\n                                     JOIN centros c ON i.centro_id = c.id\n                                     WHERE a.id = ?");
        $stmtCentro->execute([$actividad_id]);
        $centroRow = $stmtCentro->fetch(PDO::FETCH_ASSOC);
        if ($centroRow) { $centroIdForLogin = $centroRow['centro_id']; }
    } catch (Exception $e) {
        // Silencioso: si falla, seguimos sin centro_id
    }
    $currentUrl = $_SERVER['REQUEST_URI'] ?? ('asistencia.php?actividad_id=' . urlencode($actividad_id));
    $params = ['return_to' => $currentUrl];
    if ($centroIdForLogin) { $params['centro_id'] = $centroIdForLogin; }
    $location = 'index.php?' . http_build_query($params);
    header("Location: $location");
    exit;
}

// Consultar datos de la actividad, instalación y centro
$stmtActividad = $pdo->prepare("
    SELECT a.*, i.nombre as instalacion_nombre, c.nombre as centro_nombre 
    FROM actividades a 
    JOIN instalaciones i ON a.instalacion_id = i.id 
    JOIN centros c ON i.centro_id = c.id 
    WHERE a.id = ?
");
$stmtActividad->execute([$actividad_id]);
$actividad = $stmtActividad->fetch(PDO::FETCH_ASSOC);

// Consultar los inscritos en la actividad
$stmtUsuarios = $pdo->prepare("
    SELECT id, nombre, apellidos 
    FROM inscritos 
    WHERE actividad_id = ?
");
$stmtUsuarios->execute([$actividad_id]);
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

// Obtener la fecha seleccionada o usar la fecha actual
$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$fecha_mostrar = date('d/m/Y', strtotime($fecha_seleccionada));

// Consultamos las asistencias para esta actividad en la fecha seleccionada
$stmt = $pdo->prepare("SELECT usuario_id, asistio FROM asistencias 
                      WHERE actividad_id = ? AND fecha = ?");
$stmt->execute([$actividad_id, $fecha_seleccionada]);

$asistencias = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $asistencias[$row['usuario_id']] = $row['asistio'];
}

$pageTitle = "Control de Asistencia";

$extraStyles = "
  <style>
    .asiste { background-color: green; color: white; padding: 5px 10px; border: none; cursor: pointer; }
    .no-asiste { background-color: red; color: white; padding: 5px 10px; border: none; cursor: pointer; }
    .selected { opacity: 0.8; }
    
    /* Estilos para los mensajes de confirmación y error */
    .mensaje-exito, .mensaje-error {
      padding: 10px 15px;
      margin: 10px 0;
      border-radius: 4px;
      font-weight: bold;
      animation: fadeOut 5s forwards;
      position: relative;
    }
    
    .mensaje-exito {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .mensaje-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    @keyframes fadeOut {
      0% { opacity: 1; }
      70% { opacity: 1; }
      100% { opacity: 0; }
    }
    
    /* Estilos para la sección de observaciones */
    .observaciones-container {
      margin-top: 20px;
      width: 100%;
    }
    
    .observaciones-container h3 {
      margin-bottom: 10px;
      font-size: 1.2rem;
      color: #333;
    }
    
    .observaciones-textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-family: inherit;
      font-size: 1rem;
      resize: vertical;
    }
    
    .observaciones-textarea:focus {
      border-color: #4a90e2;
      outline: none;
      box-shadow: 0 0 5px rgba(74, 144, 226, 0.5);
    }
    
    td {
      position: relative;
    }
  </style>
";
require_once 'includes/header.php';
?>
  <script>
    // Función para marcar el estado de asistencia
    function marcar(usuarioId, estado) {
      // estado: 1 = asiste, 0 = no asiste
      document.getElementById('asist_' + usuarioId).value = estado;
      // Actualizamos la apariencia de los botones
      document.getElementById('btn_asiste_' + usuarioId).classList.remove('selected');
      document.getElementById('btn_no_asiste_' + usuarioId).classList.remove('selected');
      if(estado == 1){
          document.getElementById('btn_asiste_' + usuarioId).classList.add('selected');
      } else {
          document.getElementById('btn_no_asiste_' + usuarioId).classList.add('selected');
      }
    }
    
    // Script para ocultar los mensajes de confirmación y error después de 5 segundos
    document.addEventListener('DOMContentLoaded', function() {
      const mensajes = document.querySelectorAll('.mensaje-exito, .mensaje-error');
      
      mensajes.forEach(function(mensaje) {
        // Añadir botón de cierre
        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.position = 'absolute';
        closeBtn.style.right = '10px';
        closeBtn.style.top = '5px';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.fontSize = '20px';
        closeBtn.style.fontWeight = 'bold';
        closeBtn.onclick = function() {
          mensaje.style.display = 'none';
        };
        mensaje.appendChild(closeBtn);
        
        // Ocultar automáticamente después de 5 segundos
        setTimeout(function() {
          mensaje.style.display = 'none';
        }, 5000);
      });
    });
  </script>
  <script>
    function showTempMessage(text, isError) {
      const msg = document.createElement('div');
      msg.className = isError ? 'mensaje-error' : 'mensaje-exito';
      msg.textContent = text;
      // Cerrar manual
      const closeBtn = document.createElement('span');
      closeBtn.innerHTML = '×';
      closeBtn.style.position = 'absolute';
      closeBtn.style.right = '10px';
      closeBtn.style.top = '5px';
      closeBtn.style.cursor = 'pointer';
      closeBtn.style.fontSize = '20px';
      closeBtn.style.fontWeight = 'bold';
      closeBtn.onclick = function() { msg.remove(); };
      msg.appendChild(closeBtn);
      // Insertar arriba del contenido
      const container = document.querySelector('.content-container') || document.body;
      container.insertBefore(msg, container.firstChild);
      // Autocierre a los 5s
      setTimeout(() => { msg.remove(); }, 5000);
    }

    function copyActivityLink() {
      try {
        const actividadId = '<?php echo htmlspecialchars($actividad_id); ?>';
        const absolute = new URL('asistencia.php?actividad_id=' + encodeURIComponent(actividadId), window.location.href).toString();
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(absolute).then(() => {
            showTempMessage('Enlace copiado');
          }).catch(() => {
            fallbackCopyText(absolute);
          });
        } else {
          fallbackCopyText(absolute);
        }
      } catch (e) {
        showTempMessage('No se pudo copiar el enlace', true);
      }
    }

    function fallbackCopyText(text) {
      const input = document.createElement('input');
      input.value = text;
      document.body.appendChild(input);
      input.select();
      input.setSelectionRange(0, 99999);
      let ok = false;
      try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
      document.body.removeChild(input);
      if (ok) {
        showTempMessage('Enlace copiado');
      } else {
        showTempMessage('No se pudo copiar el enlace', true);
      }
    }
  </script>
  
  <button class="menu-button" onclick="showModal()">Volver a...</button>
  
  <div class="modal-backdrop" id="menuModal">
    <div class="modal">
      <button class="modal-close" onclick="hideModal()">×</button>
      <h3>Navegación</h3>
      <ul class="nav-list">
        <li class="nav-item" onclick="window.location='index.php'">Inicio</li>
        <li class="nav-item" onclick="window.location='instalaciones.php'">Instalaciones</li>
        <li class="nav-item" onclick="window.location='actividades.php?instalacion_id=<?php echo $actividad['instalacion_id']; ?>'">Actividades</li>
      </ul>
    </div>
  </div>

  <script>
    function showModal() {
      const modal = document.getElementById('menuModal');
      modal.style.display = 'flex';
      // Forzar un reflow para que la transición funcione
      modal.offsetHeight;
      modal.classList.add('show');
    }

    function hideModal() {
      const modal = document.getElementById('menuModal');
      modal.classList.remove('show');
      // Esperar a que termine la transición antes de ocultar
      setTimeout(() => {
        modal.style.display = 'none';
      }, 300);
    }

    // Cerrar modal al hacer clic fuera
    document.getElementById('menuModal').addEventListener('click', function(e) {
      if (e.target === this) {
        hideModal();
      }
    });
  </script>

  <div class="content-wrapper">
    <div class="content-container">
      <div class="actions-row">
        <button type="button" class="btn-primary btn-action" onclick="copyActivityLink()" aria-label="Copiar enlace a actividad">
          <i class="fas fa-link"></i>
          Copiar enlace a actividad
        </button>
        <button type="button" class="btn-outline btn-action" disabled title="Próximamente disponible">
          <i class="fas fa-heart"></i>
          Añadir a favoritos
        </button>
      </div>
      <h1>Pasa lista, que yo vigilo</h1>
      <div class="breadcrumbs">
        <a href="instalaciones.php"><?php echo htmlspecialchars(html_entity_decode($actividad['centro_nombre'])); ?></a>
        <span class="separator">›</span>
        <a href="actividades.php?instalacion_id=<?php echo htmlspecialchars($actividad['instalacion_id']); ?>"><?php echo htmlspecialchars(html_entity_decode($actividad['instalacion_nombre'])); ?></a>
        <span class="separator">›</span>
        <span class="current"><?php echo htmlspecialchars(html_entity_decode($actividad['nombre'])); ?></span>
      </div>
      
      <?php 
      // Mostrar mensaje de éxito si existe
      if (isset($_SESSION['mensaje_exito'])) {
          echo '<div class="mensaje-exito">' . htmlspecialchars($_SESSION['mensaje_exito']) . '</div>';
          unset($_SESSION['mensaje_exito']); // Eliminar el mensaje después de mostrarlo
      }
      
      // Mostrar mensaje de error si existe
      if (isset($_SESSION['mensaje_error'])) {
          echo '<div class="mensaje-error">' . htmlspecialchars($_SESSION['mensaje_error']) . '</div>';
          unset($_SESSION['mensaje_error']); // Eliminar el mensaje después de mostrarlo
      }
      ?>
      
      <div class="date-container">
        <span class="date-display">
            <i class="fas fa-calendar"></i>
            Fecha de asistencia
        </span>
        <form method="get" class="date-picker-form">
            <input type="hidden" name="actividad_id" value="<?php echo $actividad_id; ?>">
            <input type="date" 
                   name="fecha" 
                   value="<?php echo $fecha_seleccionada; ?>" 
                   max="<?php echo date('Y-m-d'); ?>"
                   onchange="this.form.submit()"
                   aria-label="Seleccionar fecha">
        </form>
      </div>
      <form action="registrar_asistencia.php" method="post">
        <input type="hidden" name="actividad_id" value="<?php echo $actividad_id; ?>">
        <input type="hidden" name="fecha" value="<?php echo $fecha_seleccionada; ?>">
        <table class="attendance-table">
          <thead>
            <tr>
              <th>Apellido, Nombre</th>
              <th>Asistencia</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($usuarios as $usuario): 
                $estado = isset($asistencias[$usuario['id']]) ? $asistencias[$usuario['id']] : 0;
            ?>
            <tr id="row_<?php echo $usuario['id']; ?>" class="attendance-row">
              <td><?php echo htmlspecialchars($usuario['apellidos'] . ", " . $usuario['nombre']); ?></td>
              <td>
                <input type="hidden" id="asist_<?php echo $usuario['id']; ?>" 
                       name="asistencias[<?php echo $usuario['id']; ?>]" 
                       value="<?php echo $estado; ?>">
                <div class="attendance-buttons">
                  <button type="button" 
                          id="btn_asiste_<?php echo $usuario['id']; ?>" 
                          onclick="marcar(<?php echo $usuario['id']; ?>, 1)" 
                          class="btn-attendance asiste <?php echo $estado==1 ? 'selected' : ''; ?>">
                    <span>Presente</span>
                  </button>
                  <button type="button" 
                          id="btn_no_asiste_<?php echo $usuario['id']; ?>" 
                          onclick="marcar(<?php echo $usuario['id']; ?>, 0)" 
                          class="btn-attendance no-asiste <?php echo $estado==0 ? 'selected' : ''; ?>">
                    <span>Ausente</span>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <br>
        
        <!-- Sección de Observaciones -->
        <div class="observaciones-container">
          <h3>Observaciones de la sesión</h3>
          <textarea name="observaciones" rows="4" class="observaciones-textarea" placeholder="Escribe aquí las observaciones de esta sesión..."><?php 
            // Consultar si hay observaciones para esta fecha y actividad
            $stmtObs = $pdo->prepare("SELECT observacion FROM observaciones WHERE actividad_id = ? AND fecha = ?");
            $stmtObs->execute([$actividad_id, $fecha_seleccionada]);
            $observacion = $stmtObs->fetchColumn();
            echo htmlspecialchars($observacion ?? '');
          ?></textarea>
        </div>
        <br>
        
        <button type="submit" class="confirm-attendance-button">Confirmar Asistencias</button>
      </form>
    </div>

    <!-- Enlace para añadir inscritos eliminado para evitar modificaciones desde esta vista -->
  </div>

  <?php require_once 'includes/footer.php'; ?>
