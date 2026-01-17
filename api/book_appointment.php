<?php
// Prevent any output before JSON
ob_start();

require_once '../includes/config.php';
require_once '../includes/db.php';

// Clear output buffer
ob_clean();

header('Content-Type: application/json');

// Start session for rate limiting only
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple sanitize function
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Simple email validation
function validEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
// Rate limiting
$current_time = time();
$rate_limit_window = 60;
$max_bookings_per_window = 3;

if (!isset($_SESSION['booking_timestamps'])) {
    $_SESSION['booking_timestamps'] = [];
}

$_SESSION['booking_timestamps'] = array_filter($_SESSION['booking_timestamps'], function($timestamp) use ($current_time, $rate_limit_window) {
    return ($current_time - $timestamp) < $rate_limit_window;
});

if (count($_SESSION['booking_timestamps']) >= $max_bookings_per_window) {
    echo json_encode(['success' => false, 'message' => 'Too many booking attempts. Please wait.']);
    exit;
}

$_SESSION['booking_timestamps'][] = $current_time;

// Get form data
$patient_name = clean($_POST['patient_name'] ?? '');
$email = clean($_POST['email'] ?? '');
$phone = clean($_POST['phone'] ?? '');
$service_id = intval($_POST['service_id'] ?? 0);
$doctor_id = intval($_POST['doctor_id'] ?? 0);
$appointment_date = clean($_POST['appointment_date'] ?? '');
$appointment_time = clean($_POST['appointment_time'] ?? '');

// Validation
$errors = [];

if (empty($patient_name) || strlen($patient_name) < 3) {
    $errors[] = 'Patient name required (min 3 characters)';
}

if (empty($email) || !validEmail($email)) {
    $errors[] = 'Valid email required';
}

if (empty($phone) || !preg_match('/^92[0-9]{10}$/', $phone)) {
    $errors[] = 'Phone format: 92XXXXXXXXXX';
}

if ($service_id <= 0) $errors[] = 'Select service';
if ($doctor_id <= 0) $errors[] = 'Select doctor';
if (empty($appointment_date)) $errors[] = 'Select date';
if (empty($appointment_time)) $errors[] = 'Select time';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Check service exists
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Invalid service']);
        exit;
    }
    
    // Check doctor exists
    $stmt = $db->prepare("SELECT * FROM doctors WHERE id = ? AND is_active = 1");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Invalid doctor']);
        exit;
    }
    
    // Check for double booking
    $stmt = $db->prepare("SELECT id FROM appointments 
                          WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
                          AND status != 'cancelled' FOR UPDATE");
    $stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
    
    if ($stmt->fetch()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Time slot already booked']);
        exit;
    }
    
    // Insert appointment
    $stmt = $db->prepare("INSERT INTO appointments 
                          (patient_name, email, phone, service_id, doctor_id, appointment_date, appointment_time, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    $stmt->execute([$patient_name, $email, $phone, $service_id, $doctor_id, $appointment_date, $appointment_time]);
    
    $appointment_id = $db->lastInsertId();
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully! Doctor will confirm soon.',
        'appointment_id' => $appointment_id
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Booking failed. Please try again.']);
}
exit;
?>