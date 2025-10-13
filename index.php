<?php
require_once 'config/config.php';

// Consultamos los centros desde la tabla
$sql = "SELECT id, nombre FROM centros";
$stmt = $pdo->query($sql);
$centros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Sistema de Asistencia";
require_once 'includes/header.php';
?>
    <div class="container home-hero">
        <!-- Logo placeholder, reemplazar src con tu logo real -->
        <img src="public/assets/images/logo.png" alt="Logo" class="logo" onerror="this.src='data:image/svg+xml;charset=UTF-8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'120\' viewBox=\'0 0 120 120\'><rect width=\'120\' height=\'120\' fill=\'%2323AAC5\'/><text x=\'50%\' y=\'50%\' font-size=\'50\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\'>P</text></svg>'">
        <h1>¡Hola! Soy Puri</h1>
        <span class="subtitle">Estoy aquí para ayudarte a controlar quién entra y quién sale</span>
         
        <?php if(isset($_SESSION['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
         
        <form action="login_centro.php" method="post">
            <?php if (isset($_GET['return_to']) && $_GET['return_to'] !== ''): ?>
                <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_GET['return_to']); ?>">
            <?php endif; ?>
             <div class="form-group">
                 <label for="centro_id">
                     <i class="fas fa-building"></i> Lugar de Trabajo
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
                                 <span>Lugar de Trabajo seleccionado:</span>
                                 <strong><?php echo htmlspecialchars($preNombre ?: $preId); ?></strong>
                                 <button type="button" class="btn-ghost btn-action btn-icon-mobile" onclick="enableCentroSelect()" aria-label="cambiar Lugar de Trabajo">
                                     <i class="fas fa-edit" aria-hidden="true"></i>
                                 </button>
                             </span>
                         </div>
                     </div>
                     <input type="hidden" name="centro_id" id="centro_id_hidden" value="<?php echo htmlspecialchars($preId); ?>">
                 <?php endif; ?>
                 <select name="centro_id" id="centro_id" <?php echo $preselected ? 'disabled style="opacity:.6"' : ''; ?>>
                     <option value="">Oye, y tú, ¿de quién eres?</option>
                     <?php foreach ($centros as $centro): ?>
                         <option value="<?php echo htmlspecialchars($centro['id']); ?>" <?php echo ($preselected && (string)$preId === (string)$centro['id']) ? 'selected' : ''; ?>>
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
        <script>
          function enableCentroSelect() {
            const sel = document.getElementById('centro_id');
            const hidden = document.getElementById('centro_id_hidden');
            if (sel) { sel.disabled = false; sel.style.opacity = ''; sel.focus(); }
            if (hidden) { hidden.remove(); }
          }
        </script>
    </div>
<?php require_once 'includes/footer.php'; ?>
