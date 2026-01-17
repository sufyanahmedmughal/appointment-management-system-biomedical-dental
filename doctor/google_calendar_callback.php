<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/google_calendar.php';

if (!isset($_SESSION['doctor_id'])) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['code'])) {
    $doctor_id = $_SESSION['doctor_id'];
    $client = getGoogleClient(SITE_URL . '/doctor/google_calendar_callback.php');
    
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            die('Error: ' . $token['error']);
        }
        
        // Store token in database
        $db = getDB();
        $stmt = $db->prepare("UPDATE doctors 
                              SET google_access_token = ?,
                                  google_refresh_token = ?,
                                  google_calendar_connected = 1,
                                  google_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                              WHERE id = ?");
        
        $access_token = json_encode($token);
        $refresh_token = $token['refresh_token'] ?? null;
        
        $stmt->execute([$access_token, $refresh_token, $doctor_id]);
        
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