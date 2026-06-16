<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$user_id = $_GET['user_id'] ?? '';

if ($user_id === '') {
    echo json_encode([
        "status" => "error",
        "message" => "User ID is required"
    ]);
    exit();
}

$stmt = mysqli_prepare($conn, "
    SELECT 
        f.feedback_id,
        f.comment,
        f.rating,
        f.created_at,
        p.title AS property_title
    FROM feedback f
    LEFT JOIN properties p ON f.property_id = p.property_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare query"
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$feedback = [];

while ($row = mysqli_fetch_assoc($result)) {
    $feedback[] = [
        "feedback_id" => (int)$row['feedback_id'],
        "property_title" => $row['property_title'] ?? 'General',
        "comment" => $row['comment'],
        "rating" => (int)$row['rating'],
        "created_at" => $row['created_at']
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $feedback
]);
?>