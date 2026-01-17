<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();

// Get statistics
$stats = [
    'total_appointments' => $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
    'pending_appointments' => $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn(),
    'total_services' => $db->query("SELECT COUNT(*) FROM services WHERE is_active = 1")->fetchColumn(),
    'total_doctors' => $db->query("SELECT COUNT(*) FROM doctors WHERE is_active = 1")->fetchColumn(),
    'pending_reviews' => $db->query("SELECT COUNT(*) FROM reviews WHERE approved = 0 AND source = 'manual'")->fetchColumn(),
    'total_reviews' => $db->query("SELECT COUNT(*) FROM reviews WHERE approved = 1")->fetchColumn()
];

// Get recent appointments
$stmt = $db->query("SELECT a.*, s.name as service_name, d.name as doctor_name 
                    FROM appointments a 
                    JOIN services s ON a.service_id = s.id 
                    JOIN doctors d ON a.doctor_id = d.id 
                    ORDER BY a.created_at DESC LIMIT 5");
$recent_appointments = $stmt->fetchAll();

// Get pending reviews
$stmt = $db->query("SELECT * FROM reviews WHERE approved = 0 AND source = 'manual' ORDER BY created_at DESC LIMIT 5");
$pending_reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-tooth text-blue-600 text-2xl"></i>
                <h1 class="text-xl font-bold text-gray-800">Admin Panel</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-700"><i class="fas fa-user mr-2"></i><?php echo $_SESSION['admin_name']; ?></span>
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
                    <a href="dashboard.php" class="flex items-center px-6 py-3 bg-blue-600">
                        <i class="fas fa-chart-line mr-3"></i>Dashboard
                    </a>
                </li>
                <li>
                    <a href="appointments.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                        <i class="fas fa-calendar-check mr-3"></i>Appointments
                        <?php if ($stats['pending_appointments'] > 0): ?>
                        <span class="ml-auto bg-red-600 px-2 py-1 rounded-full text-xs"><?php echo $stats['pending_appointments']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="services.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                        <i class="fas fa-briefcase mr-3"></i>Services
                    </a>
                </li>
                <li>
                    <a href="doctors.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                        <i class="fas fa-user-md mr-3"></i>Doctors
                    </a>
                </li>
                <li>
                    <a href="reviews.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                        <i class="fas fa-star mr-3"></i>Reviews
                        <?php if ($stats['pending_reviews'] > 0): ?>
                        <span class="ml-auto bg-yellow-600 px-2 py-1 rounded-full text-xs"><?php echo $stats['pending_reviews']; ?></span>
                        <?php endif; ?>
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
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Dashboard Overview</h2>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
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
                            <p class="text-gray-500 text-sm">Pending Appointments</p>
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
                            <p class="text-gray-500 text-sm">Active Services</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_services']; ?></p>
                        </div>
                        <div class="bg-green-100 p-4 rounded-full">
                            <i class="fas fa-briefcase text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Active Doctors</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_doctors']; ?></p>
                        </div>
                        <div class="bg-purple-100 p-4 rounded-full">
                            <i class="fas fa-user-md text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Pending Reviews</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['pending_reviews']; ?></p>
                        </div>
                        <div class="bg-orange-100 p-4 rounded-full">
                            <i class="fas fa-star-half-alt text-orange-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Approved Reviews</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_reviews']; ?></p>
                        </div>
                        <div class="bg-pink-100 p-4 rounded-full">
                            <i class="fas fa-star text-pink-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Recent Appointments</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-3 px-4">Patient Name</th>
                                <th class="text-left py-3 px-4">Service</th>
                                <th class="text-left py-3 px-4">Doctor</th>
                                <th class="text-left py-3 px-4">Date & Time</th>
                                <th class="text-left py-3 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_appointments)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">No appointments yet</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_appointments as $apt): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($apt['service_name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                                <td class="py-3 px-4"><?php echo formatDate($apt['appointment_date']) . ' ' . formatTime($apt['appointment_time']); ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        <?php echo $apt['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($apt['status'] === 'confirmed' ? 'bg-blue-100 text-blue-800' : 
                                                   ($apt['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')); ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-right">
                    <a href="appointments.php" class="text-blue-600 hover:underline">View All Appointments →</a>
                </div>
            </div>

            <!-- Pending Reviews -->
            <?php if (!empty($pending_reviews)): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Pending Reviews</h3>
                <div class="space-y-4">
                    <?php foreach ($pending_reviews as $review): ?>
                    <div class="border-l-4 border-yellow-500 bg-yellow-50 p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($review['name']); ?></p>
                                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                <p class="text-sm text-gray-500 mt-2"><?php echo formatDate($review['created_at']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 text-right">
                    <a href="reviews.php" class="text-blue-600 hover:underline">Manage Reviews →</a>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>