<?php

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../auth_middleware.php';
require_once __DIR__ . '/../../../includes/evaluaciones_helpers.php';
require_once __DIR__ . '/_common.php';

$admin_info = getAdminInfo();

