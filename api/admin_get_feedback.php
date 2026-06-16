<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$sql = "SELECT f.*, u.name AS user_name, p.title AS property_title
FROM feedback f
LEFT JOIN users u ON f.user_id = u.user_id
LEFT JOIN properties p ON f.property_id = p.property_id
ORDER BY f.feedback_id DESC";

$result = mysqli_query($conn, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "status"=>"success",
    "data"=>$data
]);