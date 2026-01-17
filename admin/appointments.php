<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $appointment_id = (int)($_POST['appointment_id'] ?? 0);
        
        if ($action === 'update_status' && $appointment_id > 0) {
            $status = $_POST['status'];
            $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $appointment_id])) {
                $message = 'Appointment status updated successfully';
                $messageType = 'success';
            }
        }
        
        if ($action === 'send_reminder' && $appointment_id > 0) {
            // Check if reminder already sent
            $stmt = $db->prepare("SELECT * FROM appointments WHERE id = ? AND reminder_sent = 0");
            $stmt->execute([$appointment_id]);
            $appointment = $stmt->fetch();
            
            if ($appointment) {
                // Send email reminder
                if (file_exists('../includes/email.php')) {
                    require_once '../includes/email.php';
                    
                    // Get full appointment details
                    $stmt = $db->prepare("SELECT a.*, s.name as service_name, d.name as doctor_name 
                                          FROM appointments a 
                                          JOIN services s ON a.service_id = s.id 
                                          JOIN doctors d ON a.doctor_id = d.id 
                                          WHERE a.id = ?");
                    $stmt->execute([$appointment_id]);
                    $apt_data = $stmt->fetch();
                    
                    if (sendReviewRequest($apt_data)) {
                        // Mark reminder as sent
                        $stmt = $db->prepare("UPDATE appointments SET reminder_sent = 1 WHERE id = ?");
                        $stmt->execute([$appointment_id]);
                        
                        $message = 'Review request sent successfully to patient email';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to send email. Please check SMTP settings.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Email functionality not configured';
                    $messageType = 'error';
                }
            } else {
                $message = 'Reminder already sent or appointment not found';
                $messageType = 'error';
            }
        }
        
        if ($action === 'delete' && $appointment_id > 0) {
            $stmt = $db->prepare("DELETE FROM appointments WHERE id = ?");
            if ($stmt->execute([$appointment_id])) {
                $message = 'Appointment deleted successfully';
                $messageType = 'success';
            }
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT a.*, s.name as service_name, d.name as doctor_name 
          FROM appointments a 
          JOIN services s ON a.service_id = s.id 
          JOIN doctors d ON a.doctor_id = d.id 
          WHERE 1=1";

if ($filter === 'pending') {
    $query .= " AND a.status = 'pending'";
} elseif ($filter === 'confirmed') {
    $query .= " AND a.status = 'confirmed'";
} elseif ($filter === 'completed') {
    $query .= " AND a.status = 'completed'";
}

if (!empty($search)) {
    $query .= " AND (a.patient_name LIKE :search OR a.email LIKE :search OR a.phone LIKE :search)";
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', '%' . $search . '%');
}
$stmt->execute();
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation (same as dashboard) -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-tooth text-blue-600 text-2xl"></i>
                <h1 class="text-xl font-bold text-gray-800">Admin Panel</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a>
                <span class="text-gray-700"><?php echo $_SESSION['admin_name']; ?></span>
                <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Appointments Management</h2>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle mr-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <form method="GET" class="flex space-x-2">
                        <input type="text" name="search" placeholder="Search by name, email or phone..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-600">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="flex space-x-2">
                    <a href="?filter=all" class="flex-1 text-center px-4 py-2 rounded-lg <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        All
                    </a>
                    <a href="?filter=pending" class="flex-1 text-center px-4 py-2 rounded-lg <?php echo $filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        Pending
                    </a>
                    <a href="?filter=confirmed" class="flex-1 text-center px-4 py-2 rounded-lg <?php echo $filter === 'confirmed' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        Confirmed
                    </a>
                    <a href="?filter=completed" class="flex-1 text-center px-4 py-2 rounded-lg <?php echo $filter === 'completed' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        Completed
                    </a>
                </div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4">ID</th>
                            <th class="text-left py-3 px-4">Patient</th>
                            <th class="text-left py-3 px-4">Contact</th>
                            <th class="text-left py-3 px-4">Service</th>
                            <th class="text-left py-3 px-4">Doctor</th>
                            <th class="text-left py-3 px-4">Date & Time</th>
                            <th class="text-left py-3 px-4">Status</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500">No appointments found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($appointments as $apt): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4">#<?php echo $apt['id']; ?></td>
                            <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                            <td class="py-3 px-4">
                                <div class="text-sm">
                                    <div><i class="fas fa-envelope mr-1 text-gray-500"></i><?php echo htmlspecialchars($apt['email']); ?></div>
                                    <div><i class="fas fa-phone mr-1 text-gray-500"></i><?php echo htmlspecialchars($apt['phone']); ?></div>
                                </div>
                            </td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($apt['service_name']); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                            <td class="py-3 px-4">
                                <div><?php echo formatDate($apt['appointment_date']); ?></div>
                                <div class="text-sm text-gray-600"><?php echo formatTime($apt['appointment_time']); ?></div>
                            </td>
                            <td class="py-3 px-4">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" 
                                            class="px-3 py-1 rounded-full text-xs font-semibold border-0
                                            <?php echo $apt['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                       ($apt['status'] === 'confirmed' ? 'bg-blue-100 text-blue-800' : 
                                                       ($apt['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')); ?>">
                                        <option value="pending" <?php echo $apt['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $apt['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="completed" <?php echo $apt['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $apt['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <?php if ($apt['reminder_sent'] == 0): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Send review request to patient?');">
                                        <input type="hidden" name="action" value="send_reminder">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700" title="Send Review Request">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-xs text-green-600" title="Reminder sent">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this appointment?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>