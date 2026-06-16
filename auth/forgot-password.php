<?php
session_start();
require_once("../config/db.php");
require_once("../config/mailer.php");

use PHPMailer\PHPMailer\Exception;

$sent  = false;
$error = "";

if (isset($_POST['send_reset'])) {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($exists) {
            $del = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
            mysqli_stmt_bind_param($del, "s", $email);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);

            $token      = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $ins = mysqli_prepare($conn,
                "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins, "sss", $email, $token, $expires_at);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            $reset_url = APP_URL . "/auth/reset-password.php?token=" . urlencode($token);

            try {
                $mail = make_mailer();
                $mail->addAddress($email);
                $mail->Subject = 'Reset your Laas Real Estate password';
                $mail->Body = "
                <div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;padding:32px 24px;'>
                    <h2 style='color:#0D1B3E;'>Reset your password</h2>
                    <p>We received a request to reset your password.</p>
                    <p>Click the button below. This link expires in <strong>1 hour</strong>.</p>
                    <a href='{$reset_url}' style='display:inline-block;padding:13px 28px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold;'>
                        Reset Password
                    </a>
                    <p style='font-size:12px;color:#64748b;margin-top:25px;'>
                        If you did not request this, ignore this email.
                    </p>
                    <p style='font-size:12px;color:#64748b;'>
                        Link: {$reset_url}
                    </p>
                </div>";

                $mail->AltBody = "Reset your password: {$reset_url}";
                $mail->send();

            } catch (Exception $e) {
                die("Mailer Error: " . $mail->ErrorInfo);
            }
        }

        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password | Laas Real Estate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">

  <div class="auth-left">
    <div class="auth-left-circle"></div>

    <div class="auth-left-top">
      <div class="auth-logo-wrap">
        <img src="../assets/images/logo.jpg" alt="Laas Real Estate">
        <span>Laas Real Estate</span>
      </div>
    </div>

    <div class="auth-left-body">
      <div class="auth-left-eyebrow">Account recovery</div>
      <h1>Reset your<br><em>password</em><br>securely.</h1>
      <p>We'll send a secure one-time link to your email address. It expires in 1 hour.</p>

      <div class="auth-stats">
        <div class="auth-stat">
          <strong>1 hr</strong>
          <span>Link expiry</span>
        </div>
        <div class="auth-stat">
          <strong>Safe</strong>
          <span>Secure</span>
        </div>
        <div class="auth-stat">
          <strong>Fast</strong>
          <span>Delivery</span>
        </div>
      </div>
    </div>

    <div class="auth-left-bottom">
      <p>Remembered your password?</p>
      <a href="login.php" class="auth-outline-btn">
        Back to login <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-box">

      <div class="auth-page-label">Account Recovery</div>
      <h2>Forgot password?</h2>
      <p class="auth-form-subtitle">Enter your email and we'll send you a reset link</p>

      <?php if ($sent): ?>
      <div class="auth-notice" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">
        <i class="fa-solid fa-circle-check"></i>
        If that email is registered, a reset link is on its way. Check your inbox and spam folder.
      </div>
      <div class="auth-extra" style="margin-top:10px;">
        <a href="login.php"><i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> Back to login</a>
      </div>

      <?php else: ?>

      <?php if (!empty($error)): ?>
      <div class="auth-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <label class="auth-field-label">Email address</label>
        <div class="auth-group">
          <i class="fa-regular fa-envelope"></i>
          <input type="email" name="email" placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>

        <button type="submit" name="send_reset" class="auth-btn">
          Send Reset Link &nbsp;<i class="fa-solid fa-paper-plane"></i>
        </button>

        <div class="auth-extra">
          <a href="login.php"><i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> Back to login</a>
        </div>
      </form>

      <?php endif; ?>

      <div class="auth-trust">
        <div class="auth-trust-item"><i class="fa-solid fa-shield-halved"></i> Secure link</div>
        <div class="auth-trust-item"><i class="fa-solid fa-clock"></i> Expires in 1 hr</div>
        <div class="auth-trust-item"><i class="fa-solid fa-lock"></i> One-time use</div>
      </div>

    </div>
  </div>

</div>

</body>
</html>