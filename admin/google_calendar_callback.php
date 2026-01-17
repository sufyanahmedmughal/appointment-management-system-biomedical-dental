<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/google_calendar.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['code'])) {
    $client = getGoogleClient(ADMIN_URL . '/google_calendar_callback.php');
    
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            die('Error: ' . $token['error']);
        }
        
        // Store token in database
        $db = getDB();
        
        // For admin, store in settings
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) 
                              VALUES ('admin_google_token', ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?");
        $token_json = json_encode($token);
        $stmt->execute([$token_json, $token_json]);
        
        header('Location: dashboard.php?calendar_connected=1');
        exit;
        
    } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    header('Location: dashboard.php');
    exit;
}
?>