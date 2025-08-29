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
                <select name="centro_id" id="centro_id" required>
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
<?php require_once 'includes/footer.php'; ?>
