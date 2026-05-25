<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';

// Log logout activity before destroying session
$user = current_user();
if ($user) {
    $database = new Database();
    $conn = $database->connect();
    if ($conn) {
        $activityLog = new ActivityLog($conn);
        $activityLog->log($user['id'], 'logout');
    }
}

logout_user();
header('Location: login.php?logged_out=1');
exit;
