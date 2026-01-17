<?php
// Session already started in functions.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$error = '';

// Redirect if already logged in
if (isset($_SESSION['doctor_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM doctors WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $doctor = $stmt->fetch();
        
        if ($doctor && password_verify($password, $doctor['password_hash'])) {
            // Set session
            $_SESSION['doctor_id'] = $doctor['id'];
            $_SESSION['doctor_email'] = $doctor['email'];
            $_SESSION['doctor_name'] = $doctor['name'];
            $_SESSION['user_type'] = 'doctor';
            $_SESSION['login_time'] = time();
            
            // Update last login
            $stmt = $db->prepare("UPDATE doctors SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$doctor['id']]);
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - Bio Medical Dental Care</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-500 to-green-700 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <i class="fas fa-user-md text-green-600 text-5xl mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800">Doctor Portal</h1>
            <p class="text-gray-600 mt-2">Bio Medical Dental Care</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-envelope mr-2"></i>Email Address
                </label>
                <input type="email" name="email" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-green-600"
                       placeholder="dr.name@biomedical.com">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-green-600"
                       placeholder="Enter your password">
            </div>
            
            <button type="submit" 
                    class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                <i class="fas fa-sign-in-alt mr-2"></i>Login to Dashboard
            </button>
        </form>
        
        <div class="mt-6 text-center text-gray-600 text-sm">
            <p>Contact admin if you forgot your password</p>
        </div>
        
        <div class="mt-8 text-center">
            <a href="../index.php" class="text-green-600 hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Back to Website
            </a>
        </div>
    </div>
</body>
</html>