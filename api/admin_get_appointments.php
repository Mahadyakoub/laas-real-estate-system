<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$sql = "SELECT a.*, p.title AS property_title, u.name AS user_name
FROM appointments a
JOIN properties p ON a.property_id = p.property_id
JOIN users u ON a.user_id = u.user_id
ORDER BY a.appointment_id DESC";

$result = mysqli_query($conn, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "status"=>"success",
    "data"=>$data
]);