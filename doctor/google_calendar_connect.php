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

$client = getGoogleClient(SITE_URL . '/doctor/google_calendar_callback.php');
$auth_url = $client->createAuthUrl();

header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit;
?>