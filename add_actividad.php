<?php
require_once 'config/config.php';

if(!isset($_POST['instalacion_id'], $_POST['nombre'], $_POST['horario'])){
    header("Location: actividades.php");
    exit;
}

$instalacion_id = $_POST['instalacion_id'];
$nombre = $_POST['nombre'];
$horario = $_POST['horario'];

$stmt = $pdo->prepare("INSERT INTO actividades (instalacion_id, nombre, horario) VALUES (?, ?, ?)");
$stmt->execute([$instalacion_id, $nombre, $horario]);

header("Location: actividades.php?instalacion_id=" . $instalacion_id);
exit;
?>
