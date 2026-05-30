<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/JobQueue.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/helpers/Logger.php';

$database = new Database();
$conn = $database->connect();
$queue = new JobQueue($conn);

$jobs = $queue->fetchPending(20);
if (!$jobs) {
    echo "No pending jobs found.\n";
    exit(0);
}

foreach ($jobs as $job) {
    $queue->markProcessing($job['id']);
    $payload = json_decode($job['payload'], true) ?: [];

    try {
        switch ($job['job_type']) {
            case 'send_order_confirmation':
                Logger::info('Processing send_order_confirmation', $payload);
                // Placeholder: integrate with email gateway here.
                Logger::info('Order confirmation queued', ['order_id' => $payload['order_id'], 'recipient' => $payload['recipient']]);
                break;
            case 'analytics_event':
                Logger::info('Processing analytics_event', $payload);
                // Placeholder: send event to analytics or update summary tables.
                break;
            case 'notification':
                Logger::info('Processing notification', $payload);
                // Placeholder: send SMS/webhook/push notification.
                break;
            default:
                Logger::warning('Unknown job type skipped', ['job_type' => $job['job_type']]);
                break;
        }

        $queue->complete($job['id']);
    } catch (Throwable $e) {
        Logger::error('Failed to process queued job', ['job_id' => $job['id'], 'error' => $e->getMessage()]);
        $queue->fail($job['id'], $e->getMessage());
    }
}

echo "Queue processing complete. Processed: " . count($jobs) . " jobs.\n";
