<?php
session_start();
include '../databse/connect.php'; // Ensure this path is correct

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User not logged in'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json = file_get_contents('php://input');
    // Decode the JSON data
    $data = json_decode($json, true);
    
    // Validate the data
    if (!isset($data['productId']) || !is_numeric($data['productId'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid product ID'
        ]);
        exit;
    }
    
    $productId = intval($data['productId']);
    $userId = $_SESSION['userid']; // Assuming you store the user ID in the session
    
    // Initialize the cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add or update the product quantity in cart
    if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = 1; // Initial quantity
    } else {
        $_SESSION['cart'][$productId]++; // Increment quantity if already exists
    }
    
    // Insert or update the cart in the database
    $quantity = $_SESSION['cart'][$productId];
    
    try {
        // Check if the product already exists in the cart for the user
        $checkQuery = "SELECT * FROM tbl_cart WHERE product_id = ? AND user_id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ii", $productId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update the quantity if the product already exists in the cart
            $updateQuery = "UPDATE tbl_cart SET quantity = quantity + 1, added_at = CURRENT_TIMESTAMP WHERE product_id = ? AND user_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $productId, $userId);
            $success = $updateStmt->execute();
        } else {
            // Insert a new record into the cart
            $insertQuery = "INSERT INTO tbl_cart (product_id, quantity, user_id) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iii", $productId, $quantity, $userId);
            $success = $insertStmt->execute();
        }
        
        if ($success) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Product added to cart!',
                'cartCount' => count($_SESSION['cart'])
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $conn->error
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Exception: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>