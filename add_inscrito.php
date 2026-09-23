<?php
require_once 'config/config.php';
require_once 'includes/roster.php';

if(!isset($_POST['actividad_id'], $_POST['nombre'], $_POST['apellido'], $_POST['dni'], $_POST['telefono'])){
    header("Location: asistencia.php?actividad_id=".$_POST['actividad_id']);
    exit;
}

$actividad_id = $_POST['actividad_id'];
$nombre = $_POST['nombre'];
$apellidos = $_POST['apellido'];
$dni = $_POST['dni'];
$telefono = $_POST['telefono'];

$pdo->beginTransaction();
$person = rosterEnsureActive($pdo, (int) $actividad_id, $nombre, $apellidos, date('Y-m-d'));
$pdo->prepare('UPDATE inscritos SET dni = ?, telefono = ? WHERE id = ?')->execute([$dni, $telefono, $person['id']]);
$pdo->commit();

header("Location: asistencia.php?actividad_id=" . $actividad_id);
exit;
?>
