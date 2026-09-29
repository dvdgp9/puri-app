<?php
// Ejecutar con: php tests/admin_remember_test.php
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../admin/login.php';
require_once __DIR__ . '/../admin/auth_middleware.php';

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->sqliteCreateFunction('NOW', static fn() => gmdate('Y-m-d H:i:s'));
$pdo->exec("CREATE TABLE admins (id INTEGER PRIMARY KEY, username TEXT, role TEXT)");
$pdo->exec("CREATE TABLE admin_sessions (id INTEGER PRIMARY KEY, admin_id INTEGER, token TEXT, expires_at TEXT)");
$pdo->exec("INSERT INTO admins VALUES (1, 'test-admin', 'admin')");
$validToken = str_repeat('a', 64);
$expiredToken = str_repeat('b', 64);
$insert = $pdo->prepare('INSERT INTO admin_sessions (admin_id, token, expires_at) VALUES (1, ?, ?)');
$insert->execute([$validToken, gmdate('Y-m-d H:i:s', time() + 3600)]);
$insert->execute([$expiredToken, gmdate('Y-m-d H:i:s', time() - 3600)]);

$_SESSION = [];
$_COOKIE['admin_remember_token'] = $validToken;
if (!restoreAdminSessionFromRememberCookie() || $_SESSION['admin_id'] != 1 || $_SESSION['admin_username'] !== 'test-admin') {
    throw new RuntimeException('No se restauró un token válido');
}

// La tabla antigua sin last_used_at debe seguir permitiendo restaurar la sesión.
$_SESSION = [];
$_COOKIE['admin_remember_token'] = $expiredToken;
if (restoreAdminSessionFromRememberCookie() || isset($_SESSION['admin_logged_in'])) {
    throw new RuntimeException('Se aceptó un token caducado');
}

echo "OK: token válido y token caducado\n";
