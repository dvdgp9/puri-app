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
        header("Location: instalaciones.php");
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