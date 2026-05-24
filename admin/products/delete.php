<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();

if ($conn && isset($_GET['id'])) {
    $stmt = $conn->prepare("UPDATE products SET status='inactive' WHERE id=?");
    $stmt->execute([(int) $_GET['id']]);
}

header('Location: index.php');
exit;
