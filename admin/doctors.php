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
        
        // Add New Doctor
        if ($action === 'add') {
            $name = sanitize($_POST['name']);
            $passion = sanitize($_POST['passion']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone'] ?? '');
            $password = $_POST['password'];
            
            if (empty($name) || empty($passion) || empty($email) || empty($password)) {
                $message = 'Please fill all required fields';
                $messageType = 'error';
            } elseif (!validateEmail($email)) {
                $message = 'Invalid email format';
                $messageType = 'error';
            } elseif (strlen($password) < 6) {
                $message = 'Password must be at least 6 characters';
                $messageType = 'error';
            } else {
                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM doctors WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $message = 'Email already exists';
                    $messageType = 'error';
                } else {
                    // Handle image upload
                    $picture = '';
                    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                        $upload = uploadImage($_FILES['picture'], 'doctor');
                        if ($upload['success']) {
                            $picture = $upload['filename'];
                        }
                    }
                    
                    // Hash password
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    
                    $stmt = $db->prepare("INSERT INTO doctors (name, picture, passion, email, password_hash, phone) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$name, $picture, $passion, $email, $password_hash, $phone])) {
                        $message = "Doctor added successfully! Login credentials - Email: $email, Password: [as entered]";
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to add doctor';
                        $messageType = 'error';
                    }
                }
            }
        }
        
        // Edit Doctor
        if ($action === 'edit') {
            $id = intval($_POST['doctor_id']);
            $name = sanitize($_POST['name']);
            $passion = sanitize($_POST['passion']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone'] ?? '');
            
            if (empty($name) || empty($passion) || empty($email)) {
                $message = 'Please fill all required fields';
                $messageType = 'error';
            } else {
                // Check email uniqueness (except for current doctor)
                $stmt = $db->prepare("SELECT id FROM doctors WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                
                if ($stmt->fetch()) {
                    $message = 'Email already exists';
                    $messageType = 'error';
                } else {
                    // Get current doctor data
                    $stmt = $db->prepare("SELECT picture FROM doctors WHERE id = ?");
                    $stmt->execute([$id]);
                    $currentDoctor = $stmt->fetch();
                    $picture = $currentDoctor['picture'];
                    
                    // Handle new image upload
                    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                        $upload = uploadImage($_FILES['picture'], 'doctor');
                        if ($upload['success']) {
                            if ($picture) deleteImage($picture);
                            $picture = $upload['filename'];
                        }
                    }
                    
                    $stmt = $db->prepare("UPDATE doctors SET name = ?, picture = ?, passion = ?, email = ?, phone = ? WHERE id = ?");
                    if ($stmt->execute([$name, $picture, $passion, $email, $phone, $id])) {
                        $message = 'Doctor updated successfully';
                        $messageType = 'success';
                    }
                }
            }
        }
        
        // Reset Password
        if ($action === 'reset_password') {
            $id = intval($_POST['doctor_id']);
            $new_password = $_POST['new_password'];
            
            if (strlen($new_password) < 6) {
                $message = 'Password must be at least 6 characters';
                $messageType = 'error';
            } else {
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE doctors SET password_hash = ? WHERE id = ?");
                if ($stmt->execute([$password_hash, $id])) {
                    $message = 'Password reset successfully';
                    $messageType = 'success';
                }
            }
        }
        
        // Toggle Active Status
        if ($action === 'toggle_active') {
            $id = intval($_POST['doctor_id']);
            $stmt = $db->prepare("UPDATE doctors SET is_active = NOT is_active WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Doctor status updated';
                $messageType = 'success';
            }
        }
        
        // Delete Doctor
        if ($action === 'delete') {
            $id = intval($_POST['doctor_id']);
            
            $stmt = $db->prepare("SELECT picture FROM doctors WHERE id = ?");
            $stmt->execute([$id]);
            $doctor = $stmt->fetch();
            
            $stmt = $db->prepare("DELETE FROM doctors WHERE id = ?");
            if ($stmt->execute([$id])) {
                if ($doctor['picture']) deleteImage($doctor['picture']);
                $message = 'Doctor deleted successfully';
                $messageType = 'success';
            } else {
                $message = 'Cannot delete doctor (may have appointments)';
                $messageType = 'error';
            }
        }
    }
}

// Get all doctors
$stmt = $db->query("SELECT * FROM doctors ORDER BY id DESC");
$doctors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
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
            <h2 class="text-3xl font-bold text-gray-800">Doctors Management</h2>
            <button onclick="openAddModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add New Doctor
            </button>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle mr-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Doctors Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($doctors as $doctor): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $doctor['is_active'] ? '' : 'opacity-50'; ?>">
                <div class="h-64 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                    <?php if ($doctor['picture']): ?>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($doctor['picture']); ?>" alt="<?php echo htmlspecialchars($doctor['name']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-user-md text-white text-8xl"></i>
                    <?php endif; ?>
                </div>
                
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($doctor['name']); ?></h3>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $doctor['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $doctor['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    
                    <p class="text-blue-600 font-semibold mb-2">
                        <i class="fas fa-stethoscope mr-2"></i><?php echo htmlspecialchars($doctor['passion']); ?>
                    </p>
                    
                    <div class="text-sm text-gray-600 space-y-1 mb-4">
                        <p><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($doctor['email']); ?></p>
                        <?php if ($doctor['phone']): ?>
                        <p><i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($doctor['phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($doctor['google_calendar_connected']): ?>
                        <p class="text-green-600"><i class="fas fa-calendar-check mr-2"></i>Calendar Connected</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex space-x-2">
                            <button onclick='editDoctor(<?php echo json_encode($doctor); ?>)' class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            
                            <button onclick="openPasswordModal(<?php echo $doctor['id']; ?>, '<?php echo htmlspecialchars($doctor['name']); ?>')" class="flex-1 bg-yellow-600 text-white px-3 py-2 rounded text-sm hover:bg-yellow-700">
                                <i class="fas fa-key"></i> Reset
                            </button>
                        </div>
                        
                        <div class="flex space-x-2">
                            <form method="POST" class="flex-1" onsubmit="return confirm('Toggle doctor status?');">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                <button type="submit" class="w-full bg-gray-600 text-white px-3 py-2 rounded text-sm hover:bg-gray-700">
                                    <i class="fas fa-toggle-<?php echo $doctor['is_active'] ? 'on' : 'off'; ?>"></i>
                                </button>
                            </form>
                            
                            <form method="POST" class="flex-1" onsubmit="return confirm('Delete this doctor permanently?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                <button type="submit" class="w-full bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add/Edit Doctor Modal -->
    <div id="doctorModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-2xl font-bold text-gray-800">Add New Doctor</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form id="doctorForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="doctor_id" id="doctorId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Doctor Name *</label>
                    <input type="text" name="name" id="doctorName" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                           placeholder="Dr. Ahmed Hassan">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Specialty / Passion *</label>
                    <input type="text" name="passion" id="doctorPassion" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                           placeholder="Orthodontics & Cosmetic Dentistry">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Email (Login Username) *</label>
                    <input type="email" name="email" id="doctorEmail" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                           placeholder="dr.ahmed@biomedical.com">
                    <p class="text-sm text-gray-500 mt-1">This will be used to login to doctor dashboard</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Phone Number</label>
                    <input type="tel" name="phone" id="doctorPhone"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                           placeholder="923001234567">
                </div>
                
                <div id="passwordField" class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Password *</label>
                    <input type="password" name="password" id="doctorPassword" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                           placeholder="Minimum 6 characters">
                    <p class="text-sm text-gray-500 mt-1">Doctor will use this password to login</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Profile Picture</label>
                    <input type="file" name="picture" id="doctorPicture" accept="image/*"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                    <p class="text-sm text-gray-500 mt-1">Max 5MB (JPG, PNG, GIF)</p>
                    <div id="imagePreview" class="mt-4 hidden">
                        <img id="previewImg" src="" alt="Preview" class="max-w-xs rounded-lg">
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Save Doctor
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Reset Password</h3>
            <p class="text-gray-600 mb-4">Doctor: <strong id="password-doctor-name"></strong></p>
            
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="doctor_id" id="password-doctor-id">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">New Password</label>
                    <input type="password" name="new_password" required minlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                           placeholder="Minimum 6 characters">
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-yellow-600 text-white py-3 rounded-lg font-semibold hover:bg-yellow-700">
                        Reset Password
                    </button>
                    <button type="button" onclick="closePasswordModal()" class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('doctorPicture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Doctor';
            document.getElementById('formAction').value = 'add';
            document.getElementById('doctorForm').reset();
            document.getElementById('passwordField').classList.remove('hidden');
            document.getElementById('doctorPassword').required = true;
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('doctorModal').classList.remove('hidden');
        }
        
        function editDoctor(doctor) {
            document.getElementById('modalTitle').textContent = 'Edit Doctor';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('doctorId').value = doctor.id;
            document.getElementById('doctorName').value = doctor.name;
            document.getElementById('doctorPassion').value = doctor.passion;
            document.getElementById('doctorEmail').value = doctor.email;
            document.getElementById('doctorPhone').value = doctor.phone || '';
            document.getElementById('passwordField').classList.add('hidden');
            document.getElementById('doctorPassword').required = false;
            
            if (doctor.picture) {
                document.getElementById('previewImg').src = '<?php echo UPLOAD_URL; ?>' + doctor.picture;
                document.getElementById('imagePreview').classList.remove('hidden');
            } else {
                document.getElementById('imagePreview').classList.add('hidden');
            }
            
            document.getElementById('doctorModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('doctorModal').classList.add('hidden');
            document.getElementById('doctorForm').reset();
            document.getElementById('imagePreview').classList.add('hidden');
        }
        
        function openPasswordModal(doctorId, doctorName) {
            document.getElementById('password-doctor-id').value = doctorId;
            document.getElementById('password-doctor-name').textContent = doctorName;
            document.getElementById('passwordModal').classList.remove('hidden');
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').classList.add('hidden');
        }
        
        document.getElementById('doctorModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) closePasswordModal();
        });
    </script>
</body>
</html>