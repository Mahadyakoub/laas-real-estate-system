<?php
session_start();
require_once("../config/db.php");

$error = "";
$email = "";

$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
if (!empty($redirect)) {
    $redirect = ltrim($redirect, '/');
    if (strpos($redirect, '://') !== false || strpos($redirect, '..') !== false) {
        $redirect = '';
    }
}

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id, password, role FROM users WHERE email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role']    = ($user['role'] === 'admin') ? 'admin' : 'client';

                    if ($user['role'] === 'admin') {
                        header("Location: ../admin/dashboard.php"); exit();
                    } elseif ($user['role'] === 'client') {
                        header("Location: " . (!empty($redirect) ? "../$redirect" : "../user/home.php")); exit();
                    } else {
                        session_destroy();
                        $error = "Invalid user role.";
                    }
                } else { $error = "Invalid email or password."; }
            } else { $error = "Invalid email or password."; }
            mysqli_stmt_close($stmt);
        } else { $error = "Something went wrong. Please try again later."; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | Laas Real Estate</title>
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
      <div class="auth-left-eyebrow">Trusted property platform</div>
      <h1>Find your<br><em>perfect place</em><br>to call home.</h1>
      <p>Verified listings, fast appointments, and transparent pricing — all in one modern platform built for Laascaanood.</p>

      <div class="auth-stats">
        <div class="auth-stat">
          <strong>100%</strong>
          <span>Verified</span>
        </div>
        <div class="auth-stat">
          <strong>Fast</strong>
          <span>Booking</span>
        </div>
        <div class="auth-stat">
          <strong>Trusted</strong>
          <span>Feedback</span>
        </div>
      </div>
    </div>

    <div class="auth-left-bottom">
      <p>Don't have an account?</p>
      <a href="register.php" class="auth-outline-btn">
        Create account <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- ===== RIGHT PANEL ===== -->
  <div class="auth-right">
    <div class="auth-form-box">

      <div class="auth-page-label">Client Portal</div>
      <h2>Welcome back</h2>
      <p class="auth-form-subtitle">Sign in to continue exploring verified properties</p>

      <?php if (!empty($redirect)): ?>
      <div class="auth-notice">
        <i class="fa-solid fa-circle-info"></i>
        Please sign in to continue.
      </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
      <div class="auth-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

        <label class="auth-field-label">Email address</label>
        <div class="auth-group">
          <i class="fa-regular fa-envelope"></i>
          <input type="email" name="email" placeholder="you@example.com"
                 value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="auth-label-row">
          <label class="auth-field-label" style="margin:0">Password</label>
          <a href="forgot-password.php" class="auth-forgot">Forgot password?</a>
        </div>
        <div class="auth-group">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="pwInput" placeholder="••••••••" required>
          <button type="button" class="auth-eye" onclick="togglePw('pwInput','eyeIcon')">
            <i class="fa-regular fa-eye" id="eyeIcon"></i>
          </button>
        </div>

        <button type="submit" name="login" class="auth-btn">
          Sign In &nbsp;<i class="fa-solid fa-arrow-right"></i>
        </button>

        <div class="auth-extra">
          Don't have an account? <a href="register.php">Register here</a>
        </div>

      </form>

      <div class="auth-trust">
        <div class="auth-trust-item"><i class="fa-solid fa-shield-halved"></i> Verified listings</div>
        <div class="auth-trust-item"><i class="fa-solid fa-lock"></i> Secure login</div>
        <div class="auth-trust-item"><i class="fa-solid fa-star"></i> Trusted platform</div>
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