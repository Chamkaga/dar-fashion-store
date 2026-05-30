<?php

class JobQueue {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function enqueue($job_type, array $payload, $delaySeconds = 0) {
        $availableAt = date('Y-m-d H:i:s', time() + max(0, (int)$delaySeconds));
        $stmt = $this->conn->prepare("INSERT INTO jobs (job_type, payload, available_at) VALUES (?, ?, ?)");
        return $stmt->execute([$job_type, json_encode($payload), $availableAt]);
    }

    public function fetchPending($limit = 10) {
        $stmt = $this->conn->prepare("SELECT * FROM jobs WHERE status = 'pending' AND available_at <= NOW() ORDER BY created_at ASC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markProcessing($jobId) {
        $stmt = $this->conn->prepare("UPDATE jobs SET status = 'processing', attempts = attempts + 1, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$jobId]);
    }

    public function complete($jobId) {
        $stmt = $this->conn->prepare("UPDATE jobs SET status = 'completed', updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$jobId]);
    }

    public function fail($jobId, $errorMessage) {
        $stmt = $this->conn->prepare("UPDATE jobs SET status = 'failed', last_error = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$errorMessage, $jobId]);
    }
}
