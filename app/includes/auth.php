<?php
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
