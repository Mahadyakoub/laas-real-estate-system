<?php
// ==========================================================
// config/mailer.php — PHPMailer + Gmail SMTP + Dotenv
// ==========================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// ==========================================================
// LOAD .ENV FILE
// ==========================================================

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// ==========================================================
// MAIL CONFIGURATION
// ==========================================================

define('MAIL_FROM', $_ENV['MAIL_FROM']);
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME']);
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME']);
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD']);
define('APP_URL', $_ENV['APP_URL']);

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

        // Gmail SSL
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Uncomment for debugging if needed
        // $mail->SMTPDebug = 2;

        // Sender Information
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);

        // Email Settings
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        return $mail;

    } catch (Exception $e) {

        die("Mailer Error: " . $mail->ErrorInfo);

    }
}
?>