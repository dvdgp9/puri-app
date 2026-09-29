<?php
// Redirect root /admin to login or dashboard based on session or remember token.
require_once 'auth_middleware.php';

if (isLoggedIn() || restoreAdminSessionFromRememberCookie()) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;
