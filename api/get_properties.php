<?php
header("Content-Type: application/json");
require_once("../config/db.php");

$baseImageUrl = "http://10.0.2.2/laas_rental_system/assets/images/properties/";

$sql = "SELECT 
            property_id,
            title,
            location,
            price,
            category,
            image,
            description,
            bedrooms,
            bathrooms,
            size,
            status
        FROM properties
        WHERE status='Available'
        ORDER BY property_id DESC";

$result = mysqli_query($conn, $sql);
$properties = [];

while ($row = mysqli_fetch_assoc($result)) {
    $propertyId = (int)$row['property_id'];

    $images = [];

    if (!empty($row['image'])) {
        $images[] = $baseImageUrl . $row['image'];
    }

    $imgSql = "SELECT image_path FROM property_images 
               WHERE property_id=$propertyId 
               ORDER BY sort_order ASC, image_id ASC";
    $imgResult = mysqli_query($conn, $imgSql);

    while ($img = mysqli_fetch_assoc($imgResult)) {
        if (!empty($img['image_path'])) {
            $images[] = $baseImageUrl . $img['image_path'];
        }
    }

    $properties[] = [
        "property_id" => $propertyId,
        "title" => $row['title'],
        "location" => $row['location'],
        "price" => (float)$row['price'],
        "category" => $row['category'],
        "image" => !empty($images) ? $images[0] : null,
        "images" => $images,
        "description" => $row['description'] ?: "",
        "bedrooms" => (int)$row['bedrooms'],
        "bathrooms" => (int)$row['bathrooms'],
        "size" => (int)$row['size'],
        "status" => $row['status']
    ];
}

echo json_encode([
    "status" => "success",
    "count" => count($properties),
    "data" => $properties
]);
?>