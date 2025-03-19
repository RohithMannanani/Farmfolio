<?php
session_start();
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

// Include database connection
include '../databse/connect.php';

$totalAmount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;

// Add these new functions at the top of the file
function validateStock($conn, $cart_items) {
    $errors = array();
    foreach ($cart_items as $productId => $quantity) {
        $query = "SELECT stock, product_name FROM tbl_products WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);
        
        if ($product['stock'] < $quantity) {
            $errors[] = "Insufficient stock for {$product['product_name']}. Available: {$product['stock']}, Requested: {$quantity}";
        }
    }
    return $errors;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $userId = $_SESSION['userid'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    
    // Validate stock before processing
    $stock_errors = validateStock($conn, $_SESSION['cart']);
    
    if (!empty($stock_errors)) {
        $_SESSION['error'] = "Stock validation failed:\n" . implode("\n", $stock_errors);
        header('Location: cart.php');
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create order in orders table with delivery details
        $orderQuery = "INSERT INTO tbl_orders (user_id, total_amount, order_status, order_date, delivery_address, phone_number, payment_method) 
                      VALUES (?, ?, 'pending', NOW(), ?, ?, 'cod')";
        $stmt = $conn->prepare($orderQuery);
        $stmt->bind_param("idss", $userId, $totalAmount, $address, $phone);
        $stmt->execute();
        $orderId = $conn->insert_id;
        
        // Insert order items
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            // Get product price
            $priceQuery = "SELECT price FROM tbl_products WHERE product_id = ?";
            $stmt = $conn->prepare($priceQuery);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            $itemPrice = $product['price'];
            $subtotal = $itemPrice * $quantity;
            
            // Insert order item
            $itemQuery = "INSERT INTO tbl_order_items (order_id, product_id, quantity, price, subtotal) 
                         VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($itemQuery);
            $stmt->bind_param("iiids", $orderId, $productId, $quantity, $itemPrice, $subtotal);
            $stmt->execute();
        }
        
        // Update product stock
        $stock_update_success = updateProductStock($conn, $orderId);
        if (!$stock_update_success) {
            throw new Exception("Failed to update product stock");
        }
        
        // Clear cart
        $clearCartQuery = "DELETE FROM tbl_cart WHERE user_id = ?";
        $stmt = $conn->prepare($clearCartQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Clear session cart
        unset($_SESSION['cart']);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Redirect to success page
        header('Location: order_success.php?order_id=' . $orderId);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error processing order: " . $e->getMessage();
        header('Location: cart.php');
        exit;
    }
}
?>

<!-- Add this at the top of your HTML, after the <body> tag -->
<?php if(isset($_SESSION['error'])): ?>
    <div class="error-message" style="background: #fee2e2; color: #991b1b; padding: 15px; margin: 20px auto; max-width: 800px; border-radius: 8px; text-align: center;">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Farmfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .checkout-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .checkout-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .checkout-header h1 {
            color: #1a4d2e;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .order-summary h2 {
            color: #1a4d2e;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .total-amount {
            font-size: 24px;
            color: #1a4d2e;
            font-weight: bold;
            margin-top: 10px;
        }

        .submit-btn {
            background-color: #1a4d2e;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #2d6a4f;
        }

        .payment-info {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .payment-info p {
            color: #1a4d2e;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Checkout</h1>
        </div>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <p>Total Items: <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></p>
            <p class="total-amount">Total Amount: ₹<?php echo number_format($totalAmount, 2); ?></p>
        </div>

        <div class="payment-info">
            <p><i class="fas fa-info-circle"></i> Payment Method: Cash on Delivery</p>
        </div>

        <form method="POST" action="checkout.php">
            <input type="hidden" name="total_amount" value="<?php echo $totalAmount; ?>">
            
            <div class="form-group">
                <label for="address">Delivery Address *</label>
                <textarea id="address" name="address" required 
                    placeholder="Enter your complete delivery address"></textarea>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" required 
                    placeholder="Enter your contact number"
                    pattern="[0-9]{10}" 
                    title="Please enter a valid 10-digit phone number">
            </div>

            <button type="submit" name="place_order" class="submit-btn">
                Place Order
            </button>
        </form>
    </div>
</body>
</html> 