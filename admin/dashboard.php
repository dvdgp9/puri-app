<?php
/**
 * Dashboard principal - redirige a la SPA
 */

require_once 'auth_middleware.php';

// Si llegamos aquí, el usuario está autenticado
// Redirigir a la SPA
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo al Dashboard...</title>
</head>
<body>
    <script>
        // Redirigir a la SPA
        window.location.href = 'index.html';
    </script>
    <p>Redirigiendo al dashboard...</p>
    <p>Si no eres redirigido automáticamente, <a href="index.html">haz clic aquí</a>.</p>
</body>
</html>
