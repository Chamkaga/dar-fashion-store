<?php

class AuditLog {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function log($user_id, $action, $target_type = null, $target_id = null, $details = [], $ip_address = null, $user_agent = null) {
        $stmt = $this->conn->prepare("INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $detailsJson = !empty($details) ? json_encode($details) : null;
        $ip_address = $ip_address ?: $this->getClientIP();
        $user_agent = $user_agent ?: substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        return $stmt->execute([$user_id, $action, $target_type, $target_id, $detailsJson, $ip_address, $user_agent]);
    }

    private function getClientIP() {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
