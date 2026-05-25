<?php

require_once __DIR__ . '/includes/env.php';

$projectRoot = dirname(__DIR__);
load_env_file($projectRoot . '/.env');

if (is_production_env()) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    error_reporting(E_ALL);
}

$logDir = $projectRoot . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

ini_set('error_log', $logDir . '/error.log');

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
