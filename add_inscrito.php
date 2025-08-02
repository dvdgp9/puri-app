<?php
require_once 'config/config.php';

if(!isset($_POST['actividad_id'], $_POST['nombre'], $_POST['apellido'], $_POST['dni'], $_POST['telefono'])){
    header("Location: asistencia.php?actividad_id=".$_POST['actividad_id']);
    exit;
}

$actividad_id = $_POST['actividad_id'];
$nombre = $_POST['nombre'];
$apellidos = $_POST['apellido'];
$dni = $_POST['dni'];
$telefono = $_POST['telefono'];

// Insertamos directamente en la tabla inscritos
$stmt = $pdo->prepare("INSERT INTO inscritos (nombre, apellidos, dni, telefono, actividad_id) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$nombre, $apellidos, $dni, $telefono, $actividad_id]);

header("Location: asistencia.php?actividad_id=" . $actividad_id);
exit;
?>
