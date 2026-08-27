<?php
/**
 * Página de detalle de actividad (gestión de participantes)
 */
require_once 'auth_middleware.php';
require_once '../config/config.php';
require_once '../includes/actividad_helpers.php';

$actividad_id = intval($_GET['id'] ?? 0);
if ($actividad_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

$admin_info = getAdminInfo();

// Obtener actividad + instalación + centro
$query = "SELECT a.id, a.nombre, a.grupo, a.instalacion_id, a.dias_semana, a.hora_inicio, a.hora_fin, a.fecha_inicio, a.fecha_fin,
                 i.nombre AS instalacion_nombre, i.centro_id,
                 c.nombre AS centro_nombre, c.direccion AS centro_direccion
          FROM actividades a
          INNER JOIN instalaciones i ON i.id = a.instalacion_id
          INNER JOIN centros c ON c.id = i.centro_id
          WHERE a.id = ?";
$params = [$actividad_id];

if ($admin_info['role'] !== 'superadmin') {
    $query .= " AND c.id IN (SELECT centro_id FROM admin_asignaciones WHERE admin_id = ?)";
    $params[] = $admin_info['id'];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$actividad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actividad) {
    header("Location: dashboard.php");
    exit;
}

    // Inyectar contexto para JS
    $activity_js_ctx = json_encode($actividad);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(html_entity_decode($actividad['nombre'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?> - Sistema Puri</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=GeistSans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        const ActivityPage = {
            id: <?= $actividad['id'] ?>,
            ctx: <?= $activity_js_ctx ?>
        };
    </script>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="logo-section">
            <div class="logo">P</div>
            <div class="title">Puri: Gestión de centros deportivos</div>
        </div>
        <div class="actions">
            <a href="informes.php" class="btn btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17h6l3 3v-3h2V9h-2M4 4h11v8H9l-3 3v-3H4V4z"/>
                </svg>
                Informes
            </a>
            <div class="dropdown">
                <button class="btn btn-secondary" id="profile-dropdown-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?= htmlspecialchars($_SESSION['admin_username']) ?>
                </button>
                <div class="dropdown-content" id="profile-dropdown">
                    <a href="account.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756.426-1.756 2.924 0 3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Mi Cuenta
                    </a>
                    <a href="logout.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-content">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="dashboard.php">Escritorio</a>
            <span class="breadcrumb-separator">/</span>
            <a href="center.php?id=<?= (int)$actividad['centro_id'] ?>"><?= htmlspecialchars($actividad['centro_nombre']) ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="installation.php?id=<?= (int)$actividad['instalacion_id'] ?>"><?= htmlspecialchars($actividad['instalacion_nombre']) ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Actividad</span>
        </nav>

        <!-- Activity Header -->
        <div class="center-header-section">
            <div class="center-header-left">
                <button onclick="goBackToInstallation(<?= (int)$actividad['instalacion_id'] ?>)" class="btn btn-secondary">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Volver
                </button>
            </div>
            <div class="center-header-center">
                <h1 class="center-title installation-title"><?= formatearNombreActividad($actividad['nombre'], $actividad['grupo'] ?? null) ?></h1>
                <p class="center-address">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                    <?= htmlspecialchars($actividad['centro_nombre']) ?> · <?= htmlspecialchars($actividad['instalacion_nombre']) ?>
                    <?php
                        $dias = isset($actividad['dias_semana']) ? trim($actividad['dias_semana']) : '';
                        if ($dias !== '') {
                            $map = [
                                'lunes' => 'L', 'martes' => 'M', 'miércoles' => 'X', 'miercoles' => 'X',
                                'jueves' => 'J', 'viernes' => 'V', 'sábado' => 'S', 'sabado' => 'S', 'domingo' => 'D',
                                'l' => 'L', 'm' => 'M', 'x' => 'X', 'j' => 'J', 'v' => 'V', 's' => 'S', 'd' => 'D'
                            ];
                            $parts = preg_split('/\s*,\s*/u', $dias, -1, PREG_SPLIT_NO_EMPTY);
                            if (count($parts) <= 1) {
                                $parts = preg_split('/\s*y\s*/iu', $dias, -1, PREG_SPLIT_NO_EMPTY);
                            }
                            $letters = [];
                            foreach ($parts as $p) {
                                $key = mb_strtolower(trim($p), 'UTF-8');
                                $letters[] = $map[$key] ?? mb_strtoupper(mb_substr($key, 0, 1, 'UTF-8'), 'UTF-8');
                            }
                            echo ' · ' . htmlspecialchars(implode(', ', $letters));
                        }
                        $hIni = isset($actividad['hora_inicio']) && $actividad['hora_inicio'] ? substr($actividad['hora_inicio'], 0, 5) : null;
                        $hFin = isset($actividad['hora_fin']) && $actividad['hora_fin'] ? substr($actividad['hora_fin'], 0, 5) : null;
                        if ($hIni || $hFin) {
                            echo ' · ' . htmlspecialchars(trim(($hIni ?: '') . ($hFin ? '–' . $hFin : '')));
                        }
                        $fi = isset($actividad['fecha_inicio']) && $actividad['fecha_inicio'] ? substr($actividad['fecha_inicio'], 0, 10) : null;
                        $ff = isset($actividad['fecha_fin']) && $actividad['fecha_fin'] ? substr($actividad['fecha_fin'], 0, 10) : null;
                        if ($fi || $ff) {
                            $formatDate = function($d) { $p = explode('-', $d); return count($p) === 3 ? ($p[2] . '/' . $p[1] . '/' . $p[0]) : $d; };
                            $fi_fmt = $fi ? $formatDate($fi) : '';
                            $ff_fmt = $ff ? $formatDate($ff) : '';
                            echo ' · ' . htmlspecialchars(trim(($fi_fmt ?: '') . ($ff_fmt ? ' → ' . $ff_fmt : '')));
                        }
                    ?>
                </p>
            </div>
            <div class="center-header-right">
                <button onclick="openEditActivityModal()" class="btn btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 16 16">
                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L10.5 8.207l-3-3L12.146.146zM11.207 9l-3-3L2.5 11.707V14.5h2.793L11.207 9z"/>
                    </svg>
                    Editar
                </button>
            </div>
        </div>

        <nav class="activity-section-tabs" role="tablist" aria-label="Contenido de la actividad">
            <button
                type="button"
                class="activity-section-tab active"
                id="participants-tab"
                role="tab"
                aria-selected="true"
                aria-controls="participants-panel">
                Participantes
            </button>
            <button
                type="button"
                class="activity-section-tab"
                id="evaluations-tab"
                role="tab"
                aria-selected="false"
                aria-controls="evaluations-panel">
                Evaluaciones
                <span class="activity-tab-count" id="evaluations-tab-count" hidden>0</span>
            </button>
        </nav>

        <!-- Panel Participantes -->
        <section class="centers-panel activity-section-panel" id="participants-panel" role="tabpanel" aria-labelledby="participants-tab">
            <div class="centers-header">
                <h2 class="centers-title">Participantes</h2>
                <div class="centers-actions">
                    <input type="text" id="search-participants" class="search-input" placeholder="Buscar participantes...">
                    <select id="sort-participants" class="sort-select">
                        <option value="apellidos">Ordenar A-Z</option>
                        <option value="-apellidos">Ordenar Z-A</option>
                    </select>
                    <button class="btn btn-secondary" type="button" onclick="openAttendanceRangeModal()">
                        Periodo
                    </button>
                    <button class="btn btn-primary" onclick="openAddParticipantsModal()">
                        + Añadir Participantes
                    </button>
                    <button class="btn btn-secondary" style="background:#e53e3e;border-color:#e53e3e;color:#fff" onclick="confirmDeleteAllParticipants()">
                        Borrar listado
                    </button>
                </div>
            </div>
            <div style="padding:0 1.5rem 1rem; color:#6b7280; font-size:0.875rem;" id="attendance-range-summary"></div>
            <div class="centers-content">
                <div id="participants-list" class="centers-list">
                    <!-- Participantes -->
                </div>
            </div>
        </section>

        <!-- Panel Evaluaciones: solo se carga al abrir esta sección -->
        <section class="centers-panel activity-section-panel" id="evaluations-panel" role="tabpanel" aria-labelledby="evaluations-tab" hidden>
            <div class="centers-header evaluations-panel-header">
                <div>
                    <h2 class="centers-title">Evaluaciones</h2>
                    <p class="evaluations-panel-copy">Planifica qué se medirá y durante qué período. El monitor elegirá el día concreto.</p>
                </div>
                <button class="btn btn-primary" type="button" onclick="openCreateEvaluationModal()">
                    Nueva evaluación
                </button>
            </div>
            <div class="centers-content">
                <div id="evaluations-list" class="evaluations-list" aria-live="polite">
                    <div class="evaluation-list-skeleton" aria-label="Cargando evaluaciones">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        window.__ACTIVITY_CTX__ = {
            id: <?= (int)$actividad['id'] ?>,
            instalacion_id: <?= (int)$actividad['instalacion_id'] ?>,
            centro_id: <?= (int)$actividad['centro_id'] ?>,
            centro_nombre: <?= json_encode($actividad['centro_nombre']) ?>,
            instalacion_nombre: <?= json_encode($actividad['instalacion_nombre']) ?>,
            nombre: <?= json_encode($actividad['nombre']) ?>,
            dias_semana: <?= json_encode($actividad['dias_semana']) ?>,
            hora_inicio: <?= json_encode($actividad['hora_inicio']) ?>,
            hora_fin: <?= json_encode($actividad['hora_fin']) ?>,
            fecha_inicio: <?= json_encode($actividad['fecha_inicio']) ?>,
            fecha_fin: <?= json_encode($actividad['fecha_fin']) ?>
        };
        function goBackToInstallation(id){ window.location.href = 'installation.php?id=' + id; }
    </script>

    <!-- Modal: Editar Actividad -->
    <div id="editActivityModal" class="modal-overlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="editActivityTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="editActivityTitle">Editar Actividad</h3>
                <button class="modal-close" onclick="closeModal('editActivityModal')" aria-label="Cerrar modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editActivityForm">
                    <input type="hidden" id="editActivityId" name="id" />
                    <div class="form-group">
                        <label for="editActivityName">Nombre *</label>
                        <input type="text" id="editActivityName" name="nombre" required>
                        <span class="field-error" id="editActivityName-error"></span>
                    </div>
                    <div class="form-group">
                        <label>Días de la semana *</label>
                        <div class="checkbox-group">
                            <label class="checkbox-inline"><input type="checkbox" name="edit_dias_semana[]" value="Lunes"> Lunes</label>
                            <label class="checkbox-inline"><input type="checkbox" name="edit_dias_semana[]" value="Martes"> Martes</label>
                            <label class="checkbox-inline"><input type="checkbox" name="edit_dias_semana[]" value="Miércoles"> Miércoles</label>
                            <label class="checkbox-inline"><input type="checkbox" name="edit_dias_semana[]" value="Jueves"> Jueves</label>
                            <label class="checkbox-inline"><input type="checkbox" name="edit_dias_semana[]" value="Viernes"> Viernes</label>
                            <label class="checkbox-inline"><input type="checkbox" name="edit_dias_semana[]" value="Sábado"> Sábado</label>
                            <label class="checkbox-inline"><input type="checkbox" name="edit_dias_semana[]" value="Domingo"> Domingo</label>
                        </div>
                        <span class="field-error" id="edit_dias_semana-error"></span>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="editActivityStart">Hora inicio</label>
                            <input type="time" id="editActivityStart" name="hora_inicio">
                        </div>
                        <div class="form-group">
                            <label for="editActivityEnd">Hora fin</label>
                            <input type="time" id="editActivityEnd" name="hora_fin">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="editActivityDateStart">Fecha inicio *</label>
                            <input type="date" id="editActivityDateStart" name="fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label for="editActivityDateEnd">Fecha fin (opcional)</label>
                            <input type="date" id="editActivityDateEnd" name="fecha_fin">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">Cancelar</button>
                <button type="submit" form="editActivityForm" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
    </div>

    <!-- Modal: Añadir Participantes -->
    <div class="modal-overlay" id="createParticipantModal">
        <div class="modal modal-large">
            <div class="modal-header">
                <h2 class="modal-title">Añadir Participantes</h2>
                <button class="modal-close" onclick="closeCreateParticipantModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Pestañas -->
                <div class="tab-navigation">
                    <button class="tab-btn active" onclick="switchParticipantTab('manual')">Añadir Manual</button>
                    <button class="tab-btn" onclick="switchParticipantTab('csv')">Subir CSV</button>
                </div>

                <!-- Pestaña Manual -->
                <div class="tab-content active" id="manualTab">
                    <form id="createParticipantForm">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Centro Deportivo</label>
                                <input type="text" id="lockedCenterName" disabled>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Instalación</label>
                                <input type="text" id="lockedInstallationName" disabled>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Actividad</label>
                                <input type="text" id="lockedActivityName" disabled>
                            </div>
                        </div>
                        <input type="hidden" id="lockedActivityId" name="actividad_id" required>
                        <div class="form-group">
                            <label>Entrada rápida</label>
                            <div class="table-wrapper">
                                <table class="editable-table" id="quickEntryTable">
                                    <thead>
                                        <tr>
                                            <th style="width:48%">Nombre</th>
                                            <th style="width:48%">Apellidos</th>
                                            <th style="width:4%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="quickEntryBody">
                                        <!-- filas dinámicas -->
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top:8px">
                                <button type="button" class="btn btn-outline" onclick="addQuickEntryRow()">+ Añadir fila</button>
                            </div>
                            <div style="margin-top:6px; font-size:12px; color:#6b7280;">
                                Consejo: puedes pegar directamente desde Excel (Nombre y Apellidos en columnas)
                            </div>
                            <div class="form-error" id="quickEntryError"></div>
                        </div>
                    </form>
                </div>

                <!-- Pestaña CSV -->
                <div class="tab-content" id="csvTab">
                    <form id="uploadParticipantCsvForm" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Centro Deportivo</label>
                                <input type="text" id="csvLockedCenterName" disabled>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Instalación</label>
                                <input type="text" id="csvLockedInstallationName" disabled>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Actividad</label>
                                <input type="text" id="csvLockedActivityName" disabled>
                            </div>
                        </div>
                        <input type="hidden" id="csvLockedActivityId" name="actividad_id" required>

                        <div class="form-group">
                            <label for="csvImportMode">Modo de importación</label>
                            <select id="csvImportMode" name="mode" class="form-control">
                                <option value="append">Añadir al listado actual</option>
                                <option value="replace">Reemplazar listado (borrar todos los participantes actuales)</option>
                            </select>
                        </div>

                        <div class="csv-section">
                            <div class="csv-info">
                                <h4>Instrucciones</h4>
                                <p>1. Descarga la plantilla CSV</p>
                                <p>2. Completa con los datos de los participantes</p>
                                <p>3. Sube el archivo completado</p>
                            </div>
                            <div class="csv-actions">
                                <a class="btn btn-secondary" href="../public/assets/plantilla-asistentes.csv" download>
                                    Descargar plantilla
                                </a>
                                <input type="file" id="participantsCsv" name="csv" accept=".csv">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateParticipantModal()">Cancelar</button>
                <button type="submit" form="createParticipantForm" class="btn btn-primary" id="createParticipantBtn">
                    <span class="btn-text">Inscribir Participante</span>
                    <span class="btn-loading">
                        <svg class="loading-spinner" width="16" height="16" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="60" stroke-dashoffset="60"/>
                        </svg>
                    </span>
                </button>
                <button type="submit" form="uploadParticipantCsvForm" class="btn btn-primary" id="uploadParticipantsCsvBtn" style="display: none;">
                    <span class="btn-text">Subir CSV</span>
                    <span class="btn-loading">
                        <svg class="loading-spinner" width="16" height="16" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="60" stroke-dashoffset="60"/>
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Participante -->
    <div id="editParticipantModal" class="modal-overlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="editParticipantTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="editParticipantTitle">Editar Participante</h3>
                <button class="modal-close" onclick="closeModal('editParticipantModal')" aria-label="Cerrar modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editParticipantForm">
                    <input type="hidden" id="editParticipantId" name="id" />
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="editParticipantName">Nombre *</label>
                            <input type="text" id="editParticipantName" name="nombre" required>
                            <span class="field-error" id="editParticipantName-error"></span>
                        </div>
                        <div class="form-group">
                            <label for="editParticipantLastName">Apellidos *</label>
                            <input type="text" id="editParticipantLastName" name="apellidos" required>
                            <span class="field-error" id="editParticipantLastName-error"></span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editParticipantModal')">Cancelar</button>
                <button type="submit" form="editParticipantForm" class="btn btn-primary" id="saveEditParticipantBtn">
                    <span class="btn-text">Guardar cambios</span>
                    <span class="btn-loading" style="display:none">Guardando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Selección de periodo de asistencias -->
    <div id="attendanceRangeModal" class="modal-overlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="attendanceRangeTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="attendanceRangeTitle">Periodo de asistencias</h3>
                <button class="modal-close" onclick="closeModal('attendanceRangeModal')" aria-label="Cerrar modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="attendanceRangeForm">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="attendanceDateStart">Fecha inicio *</label>
                            <input type="date" id="attendanceDateStart" name="fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label for="attendanceDateEnd">Fecha fin *</label>
                            <input type="date" id="attendanceDateEnd" name="fecha_fin" required>
                        </div>
                    </div>
                    <div class="form-error" id="attendanceRangeError"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="resetAttendanceRangeToDefault()">Por defecto</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('attendanceRangeModal')">Cancelar</button>
                <button type="submit" form="attendanceRangeForm" class="btn btn-primary">Aplicar</button>
            </div>
        </div>
    </div>

    <!-- Modal: Crear o editar evaluación -->
    <div id="evaluationModal" class="modal-overlay" aria-hidden="true">
        <div class="modal modal-medium" role="dialog" aria-modal="true" aria-labelledby="evaluationModalTitle">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="evaluationModalTitle">Nueva evaluación</h3>
                    <p class="modal-intro">El monitor decidirá la fecha real dentro del período indicado.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('evaluationModal')" aria-label="Cerrar modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="evaluationForm" novalidate>
                    <input type="hidden" id="evaluationId" name="evaluacion_id">
                    <div class="form-group">
                        <label for="evaluationName">Nombre de la evaluación *</label>
                        <input type="text" id="evaluationName" name="nombre" maxlength="150" autocomplete="off" required>
                        <p class="form-hint">Ejemplo: Burpees en 1 minuto.</p>
                        <span class="field-error" id="evaluationName-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="evaluationInstructions">Indicaciones para el monitor</label>
                        <textarea id="evaluationInstructions" name="instrucciones" rows="3" placeholder="Describe cómo realizar la prueba de forma breve."></textarea>
                        <span class="field-error" id="evaluationInstructions-error"></span>
                    </div>
                    <fieldset class="evaluation-form-section">
                        <legend>Período disponible</legend>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="evaluationDateStart">Desde *</label>
                                <input type="date" id="evaluationDateStart" name="fecha_inicio" required>
                                <span class="field-error" id="evaluationDateStart-error"></span>
                            </div>
                            <div class="form-group">
                                <label for="evaluationDateEnd">Hasta *</label>
                                <input type="date" id="evaluationDateEnd" name="fecha_fin" required>
                                <span class="field-error" id="evaluationDateEnd-error"></span>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="evaluation-form-section">
                        <legend>Dato que se registrará</legend>
                        <div class="form-group">
                            <label for="evaluationFieldName">Nombre del dato *</label>
                            <input type="text" id="evaluationFieldName" name="campo_nombre" maxlength="150" autocomplete="off" required>
                            <p class="form-hint">Ejemplo: Número de burpees.</p>
                            <span class="field-error" id="evaluationFieldName-error"></span>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="evaluationDataType">Formato *</label>
                                <select id="evaluationDataType" name="tipo_dato" required>
                                    <option value="entero">Número entero</option>
                                    <option value="decimal">Número con decimales</option>
                                    <option value="duracion">Duración</option>
                                    <option value="texto_corto">Texto corto</option>
                                </select>
                                <span class="field-error" id="evaluationDataType-error"></span>
                            </div>
                            <div class="form-group">
                                <label for="evaluationUnit">Unidad</label>
                                <input type="text" id="evaluationUnit" name="unidad" maxlength="50" placeholder="repeticiones">
                                <span class="field-error" id="evaluationUnit-error"></span>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-error evaluation-form-error" id="evaluationFormError" role="alert"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('evaluationModal')">Cancelar</button>
                <button type="submit" form="evaluationForm" class="btn btn-primary" id="saveEvaluationBtn">
                    <span class="btn-text">Guardar evaluación</span>
                    <span class="btn-loading">Guardando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Resultados de evaluación -->
    <div id="evaluationResultsModal" class="modal-overlay" aria-hidden="true">
        <div class="modal modal-large evaluation-results-modal" role="dialog" aria-modal="true" aria-labelledby="evaluationResultsTitle">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="evaluationResultsTitle">Resultados</h3>
                    <p class="modal-intro" id="evaluationResultsMeta"></p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal('evaluationResultsModal')" aria-label="Cerrar modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="evaluation-results-toolbar">
                    <p id="evaluationResultsCoverage" aria-live="polite"></p>
                    <button class="btn btn-secondary" type="button" id="reopenEvaluationBtn" onclick="reopenEvaluation()" hidden>Reabrir para el monitor</button>
                </div>
                <div id="evaluation-results-list" class="evaluation-results-list" aria-live="polite">
                    <div class="evaluation-list-skeleton" aria-label="Cargando resultados">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('evaluationResultsModal')">Cerrar</button>
            </div>
        </div>
    </div>

    <script src="assets/js/activity.js"></script>
</body>
</html>
