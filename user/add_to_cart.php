<?php
session_start();
include '../databse/connect.php'; // Ensure you include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
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
        $updateStmt->execute();
    } else {
        // Insert a new record into the cart
        $insertQuery = "INSERT INTO tbl_cart (product_id, quantity, user_id) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iii", $productId, $quantity, $userId);
        $insertStmt->execute();
    }

    echo json_encode([
        'status' => 'success', 
        'message' => 'Product added to cart!',
        'cartCount' => count($_SESSION['cart'])
    ]);
}
?> 