<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$sql = "SELECT * FROM properties ORDER BY property_id DESC";
$result = mysqli_query($conn, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $data
]);