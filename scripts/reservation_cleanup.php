<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/InventoryReservation.php';
require_once __DIR__ . '/../app/helpers/Logger.php';

$database = new Database();
$conn = $database->connect();
$reservation = new InventoryReservation($conn);

Logger::info('Reservation cleanup started');
$restored = $reservation->releaseExpired();
Logger::info('Reservation cleanup completed', ['restored_count' => $restored]);

echo "Reservation cleanup finished. Restored: {$restored}\n";
