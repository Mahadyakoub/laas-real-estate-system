<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$id = $_POST['appointment_id'];
$status = $_POST['status'];

$sql = "UPDATE appointments SET status=? WHERE appointment_id=?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $status, $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error"]);
}