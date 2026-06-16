<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($property_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid property ID"
    ]);
    exit();
}

$stmt = mysqli_prepare($conn, "
    SELECT property_id, title, location, price, category, image,
           description, bedrooms, bathrooms, size, status
    FROM properties
    WHERE property_id = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare query"
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $property_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$property = mysqli_fetch_assoc($result);

if ($property) {
    echo json_encode([
        "status" => "success",
        "data" => [
            "property_id" => (int)$property['property_id'],
            "title"       => $property['title'],
            "location"    => $property['location'],
            "price"       => (float)$property['price'],
            "category"    => $property['category'],
            "image"       => $property['image']
                ? "http://localhost/laas_rental_system/assets/images/properties/" . $property['image']
                : null,
            "description" => $property['description'] ?: "",
            "bedrooms"    => (int)$property['bedrooms'],
            "bathrooms"   => (int)$property['bathrooms'],
            "size"        => (int)$property['size'],
            "status"      => $property['status']
        ]
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Property not found"
    ]);
}
?>