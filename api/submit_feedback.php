<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$user_id = $_POST['user_id'] ?? '';
$property_id = $_POST['property_id'] ?? '';
$rating = $_POST['rating'] ?? '';
$comment = trim($_POST['comment'] ?? '');

if ($user_id === '' || $property_id === '' || $rating === '' || $comment === '') {
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required"
    ]);
    exit();
}

$rating = (int)$rating;
if ($rating < 1 || $rating > 5) {
    echo json_encode([
        "status" => "error",
        "message" => "Rating must be between 1 and 5"
    ]);
    exit();
}

$stmt = mysqli_prepare($conn, "
    INSERT INTO feedback (comment, rating, user_id, property_id)
    VALUES (?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare query"
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "siii", $comment, $rating, $user_id, $property_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "status" => "success",
        "message" => "Feedback submitted successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to submit feedback"
    ]);
}
?>