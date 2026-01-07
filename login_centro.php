<?php
require_once 'config/config.php';

if(isset($_POST['centro_id']) && isset($_POST['password'])){
    $centro_id = $_POST['centro_id'];
    $password = $_POST['password'];
    
    // Consulta el centro
    $stmt = $pdo->prepare("SELECT * FROM centros WHERE id = ?");
    $stmt->execute([$centro_id]);
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Validación de contraseña (soporta hash y texto plano para compatibilidad)
    if($centro){
        $is_valid = false;
        
        // Primero intentamos verificar como hash (nuevo sistema)
        if (password_verify($password, $centro['password'])) {
            $is_valid = true;
        } 
        // Si falla, intentamos comparación directa (sistema antiguo)
        else if ($centro['password'] === $password) {
            $is_valid = true;
        }

        if ($is_valid) {
            $_SESSION['centro_id'] = $centro_id;
            // Redirección segura a return_to si existe y es relativa (misma app)
            $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : '';
            if ($returnTo && !preg_match('/^https?:\/\//i', $returnTo)) {
                header("Location: " . $returnTo);
            } else {
                header("Location: instalaciones.php");
            }
            exit;
        } else {
            $_SESSION['error'] = "Credenciales incorrectas.";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Credenciales incorrectas.";
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>