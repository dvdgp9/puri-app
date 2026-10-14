<?php
require_once '../config/config.php';
require_once 'auth_middleware.php';
require_once '../includes/sabadell_permissions.php';
$admin = getAdminInfo();
if (!sabadellCanImport($pdo, (int) $admin['id'])) {
    http_response_code(403);
    exit('No tienes permiso para importar listados de Sabadell.');
}
$stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ?');
$stmt->execute([$admin['id']]);
$superadmin = $stmt->fetchColumn() === 'superadmin';
$stmt = $pdo->prepare('SELECT id, nombre FROM centros WHERE activo = 1' . ($superadmin ? '' : ' AND id IN (SELECT centro_id FROM admin_asignaciones WHERE admin_id = ?)') . ' ORDER BY nombre');
$stmt->execute($superadmin ? [] : [$admin['id']]);
$centers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$selected = (int) ($_GET['centro_id'] ?? 0);
$_SESSION['sabadell_csrf'] ??= bin2hex(random_bytes(24));
$escape = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Listado de Sabadell · Puri</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= filemtime(__DIR__ . '/assets/css/admin.css') ?>">
    <link rel="stylesheet" href="assets/css/sabadell-import.css?v=<?= filemtime(__DIR__ . '/assets/css/sabadell-import.css') ?>">
</head>
<body>
    <header class="admin-header">
        <div class="logo-section"><div class="logo">P</div><div class="title">Puri · Listado de Sabadell</div></div>
        <a href="<?= $selected ? 'center.php?id=' . $selected : 'dashboard.php' ?>" class="btn btn-secondary">Volver</a>
    </header>
    <main class="admin-content sabadell-main" id="sabadellApp" data-csrf="<?= $escape($_SESSION['sabadell_csrf']) ?>">
        <h1>Importar listado del Ayuntamiento de Sabadell</h1>
        <p class="sabadell-description">Sube el PDF municipal, comprueba los grupos y revisa los cambios antes de guardar.</p>
        <p class="form-hint">Elige el curso antes de revisar. Cambiar de curso reinicia las correcciones y las confirmaciones del PDF.</p>
        <form id="sabadellForm">
            <div class="sabadell-controls">
                <div class="form-group"><label for="sabadellCenter">Centro</label>
                    <select class="form-input" id="sabadellCenter" required><option value="">Selecciona un centro</option>
                        <?php foreach ($centers as $center): ?><option value="<?= (int) $center['id'] ?>" <?= (int) $center['id'] === $selected ? 'selected' : '' ?>><?= $escape($center['nombre']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label for="sabadellDestination">Carga</label><select class="form-input" id="sabadellDestination"><option value="actual">Actualizar grupos del curso</option><option value="nueva">Crear una nueva edición del curso</option></select></div>
                <div class="form-group"><label for="sabadellEdition">Curso de destino</label><select class="form-input" id="sabadellEdition"></select></div>
                <div class="form-group"><label for="sabadellStart">Inicio del curso</label><input class="form-input" type="date" id="sabadellStart"></div>
                <div class="form-group"><label for="sabadellEnd">Fin del curso (opcional)</label><input class="form-input" type="date" id="sabadellEnd"></div>
            </div>
            <p id="sabadellScopeHelp" class="form-hint">Se actualizan los participantes de los grupos incluidos. Los grupos ausentes del PDF se mantienen.</p>
            <div class="sabadell-file">
                <label for="sabadellFile">PDF del Ayuntamiento</label>
                <input type="file" accept=".pdf" id="sabadellFile" aria-describedby="sabadellFileHelp">
                <p class="form-hint" id="sabadellFileHelp">Hasta 5 MB y 100 páginas. Las fechas del documento no se usan como fechas del curso.</p>
            </div>
            <p id="sabadellStatus" role="status" aria-live="polite"></p>
            <p id="sabadellError" class="form-error" role="alert"></p>
            <div id="sabadellEditor" hidden>
                <p class="sabadell-note">Comprueba los nombres de cada grupo. En nombres nuevos, los dos primeros términos se proponen como apellidos; corrige los apellidos compuestos y otros casos. Para omitir una fila, marca «Excluir».</p>
                <div id="sabadellGroups"></div>
                <div id="sabadellReview" class="sabadell-review" tabindex="-1" aria-live="polite" hidden></div>
                <div class="sabadell-footer"><span id="sabadellProgress"></span><button type="submit" class="btn btn-primary" id="sabadellReviewButton">Revisar cambios</button><button type="button" class="btn btn-primary" id="sabadellSave" hidden>Guardar listado</button></div>
            </div>
        </form>
    </main>
    <script src="assets/js/sabadell-import.js?v=<?= filemtime(__DIR__ . '/assets/js/sabadell-import.js') ?>"></script>
</body>
</html>
