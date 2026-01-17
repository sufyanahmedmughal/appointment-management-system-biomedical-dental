<?php
// Session already started in functions.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['doctor_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: index.php');
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$db = getDB();
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        
        if ($action === 'confirm' && $appointment_id > 0) {
            // Verify appointment belongs to this doctor
            $stmt = $db->prepare("SELECT * FROM appointments WHERE id = ? AND doctor_id = ?");
            $stmt->execute([$appointment_id, $doctor_id]);
            $apt = $stmt->fetch();
            
            if ($apt) {
                $stmt = $db->prepare("UPDATE appointments SET status = 'confirmed', confirmed_by_doctor = 1 WHERE id = ?");
                if ($stmt->execute([$appointment_id])) {
                    // Send confirmation email to patient
                    if (file_exists('../includes/email.php')) {
                        require_once '../includes/email.php';
                        
                        $stmt = $db->prepare("SELECT a.*, s.name as service_name, d.name as doctor_name 
                                              FROM appointments a 
                                              JOIN services s ON a.service_id = s.id 
                                              JOIN doctors d ON a.doctor_id = d.id 
                                              WHERE a.id = ?");
                        $stmt->execute([$appointment_id]);
                        $apt_data = $stmt->fetch();
                        
                        sendAppointmentConfirmation($apt_data);
                    }
                    
                    $message = "Appointment confirmed! Email sent to patient. WhatsApp: {$apt['phone']} - Message: 'Your appointment is confirmed for " . formatDate($apt['appointment_date']) . " at " . formatTime($apt['appointment_time']) . "'";
                    $messageType = 'success';
                }
            }
        }
        
        if ($action === 'complete' && $appointment_id > 0) {
            $stmt = $db->prepare("UPDATE appointments SET status = 'completed' WHERE id = ? AND doctor_id = ?");
            if ($stmt->execute([$appointment_id, $doctor_id])) {
                $message = 'Appointment marked as completed';
                $messageType = 'success';
            }
        }
        
        if ($action === 'cancel' && $appointment_id > 0) {
            $cancel_reason = sanitize($_POST['cancel_reason'] ?? 'Doctor cancelled appointment');
            
            // Get appointment details before cancelling
            $stmt = $db->prepare("SELECT a.*, s.name as service_name, d.name as doctor_name 
                                  FROM appointments a 
                                  JOIN services s ON a.service_id = s.id 
                                  JOIN doctors d ON a.doctor_id = d.id 
                                  WHERE a.id = ? AND a.doctor_id = ?");
            $stmt->execute([$appointment_id, $doctor_id]);
            $apt = $stmt->fetch();
            
            if ($apt) {
                $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled', notes = ? WHERE id = ? AND doctor_id = ?");
                if ($stmt->execute([$cancel_reason, $appointment_id, $doctor_id])) {
                    // Send cancellation email
                    if (file_exists('../includes/email.php')) {
                        require_once '../includes/email.php';
                        
                        $subject = "Appointment Cancelled - Bio Medical Dental Care";
                        $body = "
                        <html>
                        <body style='font-family: Arial, sans-serif;'>
                            <h2 style='color: #dc2626;'>Appointment Cancelled</h2>
                            <p>Dear {$apt['patient_name']},</p>
                            <p>Your appointment has been cancelled by the doctor.</p>
                            <p><strong>Cancelled Appointment Details:</strong></p>
                            <ul>
                                <li><strong>Service:</strong> {$apt['service_name']}</li>
                                <li><strong>Doctor:</strong> {$apt['doctor_name']}</li>
                                <li><strong>Date:</strong> " . formatDate($apt['appointment_date']) . "</li>
                                <li><strong>Time:</strong> " . formatTime($apt['appointment_time']) . "</li>
                            </ul>
                            <p><strong>Reason:</strong> {$cancel_reason}</p>
                            <p>Please contact us to reschedule or book a new appointment.</p>
                            <p>Best regards,<br>Bio Medical Dental Care<br>Phone: " . getSetting('clinic_phone') . "</p>
                        </body>
                        </html>
                        ";
                        
                        sendEmail($apt['email'], $subject, $body, $apt['patient_name']);
                    }
                    
                    $whatsapp_msg = urlencode("Your appointment has been cancelled. Reason: {$cancel_reason}. Date: " . formatDate($apt['appointment_date']) . " at " . formatTime($apt['appointment_time']) . ". Please contact us to reschedule.");
                    
                    $message = "Appointment cancelled! Email sent to patient. <a href='https://wa.me/{$apt['phone']}?text={$whatsapp_msg}' target='_blank' class='text-green-600 underline'>Click to send WhatsApp message</a>";
                    $messageType = 'success';
                }
            }
        }
        
        if ($action === 'reschedule' && $appointment_id > 0) {
            $new_date = sanitize($_POST['new_date'] ?? '');
            $new_time = sanitize($_POST['new_time'] ?? '');
            $reschedule_reason = sanitize($_POST['reschedule_reason'] ?? 'Doctor rescheduled appointment');
            
            if (!empty($new_date) && !empty($new_time)) {
                // Check if new slot is available
                $stmt = $db->prepare("SELECT id FROM appointments 
                                      WHERE doctor_id = ? 
                                      AND appointment_date = ? 
                                      AND appointment_time = ? 
                                      AND status != 'cancelled'
                                      AND id != ?");
                $stmt->execute([$doctor_id, $new_date, $new_time, $appointment_id]);
                
                if ($stmt->fetch()) {
                    $message = 'New time slot is already booked. Please choose another time.';
                    $messageType = 'error';
                } else {
                    // Get old appointment details
                    $stmt = $db->prepare("SELECT a.*, s.name as service_name, d.name as doctor_name 
                                          FROM appointments a 
                                          JOIN services s ON a.service_id = s.id 
                                          JOIN doctors d ON a.doctor_id = d.id 
                                          WHERE a.id = ?");
                    $stmt->execute([$appointment_id]);
                    $old_apt = $stmt->fetch();
                    
                    $stmt = $db->prepare("UPDATE appointments 
                                          SET appointment_date = ?, appointment_time = ?, status = 'rescheduled', notes = ? 
                                          WHERE id = ? AND doctor_id = ?");
                    if ($stmt->execute([$new_date, $new_time, $reschedule_reason, $appointment_id, $doctor_id])) {
                        // Send reschedule email
                        if (file_exists('../includes/email.php')) {
                            require_once '../includes/email.php';
                            
                            $subject = "Appointment Rescheduled - Bio Medical Dental Care";
                            $body = "
                            <html>
                            <body style='font-family: Arial, sans-serif;'>
                                <h2 style='color: #2563eb;'>Appointment Rescheduled</h2>
                                <p>Dear {$old_apt['patient_name']},</p>
                                <p>Your appointment has been rescheduled by the doctor.</p>
                                
                                <p><strong>Previous Appointment:</strong></p>
                                <ul>
                                    <li>Date: " . formatDate($old_apt['appointment_date']) . "</li>
                                    <li>Time: " . formatTime($old_apt['appointment_time']) . "</li>
                                </ul>
                                
                                <p><strong>New Appointment:</strong></p>
                                <ul>
                                    <li><strong>Service:</strong> {$old_apt['service_name']}</li>
                                    <li><strong>Doctor:</strong> {$old_apt['doctor_name']}</li>
                                    <li><strong>Date:</strong> " . formatDate($new_date) . "</li>
                                    <li><strong>Time:</strong> " . formatTime($new_time) . "</li>
                                </ul>
                                
                                <p><strong>Reason:</strong> {$reschedule_reason}</p>
                                <p>If this time doesn't work for you, please contact us to reschedule.</p>
                                <p>Best regards,<br>Bio Medical Dental Care<br>Phone: " . getSetting('clinic_phone') . "</p>
                            </body>
                            </html>
                            ";
                            
                            sendEmail($old_apt['email'], $subject, $body, $old_apt['patient_name']);
                        }
                        
                        $whatsapp_msg = urlencode("Your appointment has been rescheduled. Reason: {$reschedule_reason}. New date: " . formatDate($new_date) . " at " . formatTime($new_time) . ". If this doesn't work for you, please contact us.");
                        
                        $message = "Appointment rescheduled! Email sent to patient. <a href='https://wa.me/{$old_apt['phone']}?text={$whatsapp_msg}' target='_blank' class='text-green-600 underline'>Click to send WhatsApp message</a>";
                        $messageType = 'success';
                    }
                }
            }
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Build query
$query = "SELECT a.*, s.name as service_name 
          FROM appointments a 
          JOIN services s ON a.service_id = s.id 
          WHERE a.doctor_id = ?";

if ($filter === 'pending') {
    $query .= " AND a.status = 'pending'";
} elseif ($filter === 'confirmed') {
    $query .= " AND a.status = 'confirmed'";
} elseif ($filter === 'completed') {
    $query .= " AND a.status = 'completed'";
} elseif ($filter === 'today') {
    $query .= " AND a.appointment_date = CURDATE()";
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $db->prepare($query);
$stmt->execute([$doctor_id]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Doctor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-user-md text-green-600 text-2xl"></i>
                <h1 class="text-xl font-bold text-gray-800">Doctor Dashboard</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-green-600 hover:underline">Dashboard</a>
                <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-8">My Appointments</h2>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle mr-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-wrap gap-2">
                <a href="?filter=all" class="px-4 py-2 rounded-lg <?php echo $filter === 'all' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    All
                </a>
                <a href="?filter=today" class="px-4 py-2 rounded-lg <?php echo $filter === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    Today
                </a>
                <a href="?filter=pending" class="px-4 py-2 rounded-lg <?php echo $filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    Pending
                </a>
                <a href="?filter=confirmed" class="px-4 py-2 rounded-lg <?php echo $filter === 'confirmed' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    Confirmed
                </a>
                <a href="?filter=completed" class="px-4 py-2 rounded-lg <?php echo $filter === 'completed' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    Completed
                </a>
            </div>
        </div>

        <!-- Appointments List -->
        <?php if (empty($appointments)): ?>
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
            <p class="text-xl text-gray-500">No appointments found</p>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($appointments as $apt): ?>
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                <div class="flex flex-col md:flex-row justify-between">
                    <div class="flex-1 mb-4 md:mb-0">
                        <div class="flex items-center space-x-3 mb-3">
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($apt['patient_name']); ?></h3>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                <?php echo $apt['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($apt['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                           ($apt['status'] === 'completed' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800')); ?>">
                                <?php echo ucfirst($apt['status']); ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-gray-600">
                            <div><i class="fas fa-tooth mr-2 text-blue-600"></i><strong>Service:</strong> <?php echo htmlspecialchars($apt['service_name']); ?></div>
                            <div><i class="fas fa-calendar mr-2 text-blue-600"></i><strong>Date:</strong> <?php echo formatDate($apt['appointment_date']); ?></div>
                            <div><i class="far fa-clock mr-2 text-blue-600"></i><strong>Time:</strong> <?php echo formatTime($apt['appointment_time']); ?></div>
                            <div><i class="fas fa-envelope mr-2 text-blue-600"></i><strong>Email:</strong> <?php echo htmlspecialchars($apt['email']); ?></div>
                            <div class="md:col-span-2">
                                <i class="fas fa-phone mr-2 text-green-600"></i>
                                <strong>Phone (WhatsApp):</strong> 
                                <span class="font-mono bg-green-50 px-2 py-1 rounded"><?php echo htmlspecialchars($apt['phone']); ?></span>
                                <a href="https://wa.me/<?php echo $apt['phone']; ?>" target="_blank" class="ml-2 text-green-600 hover:text-green-700">
                                    <i class="fab fa-whatsapp"></i> Send WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col space-y-2 md:ml-6">
                        <?php if ($apt['status'] === 'pending'): ?>
                        <form method="POST" onsubmit="return confirm('Confirm this appointment? Patient will receive email confirmation.');">
                            <input type="hidden" name="action" value="confirm">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                <i class="fas fa-check mr-2"></i>Confirm
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if ($apt['status'] === 'confirmed'): ?>
                        <form method="POST" onsubmit="return confirm('Mark as completed?');">
                            <input type="hidden" name="action" value="complete">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                                <i class="fas fa-check-circle mr-2"></i>Complete
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if (in_array($apt['status'], ['pending', 'confirmed'])): ?>
                        <button onclick="openRescheduleModal(<?php echo htmlspecialchars(json_encode($apt)); ?>)" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fas fa-calendar-alt mr-2"></i>Reschedule
                        </button>
                        
                        <form method="POST" onsubmit="return confirm('Cancel this appointment?');">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Reschedule Appointment</h3>
            <p class="text-gray-600 mb-4">Patient: <strong id="modal-patient-name"></strong></p>
            
            <form method="POST">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="appointment_id" id="modal-appointment-id">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">New Date</label>
                    <input type="date" name="new_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border rounded">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">New Time</label>
                    <input type="time" name="new_time" required class="w-full px-4 py-2 border rounded">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Reason for Rescheduling *</label>
                    <textarea name="reschedule_reason" required rows="3" class="w-full px-4 py-2 border rounded" placeholder="e.g., Emergency, Doctor unavailable, etc."></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                        Reschedule
                    </button>
                    <button type="button" onclick="closeRescheduleModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold text-red-600 mb-4">Cancel Appointment</h3>
            <p class="text-gray-600 mb-4">Patient: <strong id="cancel-patient-name"></strong></p>
            
            <form method="POST">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="appointment_id" id="cancel-appointment-id">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Reason for Cancellation *</label>
                    <textarea name="cancel_reason" required rows="3" class="w-full px-4 py-2 border rounded" placeholder="e.g., Doctor emergency, Patient requested, etc."></textarea>
                    <p class="text-sm text-gray-500 mt-1">Patient will receive this reason via email and WhatsApp</p>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded hover:bg-red-700">
                        Cancel Appointment
                    </button>
                    <button type="button" onclick="closeCancelModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded hover:bg-gray-400">
                        Back
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRescheduleModal(apt) {
            document.getElementById('modal-patient-name').textContent = apt.patient_name;
            document.getElementById('modal-appointment-id').value = apt.id;
            document.getElementById('rescheduleModal').classList.remove('hidden');
        }
        
        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').classList.add('hidden');
        }
        
        function openCancelModal(aptId, patientName) {
            document.getElementById('cancel-patient-name').textContent = patientName;
            document.getElementById('cancel-appointment-id').value = aptId;
            document.getElementById('cancelModal').classList.remove('hidden');
        }
        
        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }
        
        document.getElementById('rescheduleModal').addEventListener('click', function(e) {
            if (e.target === this) closeRescheduleModal();
        });
        
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) closeCancelModal();
        });
    </script>
</body>
</html>