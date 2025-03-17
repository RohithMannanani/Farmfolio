<?php
include '../databse/connect.php';

$category = $_GET['category'] ?? '';

if ($category) {
    $query = "SELECT category_id, sub FROM tbl_category WHERE category = ? ORDER BY sub";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subcategories = [];
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($subcategories);
} 