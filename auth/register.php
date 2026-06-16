<?php
require_once("../config/db.php");

$error = "";
$name  = "";
$email = "";

if (isset($_POST['register'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (strlen($name) < 3) {
        $error = "Full name must be at least 3 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $checkStmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "s", $email);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);

            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $error = "Email already registered.";
                mysqli_stmt_close($checkStmt);
            } else {
                mysqli_stmt_close($checkStmt);
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $role = "client";

                $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashedPassword, $role);
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        header("Location: login.php?registered=success");
                        exit();
                    } else {
                        $error = "Registration failed. Please try again.";
                        mysqli_stmt_close($stmt);
                    }
                } else { $error = "Something went wrong. Please try again later."; }
            }
        } else { $error = "Something went wrong. Please try again later."; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | Laas Real Estate</title>
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
      <div class="auth-left-eyebrow">Join our platform</div>
      <h1>Your next<br><em>great property</em><br>awaits you.</h1>
      <p>Create a free account and start discovering verified rental homes, houses for sale, and land opportunities.</p>

      <div class="auth-stats">
        <div class="auth-stat">
          <strong>100%</strong>
          <span>Verified</span>
        </div>
        <div class="auth-stat">
          <strong>Free</strong>
          <span>To join</span>
        </div>
        <div class="auth-stat">
          <strong>Fast</strong>
          <span>Booking</span>
        </div>
      </div>
    </div>

    <div class="auth-left-bottom">
      <p>Already have an account?</p>
      <a href="login.php" class="auth-outline-btn">
        Sign in <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- ===== RIGHT PANEL ===== -->
  <div class="auth-right">
    <div class="auth-form-box">

      <div class="auth-page-label">New account</div>
      <h2>Create account</h2>
      <p class="auth-form-subtitle">Join thousands of clients discovering properties on Laas</p>

      <?php if (!empty($error)): ?>
      <div class="auth-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">

        <label class="auth-field-label">Full name</label>
        <div class="auth-group">
          <i class="fa-regular fa-user"></i>
          <input type="text" name="name" placeholder="John Doe"
                 value="<?= htmlspecialchars($name) ?>" required>
        </div>

        <label class="auth-field-label">Email address</label>
        <div class="auth-group">
          <i class="fa-regular fa-envelope"></i>
          <input type="email" name="email" placeholder="you@example.com"
                 value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <label class="auth-field-label">Password</label>
        <div class="auth-group">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="pw1" placeholder="Min. 6 characters" required>
          <button type="button" class="auth-eye" onclick="togglePw('pw1','eye1')">
            <i class="fa-regular fa-eye" id="eye1"></i>
          </button>
        </div>

        <label class="auth-field-label">Confirm password</label>
        <div class="auth-group">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="confirm_password" id="pw2" placeholder="Repeat password" required>
          <button type="button" class="auth-eye" onclick="togglePw('pw2','eye2')">
            <i class="fa-regular fa-eye" id="eye2"></i>
          </button>
        </div>

        <button type="submit" name="register" class="auth-btn">
          Create Account &nbsp;<i class="fa-solid fa-arrow-right"></i>
        </button>

        <div class="auth-extra">
          Already have an account? <a href="login.php">Sign in here</a>
        </div>

      </form>

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