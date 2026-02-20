<?php
/**
 * Mi Cuenta (Perfil de Admin)
 */
require_once 'auth_middleware.php';
require_once '../config/config.php';

$admin_info = getAdminInfo();

// Cargar centros asignados (todos si es superadmin)
try {
    if ($admin_info['role'] === 'superadmin') {
        $stmt = $pdo->query("SELECT id, nombre, direccion FROM centros ORDER BY nombre ASC");
        $centros = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare(
            "SELECT c.id, c.nombre, c.direccion
             FROM centros c
             INNER JOIN admin_asignaciones aa ON aa.centro_id = c.id
             WHERE aa.admin_id = ?
             ORDER BY c.nombre ASC"
        );
        $stmt->execute([$admin_info['id']]);
        $centros = $stmt->fetchAll();
    }
} catch (Exception $e) {
    error_log('Error al cargar centros en account.php: ' . $e->getMessage());
    $centros = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Sistema Puri</title>
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
                    <?= htmlspecialchars($admin_info['username']); ?>
                </button>
                <div class="dropdown-content" id="profile-dropdown">
                    <a href="account.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756.426-1.756 2.924 0 3.35a1.724 1.724 0 001.066-2.573c-.94 1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
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
        <nav class="breadcrumbs">
            <a href="dashboard.php">Escritorio</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Mi Cuenta</span>
        </nav>

        <div class="centers-panel">
            <div class="centers-header">
                <h2 class="centers-title">Información de usuario</h2>
            </div>
            <div class="centers-content">
                <div class="profile-card">
                    <div class="profile-row"><strong>Usuario:</strong> <?= htmlspecialchars($admin_info['username']); ?></div>
                    <div class="profile-row"><strong>Rol:</strong> <?= htmlspecialchars($admin_info['role']); ?></div>
                </div>
            </div>
        </div>

        <div class="centers-panel">
            <div class="centers-header">
                <h2 class="centers-title">Centros asignados</h2>
            </div>
            <div class="centers-content">
                <?php if (empty($centros)): ?>
                    <div class="empty-state">No hay centros asignados.</div>
                <?php else: ?>
                    <ul class="list-simple">
                        <?php foreach ($centros as $c): ?>
                            <li>
                                <a href="center.php?id=<?= (int)$c['id'] ?>">
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </a>
                                <span class="muted">· <?= htmlspecialchars($c['direccion'] ?: 'Sin dirección') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="centers-panel">
            <div class="centers-header">
                <h2 class="centers-title">Cambiar contraseña</h2>
            </div>
            <div class="centers-content">
                <form id="changePasswordForm" class="form-vertical">
                    <div class="form-group">
                        <label for="current_password">Contraseña actual</label>
                        <input type="password" id="current_password" name="current_password" required>
                        <span class="field-error" id="current_password-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Nueva contraseña</label>
                        <input type="password" id="new_password" name="new_password" minlength="8" required>
                        <small class="form-text">Mínimo 8 caracteres.</small>
                        <span class="field-error" id="new_password-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmar nueva contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <span class="field-error" id="confirm_password-error"></span>
                    </div>
                    <div id="changePasswordMessage" class="form-message" style="display:none"></div>
                    <button type="submit" class="btn btn-primary" id="changePasswordBtn">
                        <span class="btn-text">Guardar</span>
                        <span class="btn-loading" style="display:none">
                            <svg class="loading-spinner" width="16" height="16" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="60" stroke-dashoffset="60"/>
                            </svg>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/js/account.js"></script>
</body>
</html>
