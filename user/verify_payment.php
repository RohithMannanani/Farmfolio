<?php
session_start();
include '../databse/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['checkout_details'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid session']);
    exit;
}

try {
    $details = $_SESSION['checkout_details'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Make sure we use 'userid' instead of 'user_id'
        $userId = $_SESSION['userid']; // Changed from user_id to userid
        
        if (!$userId) {
            throw new Exception("User ID not found in session");
        }
        
        // Create order in orders table
        $orderQuery = "INSERT INTO tbl_orders (
            user_id, 
            total_amount, 
            order_status, 
            order_date,
            delivery_address, 
            phone_number, 
            payment_method, 
            payment_id, 
            payment_status
        ) VALUES (?, ?, 'pending', NOW(), ?, ?, 'razorpay', ?, 'paid')";
        
        $stmt = $conn->prepare($orderQuery);
        $stmt->bind_param("idsss", 
            $userId, // Changed from details['user_id'] to $userId
            $details['amount'],
            $details['address'],
            $details['phone'],
            $_POST['razorpay_payment_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create order: " . $stmt->error);
        }
        
        $orderId = $conn->insert_id;
        
        // Insert order items
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            throw new Exception("Cart is empty");
        }
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            // Get product price
            $priceQuery = "SELECT price FROM tbl_products WHERE product_id = ?";
            $stmt = $conn->prepare($priceQuery);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if (!$product) {
                throw new Exception("Product not found: " . $productId);
            }
            
            $itemPrice = $product['price'];
            $subtotal = $itemPrice * $quantity;
            
            // Insert order item
            $itemQuery = "INSERT INTO tbl_order_items (
                order_id, 
                product_id, 
                quantity, 
                price, 
                subtotal
            ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($itemQuery);
            $stmt->bind_param("iiids", 
                $orderId, 
                $productId, 
                $quantity, 
                $itemPrice, 
                $subtotal
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create order item: " . $stmt->error);
            }
        }
        
        // Update product stock
        $stock_update_success = updateProductStock($conn, $orderId);
        if (!$stock_update_success) {
            throw new Exception("Failed to update product stock");
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Clear session data
        unset($_SESSION['cart']);
        unset($_SESSION['checkout_details']);
        unset($_SESSION['razorpay_order_id']);
        
        echo json_encode([
            'status' => 'success',
            'redirect' => "order_success.php?order_id=" . $orderId
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw new Exception("Order processing failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Helper function for updating stock
function updateProductStock($conn, $orderId) {
    try {
        $get_items_query = "SELECT oi.product_id, oi.quantity, p.stock 
                           FROM tbl_order_items oi
                           JOIN tbl_products p ON oi.product_id = p.product_id
                           WHERE oi.order_id = ?";
        
        $stmt = mysqli_prepare($conn, $get_items_query);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $items_result = mysqli_stmt_get_result($stmt);
        
        while ($item = mysqli_fetch_assoc($items_result)) {
            $new_stock = $item['stock'] - $item['quantity'];
            
            if ($new_stock < 0) {
                throw new Exception("Insufficient stock for product ID: " . $item['product_id']);
            }
            
            $update_stock_query = "UPDATE tbl_products 
                                 SET stock = ? 
                                 WHERE product_id = ? AND stock >= ?";
            
            $update_stmt = mysqli_prepare($conn, $update_stock_query);
            mysqli_stmt_bind_param($update_stmt, "iii", $new_stock, $item['product_id'], $item['quantity']);
            $update_result = mysqli_stmt_execute($update_stmt);
            
            if (!$update_result) {
                throw new Exception("Failed to update stock for product ID: " . $item['product_id']);
            }
        }
        return true;
    } catch (Exception $e) {
        error_log("Stock update failed: " . $e->getMessage());
        return false;
    }
}

exit; 