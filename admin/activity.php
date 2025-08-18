<?php
/**
 * Página de detalle de actividad (gestión de participantes)
 */
require_once 'auth_middleware.php';
require_once '../config/config.php';

$actividad_id = intval($_GET['id'] ?? 0);
if ($actividad_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

try {
    $admin_info = getAdminInfo();

    // Obtener actividad + instalación + centro
    $query = "SELECT a.id, a.nombre, a.instalacion_id, a.dias_semana, a.hora_inicio, a.hora_fin, a.fecha_inicio, a.fecha_fin,
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
        header("Location: dashboard.php?error=actividad_no_encontrada");
        exit;
    }
} catch (Exception $e) {
    error_log("Error en activity.php: " . $e->getMessage());
    header("Location: dashboard.php?error=error_sistema");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($actividad['nombre']) ?> - Sistema Puri</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=GeistSans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="logo-section">
            <div class="logo">P</div>
            <div class="title">Puri: Gestión de centros deportivos</div>
        </div>
        <div class="actions">
            <div class="dropdown">
                <button class="btn btn-secondary" id="profile-dropdown-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?= htmlspecialchars($_SESSION['admin_username']) ?>
                </button>
                <div class="dropdown-content" id="profile-dropdown">
                    <a href="#" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configuración
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
            <span class="breadcrumb-current">Participantes</span>
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
                <h1 class="center-title installation-title"><?= htmlspecialchars($actividad['nombre']) ?></h1>
                <p class="center-address">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                    <?= htmlspecialchars($actividad['centro_nombre']) ?> · <?= htmlspecialchars($actividad['instalacion_nombre']) ?>
                </p>
            </div>
            <div class="center-header-right">
                <button onclick="openEditActivityModal()" class="btn btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 16 16">
                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L10.5 8.207l-3-3L12.146.146zM11.207 9l-3-3L2.5 11.707V14.5h2.793L11.207 9z"/>
                    </svg>
                    Editar
                </button>
                <button class="btn btn-primary" onclick="openAddParticipantsModal()">
                    + Añadir Participantes
                </button>
            </div>
        </div>

        <!-- Panel Participantes -->
        <div class="centers-panel">
            <div class="centers-header">
                <h2 class="centers-title">Participantes</h2>
                <div class="centers-actions">
                    <input type="text" id="search-participants" class="search-input" placeholder="Buscar participantes...">
                    <select id="sort-participants" class="sort-select">
                        <option value="apellidos">Ordenar A-Z</option>
                        <option value="-apellidos">Ordenar Z-A</option>
                    </select>
                    <button class="btn btn-primary" onclick="openAddParticipantsModal()">
                        + Añadir Participantes
                    </button>
                </div>
            </div>
            <div class="centers-content">
                <div id="participants-list" class="centers-list">
                    <!-- Participantes -->
                </div>
            </div>
        </div>
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
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="participantName">Nombre *</label>
                                <input type="text" id="participantName" name="nombre" required placeholder="Ej: Juan">
                                <span class="field-error" id="participantName-error"></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="participantLastName">Apellidos *</label>
                                <input type="text" id="participantLastName" name="apellidos" required placeholder="Ej: Pérez García">
                                <span class="field-error" id="participantLastName-error"></span>
                            </div>
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
                <button type="submit" form="createParticipantForm" class="btn btn-primary" id="createParticipantBtn">Añadir</button>
                <button type="submit" form="uploadParticipantCsvForm" class="btn btn-primary" id="uploadParticipantsCsvBtn">Subir CSV</button>
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

    <script src="assets/js/activity.js"></script>
</body>
</html>
