<?php
require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';

function getGoogleClient($redirect_uri = null) {
    $client = new Google_Client();
    
    // Get credentials from database
    $client_id = getSetting('google_oauth_client_id');
    $client_secret = getSetting('google_oauth_client_secret');
    $default_redirect = getSetting('google_oauth_redirect_uri');
    
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);
    $client->setRedirectUri($redirect_uri ?? $default_redirect);
    
    $client->addScope(Google_Service_Calendar::CALENDAR);
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    
    return $client;
}

function createCalendarEvent($doctor_id, $appointment) {
    $db = getDB();
    
    // Get doctor's access token
    $stmt = $db->prepare("SELECT google_access_token, google_refresh_token FROM doctors WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();
    
    if (!$doctor || !$doctor['google_access_token']) {
        return false;
    }
    
    $client = getGoogleClient();
    $client->setAccessToken($doctor['google_access_token']);
    
    // Refresh token if expired
    if ($client->isAccessTokenExpired()) {
        if ($doctor['google_refresh_token']) {
            $client->fetchAccessTokenWithRefreshToken($doctor['google_refresh_token']);
            $new_token = $client->getAccessToken();
            
            // Update token in database
            $stmt = $db->prepare("UPDATE doctors SET google_access_token = ? WHERE id = ?");
            $stmt->execute([json_encode($new_token), $doctor_id]);
        } else {
            return false;
        }
    }
    
    $service = new Google_Service_Calendar($client);
    
    // Create event
    $event = new Google_Service_Calendar_Event([
        'summary' => 'Appointment: ' . $appointment['patient_name'],
        'description' => "Service: " . $appointment['service_name'] . "\nPhone: " . $appointment['phone'],
        'start' => [
            'dateTime' => $appointment['appointment_date'] . 'T' . $appointment['appointment_time'],
            'timeZone' => 'Asia/Karachi',
        ],
        'end' => [
            'dateTime' => $appointment['appointment_date'] . 'T' . $appointment['end_time'],
            'timeZone' => 'Asia/Karachi',
        ],
        'reminders' => [
            'useDefault' => false,
            'overrides' => [
                ['method' => 'email', 'minutes' => 24 * 60],
                ['method' => 'popup', 'minutes' => 120],
            ],
        ],
    ]);
    
    try {
        $event = $service->events->insert('primary', $event);
        return $event->getId();
    } catch (Exception $e) {
        error_log("Google Calendar error: " . $e->getMessage());
        return false;
    }
}
?>