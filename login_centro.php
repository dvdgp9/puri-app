<?php
require_once 'config/config.php';

if(isset($_POST['centro_id']) && isset($_POST['password'])){
    $centro_id = $_POST['centro_id'];
    $password = $_POST['password'];
    
    // Consulta el centro
    $stmt = $pdo->prepare("SELECT * FROM centros WHERE id = ?");
    $stmt->execute([$centro_id]);
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Validación simple de contraseña (en producción, considerar hashing)
    if($centro && $centro['password'] === $password){
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
    header("Location: index.php");
    exit;
}
?>