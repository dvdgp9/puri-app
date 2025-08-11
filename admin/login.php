<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrador - Sistema Puri</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-user-shield"></i> Panel de Administración</h1>
        <span class="subtitle">Acceso para administradores del sistema</span>
        
        <?php
        session_start();
        if (isset($_SESSION['admin_error'])) {
            echo '<div class="error-message">' . $_SESSION['admin_error'] . '</div>';
            unset($_SESSION['admin_error']);
        }
        ?>
        
        <form method="POST" action="process_login.php">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Usuario
                </label>
                <input type="text" id="username" name="username" required 
                       placeholder="Ingrese su nombre de usuario">
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Contraseña
                </label>
                <input type="password" id="password" name="password" required 
                       placeholder="Ingrese su contraseña">
            </div>
            
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="../index.php" style="color: var(--primary-color); text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Volver al sistema principal
            </a>
        </div>
    </div>
</body>
</html>
