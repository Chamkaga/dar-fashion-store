<?php

class Logger {
    private static function getLogDir() {
        $dir = dirname(__DIR__, 1) . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public static function log($type, $message, $context = []) {
        $dir = self::getLogDir();
        $path = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i', '_', strtolower($type)) . '.log';
        $entry = [
            'timestamp' => date('c'),
            'message' => $message,
            'context' => $context,
        ];
        @file_put_contents($path, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function info($message, $context = []) {
        self::log('info', $message, $context);
    }

    public static function warning($message, $context = []) {
        self::log('warning', $message, $context);
    }

    public static function error($message, $context = []) {
        self::log('error', $message, $context);
    }
}
