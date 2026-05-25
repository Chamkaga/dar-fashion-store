<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_admin('login.php');
header('Location: dashboard.php');
exit;
