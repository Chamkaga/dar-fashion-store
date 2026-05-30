<?php

/**
 * Handle product image upload
 * 
 * @param array $file The $_FILES entry for the image
 * @param string $uploadDir The directory to save the file (relative path)
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function upload_product_image($file, $uploadDir = '../../assets/images/products/') {
    $response = ['success' => false, 'path' => null, 'error' => null];
    
    // Validate file was uploaded
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        $response['error'] = 'File upload failed. Please try again.';
        return $response;
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        $response['error'] = 'File size exceeds 5MB limit.';
        return $response;
    }
    
    // Validate file type
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        $response['error'] = 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.';
        return $response;
    }
    
    // Create upload directory if it doesn't exist
    $fullDir = __DIR__ . '/' . $uploadDir;
    if (!is_dir($fullDir)) {
        if (!mkdir($fullDir, 0755, true)) {
            $response['error'] = 'Failed to create upload directory.';
            return $response;
        }
    }
    
    // Generate unique filename with timestamp
    $timestamp = time();
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $timestamp . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
    $filepath = $fullDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Return path relative to website root
        $response['success'] = true;
        $response['path'] = '/assets/images/products/' . $filename;
        return $response;
    } else {
        $response['error'] = 'Failed to save file. Check directory permissions.';
        return $response;
    }
}

/**
 * Handle product image deletion
 * 
 * @param string $imagePath The image path to delete
 * @return bool True if deleted or not a local file, false on error
 */
function delete_product_image($imagePath) {
    // Only delete if it's a local file path (not external URL)
    if (!$imagePath || strpos($imagePath, 'http') === 0) {
        return true; // Don't delete external URLs
    }
    
    $fullPath = __DIR__ . '/' . $imagePath;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    
    return true;
}

?>
