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

// Handle schedule updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_schedule') {
        try {
            $db->beginTransaction();
            
            // Delete existing schedule for this doctor
            $stmt = $db->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
            $stmt->execute([$doctor_id]);
            
            // Insert new schedules
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            foreach ($days as $day) {
                $enabled = isset($_POST[$day . '_enabled']) ? 1 : 0;
                
                if ($enabled) {
                    $start_time = $_POST[$day . '_start'] ?? '';
                    $end_time = $_POST[$day . '_end'] ?? '';
                    
                    if (!empty($start_time) && !empty($end_time)) {
                        $stmt = $db->prepare("INSERT INTO doctor_schedules 
                                              (doctor_id, day_of_week, start_time, end_time, is_available) 
                                              VALUES (?, ?, ?, ?, 1)");
                        $stmt->execute([$doctor_id, $day, $start_time, $end_time]);
                    }
                }
            }
            
            $db->commit();
            $message = 'Schedule updated successfully!';
            $messageType = 'success';
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Error updating schedule: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get current schedule
$stmt = $db->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')");
$stmt->execute([$doctor_id]);
$schedules = $stmt->fetchAll();

// Organize by day
$schedule_by_day = [];
foreach ($schedules as $sch) {
    $schedule_by_day[$sch['day_of_week']] = $sch;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Doctor Dashboard</title>
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
        <h2 class="text-3xl font-bold text-gray-800 mb-8">My Schedule</h2>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle mr-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6">Set Your Weekly Availability</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_schedule">
                
                <?php
                $days = [
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday',
                    'sunday' => 'Sunday'
                ];
                
                foreach ($days as $day => $day_name):
                    $schedule = $schedule_by_day[$day] ?? null;
                    $is_enabled = $schedule !== null;
                    $start_time = $schedule['start_time'] ?? '09:00';
                    $end_time = $schedule['end_time'] ?? '17:00';
                ?>
                
                <div class="mb-6 pb-6 border-b border-gray-200">
                    <div class="flex items-center mb-3">
                        <input type="checkbox" 
                               name="<?php echo $day; ?>_enabled" 
                               id="<?php echo $day; ?>_enabled" 
                               <?php echo $is_enabled ? 'checked' : ''; ?>
                               class="mr-3 w-5 h-5 text-green-600"
                               onchange="toggleDay('<?php echo $day; ?>')">
                        <label for="<?php echo $day; ?>_enabled" class="text-lg font-semibold text-gray-800">
                            <?php echo $day_name; ?>
                        </label>
                    </div>
                    
                    <div id="<?php echo $day; ?>_times" class="grid grid-cols-1 md:grid-cols-2 gap-4 ml-8 <?php echo $is_enabled ? '' : 'hidden'; ?>">
                        <div>
                            <label class="block text-gray-700 mb-2">Start Time</label>
                            <input type="time" 
                                   name="<?php echo $day; ?>_start" 
                                   value="<?php echo $start_time; ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-600">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">End Time</label>
                            <input type="time" 
                                   name="<?php echo $day; ?>_end" 
                                   value="<?php echo $end_time; ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-600">
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
                
                <div class="flex space-x-4">
                    <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700">
                        <i class="fas fa-save mr-2"></i>Save Schedule
                    </button>
                    <a href="dashboard.php" class="bg-gray-300 text-gray-700 px-8 py-3 rounded-lg font-semibold hover:bg-gray-400">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Tips -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="font-bold text-blue-800 mb-3">
                <i class="fas fa-info-circle mr-2"></i>Tips for Setting Your Schedule
            </h4>
            <ul class="space-y-2 text-blue-700">
                <li><i class="fas fa-check mr-2"></i>Patients will see available slots based on this schedule</li>
                <li><i class="fas fa-check mr-2"></i>You can set different hours for each day</li>
                <li><i class="fas fa-check mr-2"></i>Uncheck days when you're not available</li>
                <li><i class="fas fa-check mr-2"></i>Use the "Leaves" section for specific dates you won't be available</li>
                <li><i class="fas fa-check mr-2"></i>Appointment slots are generated every 30 minutes</li>
            </ul>
        </div>
    </div>

    <script>
        function toggleDay(day) {
            const checkbox = document.getElementById(day + '_enabled');
            const timesDiv = document.getElementById(day + '_times');
            
            if (checkbox.checked) {
                timesDiv.classList.remove('hidden');
            } else {
                timesDiv.classList.add('hidden');
            }
        }
    </script>
</body>
</html>