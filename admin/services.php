<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$db = getDB();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add New Service
        if ($action === 'add') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $price = floatval($_POST['price']);
            $discount = floatval($_POST['discount'] ?? 0);
            $duration = intval($_POST['duration']);
            
            if (empty($name) || empty($description) || $price <= 0 || $duration <= 0) {
                $message = 'Please fill all required fields with valid values';
                $messageType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO services (name, description, price, discount, duration) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $description, $price, $discount, $duration])) {
                    $message = 'Service added successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add service';
                    $messageType = 'error';
                }
            }
        }
        
        // Edit Service
        if ($action === 'edit') {
            $id = intval($_POST['service_id']);
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $price = floatval($_POST['price']);
            $discount = floatval($_POST['discount'] ?? 0);
            $duration = intval($_POST['duration']);
            
            if (empty($name) || empty($description) || $price <= 0 || $duration <= 0) {
                $message = 'Please fill all required fields with valid values';
                $messageType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE services SET name = ?, description = ?, price = ?, discount = ?, duration = ? WHERE id = ?");
                if ($stmt->execute([$name, $description, $price, $discount, $duration, $id])) {
                    $message = 'Service updated successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update service';
                    $messageType = 'error';
                }
            }
        }
        
        // Toggle Active Status
        if ($action === 'toggle_active') {
            $id = intval($_POST['service_id']);
            $stmt = $db->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Service status updated';
                $messageType = 'success';
            }
        }
        
        // Delete Service
        if ($action === 'delete') {
            $id = intval($_POST['service_id']);
            $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Service deleted successfully';
                $messageType = 'success';
            } else {
                $message = 'Cannot delete service (may have appointments)';
                $messageType = 'error';
            }
        }
    }
}

// Get all services
$stmt = $db->query("SELECT * FROM services ORDER BY display_order ASC, id DESC");
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - Admin Panel</title>
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
                <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a>
                <span class="text-gray-700"><?php echo $_SESSION['admin_name']; ?></span>
                <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Services Management</h2>
            <button onclick="openAddModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add New Service
            </button>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle mr-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($services as $service): ?>
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $service['is_active'] ? '' : 'opacity-50'; ?>">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $service['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($service['description']); ?></p>
                
                <div class="mb-4">
                    <?php if ($service['discount'] > 0): ?>
                    <div>
                        <span class="text-gray-400 line-through"><?php echo formatPrice($service['price']); ?></span>
                        <span class="text-2xl font-bold text-blue-600 ml-2">
                            <?php echo formatPrice(getDiscountedPrice($service['price'], $service['discount'])); ?>
                        </span>
                    </div>
                    <span class="text-sm text-green-600"><?php echo $service['discount']; ?>% Discount</span>
                    <?php else: ?>
                    <span class="text-2xl font-bold text-blue-600"><?php echo formatPrice($service['price']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="text-sm text-gray-500 mb-4">
                    <i class="far fa-clock"></i> Duration: <?php echo $service['duration']; ?> minutes
                </div>
                
                <div class="flex space-x-2">
                    <button onclick='editService(<?php echo json_encode($service); ?>)' class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    
                    <form method="POST" class="flex-1" onsubmit="return confirm('Toggle service status?');">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                        <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
                            <i class="fas fa-toggle-<?php echo $service['is_active'] ? 'on' : 'off'; ?>"></i>
                        </button>
                    </form>
                    
                    <form method="POST" class="flex-1" onsubmit="return confirm('Delete this service permanently?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="serviceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-2xl font-bold text-gray-800">Add New Service</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form id="serviceForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="service_id" id="serviceId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Service Name *</label>
                    <input type="text" name="name" id="serviceName" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                           placeholder="e.g., Teeth Whitening">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Description *</label>
                    <textarea name="description" id="serviceDescription" required rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                              placeholder="Brief description of the service"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Price (Rs.) *</label>
                        <input type="number" name="price" id="servicePrice" required min="0" step="0.01"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                               placeholder="5000">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Discount (%)</label>
                        <input type="number" name="discount" id="serviceDiscount" min="0" max="100" step="1" value="0"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                               placeholder="10">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Duration (minutes) *</label>
                    <input type="number" name="duration" id="serviceDuration" required min="15" step="15"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                           placeholder="30">
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Save Service
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
            document.getElementById('modalTitle').textContent = 'Add New Service';
            document.getElementById('formAction').value = 'add';
            document.getElementById('serviceForm').reset();
            document.getElementById('serviceModal').classList.remove('hidden');
        }
        
        function editService(service) {
            document.getElementById('modalTitle').textContent = 'Edit Service';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('serviceName').value = service.name;
            document.getElementById('serviceDescription').value = service.description;
            document.getElementById('servicePrice').value = service.price;
            document.getElementById('serviceDiscount').value = service.discount;
            document.getElementById('serviceDuration').value = service.duration;
            document.getElementById('serviceModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('serviceModal').classList.add('hidden');
            document.getElementById('serviceForm').reset();
        }
        
        // Close modal on outside click
        document.getElementById('serviceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>