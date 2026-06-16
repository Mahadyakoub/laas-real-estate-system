<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$uploadDir = "../assets/images/properties/";

function uploadImage($file, $uploadDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) return null;

    $newName = uniqid("property_", true) . "." . $ext;
    $target = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $newName;
    }

    return null;
}

$title = $_POST['title'] ?? '';
$location = $_POST['location'] ?? '';
$price = $_POST['price'] ?? 0;
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';
$bedrooms = $_POST['bedrooms'] ?? 0;
$bathrooms = $_POST['bathrooms'] ?? 0;
$size = $_POST['size'] ?? 0;
$status = $_POST['status'] ?? 'Available';

$mainImage = uploadImage($_FILES['image'] ?? null, $uploadDir);

$sql = "INSERT INTO properties 
(title, location, price, category, description, bedrooms, bathrooms, size, image, status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param(
    $stmt,
    "ssissiiiss",
    $title,
    $location,
    $price,
    $category,
    $description,
    $bedrooms,
    $bathrooms,
    $size,
    $mainImage,
    $status
);

if (mysqli_stmt_execute($stmt)) {
    $propertyId = mysqli_insert_id($conn);

    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['name'] as $i => $name) {
            $file = [
                'name' => $_FILES['images']['name'][$i],
                'type' => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error' => $_FILES['images']['error'][$i],
                'size' => $_FILES['images']['size'][$i],
            ];

            $extraImage = uploadImage($file, $uploadDir);

            if ($extraImage) {
                $sortOrder = $i + 1;
                $imgSql = "INSERT INTO property_images (property_id, image_path, sort_order)
                           VALUES (?, ?, ?)";
                $imgStmt = mysqli_prepare($conn, $imgSql);
                mysqli_stmt_bind_param($imgStmt, "isi", $propertyId, $extraImage, $sortOrder);
                mysqli_stmt_execute($imgStmt);
            }
        }
    }

    echo json_encode(["status" => "success", "message" => "Property added successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to add property"]);
}
?>