<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'biomedical_dental_care');

// Site Configuration
define('SITE_URL', 'http://localhost/biomedical-dental-care');
define('ADMIN_URL', SITE_URL . '/admin');

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/images/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Karachi');

// Error Reporting (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>