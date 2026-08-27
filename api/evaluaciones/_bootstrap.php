<?php

require_once __DIR__ . '/../../config/config.php';

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/evaluaciones_helpers.php';
require_once __DIR__ . '/_common.php';

$monitor_center_id = filter_var($_SESSION['centro_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($monitor_center_id === false) {
    evaluacionesMonitorRespond(401, [
        'success' => false,
        'error' => [
            'code' => 'SESION_CENTRO_REQUERIDA',
            'message' => 'Inicia sesión con el centro para continuar.',
        ],
    ]);
}

$monitor_center_id = (int) $monitor_center_id;
