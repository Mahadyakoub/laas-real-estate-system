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
        a.appointment_id,
        a.appointment_date,
        a.status,
        a.note,
        a.created_at,
        p.property_id,
        p.title,
        p.location
    FROM appointments a
    INNER JOIN properties p ON a.property_id = p.property_id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_id DESC
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

$appointments = [];

while ($row = mysqli_fetch_assoc($result)) {
    $appointments[] = [
        "appointment_id"   => (int)$row['appointment_id'],
        "appointment_date" => $row['appointment_date'],
        "status"           => $row['status'],
        "note"             => $row['note'],
        "created_at"       => $row['created_at'],
        "property_id"      => (int)$row['property_id'],
        "property_title"   => $row['title'],
        "location"         => $row['location']
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $appointments
]);
?>