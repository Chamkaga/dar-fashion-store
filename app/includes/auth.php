<?php

require_once __DIR__ . '/../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return current_user() !== null;
}

function is_admin() {
    $user = current_user();
    return $user && ($user['role'] ?? '') === 'admin';
}

function login_user(array $user) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'fullname' => $user['fullname'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'customer'
    ];
}

function logout_user() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_login($redirect = '../public/login.php') {
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_admin($redirect = 'login.php') {
    if (!is_admin()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_customer($loginRedirect = '../public/login.php', $adminRedirect = '../admin/profile.php') {
    require_login($loginRedirect);

    if (is_admin()) {
        header('Location: ' . $adminRedirect);
        exit;
    }
}

function post_login_redirect($user) {
    return ($user['role'] ?? '') === 'admin' ? '../admin/dashboard.php' : 'shop.php';
}
