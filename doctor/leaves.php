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

// Handle leave actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_leave') {
            $leave_date = sanitize($_POST['leave_date']);
            $leave_type = sanitize($_POST['leave_type']);
            $start_time = sanitize($_POST['start_time'] ?? '');
            $end_time = sanitize($_POST['end_time'] ?? '');
            $reason = sanitize($_POST['reason'] ?? '');
            
            if (empty($leave_date)) {
                $message = 'Leave date is required';
                $messageType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO doctor_leaves (doctor_id, leave_date, start_time, end_time, leave_type, reason) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$doctor_id, $leave_date, $start_time, $end_time, $leave_type, $reason])) {
                    $message = 'Leave added successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add leave';
                    $messageType = 'error';
                }
            }
        }
        
        if ($action === 'delete_leave') {
            $leave_id = intval($_POST['leave_id']);
            $stmt = $db->prepare("DELETE FROM doctor_leaves WHERE id = ? AND doctor_id = ?");
            if ($stmt->execute([$leave_id, $doctor_id])) {
                $message = 'Leave deleted successfully';
                $messageType = 'success';
            }
        }
    }
}

// Get all leaves
$stmt = $db->prepare("SELECT * FROM doctor_leaves WHERE doctor_id = ? ORDER BY leave_date DESC");
$stmt->execute([$doctor_id]);
$leaves = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaves & Breaks - Doctor Dashboard</title>
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
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Leaves & Breaks</h2>
            <button onclick="openAddModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Add Leave
            </button>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle mr-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Leaves List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4">Date</th>
                        <th class="text-left py-3 px-4">Type</th>
                        <th class="text-left py-3 px-4">Time</th>
                        <th class="text-left py-3 px-4">Reason</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaves)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-500">No leaves scheduled</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($leaves as $leave): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?php echo formatDate($leave['leave_date']); ?></td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                <?php echo $leave['leave_type'] === 'full_day' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $leave['leave_type'])); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($leave['leave_type'] === 'full_day'): ?>
                                Full Day
                            <?php else: ?>
                                <?php echo formatTime($leave['start_time']); ?> - <?php echo formatTime($leave['end_time']); ?>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4"><?php echo htmlspecialchars($leave['reason']); ?></td>
                        <td class="py-3 px-4">
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this leave?');">
                                <input type="hidden" name="action" value="delete_leave">
                                <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Leave Modal -->
    <div id="leaveModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">Add Leave/Break</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="add_leave">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Leave Date *</label>
                    <input type="date" name="leave_date" required min="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-green-600">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Leave Type *</label>
                    <select name="leave_type" id="leave_type" required onchange="toggleTimes()"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-green-600">
                        <option value="full_day">Full Day</option>
                        <option value="partial">Partial Day</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
                
                <div id="timeFields" class="mb-4 hidden">
                    <label class="block text-gray-700 font-semibold mb-2">Time Range</label>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="time" name="start_time" 
                               class="px-4 py-2 border rounded-lg focus:outline-none focus:border-green-600">
                        <input type="time" name="end_time"
                               class="px-4 py-2 border rounded-lg focus:outline-none focus:border-green-600">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Reason (Optional)</label>
                    <textarea name="reason" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-green-600"></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700">
                        Add Leave
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('leaveModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('leaveModal').classList.add('hidden');
        }
        
        function toggleTimes() {
            const leaveType = document.getElementById('leave_type').value;
            const timeFields = document.getElementById('timeFields');
            
            if (leaveType === 'partial') {
                timeFields.classList.remove('hidden');
            } else {
                timeFields.classList.add('hidden');
            }
        }
        
        document.getElementById('leaveModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>