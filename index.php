<?php
require_once 'config/config.php';

// Consultamos los centros desde la tabla
$sql = "SELECT id, nombre FROM centros";
$stmt = $pdo->query($sql);
$centros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Sistema de Asistencia";
$lang = "es";  // Set the language
require_once 'includes/header.php';
?>
    <!-- Header superior con logo y saludo -->
    <div class="top-header">
        <div class="container">
            <div class="header-content">
                <img src="public/assets/images/logo.png" alt="Logo" class="header-logo" onerror="this.src='data:image/svg+xml;charset=UTF-8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\' viewBox=\'0 0 60 60\'><rect width=\'60\' height=\'60\' fill=\'%2323AAC5\'/><text x=\'50%\' y=\'50%\' font-size=\'24\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\'>P</text></svg>'">
                <div class="header-text">
                    <h1>¡Hola! Soy Puri</h1>
                    <span class="subtitle">Estoy aquí para ayudarte a controlar quién entra y quién sale</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de favoritos -->
    <div class="favorites-section" id="favoritesSection" style="display: none;">
        <div class="container">
            <h2>Tus favoritos</h2>
            <div class="favorites-grid" id="favoritesGrid">
                <!-- Los favoritos se cargarán aquí dinámicamente -->
            </div>
        </div>
    </div>

    <div class="container home-hero">
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form action="login_centro.php" method="post">
            <?php if (isset($_GET['return_to']) && $_GET['return_to'] !== ''): ?>
                <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_GET['return_to']); ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="centro_id">
                    <i class="fas fa-building"></i> Centro
                </label>
                <?php
                  $preselected = isset($_GET['centro_id']) && $_GET['centro_id'] !== '';
                  $preId = $preselected ? (string)$_GET['centro_id'] : '';
                  // Encontrar nombre del centro preseleccionado para mostrar en la píldora
                  $preNombre = '';
                  if ($preselected) {
                    foreach ($centros as $c) { if ((string)$c['id'] === $preId) { $preNombre = $c['nombre']; break; } }
                  }
                ?>
                <?php if ($preselected): ?>
                  <div class="confirmed-centro">
                    <div class="pill-container">
                      <span class="pill">
                        <span>Centro seleccionado:</span>
                        <strong><?php echo htmlspecialchars($preNombre ?: $preId); ?></strong>
                        <button type="button" class="btn-ghost btn-action btn-icon-mobile" onclick="enableCentroSelect()" aria-label="Cambiar centro">
                          <i class="fas fa-edit" aria-hidden="true"></i>
                        </button>
                      </span>
                    </div>
                  </div>
                  <input type="hidden" name="centro_id" id="centro_id_hidden" value="<?php echo htmlspecialchars($preId); ?>">
                <?php endif; ?>
                <select name="centro_id" id="centro_id" required <?php echo $preselected ? 'disabled' : ''; ?>>
                    <option value="">Oye, y tú, ¿de quién eres?</option>
                    <?php foreach($centros as $centro): ?>
                        <option value="<?php echo $centro['id']; ?>" <?php echo (isset($_GET['centro_id']) && (string)$_GET['centro_id'] === (string)$centro['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($centro['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Contraseña
                </label>
                <input type="password" name="password" id="password" required autofocus>
            </div>

            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Acceder
            </button>
        </form>
    </div>

    <!-- Modal de acceso a favorito -->
    <div class="modal-overlay" id="favoritoModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Acceder a actividad</h3>
                <button type="button" class="modal-close" onclick="closeFavoritoModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="favorito-info" id="favoritoInfo">
                    <!-- Info de la actividad se cargará aquí -->
                </div>
                <form id="favoritoForm">
                    <div class="form-group">
                        <label for="favoritoPassword">
                            <i class="fas fa-lock"></i> Contraseña del centro
                        </label>
                        <input type="password" id="favoritoPassword" required>
                    </div>
                    <button type="submit" class="btn-primary btn-action">
                        <i class="fas fa-sign-in-alt"></i>
                        Acceder
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
      // Device ID único por navegador
      function getDeviceId() {
        let deviceId = localStorage.getItem('puri_device_id');
        if (!deviceId) {
          deviceId = 'device_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
          localStorage.setItem('puri_device_id', deviceId);
        }
        return deviceId;
      }

      // Cargar favoritos al iniciar
      document.addEventListener('DOMContentLoaded', function() {
        loadFavoritos();
      });

      async function loadFavoritos() {
        try {
          const response = await fetch('ajax/favoritos.php?action=list&device_id=' + encodeURIComponent(getDeviceId()));
          const data = await response.json();
          
          if (data.success && data.favoritos.length > 0) {
            renderFavoritos(data.favoritos);
            document.getElementById('favoritesSection').style.display = 'block';
          }
        } catch (error) {
          console.error('Error cargando favoritos:', error);
        }
      }

      function renderFavoritos(favoritos) {
        const grid = document.getElementById('favoritesGrid');
        grid.innerHTML = favoritos.map(fav => `
          <div class="favorito-card" onclick="openFavoritoModal(${fav.actividad_id}, '${fav.centro_nombre}', '${fav.instalacion_nombre}', '${fav.actividad_nombre}', ${fav.centro_id})">
            <div class="favorito-header">
              <h3>${fav.actividad_nombre}</h3>
              <button type="button" class="btn-ghost btn-action" onclick="event.stopPropagation(); removeFavorito(${fav.actividad_id})" aria-label="Quitar de favoritos">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <div class="favorito-details">
              <p><i class="fas fa-building"></i> ${fav.centro_nombre}</p>
              <p><i class="fas fa-map-marker-alt"></i> ${fav.instalacion_nombre}</p>
            </div>
            <button type="button" class="btn-primary btn-action">
              <i class="fas fa-play"></i>
              Continuar
            </button>
          </div>
        `).join('');
      }

      function openFavoritoModal(actividadId, centroNombre, instalacionNombre, actividadNombre, centroId) {
        document.getElementById('favoritoInfo').innerHTML = `
          <div class="activity-info">
            <h4>${actividadNombre}</h4>
            <p><i class="fas fa-building"></i> ${centroNombre}</p>
            <p><i class="fas fa-map-marker-alt"></i> ${instalacionNombre}</p>
          </div>
        `;
        
        document.getElementById('favoritoForm').onsubmit = function(e) {
          e.preventDefault();
          const password = document.getElementById('favoritoPassword').value;
          accessFavorito(actividadId, centroId, password);
        };
        
        document.getElementById('favoritoModal').classList.add('show');
        document.getElementById('favoritoPassword').focus();
      }

      function closeFavoritoModal() {
        document.getElementById('favoritoModal').classList.remove('show');
        document.getElementById('favoritoPassword').value = '';
      }

      async function accessFavorito(actividadId, centroId, password) {
        try {
          const formData = new FormData();
          formData.append('centro_id', centroId);
          formData.append('password', password);
          
          const response = await fetch('login_centro.php', {
            method: 'POST',
            body: formData
          });
          
          if (response.ok) {
            // Login exitoso, redirigir a asistencia
            window.location.href = `asistencia.php?actividad_id=${actividadId}`;
          } else {
            showToast('Contraseña incorrecta', 'error');
          }
        } catch (error) {
          showToast('Error al acceder', 'error');
        }
      }

      async function removeFavorito(actividadId) {
        try {
          const response = await fetch('ajax/favoritos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'remove',
              device_id: getDeviceId(),
              actividad_id: actividadId
            })
          });
          
          const data = await response.json();
          if (data.success) {
            showToast('Favorito eliminado', 'success');
            loadFavoritos();
          }
        } catch (error) {
          showToast('Error al eliminar favorito', 'error');
        }
      }

      function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `mensaje-${type === 'error' ? 'error' : 'exito'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
      }

      function enableCentroSelect() {
        const sel = document.getElementById('centro_id');
        const hidden = document.getElementById('centro_id_hidden');
        if (sel) { sel.disabled = false; sel.style.opacity = ''; sel.focus(); }
        if (hidden) { hidden.parentNode.removeChild(hidden); }
      }

      // Cerrar modal al hacer clic fuera
      document.getElementById('favoritoModal').addEventListener('click', function(e) {
        if (e.target === this) {
          closeFavoritoModal();
        }
      });
    </script>
<?php require_once 'includes/footer.php'; ?>
