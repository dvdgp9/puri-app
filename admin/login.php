<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrador - Sistema Puri</title>
    <link rel="stylesheet" href="../public/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-user-shield"></i> Panel de Administración</h1>
                <p>Acceso para administradores del sistema</p>
            </div>
            
            <?php
            session_start();
            if (isset($_SESSION['admin_error'])) {
                echo '<div class="alert alert-error">' . $_SESSION['admin_error'] . '</div>';
                unset($_SESSION['admin_error']);
            }
            ?>
            
            <form method="POST" action="process_login.php" class="login-form">
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
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php" class="link-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al sistema principal
                </a>
            </div>
        </div>
    </div>
    
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .login-form input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .login-form input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
    </style>
</body>
</html>
