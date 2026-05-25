<?php

function load_env_file($path) {
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || env_starts_with($line, '#') || !env_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function env_starts_with($haystack, $needle) {
    if (function_exists('str_starts_with')) {
        return str_starts_with($haystack, $needle);
    }

    return strncmp($haystack, $needle, strlen($needle)) === 0;
}

function env_contains($haystack, $needle) {
    if (function_exists('str_contains')) {
        return str_contains($haystack, $needle);
    }

    return strpos($haystack, $needle) !== false;
}

function app_env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    return $value;
}

function is_production_env() {
    return strtolower((string) app_env('APP_ENV', 'development')) === 'production';
}
