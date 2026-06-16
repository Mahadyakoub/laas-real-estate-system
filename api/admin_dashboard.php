<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'];
$properties = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM properties"))['total'];
$appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments"))['total'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status='Pending'"))['total'];

echo json_encode([
    "status"=>"success",
    "data"=>[
        "users"=>$users,
        "properties"=>$properties,
        "appointments"=>$appointments,
        "pending"=>$pending
    ]
]);