<?php
// PHPMailer Library
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body, $patientName = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = getSetting('smtp_host', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = getSetting('smtp_username');
        $mail->Password = getSetting('smtp_password');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getSetting('smtp_port', 587);
        
        // Recipients
        $mail->setFrom(getSetting('clinic_email'), getSetting('clinic_name'));
        $mail->addAddress($to, $patientName);
        $mail->addReplyTo(getSetting('clinic_email'), getSetting('clinic_name'));
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendAppointmentConfirmation($appointment) {
    $subject = "Appointment Confirmation - Bio Medical Dental Care";
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Appointment Confirmed!</h2>
        <p>Dear {$appointment['patient_name']},</p>
        <p>Your appointment has been confirmed:</p>
        <ul>
            <li><strong>Service:</strong> {$appointment['service_name']}</li>
            <li><strong>Doctor:</strong> {$appointment['doctor_name']}</li>
            <li><strong>Date:</strong> " . formatDate($appointment['appointment_date']) . "</li>
            <li><strong>Time:</strong> " . formatTime($appointment['appointment_time']) . "</li>
        </ul>
        <p>Please arrive 10 minutes early.</p>
        <p>Best regards,<br>Bio Medical Dental Care</p>
    </body>
    </html>
    ";
    
    return sendEmail($appointment['email'], $subject, $body, $appointment['patient_name']);
}

function sendReviewRequest($appointment) {
    $reviewLink = getSetting('google_review_link');
    $subject = "Please Share Your Experience - Bio Medical Dental Care";
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>We'd Love Your Feedback!</h2>
        <p>Dear {$appointment['patient_name']},</p>
        <p>Thank you for visiting Bio Medical Dental Care. We hope you had a great experience!</p>
        <p>Your feedback helps us improve our services. Please take a moment to share your experience:</p>
        <p style='margin: 20px 0;'>
            <a href='{$reviewLink}' style='background: #0066cc; color: white; padding: 12px 24px; 
               text-decoration: none; border-radius: 5px; display: inline-block;'>
                Leave a Review
            </a>
        </p>
        <p>Best regards,<br>Bio Medical Dental Care Team</p>
    </body>
    </html>
    ";
    
    return sendEmail($appointment['email'], $subject, $body, $appointment['patient_name']);
}
?>