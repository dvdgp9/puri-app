<?php
require_once 'config/config.php';

// Verifica que se haya autenticado el centro
if(!isset($_SESSION['centro_id'])){
    header("Location: index.php");
    exit;
}

$centro_id = $_SESSION['centro_id'];

// Obtener el nombre del centro
$stmt_centro = $pdo->prepare("SELECT nombre FROM centros WHERE id = ?");
$stmt_centro->execute([$centro_id]);
$centro = $stmt_centro->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM instalaciones WHERE centro_id = ?");
$stmt->execute([$centro_id]);
$instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set the page title and include the header.
$pageTitle = "Instalaciones";
require_once 'includes/header.php';
?>

  <button class="menu-button" onclick="showModal()">Volver a...</button>
  
  <div class="modal-backdrop" id="menuModal">
    <div class="modal">
      <button class="modal-close" onclick="hideModal()">×</button>
      <h3>Navegación</h3>
      <ul class="nav-list">
        <li class="nav-item" onclick="window.location='index.php'">Inicio</li>
      </ul>
    </div>
  </div>

  <div class="content-wrapper">
    <div class="content-container">
      <h1>Mira, mira, ¡qué instalaciones tan apañadas!</h1>
      <span class="item-title center-name"><?php echo html_entity_decode(htmlspecialchars($centro['nombre'])); ?></span>
      
      <!-- Barra de búsqueda y ordenación -->
      <div class="search-sort-container">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" id="search-input" placeholder="Buscar instalaciones...">
        </div>
        <div class="sort-box">
          <label for="sort-select">Ordenar por:</label>
          <select id="sort-select">
            <option value="nombre-asc">Nombre (A-Z)</option>
            <option value="nombre-desc">Nombre (Z-A)</option>
          </select>
        </div>
      </div>
      
      <!-- Mensaje de no resultados -->
      <div id="no-results-message" class="no-results-message" style="display: none;">
        <p>No se encontraron instalaciones que coincidan con la búsqueda.</p>
      </div>
      
      <ul class="list-container">
        <?php foreach($instalaciones as $instalacion): ?>
          <li class="list-item">
            <div class="item-title-container">
              <a href="actividades.php?instalacion_id=<?php echo $instalacion['id']; ?>">
                <span class="item-title"><?php echo html_entity_decode(htmlspecialchars($instalacion['nombre'])); ?></span>
              </a>
              <div class="item-actions">
                <button class="options-button" onclick="showOptionsModal(<?php echo $instalacion['id']; ?>)">
                  <i class="fas fa-ellipsis-h"></i>
                </button>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    
    <a href="crear_instalacion.php?centro_id=<?php echo $centro_id; ?>" class="button btn-outline btn-rounded">
        <i class="fas fa-plus"></i> Crear Nueva Instalación
    </a>
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

    function showOptionsModal(instalacionId) {
      const modal = document.getElementById('optionsModal-' + instalacionId);
      modal.style.display = 'flex';
      modal.offsetHeight;
      modal.classList.add('show');
    }

    function hideOptionsModal(instalacionId) {
      const modal = document.getElementById('optionsModal-' + instalacionId);
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

  <!-- Modales de opciones (fuera del list-container) -->
  <?php foreach($instalaciones as $instalacion): ?>
    <div class="modal-backdrop options-modal" id="optionsModal-<?php echo $instalacion['id']; ?>">
      <div class="modal">
          <button class="modal-close" onclick="hideOptionsModal(<?php echo $instalacion['id']; ?>)">×</button>
          <h3>Opciones de la instalación</h3>
          <div class="modal-options">
              <a href="editar_instalacion.php?id=<?php echo $instalacion['id']; ?>" class="edit-button">
                  <i class="fas fa-pencil-alt"></i> Editar
              </a>
              <a href="borrar_instalacion.php?id=<?php echo $instalacion['id']; ?>" class="delete-button" onclick="return confirm('¿Estás seguro? Se borrarán también sus actividades.');">
                  <i class="fas fa-times"></i> Borrar
              </a>
          </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php require_once 'includes/footer.php'; ?>
