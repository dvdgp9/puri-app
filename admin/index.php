<?php
// Redirect root /admin to login or dashboard based on session
// Do NOT include auth_middleware here to avoid auto-redirect loops.
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;
