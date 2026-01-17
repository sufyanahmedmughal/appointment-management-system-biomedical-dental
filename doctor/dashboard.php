<?php
// Session already started in functions.php - don't start again
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: index.php');
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$db = getDB();

// Get doctor info
$stmt = $db->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

// Get doctor statistics
$stats = [
    'total_appointments' => $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?"),
    'today_appointments' => $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()"),
    'pending_appointments' => $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending'"),
    'completed_appointments' => $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'completed'")
];

foreach ($stats as $key => $stmt) {
    $stmt->execute([$doctor_id]);
    $stats[$key] = $stmt->fetchColumn();
}

// Get today's appointments
$stmt = $db->prepare("SELECT a.*, s.name as service_name 
                      FROM appointments a 
                      JOIN services s ON a.service_id = s.id 
                      WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
                      ORDER BY a.appointment_time ASC");
$stmt->execute([$doctor_id]);
$today_appointments = $stmt->fetchAll();

// Get upcoming appointments (next 7 days)
$stmt = $db->prepare("SELECT a.*, s.name as service_name 
                      FROM appointments a 
                      JOIN services s ON a.service_id = s.id 
                      WHERE a.doctor_id = ? 
                      AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                      AND a.status != 'cancelled'
                      ORDER BY a.appointment_date ASC, a.appointment_time ASC
                      LIMIT 10");
$stmt->execute([$doctor_id]);
$upcoming_appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - <?php echo htmlspecialchars($doctor['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-user-md text-green-600 text-2xl"></i>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Doctor Dashboard</h1>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($doctor['name']); ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <?php if ($doctor['google_calendar_connected']): ?>
                <span class="text-green-600 text-sm">
                    <i class="fas fa-calendar-check mr-1"></i>Calendar Connected
                </span>
                <?php else: ?>
                <a href="google_calendar_connect.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fab fa-google mr-2"></i>Connect Calendar
                </a>
                <?php endif; ?>
                <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 min-h-screen text-white">
            <ul class="py-4">
                <li>
                    <a href="dashboard.php" class="flex items-center px-6 py-3 bg-green-600">
                        <i class="fas fa-chart-line mr-3"></i>Dashboard
                    </a>
                </li>
                <li>
                    <a href="appointments.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                        <i class="fas fa-calendar-alt mr-3"></i>My Appointments
                        <?php if ($stats['pending_appointments'] > 0): ?>
                        <span class="ml-auto bg-yellow-600 px-2 py-1 rounded-full text-xs"><?php echo $stats['pending_appointments']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="schedule.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                        <i class="fas fa-clock mr-3"></i>My Schedule
                    </a>
                </li>
                <li>
                    <a href="leaves.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                        <i class="fas fa-calendar-times mr-3"></i>Leaves & Breaks
                    </a>
                </li>
                <li>
                    <a href="../index.php" target="_blank" class="flex items-center px-6 py-3 hover:bg-gray-700">
                        <i class="fas fa-external-link-alt mr-3"></i>View Website
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Welcome, Dr. <?php echo htmlspecialchars($doctor['name']); ?></h2>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Appointments</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_appointments']; ?></p>
                        </div>
                        <div class="bg-blue-100 p-4 rounded-full">
                            <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Today's Appointments</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['today_appointments']; ?></p>
                        </div>
                        <div class="bg-green-100 p-4 rounded-full">
                            <i class="fas fa-calendar-day text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Pending</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['pending_appointments']; ?></p>
                        </div>
                        <div class="bg-yellow-100 p-4 rounded-full">
                            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Completed</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['completed_appointments']; ?></p>
                        </div>
                        <div class="bg-purple-100 p-4 rounded-full">
                            <i class="fas fa-check-circle text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-calendar-day mr-2 text-green-600"></i>Today's Appointments
                </h3>
                <?php if (empty($today_appointments)): ?>
                <p class="text-gray-500 text-center py-4">No appointments scheduled for today</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($today_appointments as $apt): ?>
                    <div class="border-l-4 border-green-500 bg-green-50 p-4 rounded-r-lg">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($apt['patient_name']); ?></span>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $apt['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </div>
                                <p class="text-gray-600"><i class="fas fa-tooth mr-2"></i><?php echo htmlspecialchars($apt['service_name']); ?></p>
                                <p class="text-gray-600"><i class="far fa-clock mr-2"></i><?php echo formatTime($apt['appointment_time']); ?></p>
                                <p class="text-sm text-gray-500"><i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($apt['phone']); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-green-600"><?php echo formatTime($apt['appointment_time']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="mt-4 text-right">
                    <a href="appointments.php" class="text-green-600 hover:underline">View All Appointments →</a>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-calendar-week mr-2 text-blue-600"></i>Upcoming Appointments (Next 7 Days)
                </h3>
                <?php if (empty($upcoming_appointments)): ?>
                <p class="text-gray-500 text-center py-4">No upcoming appointments</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4">Date</th>
                                <th class="text-left py-3 px-4">Time</th>
                                <th class="text-left py-3 px-4">Patient</th>
                                <th class="text-left py-3 px-4">Service</th>
                                <th class="text-left py-3 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_appointments as $apt): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo formatDate($apt['appointment_date']); ?></td>
                                <td class="py-3 px-4"><?php echo formatTime($apt['appointment_time']); ?></td>
                                <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($apt['service_name']); ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        <?php echo $apt['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($apt['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'); ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>