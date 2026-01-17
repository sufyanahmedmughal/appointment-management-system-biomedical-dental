<?php
// DB connection
$conn = new mysqli("localhost", "root", "", "biomedical_dental_care");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin credentials
$name = "Admin";
$email = "admin@admin.com";
$password = "admin123";
$role = "super_admin";

// Secure bcrypt hash
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insert query (role added)
$sql = "INSERT INTO admin_users (name, email, password_hash, role)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $name, $email, $password_hash, $role);

if ($stmt->execute()) {
    echo "Super Admin user created successfully";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>