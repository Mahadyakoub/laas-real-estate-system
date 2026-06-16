<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: auth/login.php");
    exit();
}

if ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

if ($_SESSION['role'] === 'user') {
    header("Location: user/home.php");
    exit();
}

/* If role is invalid, destroy session and return to login */
session_destroy();
header("Location: auth/login.php");
exit();