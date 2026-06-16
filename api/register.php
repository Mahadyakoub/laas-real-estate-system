<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$role = 'client';

if ($name === '' || $email === '' || $password === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Name, email, and password are required"
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email format"
    ]);
    exit();
}

$checkStmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");

if (!$checkStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare email check"
    ]);
    exit();
}

mysqli_stmt_bind_param($checkStmt, "s", $email);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_fetch_assoc($checkResult)) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already exists"
    ]);
    exit();
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn, "
    INSERT INTO users (name, email, password, role, phone)
    VALUES (?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare registration query"
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashedPassword, $role, $phone);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "status" => "success",
        "message" => "Registration successful",
        "data" => [
            "user_id" => mysqli_insert_id($conn),
            "name" => $name,
            "email" => $email,
            "role" => $role,
            "phone" => $phone
        ]
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed"
    ]);
}
?>