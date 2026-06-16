<?php
session_start();
require_once("../config/db.php");

$error   = "";
$success = false;
$token   = trim($_GET['token'] ?? $_POST['token'] ?? '');
$valid   = false;
$email   = "";

// Validate token
if (!empty($token)) {
    $stmt = mysqli_prepare($conn,
        "SELECT email FROM password_resets
         WHERE token = ? AND used = 0 AND expires_at > NOW()
         LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $valid = true;
        $email = $row['email'];
    }
    mysqli_stmt_close($stmt);
}

// Handle form submission
if (isset($_POST['reset_password']) && $valid) {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($password) || empty($confirm)) {
        $error = "Both fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Update user password
        $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
        mysqli_stmt_bind_param($upd, "ss", $hashed, $email);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);

        // Mark token as used
        $mark = mysqli_prepare($conn, "UPDATE password_resets SET used = 1 WHERE token = ?");
        mysqli_stmt_bind_param($mark, "s", $token);
        mysqli_stmt_execute($mark);
        mysqli_stmt_close($mark);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password | Laas Real Estate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">

  <!-- ===== LEFT PANEL ===== -->
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
      <h1>Choose a<br><em>new password</em><br>you'll remember.</h1>
      <p>Pick something strong — at least 6 characters. You'll be logged in right after.</p>

      <div class="auth-stats">
        <div class="auth-stat">
          <strong>6+</strong>
          <span>Min. chars</span>
        </div>
        <div class="auth-stat">
          <strong>Safe</strong>
          <span>Encrypted</span>
        </div>
        <div class="auth-stat">
          <strong>Once</strong>
          <span>Link use</span>
        </div>
      </div>
    </div>

    <div class="auth-left-bottom">
      <p>Remembered it?</p>
      <a href="login.php" class="auth-outline-btn">
        Back to login <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- ===== RIGHT PANEL ===== -->
  <div class="auth-right">
    <div class="auth-form-box">

      <div class="auth-page-label">Set New Password</div>
      <h2>Reset password</h2>
      <p class="auth-form-subtitle">Enter and confirm your new password below</p>

      <?php if ($success): ?>
      <div class="auth-notice" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">
        <i class="fa-solid fa-circle-check"></i>
        Password updated successfully!
      </div>
      <a href="login.php" class="auth-btn" style="margin-top:16px;text-decoration:none;">
        Sign In Now &nbsp;<i class="fa-solid fa-arrow-right"></i>
      </a>

      <?php elseif (!$valid): ?>
      <div class="auth-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        This reset link is invalid or has expired.
      </div>
      <div class="auth-extra" style="margin-top:10px;">
        <a href="forgot-password.php">Request a new reset link</a>
      </div>

      <?php else: ?>

      <?php if (!empty($error)): ?>
      <div class="auth-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label class="auth-field-label">New password</label>
        <div class="auth-group">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="pw1"
                 placeholder="Min. 6 characters" required autofocus>
          <button type="button" class="auth-eye" onclick="togglePw('pw1','eye1')">
            <i class="fa-regular fa-eye" id="eye1"></i>
          </button>
        </div>

        <label class="auth-field-label">Confirm new password</label>
        <div class="auth-group">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="confirm_password" id="pw2"
                 placeholder="Repeat password" required>
          <button type="button" class="auth-eye" onclick="togglePw('pw2','eye2')">
            <i class="fa-regular fa-eye" id="eye2"></i>
          </button>
        </div>

        <button type="submit" name="reset_password" class="auth-btn">
          Update Password &nbsp;<i class="fa-solid fa-arrow-right"></i>
        </button>

        <div class="auth-extra">
          <a href="login.php"><i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> Back to login</a>
        </div>
      </form>

      <?php endif; ?>

      <div class="auth-trust">
        <div class="auth-trust-item"><i class="fa-solid fa-shield-halved"></i> Encrypted</div>
        <div class="auth-trust-item"><i class="fa-solid fa-lock"></i> Secure</div>
        <div class="auth-trust-item"><i class="fa-solid fa-star"></i> Trusted</div>
      </div>

    </div>
  </div>

</div>

<script>
function togglePw(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  input.type = input.type === 'password' ? 'text' : 'password';
  icon.className = input.type === 'text' ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
}
</script>
</body>
</html>