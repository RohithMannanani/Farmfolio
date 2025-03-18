<?php
session_start();
include '../databse/connect.php';

header('Content-Type: application/json');

// Get and sanitize inputs
$search = isset($_POST['search']) ? mysqli_real_escape_string($conn, $_POST['search']) : '';
$type = isset($_POST['type']) ? mysqli_real_escape_string($conn, $_POST['type']) : 'all';
$location = isset($_POST['location']) ? mysqli_real_escape_string($conn, $_POST['location']) : '';
$category = isset($_POST['category']) ? mysqli_real_escape_string($conn, $_POST['category']) : '';

$results = array();

try {
    // Search farms
    if ($type === 'all' || $type === 'farms') {
        $farm_query = "SELECT f.*, 
                        COUNT(DISTINCT p.product_id) as product_count
                      FROM tbl_farms f 
                      LEFT JOIN tbl_products p ON f.farm_id = p.farm_id 
                      WHERE f.status = 'active'";
        
        if (!empty($search)) {
            $farm_query .= " AND (f.farm_name LIKE '%$search%' 
                            OR f.location LIKE '%$search%'
                            OR f.description LIKE '%$search%')";
        }
        if (!empty($location)) {
            $farm_query .= " AND f.location = '$location'";
        }
        
        $farm_query .= " GROUP BY f.farm_id ORDER BY f.created_at DESC";
        $farm_result = mysqli_query($conn, $farm_query);

        if ($farm_result) {
            while ($farm = mysqli_fetch_assoc($farm_result)) {
                $results[] = array(
                    'type' => 'farm',
                    'id' => $farm['farm_id'],
                    'name' => htmlspecialchars($farm['farm_name']),
                    'location' => htmlspecialchars($farm['location']),
                    'description' => htmlspecialchars($farm['description']),
                    'product_count' => $farm['product_count'],
                    'created_at' => $farm['created_at']
                );
            }
        }
    }

    // Search products
    if ($type === 'all' || $type === 'products') {
        $product_query = "SELECT p.*, 
                         c.category, c.sub as subcategory,
                         f.farm_name, f.location
                         FROM tbl_products p 
                         JOIN tbl_category c ON p.category_id = c.category_id 
                         JOIN tbl_farms f ON p.farm_id = f.farm_id 
                         WHERE f.status = 'active' AND p.status = '0'";
        
        if (!empty($search)) {
            $product_query .= " AND (p.product_name LIKE '%$search%' 
                               OR c.category LIKE '%$search%'
                               OR c.sub LIKE '%$search%'
                               OR p.description LIKE '%$search%')";
        }
        if (!empty($category)) {
            $product_query .= " AND c.category = '$category'";
        }
        
        $product_query .= " GROUP BY p.product_id ORDER BY p.created_at DESC";
        $product_result = mysqli_query($conn, $product_query);

        if ($product_result) {
            while ($product = mysqli_fetch_assoc($product_result)) {
                $results[] = array(
                    'type' => 'product',
                    'id' => $product['product_id'],
                    'name' => htmlspecialchars($product['product_name']),
                    'category' => htmlspecialchars($product['category']),
                    'subcategory' => htmlspecialchars($product['subcategory']),
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'unit' => $product['unit'],
                    'description' => htmlspecialchars($product['description']),
                    'farm_name' => htmlspecialchars($product['farm_name']),
                    'farm_location' => htmlspecialchars($product['location']),
                    'created_at' => $product['created_at']
                );
            }
        }
    }

    echo json_encode([
        'success' => true, 
        'results' => $results,
        'total' => count($results)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while searching',
        'debug' => $e->getMessage()
    ]);
}
?> 