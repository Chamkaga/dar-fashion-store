<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/includes/auth.php';

$response = ['success' => false, 'message' => 'Invalid request'];

// Check if user is logged in
if (!is_logged_in()) {
    $response['message'] = 'Please login to add items to wishlist';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user = current_user();
$database = new Database();
$conn = $database->connect();

if (!$conn) {
    $response['message'] = 'Database connection failed';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? ''));
$product_id = (int) ($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

if (!$product_id) {
    $response['message'] = 'Invalid product ID';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Check if product exists
$stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    $response['message'] = 'Product not found';
    http_response_code(404);
    echo json_encode($response);
    exit;
}

// Handle add to wishlist
if ($action === 'add') {
    try {
        // Check if already in wishlist
        $stmt = $conn->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user['id'], $product_id]);
        
        if ($stmt->fetch()) {
            $response['success'] = true;
            $response['message'] = 'Already in wishlist';
            $response['added'] = false;
        } else {
            // Add to wishlist
            $stmt = $conn->prepare("INSERT INTO user_wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$user['id'], $product_id]);
            
            $response['success'] = true;
            $response['message'] = 'Added to wishlist';
            $response['added'] = true;
        }
    } catch (Exception $e) {
        $response['message'] = 'Error adding to wishlist: ' . $e->getMessage();
        http_response_code(500);
    }
}
// Handle remove from wishlist
elseif ($action === 'remove') {
    try {
        $stmt = $conn->prepare("DELETE FROM user_wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user['id'], $product_id]);
        
        $response['success'] = true;
        $response['message'] = 'Removed from wishlist';
        $response['added'] = false;
    } catch (Exception $e) {
        $response['message'] = 'Error removing from wishlist: ' . $e->getMessage();
        http_response_code(500);
    }
}
// Handle toggle (add if not present, remove if present)
elseif ($action === 'toggle') {
    try {
        $stmt = $conn->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user['id'], $product_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Remove from wishlist
            $stmt = $conn->prepare("DELETE FROM user_wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user['id'], $product_id]);
            $response['added'] = false;
            $response['message'] = 'Removed from wishlist';
        } else {
            // Add to wishlist
            $stmt = $conn->prepare("INSERT INTO user_wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$user['id'], $product_id]);
            $response['added'] = true;
            $response['message'] = 'Added to wishlist';
        }
        $response['success'] = true;
    } catch (Exception $e) {
        $response['message'] = 'Error toggling wishlist: ' . $e->getMessage();
        http_response_code(500);
    }
}
// Handle check if in wishlist
elseif ($action === 'check') {
    try {
        $stmt = $conn->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user['id'], $product_id]);
        
        $response['success'] = true;
        $response['added'] = (bool) $stmt->fetch();
        $response['message'] = $response['added'] ? 'In wishlist' : 'Not in wishlist';
    } catch (Exception $e) {
        $response['message'] = 'Error checking wishlist: ' . $e->getMessage();
        http_response_code(500);
    }
}
else {
    $response['message'] = 'Invalid action';
    http_response_code(400);
}

echo json_encode($response);
