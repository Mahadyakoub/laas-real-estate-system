<?php
// ==========================================================
// config/mailer.php — PHPMailer + Gmail SMTP
// ==========================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// ==========================================================
// MAIL CONFIGURATION
// ==========================================================

define('MAIL_FROM', getenv('MAIL_FROM') ?: 'mahadyakoub34@gmail.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Laas Real Estate');
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: 'mahadyakoub34@gmail.com');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/laas_rental_system');

// ==========================================================
// CREATE MAILER FUNCTION
// ==========================================================

function make_mailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    try {

        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;

        // FIXED SMTP SETTINGS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Optional debug (remove later)
        // $mail->SMTPDebug = 2;

        // Sender
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);

        // Email format
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        return $mail;

    } catch (Exception $e) {

        die("Mailer Error: " . $mail->ErrorInfo);

    }
}
?>