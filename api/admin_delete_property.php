<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$id = $_POST['property_id'];

$sql = "DELETE FROM properties WHERE property_id=?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error"]);
}