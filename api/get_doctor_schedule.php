<?php
// Prevent any output before JSON
ob_start();

require_once '../includes/config.php';
require_once '../includes/db.php';

// Don't use functions.php here to avoid session issues
// require_once '../includes/functions.php';

// Clear any previous output
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$doctor_id = intval($_GET['doctor_id'] ?? 0);

if ($doctor_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid doctor ID']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id, name FROM doctors WHERE id = ? AND is_active = 1");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT day_of_week, start_time, end_time, is_available 
                          FROM doctor_schedules 
                          WHERE doctor_id = ? 
                          ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')");
    $stmt->execute([$doctor_id]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT appointment_date as date, appointment_time as time 
                          FROM appointments 
                          WHERE doctor_id = ? 
                          AND appointment_date >= CURDATE() 
                          AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                          AND status NOT IN ('cancelled')");
    $stmt->execute([$doctor_id]);
    $booked_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT leave_date, start_time, end_time, leave_type 
                          FROM doctor_leaves 
                          WHERE doctor_id = ? 
                          AND leave_date >= CURDATE()
                          AND leave_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)");
    $stmt->execute([$doctor_id]);
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'doctor' => $doctor,
        'schedule' => $schedule,
        'booked_slots' => $booked_slots,
        'leaves' => $leaves
    ]);
    
} catch (Exception $e) {
    error_log("Schedule API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
exit;
?>