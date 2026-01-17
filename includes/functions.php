<?php
session_start();

// Check if user is logged in (admin)
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_email']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone (Pakistani format)
function validatePhone($phone) {
    return preg_match('/^(\+92|0)?[0-9]{10}$/', $phone);
}

// Upload image
function uploadImage($file, $prefix = 'img') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
    $destination = UPLOAD_PATH . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename, 'url' => UPLOAD_URL . $filename];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

// Delete image
function deleteImage($filename) {
    $filepath = UPLOAD_PATH . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// Format price
function formatPrice($price) {
    return 'Rs. ' . number_format($price, 0);
}

// Calculate discounted price
function getDiscountedPrice($price, $discount) {
    if ($discount > 0) {
        return $price - ($price * $discount / 100);
    }
    return $price;
}

// Get setting value
function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

// Update setting
function updateSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Format date for display
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Format time for display
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>