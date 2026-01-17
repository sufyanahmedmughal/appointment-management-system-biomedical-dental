<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$appointment_id = intval($_POST['appointment_id'] ?? 0);

if ($appointment_id <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid appointment ID']);
}

try {
    $db = getDB();
    
    // Check if reminder already sent
    $stmt = $db->prepare("SELECT * FROM appointments WHERE id = ? AND reminder_sent = 0");
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        jsonResponse(['success' => false, 'message' => 'Reminder already sent or appointment not found']);
    }
    
    // Get full appointment details
    $stmt = $db->prepare("SELECT a.*, s.name as service_name, d.name as doctor_name 
                          FROM appointments a 
                          JOIN services s ON a.service_id = s.id 
                          JOIN doctors d ON a.doctor_id = d.id 
                          WHERE a.id = ?");
    $stmt->execute([$appointment_id]);
    $apt_data = $stmt->fetch();
    
    // Send email reminder
    if (file_exists('../includes/email.php')) {
        require_once '../includes/email.php';
        
        if (sendReviewRequest($apt_data)) {
            // Mark reminder as sent
            $stmt = $db->prepare("UPDATE appointments SET reminder_sent = 1 WHERE id = ?");
            $stmt->execute([$appointment_id]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Review request sent successfully to patient email'
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to send email. Please check SMTP settings.']);
        }
    } else {
        jsonResponse(['success' => false, 'message' => 'Email functionality not configured']);
    }
    
} catch (Exception $e) {
    error_log("Send reminder error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred while sending reminder.'], 500);
}
?>