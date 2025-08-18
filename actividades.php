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

// Función para abreviar los días de la semana
function abreviarDias($dias) {
    $abreviaciones = [
        'Lunes' => 'L',
        'Martes' => 'M',
        'Miércoles' => 'X',
        'Jueves' => 'J',
        'Viernes' => 'V',
        'Sábado' => 'S',
        'Domingo' => 'D'
    ];
    
    $diasArray = explode(',', $dias);
    $diasAbreviados = [];
    
    foreach ($diasArray as $dia) {
        $diasAbreviados[] = isset($abreviaciones[$dia]) ? $abreviaciones[$dia] : $dia;
    }
    
    return implode(' | ', $diasAbreviados);
}

// Función para formatear las horas
function formatearHora($hora) {
    if (empty($hora)) return '';
    $horaObj = new DateTime($hora);
    return $horaObj->format('G:i') . 'h';
}

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

// Separar actividades programadas, activas y finalizadas
$actividades_programadas = [];
$actividades_activas = [];
$actividades_finalizadas = [];

foreach ($todas_actividades as $actividad) {
    $hoy = strtotime(date('Y-m-d'));
    $fecha_inicio = strtotime($actividad['fecha_inicio']);
    
    // Si la fecha de inicio es posterior a hoy, está programada
    if ($fecha_inicio > $hoy) {
        $actividades_programadas[] = $actividad;
    }
    // Si no tiene fecha de fin o la fecha de fin es mayor o igual a hoy, está activa
    elseif (empty($actividad['fecha_fin']) || strtotime($actividad['fecha_fin']) >= $hoy) {
        $actividades_activas[] = $actividad;
    } else {
        $actividades_finalizadas[] = $actividad;
    }
}

$pageTitle = "Actividades";
require_once 'includes/header.php';
?>

<script>
// Variables globales para la búsqueda
var instalacionId = <?php echo json_encode($instalacion_id); ?>;
<script>
  // Variables globales para la búsqueda
  var instalacionId = <?php echo json_encode($instalacion_id); ?>;
  </script>
  <script src="public/assets/js/actividades-search.js"></script>
  
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

  </script>

  <div class="content-wrapper">
    <div class="content-container">
      <h1>¡Vamos a ver qué se hace por aquí!</h1>
      <div class="breadcrumbs">
        <a href="instalaciones.php"><?php echo htmlspecialchars(html_entity_decode($info['centro_nombre'])); ?></a>
        <span>/</span>
        <span><?php echo htmlspecialchars(html_entity_decode($info['instalacion_nombre'])); ?></span>
      </div>
      
      <!-- Barra de filtros (rediseño) -->
      <fieldset class="filters-bar">
        <legend>Filtrar</legend>
        <div class="filters-row">
          <div class="filters-group search-group">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input type="text" id="search-input" placeholder="Buscar actividades...">
            </div>
          </div>
          <div class="filters-group sort-group">
            <label for="sort-select">Ordenar por:</label>
            <select id="sort-select">
              <option value="" disabled selected>Seleccionar orden</option>
              <option value="nombre-asc">Nombre (A-Z)</option>
              <option value="nombre-desc">Nombre (Z-A)</option>
              <option value="fecha-asc">Fecha inicio (↑)</option>
              <option value="fecha-desc">Fecha inicio (↓)</option>
            </select>
          </div>
          <div class="filters-group date-group">
            <label for="start-date-from">Inicio desde:</label>
            <input type="date" id="start-date-from">
            <label for="start-date-to">hasta:</label>
            <input type="date" id="start-date-to">
          </div>
          <div class="filters-group days-group">
            <span class="group-label">Días:</span>
            <?php $diasSemana = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
            foreach ($diasSemana as $dia): ?>
              <button type="button" class="chip chip-day" data-day="<?php echo $dia; ?>" aria-pressed="false" title="<?php echo $dia; ?>">
                <?php echo substr($dia,0,1); ?>
              </button>
            <?php endforeach; ?>
          </div>
          <div class="filters-group actions-group">
            <button type="button" id="filters-reset" class="button btn-outline btn-sm">Limpiar filtros</button>
          </div>
        </div>
      </fieldset>
      
      <!-- Mensaje de no resultados -->
      <div id="no-results-message" class="no-results-message" style="display: none;">
        <p>No se encontraron actividades que coincidan con la búsqueda.</p>
      </div>
      
      <!-- Actividades Activas -->
      <h2 class="section-title">Actividades Activas</h2>
      <?php if (empty($actividades_activas)): ?>
        <p class="empty-message">No hay actividades activas en esta instalación.</p>
      <?php else: ?>
        <ul class="list-container">
          <?php foreach($actividades_activas as $actividad): ?>
            <li class="list-item" data-fecha-inicio="<?php echo htmlspecialchars($actividad['fecha_inicio']); ?>" data-dias="<?php echo htmlspecialchars($actividad['dias_semana'] ?? ''); ?>">
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
                            echo abreviarDias(htmlspecialchars($actividad['dias_semana']));
                            if (!empty($actividad['hora_inicio']) && !empty($actividad['hora_fin'])) {
                                echo ' → ' . formatearHora($actividad['hora_inicio']) . ' - ' . formatearHora($actividad['hora_fin']);
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
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      
      <!-- Actividades Programadas -->
      <h2 class="section-title">Actividades Programadas</h2>
      <?php if (empty($actividades_programadas)): ?>
        <p class="empty-message">No hay actividades programadas en esta instalación.</p>
      <?php else: ?>
        <ul class="list-container scheduled-activities">
          <?php foreach($actividades_programadas as $actividad): ?>
            <li class="list-item" data-fecha-inicio="<?php echo htmlspecialchars($actividad['fecha_inicio']); ?>" data-dias="<?php echo htmlspecialchars($actividad['dias_semana'] ?? ''); ?>">
              <div class="item-title-container">
                <a href="asistencia.php?actividad_id=<?php echo $actividad['id']; ?>">
                  <div class="activity-card">
                    <div class="activity-name">
                      <i class="fas fa-calendar-plus"></i>
                      <span><?php echo htmlspecialchars($actividad['nombre']); ?></span>
                    </div>
                    <div class="activity-schedule">
                      <i class="fas fa-clock"></i>
                      <span>
                        <?php 
                        if (!empty($actividad['dias_semana'])) {
                            echo abreviarDias(htmlspecialchars($actividad['dias_semana']));
                            if (!empty($actividad['hora_inicio']) && !empty($actividad['hora_fin'])) {
                                echo ' → ' . formatearHora($actividad['hora_inicio']) . ' - ' . formatearHora($actividad['hora_fin']);
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
            <li class="list-item" data-fecha-inicio="<?php echo htmlspecialchars($actividad['fecha_inicio']); ?>" data-dias="<?php echo htmlspecialchars($actividad['dias_semana'] ?? ''); ?>">
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
                            echo abreviarDias(htmlspecialchars($actividad['dias_semana']));
                            if (!empty($actividad['hora_inicio']) && !empty($actividad['hora_fin'])) {
                                echo ' → ' . formatearHora($actividad['hora_inicio']) . ' - ' . formatearHora($actividad['hora_fin']);
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
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    
  </div>

  <?php require_once 'includes/footer.php'; ?>
