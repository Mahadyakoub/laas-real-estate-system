<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password are required"
    ]);
    exit();
}

$stmt = mysqli_prepare($conn, "
    SELECT user_id, name, email, password, role
    FROM users
    WHERE email = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare query"
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
    exit();
}

if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
    exit();
}

echo json_encode([
    "status" => "success",
    "message" => "Login successful",
    "data" => [
        "user_id" => (int)$user['user_id'],
        "name"    => $user['name'],
        "email"   => $user['email'],
        "role"    => $user['role']
    ]
]);
?>