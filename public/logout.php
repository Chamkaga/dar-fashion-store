<?php
require_once __DIR__ . '/../app/includes/auth.php';

logout_user();
header('Location: login.php?logged_out=1');
exit;
