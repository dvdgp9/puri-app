<?php
require_once 'config/config.php';
require_once 'includes/actividad_helpers.php';

if (!isset($_SESSION['centro_id'])) {
    header('Location: index.php');
    exit;
}

$sesion_id = filter_input(INPUT_GET, 'sesion_id', FILTER_VALIDATE_INT);
if (!$sesion_id) {
    header('Location: instalaciones.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT es.id, es.estado, es.fecha_realizacion,
            e.nombre AS evaluacion_nombre, e.actividad_id,
            a.nombre AS actividad_nombre, a.grupo AS actividad_grupo,
            i.centro_id
     FROM evaluacion_sesiones es
     INNER JOIN evaluaciones e ON e.id = es.evaluacion_id
     INNER JOIN actividades a ON a.id = e.actividad_id
     INNER JOIN instalaciones i ON i.id = a.instalacion_id
     WHERE es.id = ?'
);
$stmt->execute([$sesion_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session || (int) $session['centro_id'] !== (int) $_SESSION['centro_id']) {
    header('Location: instalaciones.php');
    exit;
}

$pageTitle = 'Evaluación';
require_once 'includes/header.php';
?>
<div class="content-wrapper evaluation-capture-page">
  <main class="content-container">
    <a class="evaluation-back-link" href="asistencia.php?actividad_id=<?php echo (int) $session['actividad_id']; ?>">
      <i class="fas fa-arrow-left" aria-hidden="true"></i>
      Volver a la actividad
    </a>

    <header class="evaluation-capture-header">
      <div>
        <p class="evaluation-capture-kicker"><?php echo formatearNombreActividad($session['actividad_nombre'], $session['actividad_grupo'] ?? null); ?></p>
        <h1 id="evaluation-capture-title"><?php echo htmlspecialchars($session['evaluacion_nombre']); ?></h1>
        <p id="evaluation-capture-meta">Cargando información…</p>
      </div>
      <span class="evaluation-capture-state" id="evaluation-capture-state">Cargando</span>
    </header>

    <section class="evaluation-capture-guidance" id="evaluation-capture-guidance" hidden>
      <h2>Indicaciones</h2>
      <p id="evaluation-capture-instructions"></p>
    </section>

    <div class="evaluation-capture-toolbar">
      <div class="evaluation-progress" id="evaluation-progress" aria-live="polite">Cargando progreso…</div>
      <div class="evaluation-filters" role="group" aria-label="Filtrar participantes">
        <button type="button" class="evaluation-filter active" id="evaluation-filter-all" aria-pressed="true">Todos</button>
        <button type="button" class="evaluation-filter" id="evaluation-filter-pending" aria-pressed="false">Pendientes</button>
      </div>
    </div>

    <section class="evaluation-capture-list" id="evaluation-capture-list" aria-live="polite">
      <div class="evaluation-monitor-loading">Cargando participantes…</div>
    </section>

    <div class="evaluation-capture-footer">
      <p id="evaluation-finish-help">Puedes salir y continuar más tarde mientras no finalices la evaluación.</p>
      <button type="button" class="btn-primary evaluation-finish-button" id="evaluation-finish-button">
        Finalizar evaluación
      </button>
    </div>
  </main>
</div>

<script>
  window.MonitorEvaluationsContext = {
    view: 'capture',
    sessionId: <?php echo (int) $sesion_id; ?>,
    activityId: <?php echo (int) $session['actividad_id']; ?>,
    today: <?php echo json_encode(date('Y-m-d')); ?>
  };
</script>
<script src="public/assets/js/evaluaciones-monitor.js"></script>
<?php require_once 'includes/footer.php'; ?>

