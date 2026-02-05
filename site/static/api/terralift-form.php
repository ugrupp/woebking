<?php
/**
 * Terralift Form Handler
 * Handles form submissions with validation, security, and email notifications
 */

// Paths
$docroot = realpath($_SERVER['DOCUMENT_ROOT']);
$tmpDir = realpath($docroot . '/../tmp');
$phpMailerDir = realpath($docroot . '/../lib/PHPMailer');
$envFile = realpath($docroot . '/../.env');
$envVars = parse_ini_file($envFile);

// Load PHPMailer
require $phpMailerDir . '/src/Exception.php';
require $phpMailerDir . '/src/PHPMailer.php';
require $phpMailerDir . '/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// CORS headers - allow requests from allowed origins
$allowedOrigins = [
    'https://www.woebking.com',
    'https://woebking.com',
    // 'http://localhost:5173',  // Temporary for development
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
    header('Access-Control-Allow-Credentials: true');
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// CSRF protection - check referer
$allowedDomains = [
    'woebking.com',
    // 'localhost:5173' // Temporary for development
];
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$isValidReferer = false;
foreach ($allowedDomains as $domain) {
    if (strpos($referer, $domain) !== false) {
        $isValidReferer = true;
        break;
    }
}

if (!$isValidReferer) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request origin']);
    exit;
}

// Rate limiting - simple file-based approach
$rateLimitFile = $tmpDir . '/terralift_rate_limit.json';
$rateLimitData = [];
if (file_exists($rateLimitFile)) {
    $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?? [];
}

$clientIp = $_SERVER['REMOTE_ADDR'];
$currentTime = time();
$timeWindow = 3600; // 1 hour
$maxRequests = 5;

// Clean old entries
$rateLimitData = array_filter($rateLimitData, function($timestamp) use ($currentTime, $timeWindow) {
    return ($currentTime - $timestamp) < $timeWindow;
});

// Check rate limit
$ipRequests = array_filter($rateLimitData, function($timestamp, $ip) use ($clientIp) {
    return $ip === $clientIp;
}, ARRAY_FILTER_USE_BOTH);

if (count($ipRequests) >= $maxRequests) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.']);
    exit;
}

// Add current request to rate limit
$rateLimitData[$clientIp . '_' . $currentTime] = $currentTime;
file_put_contents($rateLimitFile, json_encode($rateLimitData));

// Sanitization function
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validation
$errors = [];

// Required fields
$name = sanitizeInput($_POST['name'] ?? '');
$company = sanitizeInput($_POST['company'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$privacyConsent = isset($_POST['privacy_consent']);

// Optional fields
$phone = sanitizeInput($_POST['phone'] ?? '');
$location = sanitizeInput($_POST['location'] ?? '');

// Interest checkboxes
$interests = $_POST['interest'] ?? [];
$interestsSanitized = array_map('sanitizeInput', $interests);

// Validate required fields
if (empty($name)) {
    $errors[] = 'Name ist erforderlich';
}

if (empty($company)) {
    $errors[] = 'Unternehmen / Anlage ist erforderlich';
}

if (empty($email)) {
    $errors[] = 'E-Mail ist erforderlich';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ungültige E-Mail-Adresse';
}

if (!$privacyConsent) {
    $errors[] = 'Datenschutzerklärung muss akzeptiert werden';
}

if (empty($interestsSanitized)) {
    $errors[] = 'Bitte wählen Sie mindestens eine Option aus';
}

// Return errors if validation failed
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Prepare interest labels
$interestLabels = [
    'katalog' => 'Neuen Wöbking-Katalog mit Terralift anfordern',
    'referenzen' => 'Infos zu Anwendungsbeispielen von Terralift-Produkten',
    'beratung' => 'Persönliche Beratung',
];

$interestText = [];
foreach ($interestsSanitized as $interest) {
    if (isset($interestLabels[$interest])) {
        $interestText[] = $interestLabels[$interest];
    }
}

// Email configuration
$recipients = [
    'thorsten.cramer@woebking.com',
    'michael.kruse@woebking.com',
    // 'urs@21sieben.de' // For testing purposes
];

$fromEmail = 'noreply@woebking.com';
$fromName = 'Wöbking Website';

// SMTP Configuration
$smtpHost = $envVars['SMTP_HOST'];
$smtpPort = $envVars['SMTP_PORT'];
$smtpUsername = $envVars['SMTP_USERNAME'];
$smtpPassword = $envVars['SMTP_PASSWORD'];

// Function to send email using PHPMailer
function sendEmailPHPMailer($recipients, $subject, $body, $fromEmail, $fromName, $replyTo, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';

        // Sender
        $mail->setFrom($fromEmail, $fromName);
        $mail->addReplyTo($replyTo);

        // Recipients - add all at once
        if (is_array($recipients)) {
            foreach ($recipients as $recipient) {
                $mail->addAddress($recipient);
            }
        } else {
            $mail->addAddress($recipients);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Notification email to Wöbking team
$notificationSubject = 'Neue Terralift-Anfrage von ' . $name;
$notificationBody = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Neue Terralift-Anfrage</h2>

    <h3>Interesse:</h3>
    <ul>
        ' . implode('', array_map(function($item) { return '<li>' . $item . '</li>'; }, $interestText)) . '
    </ul>

    <h3>Kontaktdaten:</h3>
    <table style="border-collapse: collapse;">
        <tr>
            <td style="padding: 8px; font-weight: bold;">Name:</td>
            <td style="padding: 8px;">' . $name . '</td>
        </tr>
        <tr>
            <td style="padding: 8px; font-weight: bold;">Unternehmen/Anlage:</td>
            <td style="padding: 8px;">' . $company . '</td>
        </tr>
        <tr>
            <td style="padding: 8px; font-weight: bold;">E-Mail:</td>
            <td style="padding: 8px;"><a href="mailto:' . $email . '">' . $email . '</a></td>
        </tr>
        ' . ($phone ? '<tr><td style="padding: 8px; font-weight: bold;">Telefon:</td><td style="padding: 8px;">' . $phone . '</td></tr>' : '') . '
        ' . ($location ? '<tr><td style="padding: 8px; font-weight: bold;">PLZ/Ort:</td><td style="padding: 8px;">' . $location . '</td></tr>' : '') . '
    </table>

    <p style="margin-top: 20px; font-size: 12px; color: #6f7072;">
        Anfrage eingegangen am: ' . date('d.m.Y H:i:s') . ' Uhr<br>
        IP-Adresse: ' . $clientIp . '
    </p>
</body>
</html>
';

// Confirmation email to customer
$confirmationSubject = 'Vielen Dank für Ihr Interesse an Terralift';
$confirmationBody = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hallo ' . $name . ',</p>

    <p><strong>vielen Dank für Ihre Anfrage.</strong></p>

    <p>Wir melden uns zeitnah persönlich bei Ihnen bzw. senden Ihnen die gewünschten Informationen zu.</p>

    <p>Der neue Wöbking-Katalog mit Terralift befindet sich aktuell in Vorbereitung. Gerne informieren wir Sie, sobald dieser druckfrisch verfügbar ist.</p>

    <p style="margin-top: 30px;">
        Freundliche Grüße<br>
        Ihr Wöbking-Team
    </p>

    <hr style="margin-top: 40px; border: none; border-top: 1px solid #6f7072;">

    <p style="font-size: 12px; color: #6f7072;">
        <strong>Wöbking GmbH</strong><br>
        Rheinstraße 36<br>
        49090 Osnabrück/Hafen<br>
        Deutschland<br>
        <br>
        Telefon: +49 541 62867<br>
        E-Mail: <a href="mailto:info@woebking.com">info@woebking.com</a><br>
        Web: <a href="https://www.woebking.com">www.woebking.com</a>
    </p>
</body>
</html>
';

// Send emails
$notificationSent = sendEmailPHPMailer($recipients, $notificationSubject, $notificationBody, $fromEmail, $fromName, $email, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword);

if (!$notificationSent) {
    error_log("Failed to send notification email to recipients: " . implode(', ', $recipients));
}

$confirmationSent = sendEmailPHPMailer($email, $confirmationSubject, $confirmationBody, $fromEmail, $fromName, $fromEmail, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword);

if (!$confirmationSent) {
    error_log("Failed to send confirmation email to: $email");
}

// Log submission (optional - for debugging)
// $logFile = $tmpDir . '/terralift_submissions.log';
// $logEntry = date('Y-m-d H:i:s') . ' | ' . $email . ' | ' . $name . ' | ' . $company . "\n";
// @file_put_contents($logFile, $logEntry, FILE_APPEND);

// Response
if ($notificationSent && $confirmationSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Vielen Dank! Ihre Anfrage wurde erfolgreich übermittelt. Sie erhalten in Kürze eine Bestätigungs-E-Mail.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Es gab ein Problem beim Versenden der E-Mails. Bitte kontaktieren Sie uns direkt unter info@woebking.com'
    ]);
}
