<?php
require_once 'config/config.php';

// Verifica sesión y parámetro
if(!isset($_SESSION['centro_id'])){
    header("Location: index.php");
    exit;
}
if(!isset($_GET['actividad_id'])){
    header("Location: actividades.php");
    exit;
}
$actividad_id = $_GET['actividad_id'];

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
    
    /* Estilos para el botón de eliminar */
    .delete-btn {
      color: red;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1.2rem;
      padding: 5px;
      transition: transform 0.2s;
    }
    .delete-btn:hover {
      transform: scale(1.2);
    }
    
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
    
    /* Estilos para swipe-to-delete en móvil */
    .attendance-row {
      position: relative;
      transition: transform 0.2s ease; /* Faster transition for better responsiveness */
      touch-action: pan-y;
      width: 100%;
      display: table-row;
    }
    
    .delete-action {
      position: absolute;
      right: -80px;
      top: 0;
      bottom: 0;
      width: 80px;
      background-color: red;
      color: white;
      display: flex; /* Always display but will be off-screen */
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      z-index: 1;
    }
    
    /* Mostrar el botón de eliminar en móvil cuando se hace swipe */
    @media (max-width: 768px) {
      .delete-btn {
        display: none;
      }
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
    
    /* Asegurar que no hay transformación en desktop */
    @media (min-width: 769px) {
      .attendance-row {
        transform: none !important;
      }
      
      .delete-action {
        display: none;
      }
    }
    
    td {
      position: relative;
    }
    
    .swiped {
      transform: translateX(-80px);
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
    
    // Función para eliminar un inscrito
    function eliminarInscrito(inscritoId) {
      if (confirm('¿Estás seguro de que deseas eliminar este inscrito de esta actividad? Esta acción no se puede deshacer y solo afectará a la participación en esta actividad.')) {
        const formData = new FormData();
        formData.append('inscrito_id', inscritoId);
        formData.append('actividad_id', <?php echo $actividad_id; ?>);
        
        fetch('eliminar_inscrito.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Eliminar la fila de la tabla
            const row = document.getElementById('row_' + inscritoId);
            row.parentNode.removeChild(row);
            
            // Mostrar mensaje de éxito
            alert(data.message);
          } else {
            // Mostrar mensaje de error
            alert(data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error al eliminar el inscrito.');
        });
      }
    }
    
    // Variables para el swipe
    let touchStartX = 0;
    let touchEndX = 0;
    let currentSwipedRow = null;
    let currentTouchingRow = null;
    
    // Inicializar eventos de swipe cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
      // Obtener todas las filas de asistencia
      const rows = document.querySelectorAll('.attendance-row');
      
      // Añadir eventos de touch a cada fila
      rows.forEach(row => {
        row.addEventListener('touchstart', function(e) {
          touchStartX = e.changedTouches[0].screenX;
          currentTouchingRow = this;
          
          // Si hay una fila con swipe activo que no es esta, resetearla
          if (currentSwipedRow && currentSwipedRow !== this) {
            currentSwipedRow.classList.remove('swiped');
            currentSwipedRow = null;
          }
        }, { passive: true });
        
        row.addEventListener('touchmove', function(e) {
          if (currentTouchingRow !== this) return;
          
          const touchMoveX = e.changedTouches[0].screenX;
          const swipeDistance = touchStartX - touchMoveX;
          
          // Limitar el desplazamiento entre 0 y 80px
          let translateX = Math.min(Math.max(swipeDistance, 0), 80);
          
          // Aplicar la transformación en tiempo real durante el swipe
          this.style.transform = `translateX(-${translateX}px)`;
          
          // Prevenir el scroll vertical si estamos haciendo swipe horizontal
          if (Math.abs(swipeDistance) > 10) {
            e.preventDefault();
          }
        }, { passive: false });
        
        row.addEventListener('touchend', function(e) {
          if (currentTouchingRow !== this) return;
          
          touchEndX = e.changedTouches[0].screenX;
          const swipeDistance = touchStartX - touchEndX;
          
          // Resetear el estilo inline para usar las clases CSS
          this.style.transform = '';
          
          handleSwipe(this, swipeDistance);
          currentTouchingRow = null;
        }, { passive: true });
        
        row.addEventListener('touchcancel', function(e) {
          if (currentTouchingRow !== this) return;
          
          // Resetear el estilo inline
          this.style.transform = '';
          currentTouchingRow = null;
          
          touchEndX = e.changedTouches[0].screenX;
          handleSwipe(this, touchStartX - touchEndX);
        }, { passive: true });
      });
      
      // Añadir evento para cerrar swipe al hacer clic en cualquier parte
      document.addEventListener('click', function(e) {
        if (currentSwipedRow && !currentSwipedRow.contains(e.target) && !e.target.closest('.delete-action')) {
          currentSwipedRow.classList.remove('swiped');
          currentSwipedRow = null;
        }
      });
      
      // Añadir evento de resize para resetear filas desplazadas al cambiar a desktop
      window.addEventListener('resize', function() {
        if (window.innerWidth >= 769) {
          // Si estamos en desktop, resetear cualquier fila desplazada
          if (currentSwipedRow) {
            currentSwipedRow.classList.remove('swiped');
            currentSwipedRow = null;
          }
          
          // También resetear cualquier transformación inline
          rows.forEach(row => {
            row.style.transform = '';
          });
        }
      });
    });
    
    // Función para manejar el swipe
    function handleSwipe(row, swipeDistance) {
      const swipeThreshold = 40; // Umbral mínimo para considerar un swipe (reducido para mejor respuesta)
      
      if (swipeDistance > swipeThreshold) {
        // Swipe hacia la izquierda
        row.classList.add('swiped');
        currentSwipedRow = row;
      } else if (swipeDistance < -swipeThreshold) {
        // Swipe hacia la derecha (cerrar)
        row.classList.remove('swiped');
        currentSwipedRow = null;
      } else {
        // Si el swipe no fue suficiente, volver al estado anterior
        if (currentSwipedRow === row) {
          row.classList.add('swiped');
        } else {
          row.classList.remove('swiped');
        }
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
      <h1>Pasa lista, que yo vigilo</h1>
      <div class="breadcrumbs">
        <a href="instalaciones.php"><?php echo htmlspecialchars($actividad['centro_nombre']); ?></a>
        <span class="separator">›</span>
        <a href="actividades.php?instalacion_id=<?php echo htmlspecialchars($actividad['instalacion_id']); ?>"><?php echo htmlspecialchars($actividad['instalacion_nombre']); ?></a>
        <span class="separator">›</span>
        <span class="current"><?php echo htmlspecialchars($actividad['nombre']); ?></span>
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
              <th></th> <!-- Columna para el botón de eliminar -->
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
              <td>
                <button type="button" class="delete-btn" onclick="eliminarInscrito(<?php echo $usuario['id']; ?>)">
                  <i class="fas fa-trash-alt"></i>
                </button>
                <!-- Elemento para swipe-to-delete en móvil -->
                <div class="delete-action" onclick="eliminarInscrito(<?php echo $usuario['id']; ?>)">
                  <i class="fas fa-trash-alt"></i>
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

    <a href="crear_inscrito.php?actividad_id=<?php echo $actividad_id; ?>" class="button btn-outline btn-rounded">
      <i class="fas fa-plus"></i> Añadir Inscrita/o
    </a>
  </div>

  <!-- Barra inferior fija -->
  <div class="bottom-bar">
    <div class="excel-buttons">
      <a href="public/assets/plantilla-asistentes.csv" class="excel-button" download>
        <i class="fas fa-download"></i> Descargar plantilla CSV
      </a>
      <label class="excel-button">
        <i class="fas fa-upload"></i> Subir archivo CSV
        <input type="file" id="excelFile" accept=".csv" style="display: none;" onchange="subirExcel(this)">
      </label>
    </div>
  </div>

  <script>
    function subirExcel(input) {
      if (input.files.length > 0) {
        // Verificar que sea un archivo CSV
        if (!input.files[0].name.toLowerCase().endsWith('.csv')) {
          alert('Por favor, selecciona un archivo CSV');
          return;
        }

        const formData = new FormData();
        formData.append('excel', input.files[0]);
        formData.append('actividad_id', <?php echo $actividad_id; ?>);

        fetch('procesar_excel.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          // Siempre mostrar el mensaje del servidor
          alert(data.message);
          
          // Solo recargar si hubo importaciones exitosas
          if (data.imported > 0) {
            window.location.reload();
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error al procesar el archivo. Por favor, verifica el formato y contenido del archivo CSV.');
        });

        // Limpiar el input file después de la subida
        input.value = '';
      }
    }
  </script>
<?php require_once 'includes/footer.php'; ?>
