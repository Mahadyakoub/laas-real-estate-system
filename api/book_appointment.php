<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
$appointment_date = trim($_POST['appointment_date'] ?? '');
$note = trim($_POST['note'] ?? '');

if ($user_id <= 0 || $property_id <= 0 || $appointment_date === '') {
    echo json_encode([
        "status" => "error",
        "message" => "User ID, property ID, and appointment date are required"
    ]);
    exit();
}

if ($appointment_date < date('Y-m-d')) {
    echo json_encode([
        "status" => "error",
        "message" => "Appointment date cannot be in the past"
    ]);
    exit();
}

$propertyStmt = mysqli_prepare($conn, "
    SELECT property_id, status
    FROM properties
    WHERE property_id = ?
    LIMIT 1
");

if (!$propertyStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare property query"
    ]);
    exit();
}

mysqli_stmt_bind_param($propertyStmt, "i", $property_id);
mysqli_stmt_execute($propertyStmt);
$propertyResult = mysqli_stmt_get_result($propertyStmt);
$property = mysqli_fetch_assoc($propertyResult);

if (!$property) {
    echo json_encode([
        "status" => "error",
        "message" => "Property not found"
    ]);
    exit();
}

if ($property['status'] !== 'Available') {
    echo json_encode([
        "status" => "error",
        "message" => "This property is not available for booking"
    ]);
    exit();
}

$stmt = mysqli_prepare($conn, "
    INSERT INTO appointments (user_id, property_id, appointment_date, status, note)
    VALUES (?, ?, ?, 'Pending', ?)
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare appointment query"
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "iiss", $user_id, $property_id, $appointment_date, $note);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "status" => "success",
        "message" => "Appointment booked successfully",
        "data" => [
            "appointment_id" => mysqli_insert_id($conn),
            "user_id" => $user_id,
            "property_id" => $property_id,
            "appointment_date" => $appointment_date,
            "status" => "Pending",
            "note" => $note
        ]
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to book appointment"
    ]);
}
?>