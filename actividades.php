<?php
require_once 'config/config.php';

// Verifica que se haya autenticado el centro
if(!isset($_SESSION['centro_id'])){
    header("Location: index.php");
    exit;
}

// Se espera recibir el id de la instalación por GET
if(!isset($_GET['instalacion_id'])){
    header("Location: instalaciones.php");
    exit;
}
$instalacion_id = $_GET['instalacion_id'];

// Consultamos los datos de la instalación y el centro
$stmt_instalacion = $pdo->prepare("SELECT i.nombre as instalacion_nombre, c.nombre as centro_nombre 
                                  FROM instalaciones i 
                                  JOIN centros c ON i.centro_id = c.id 
                                  WHERE i.id = ?");
$stmt_instalacion->execute([$instalacion_id]);
$info = $stmt_instalacion->fetch(PDO::FETCH_ASSOC);

// Consultamos las actividades de la instalación
$stmt = $pdo->prepare("
    SELECT * FROM actividades 
    WHERE instalacion_id = ? 
    ORDER BY 
        CASE 
            WHEN fecha_fin IS NULL OR fecha_fin >= CURRENT_DATE THEN 0 
            ELSE 1 
        END,
        fecha_inicio DESC
");
$stmt->execute([$instalacion_id]);
$todas_actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separar actividades activas y finalizadas
$actividades_activas = [];
$actividades_finalizadas = [];

foreach ($todas_actividades as $actividad) {
    // Si no tiene fecha de fin o la fecha de fin es mayor o igual a hoy, está activa
    if (empty($actividad['fecha_fin']) || strtotime($actividad['fecha_fin']) >= strtotime(date('Y-m-d'))) {
        $actividades_activas[] = $actividad;
    } else {
        $actividades_finalizadas[] = $actividad;
    }
}

$pageTitle = "Actividades";
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

    function showOptionsModal(actividadId) {
      const modal = document.getElementById('optionsModal-' + actividadId);
      modal.style.display = 'flex';
      modal.offsetHeight;
      modal.classList.add('show');
    }

    function hideOptionsModal(actividadId) {
      const modal = document.getElementById('optionsModal-' + actividadId);
      modal.classList.remove('show');
      setTimeout(() => {
        modal.style.display = 'none';
      }, 300);
    }

    // Cerrar modal al hacer clic fuera
    document.querySelectorAll('[id^="optionsModal-"]').forEach(modal => {
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          hideOptionsModal(this.id.split('-')[1]);
        }
      });
    });
  </script>

  <div class="content-wrapper">
    <div class="content-container">
      <h1>¡Vamos a ver qué se hace por aquí!</h1>
      <div class="breadcrumbs">
        <a href="instalaciones.php"><?php echo htmlspecialchars($info['centro_nombre']); ?></a>
        <span class="separator">›</span>
        <span class="current"><?php echo htmlspecialchars($info['instalacion_nombre']); ?></span>
      </div>
      
      <!-- Actividades Activas -->
      <h2 class="section-title">Actividades Activas</h2>
      <?php if (empty($actividades_activas)): ?>
        <p class="empty-message">No hay actividades activas en esta instalación.</p>
      <?php else: ?>
        <ul class="list-container">
          <?php foreach($actividades_activas as $actividad): ?>
            <li class="list-item">
              <div class="item-title-container">
                <a href="asistencia.php?actividad_id=<?php echo $actividad['id']; ?>">
                  <div class="activity-card">
                    <div class="activity-name">
                      <i class="fas fa-play"></i>
                      <span><?php echo htmlspecialchars($actividad['nombre']); ?></span>
                    </div>
                    <div class="activity-schedule">
                      <i class="fas fa-clock"></i>
                      <span>
                        <?php 
                        if (!empty($actividad['dias_semana'])) {
                            echo htmlspecialchars($actividad['dias_semana']);
                            if (!empty($actividad['hora_inicio']) && !empty($actividad['hora_fin'])) {
                                echo ' ' . htmlspecialchars($actividad['hora_inicio']) . ' - ' . htmlspecialchars($actividad['hora_fin']);
                            }
                        } else {
                            echo htmlspecialchars($actividad['horario']);
                        }
                        ?>
                      </span>
                    </div>
                    <div class="activity-dates">
                      <i class="fas fa-calendar-alt"></i>
                      <span>
                        Desde: <?php echo date('d/m/Y', strtotime($actividad['fecha_inicio'])); ?>
                        <?php if (!empty($actividad['fecha_fin'])): ?>
                          - Hasta: <?php echo date('d/m/Y', strtotime($actividad['fecha_fin'])); ?>
                        <?php endif; ?>
                      </span>
                    </div>
                  </div>
                </a>
                <div class="item-actions">
                  <button class="options-button" onclick="showOptionsModal(<?php echo $actividad['id']; ?>)">
                    <i class="fas fa-ellipsis-h"></i>
                  </button>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      
      <!-- Actividades Finalizadas -->
      <?php if (!empty($actividades_finalizadas)): ?>
        <h2 class="section-title">Actividades Finalizadas</h2>
        <ul class="list-container finished-activities">
          <?php foreach($actividades_finalizadas as $actividad): ?>
            <li class="list-item">
              <div class="item-title-container">
                <a href="asistencia.php?actividad_id=<?php echo $actividad['id']; ?>">
                  <div class="activity-card">
                    <div class="activity-name">
                      <i class="fas fa-check-circle"></i>
                      <span><?php echo htmlspecialchars($actividad['nombre']); ?></span>
                    </div>
                    <div class="activity-schedule">
                      <i class="fas fa-clock"></i>
                      <span>
                        <?php 
                        if (!empty($actividad['dias_semana'])) {
                            echo htmlspecialchars($actividad['dias_semana']);
                            if (!empty($actividad['hora_inicio']) && !empty($actividad['hora_fin'])) {
                                echo ' ' . htmlspecialchars($actividad['hora_inicio']) . ' - ' . htmlspecialchars($actividad['hora_fin']);
                            }
                        } else {
                            echo htmlspecialchars($actividad['horario']);
                        }
                        ?>
                      </span>
                    </div>
                    <div class="activity-dates">
                      <i class="fas fa-calendar-check"></i>
                      <span>
                        Desde: <?php echo date('d/m/Y', strtotime($actividad['fecha_inicio'])); ?>
                        - Hasta: <?php echo date('d/m/Y', strtotime($actividad['fecha_fin'])); ?>
                      </span>
                    </div>
                  </div>
                </a>
                <div class="item-actions">
                  <button class="options-button" onclick="showOptionsModal(<?php echo $actividad['id']; ?>)">
                    <i class="fas fa-ellipsis-h"></i>
                  </button>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    
    <a href="crear_actividad.php?instalacion_id=<?php echo $instalacion_id; ?>" class="button btn-outline btn-rounded">
        <i class="fas fa-plus"></i> Crear Nueva Actividad
    </a>
  </div>

  <!-- Modales de opciones (fuera del list-container) -->
  <?php foreach($todas_actividades as $actividad): ?>
    <div class="modal-backdrop options-modal" id="optionsModal-<?php echo $actividad['id']; ?>">
        <div class="modal">
            <button class="modal-close" onclick="hideOptionsModal(<?php echo $actividad['id']; ?>)">×</button>
            <h3>Opciones de la actividad</h3>
            <div class="modal-options">
                <a href="editar_actividad.php?id=<?php echo $actividad['id']; ?>" class="button btn-edit btn-sm">
                    <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a href="borrar_actividad.php?id=<?php echo $actividad['id']; ?>" class="button btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que quieres borrar esta actividad?');">
                    <i class="fas fa-times"></i> Borrar
                </a>
            </div>
        </div>
    </div>
  <?php endforeach; ?>
<?php require_once 'includes/footer.php'; ?>
