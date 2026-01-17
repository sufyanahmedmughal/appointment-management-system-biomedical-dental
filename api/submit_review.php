<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Get form data
$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$review_text = sanitize($_POST['review_text'] ?? '');
$rating = 5; // Default 5 stars

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (strlen($name) < 2) {
    $errors[] = 'Name must be at least 2 characters';
}

if (!empty($email) && !validateEmail($email)) {
    $errors[] = 'Please provide a valid email';
}

if (empty($review_text)) {
    $errors[] = 'Review text is required';
}

if (strlen($review_text) < 10) {
    $errors[] = 'Review must be at least 10 characters';
}

if (strlen($review_text) > 1000) {
    $errors[] = 'Review must not exceed 1000 characters';
}

if (!empty($errors)) {
    jsonResponse(['success' => false, 'message' => implode(', ', $errors)]);
}

try {
    $db = getDB();
    
    // Insert review (requires admin approval)
    $stmt = $db->prepare("INSERT INTO reviews (name, email, review_text, rating, source, approved) 
                          VALUES (?, ?, ?, ?, 'manual', 0)");
    
    $stmt->execute([$name, $email, $review_text, $rating]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Thank you for your review! It will be published after admin approval.'
    ]);
    
} catch (Exception $e) {
    error_log("Review submission error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred. Please try again later.'], 500);
}
?>