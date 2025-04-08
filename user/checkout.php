<?php
session_start();
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
    exit; // Add exit here
}

// Include database connection and Razorpay setup
include '../databse/connect.php';
require_once('../razorpay-php/Razorpay.php');
use Razorpay\Api\Api;

$razorpay_key_id = 'rzp_test_FjIWY7OUwRhbVn';
$razorpay_key_secret = 'pxQaQUudekWwLgos0xOd2wUB';

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

// Fetch cart items for order summary
$productDetails = [];
$totalCartPrice = 0;

if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    $productIds = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    
    $query = "SELECT p.*, COALESCE(p.stock, 0) as available_stock 
              FROM tbl_products p 
              WHERE p.product_id IN ($productIds)";
    $result = mysqli_query($conn, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $productDetails[] = $row;
    }
}

// Handle Razorpay payment initialization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method']) && $_POST['payment_method'] === 'razorpay') {
    header('Content-Type: application/json');
    
    try {
        // Get form data
        $userId = $_SESSION['userid'];
        $full_address = $_POST['house_name'] . ', ' . $_POST['post_office'] . ', ' . $_POST['place'] . ', PIN: ' . $_POST['pin'];
        $phone = $_POST['phone'];
        $amount = floatval($_POST['total_amount']);

        // Validate amount
        if ($amount <= 0) {
            throw new Exception("Invalid amount");
        }

        // Initialize Razorpay
        $api = new Api($razorpay_key_id, $razorpay_key_secret);
        
        // Create Razorpay order
        $razorpayOrder = $api->order->create([
            'amount' => $amount * 100, // Convert to paise
            'currency' => 'INR',
            'payment_capture' => 1
        ]);

        // Store order details in session
        $_SESSION['razorpay_order_id'] = $razorpayOrder['id'];
        $_SESSION['checkout_details'] = [
            'address' => $full_address,
            'phone' => $phone,
            'amount' => $amount,
            'user_id' => $userId
        ];

        // Log the order creation
        error_log("Created Razorpay order: " . print_r($razorpayOrder, true));

        // Return payment details
        echo json_encode([
            'status' => 'success',
            'key' => $razorpay_key_id,
            'amount' => $amount * 100,
            'currency' => 'INR',
            'order_id' => $razorpayOrder['id'],
            'name' => 'Farmfolio',
            'description' => 'Order Payment',
            'prefill' => [
                'name' => $_SESSION['username'],
                'contact' => $phone,
            ]
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Razorpay Error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Modify the order processing code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $userId = $_SESSION['userid'];
    $house_name = mysqli_real_escape_string($conn, trim($_POST['house_name']));
    $post_office = mysqli_real_escape_string($conn, trim($_POST['post_office']));
    $place = mysqli_real_escape_string($conn, trim($_POST['place']));
    $pin = mysqli_real_escape_string($conn, trim($_POST['pin']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $payment_method = mysqli_real_escape_string($conn, trim($_POST['payment_method']));
    
    // Debug info - log the received data
    error_log("Checkout POST data: " . print_r($_POST, true));
    error_log("Payment method: " . $payment_method);
    
    // Combine address fields
    $full_address = "$house_name, $post_office, $place, PIN: $pin";

    // Add server-side PIN validation
    $pincode_json = file_get_contents('../sign up/pincode.json');
    $pincodes = json_decode($pincode_json, true)['pincodes'];
    
    if (!in_array($pin, $pincodes)) {
        $_SESSION['error'] = "Invalid PIN code. Please enter a valid serviceable PIN code.";
        header('Location: checkout.php');
        exit;
    }

    // Validate stock availability
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        $_SESSION['error'] = "Your cart is empty.";
        header('Location: cart.php');
        exit;
    }

    $stock_errors = validateStock($conn, $_SESSION['cart']);
    if (!empty($stock_errors)) {
        $_SESSION['error'] = "Stock validation failed:\n" . implode("\n", $stock_errors);
        header('Location: cart.php');
        exit;
    }

    if ($payment_method === 'razorpay') {
        // Initialize Razorpay
        $api = new Api($razorpay_key_id, $razorpay_key_secret);

        // Create Razorpay order
        $razorpayOrder = $api->order->create([
            'amount' => $totalAmount * 100, // Amount in paise
            'currency' => 'INR',
            'payment_capture' => 1
        ]);

        // Store order details in session for verification
        $_SESSION['razorpay_order_id'] = $razorpayOrder['id'];
        $_SESSION['checkout_details'] = [
            'address' => $full_address,
            'phone' => $phone,
            'amount' => $totalAmount,
            'user_id' => $userId
        ];

        // Return Razorpay details for the payment form
        echo json_encode([
            'order_id' => $razorpayOrder['id'],
            'amount' => $totalAmount * 100,
            'currency' => 'INR',
            'key' => $razorpay_key_id,
            'name' => 'Farmfolio',
            'description' => 'Order Payment',
            'prefill' => [
                'name' => $_SESSION['username'],
                'contact' => $phone,
            ]
        ]);
        exit;
    } else {
        // For COD payment - process directly
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            error_log("Processing COD order");
            
            // Create order in orders table with delivery details
            $orderQuery = "INSERT INTO tbl_orders (user_id, total_amount, order_status, order_date, delivery_address, phone_number, payment_method) 
                          VALUES (?, ?, 'pending', NOW(), ?, ?, 'cod')";
            $stmt = $conn->prepare($orderQuery);
            $stmt->bind_param("idss", $userId, $totalAmount, $full_address, $phone);
            $stmt->execute();
            $orderId = $conn->insert_id;
            
            error_log("Created order ID: " . $orderId);
            
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
                
                error_log("Added item to order: Product ID: $productId, Quantity: $quantity");
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
            
            error_log("COD order completed successfully. Redirecting to success page.");
            
            // Redirect to success page
            header("Location: order_success.php?order_id=" . $orderId);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            error_log("Order processing error: " . $e->getMessage());
            $_SESSION['error'] = "Error processing order: " . $e->getMessage();
            header('Location: cart.php');
            exit;
        }
    }
}

// Modify the Razorpay payment verification section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    $payment_id = $_POST['razorpay_payment_id'];
    $razorpay_order_id = $_POST['razorpay_order_id'];
    $signature = $_POST['razorpay_signature'];

    // Verify signature
    $api = new Api($razorpay_key_id, $razorpay_key_secret);
    
    try {
        $attributes = [
            'razorpay_payment_id' => $payment_id,
            'razorpay_order_id' => $razorpay_order_id,
            'razorpay_signature' => $signature
        ];
        
        $api->utility->verifyPaymentSignature($attributes);
        
        // Process the order using the stored session details
        if (isset($_SESSION['checkout_details'])) {
            $details = $_SESSION['checkout_details'];
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Create order in orders table
                $orderQuery = "INSERT INTO tbl_orders (user_id, total_amount, order_status, order_date, 
                             delivery_address, phone_number, payment_method, payment_id, payment_status) 
                             VALUES (?, ?, 'confirmed', NOW(), ?, ?, 'razorpay', ?, 'completed')";
                $stmt = $conn->prepare($orderQuery);
                $stmt->bind_param("idsss", 
                    $details['user_id'], 
                    $details['amount'], 
                    $details['address'], 
                    $details['phone'],
                    $payment_id
                );
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
                    $stmt->prepare($itemQuery);
                    $stmt->bind_param("iiids", $orderId, $productId, $quantity, $itemPrice, $subtotal);
                    $stmt->execute();
                }
                
                // Update product stock
                $stock_update_success = updateProductStock($conn, $orderId);
                if (!$stock_update_success) {
                    throw new Exception("Failed to update product stock");
                }
                
                // Clear cart
                unset($_SESSION['cart']);
                
                // Commit transaction
                mysqli_commit($conn);
                
                // Clear checkout details
                unset($_SESSION['checkout_details']);
                unset($_SESSION['razorpay_order_id']);
                
                echo json_encode([
                    'status' => 'success',
                    'order_id' => $orderId,
                    'redirect' => "order_success.php?order_id=" . $orderId
                ]);
                exit;
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                throw new Exception("Order processing failed: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<!-- Add this at the top of your HTML, after the <body> tag -->
<?php if(isset($_SESSION['error'])): ?>
    <div class="error-message" style="background: #fee2e2; color: #991b1b; padding: 15px; margin: 20px auto; max-width: 800px; border-radius: 8px; text-align: center;">
        <?php 
        echo htmlspecialchars($_SESSION['error']);
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

        :root {
            --primary-color: #1a4d2e;
            --primary-hover: #2d6a4f;
            --light-green: #e8f5e9;
            --border-color: #ddd;
            --error-color: #ef4444;
            --error-bg: #fee2e2;
            --success-color: #22c55e;
            --success-bg: #dcfce7;
            --bg-color: #f0f2f5;
            --text-color: #333;
            --secondary-text: #6b7280;
            --border-radius: 12px;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .checkout-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .checkout-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .checkout-header h1 {
            color: var(--primary-color);
            font-size: 28px;
            position: relative;
            display: inline-block;
        }

        .checkout-header h1:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 3px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group {
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
            animation-fill-mode: both;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 77, 46, 0.1);
            outline: none;
        }

        .form-group input.valid {
            border-color: var(--success-color);
            background-color: rgba(220, 252, 231, 0.2);
        }

        .form-group input.invalid {
            border-color: var(--error-color);
            background-color: rgba(254, 226, 226, 0.2);
        }

        .validation-message {
            font-size: 0.85rem;
            margin-top: 5px;
            border-radius: 4px;
            padding: 5px 10px;
            display: none;
        }

        .error-message-field {
            color: var(--error-color);
            background-color: var(--error-bg);
            display: none;
        }

        .success-message-field {
            color: var(--success-color);
            background-color: var(--success-bg);
            display: none;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .order-summary {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin: 30px 0;
            box-shadow: var(--box-shadow);
        }

        .summary-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
            position: relative;
        }

        .summary-title:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 80px;
            height: 2px;
            background-color: var(--primary-color);
        }

        .summary-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
            border-left: 3px solid var(--primary-color);
        }

        .summary-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .item-details h3 {
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .item-quantity {
            color: var(--secondary-text);
            font-size: 0.9rem;
        }

        .item-price p {
            color: var (--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .summary-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }

        .total-amount {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(26, 77, 46, 0.2);
        }

        .total-amount h3 {
            font-size: 1.2rem;
        }

        .total-amount p {
            font-size: 1.4rem;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        .empty-cart {
            text-align: center;
            color: var(--secondary-text);
            padding: 30px 20px;
            font-size: 1.1rem;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 16px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(26, 77, 46, 0.2);
        }

        .submit-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(26, 77, 46, 0.25);
        }

        .submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(26, 77, 46, 0.2);
        }

        .payment-info {
            background: var(--light-green);
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }

        .payment-info h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.2rem;
            position: relative;
            padding-bottom: 10px;
        }

        .payment-info h3:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary-color);
        }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .payment-option:hover {
            background: #f0f2f5;
            transform: translateY(-2px);
            border-color: var(--primary-color);
        }

        .payment-option input[type="radio"] {
            accent-color: var(--primary-color);
            width: 20px;
            height: 20px;
        }

        .payment-option label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
            font-weight: 500;
            cursor: pointer;
            font-size: 1.05rem;
            width: 100%;
        }

        .payment-option i {
            font-size: 1.3rem;
            color: var(--primary-color);
        }

        /* Enhanced animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Form Field Animations */
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.15s; }
        .form-group:nth-child(3) { animation-delay: 0.2s; }
        .form-group:nth-child(4) { animation-delay: 0.25s; }
        .form-group:nth-child(5) { animation-delay: 0.3s; }

        /* Required Field Indicator */
        .required::after {
            content: '*';
            color: var(--error-color);
            margin-left: 4px;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .checkout-container {
                padding: 20px;
                margin: 20px 10px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .payment-info {
            padding: 15px;
            }

            .payment-option label {
                font-size: 0.95rem;
            }

            .summary-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
                align-items: flex-start;
            }

            .total-amount {
                flex-direction: column;
                gap: 5px;
                text-align: center;
                padding: 15px 10px;
            }
        }

        /* Improved debug section */
        .debug-section {
            background-color: #fef3c7; 
            border-left: 4px solid #d97706; 
            padding: 20px; 
            margin: 20px auto;
            max-width: 800px; 
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .debug-section h3 {
            margin-top: 0;
            color: #92400e;
            font-size: 1.2rem;
            border-bottom: 1px solid #fcd34d;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .debug-section pre {
            white-space: pre-wrap;
            overflow-x: auto;
            background: #fffbeb;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.9rem;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Checkout</h1>
        </div>

        <div class="order-summary">
            <h2 class="summary-title">Order Summary</h2>
            <?php if (count($productDetails) > 0): ?>
                <div class="summary-items">
                    <?php foreach ($productDetails as $product): 
                        $quantity = isset($_SESSION['cart'][$product['product_id']]) ? $_SESSION['cart'][$product['product_id']] : 1;
                        $productTotal = $product['price'] * $quantity;
                        $totalCartPrice += $productTotal;
                    ?>
                        <div class="summary-item">
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p class="item-quantity">Quantity: <?php echo $quantity; ?></p>
                            </div>
                            <div class="item-price">
                                <p>₹<?php echo number_format($productTotal, 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-total">
                        <div class="total-line"></div>
                        <div class="total-amount">
                            <h3>Total Amount:</h3>
                            <p>₹<?php echo number_format($totalCartPrice, 2); ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p class="empty-cart">Your cart is empty.</p>
            <?php endif; ?>
        </div>

        <div class="payment-info">
            <h3>Select Payment Method</h3>
            <div class="payment-methods">
                <div class="payment-option">
                    <input type="radio" id="cod" name="payment_method" value="cod" checked>
                    <label for="cod">
                        <i class="fas fa-money-bill-wave"></i>
                        Cash on Delivery
                    </label>
                </div>
                <div class="payment-option">
                    <input type="radio" id="online" name="payment_method" value="razorpay">
                    <label for="online">
                        <i class="fas fa-credit-card"></i>
                        Pay Online (Cards, UPI, NetBanking)
                    </label>
                </div>
            </div>
        </div>

        <form method="POST" id="checkout-form">
            <input type="hidden" name="total_amount" value="<?php echo $totalCartPrice; ?>">
            
            <div class="form-grid">
            <div class="form-group">
                <label for="house_name" class="required">House Name</label>
                <input type="text" id="house_name" name="house_name" required 
                    placeholder="Enter your house name"
                    autocomplete="address-line1">
                    <div class="validation-message error-message-field" id="house_name_error"></div>
            </div>

            <div class="form-group">
                <label for="post_office" class="required">Post Office</label>
                <input type="text" id="post_office" name="post_office" required 
                    placeholder="Enter your post office"
                    autocomplete="address-line2">
                    <div class="validation-message error-message-field" id="post_office_error"></div>
            </div>

            <div class="form-group">
                <label for="place" class="required">Place</label>
                <input type="text" id="place" name="place" required 
                    placeholder="Enter your place"
                    autocomplete="address-level2">
                    <div class="validation-message error-message-field" id="place_error"></div>
            </div>

            <div class="form-group">
                <label for="pin" class="required">PIN Code</label>
                <input type="text" id="pin" name="pin" required 
                    placeholder="Enter your PIN code"
                    pattern="[0-9]{6}" 
                    title="Please enter a valid 6-digit PIN code"
                    autocomplete="postal-code">
                    <div class="validation-message error-message-field" id="pin_error"></div>
                    <div class="validation-message success-message-field" id="pin_success"></div>
            </div>

            <div class="form-group">
                <label for="phone" class="required">Phone Number</label>
                <input type="tel" id="phone" name="phone" required 
                    placeholder="Enter your contact number"
                    pattern="[0-9]{10}" 
                    title="Please enter a valid 10-digit phone number"
                    autocomplete="tel">
                    <div class="validation-message error-message-field" id="phone_error"></div>
            </div>

                <div class="form-group full-width">
                    <button type="submit" class="submit-btn" name="place_order">
                        <i class="fas fa-shopping-bag"></i> Place Order
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Add Razorpay script -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    // Add this function before your existing JavaScript code
    function resetSubmitButton() {
        const btn = document.querySelector('.submit-btn');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-shopping-bag"></i> Place Order';
            btn.disabled = false;
        }
    }

    // Fetch valid PINs from JSON file
    let validPincodes = [];
    fetch('../sign up/pincode.json')
        .then(response => response.json())
        .then(data => {
            validPincodes = data.pincodes;
            console.log('Loaded', validPincodes.length, 'valid pincodes');
        })
        .catch(error => console.error('Error loading pincodes:', error));

    // Form validation functions
    document.addEventListener('DOMContentLoaded', function() {
        const formFields = {
            house_name: document.getElementById('house_name'),
            post_office: document.getElementById('post_office'),
            place: document.getElementById('place'),
            pin: document.getElementById('pin'),
            phone: document.getElementById('phone')
        };
        
        // Validate PIN code against JSON data
        function validatePincode(pin) {
            pin = parseInt(pin);
            if (isNaN(pin)) return false;
            return validPincodes.includes(pin);
        }
        
        // Function to show validation error
        function showError(fieldId, message) {
            const errorElement = document.getElementById(`${fieldId}_error`);
            const field = document.getElementById(fieldId);
            
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
            
            if (field) {
                field.classList.add('invalid');
                field.classList.remove('valid');
            }
        }
        
        // Function to show validation success
        function showSuccess(fieldId, message) {
            const successElement = document.getElementById(`${fieldId}_success`);
            const errorElement = document.getElementById(`${fieldId}_error`);
            const field = document.getElementById(fieldId);
            
            if (successElement) {
                successElement.textContent = message;
                successElement.style.display = 'block';
            }
            
            if (errorElement) {
                errorElement.style.display = 'none';
            }
            
            if (field) {
                field.classList.add('valid');
                field.classList.remove('invalid');
            }
        }
        
        // Function to hide validation messages
        function hideValidation(fieldId) {
            const errorElement = document.getElementById(`${fieldId}_error`);
            const successElement = document.getElementById(`${fieldId}_success`);
            const field = document.getElementById(fieldId);
            
            if (errorElement) errorElement.style.display = 'none';
            if (successElement) successElement.style.display = 'none';
            if (field) {
                field.classList.remove('valid');
                field.classList.remove('invalid');
            }
        }
        
        // PIN code validation
        if (formFields.pin) {
            formFields.pin.addEventListener('input', function() {
                const pin = this.value.trim();
                hideValidation('pin');
                
                if (pin.length === 6) {
                    if (validatePincode(pin)) {
                        showSuccess('pin', 'Valid PIN code. Delivery available!');
                    } else {
                        showError('pin', 'Sorry, delivery is not available for this PIN code');
                    }
                } else if (pin.length > 0) {
                    showError('pin', 'PIN code must be 6 digits');
                }
            });
            
            // Validate on blur
            formFields.pin.addEventListener('blur', function() {
                const pin = this.value.trim();
                if (pin.length === 0) {
                    showError('pin', 'PIN code is required');
                } else if (pin.length !== 6) {
                    showError('pin', 'PIN code must be 6 digits');
                } else if (!validatePincode(pin)) {
                    showError('pin', 'Sorry, delivery is not available for this PIN code');
                }
            });
        }
        
        // Phone validation
        if (formFields.phone) {
            formFields.phone.addEventListener('input', function() {
                const phone = this.value.trim();
                hideValidation('phone');
                
                if (phone.length > 0 && !/^\d+$/.test(phone)) {
                    showError('phone', 'Phone number must contain only digits');
                } else if (phone.length === 10) {
                    this.classList.add('valid');
                } else if (phone.length > 0) {
                    showError('phone', 'Phone number must be 10 digits');
                }
            });
            
            // Validate on blur
            formFields.phone.addEventListener('blur', function() {
                const phone = this.value.trim();
                if (phone.length === 0) {
                    showError('phone', 'Phone number is required');
                } else if (phone.length !== 10) {
                    showError('phone', 'Phone number must be 10 digits');
                } else if (!/^\d+$/.test(phone)) {
                    showError('phone', 'Phone number must contain only digits');
                }
            });
        }
        
        // Text field validations
        ['house_name', 'post_office', 'place'].forEach(fieldId => {
            const field = formFields[fieldId];
            if (field) {
                field.addEventListener('blur', function() {
                    const value = this.value.trim();
                    if (value.length === 0) {
                        showError(fieldId, 'This field is required');
                    } else if (value.length < 3) {
                        showError(fieldId, 'Please enter at least 3 characters');
                    } else {
                        hideValidation(fieldId);
                        this.classList.add('valid');
                    }
                });
                
                field.addEventListener('input', function() {
                    if (this.value.trim().length > 0) {
                        hideValidation(fieldId);
                    }
                });
            }
        });
        
        // Form submission validation
        document.getElementById('checkout-form').addEventListener('submit', async function(e) {
            let isValid = true;
            
            // Validate all required fields
            Object.keys(formFields).forEach(fieldId => {
                const field = formFields[fieldId];
                if (field && field.required && field.value.trim() === '') {
                    showError(fieldId, 'This field is required');
                    isValid = false;
                }
            });
            
            // Special validation for PIN
            if (formFields.pin && formFields.pin.value.trim() !== '') {
                const pin = formFields.pin.value.trim();
                if (pin.length !== 6) {
                    showError('pin', 'PIN code must be 6 digits');
                    isValid = false;
                } else if (!validatePincode(pin)) {
                    showError('pin', 'Sorry, delivery is not available for this PIN code');
                    isValid = false;
                }
            }
            
            // Special validation for phone
            if (formFields.phone && formFields.phone.value.trim() !== '') {
                const phone = formFields.phone.value.trim();
                if (phone.length !== 10 || !/^\d+$/.test(phone)) {
                    showError('phone', 'Please enter a valid 10-digit phone number');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
        
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            console.log('Form validation passed. Payment method:', paymentMethod);
        
            if (paymentMethod === 'cod') {
                // For Cash on Delivery, let the form submit normally
                console.log('COD selected - allowing normal form submission');
                document.getElementById('checkout-form').setAttribute('action', '');
                return true;
            } else {
                // For Razorpay, prevent the default form submission
                e.preventDefault();
                console.log('Razorpay selected - handling payment');
                
                try {
                    // Show loading state
                    const btn = document.querySelector('.submit-btn');
                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                        btn.disabled = true;
                    }
                    
                    // Get form data
                    const formData = new FormData(this);
                    formData.append('payment_method', 'razorpay');
                    
                    // Initialize payment
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Network response was not ok');
                    }
                    
                    const data = await response.json();
                    console.log('Razorpay initialization:', data);
                    
                    if (data.status === 'error') {
                        throw new Error(data.message);
                    }

                    // Configure Razorpay
                    const options = {
                        key: data.key,
                        amount: data.amount,
                        currency: data.currency,
                        name: data.name,
                        description: data.description,
                        order_id: data.order_id,
                        prefill: data.prefill,
                        handler: async function(response) {
                            try {
                                console.log('Payment response:', response);
                                
                                const verifyData = new FormData();
                                verifyData.append('razorpay_payment_id', response.razorpay_payment_id);
                                verifyData.append('razorpay_order_id', data.order_id);
                                verifyData.append('razorpay_signature', response.razorpay_signature);

                                const verifyResponse = await fetch('verify_payment.php', {
                                    method: 'POST',
                                    body: verifyData
                                });

                                const result = await verifyResponse.json();
                                console.log('Verification result:', result);

                                if (result.status === 'success') {
                                    window.location.href = result.redirect;
                                } else {
                                    throw new Error(result.message || 'Payment verification failed');
                                }
                            } catch (error) {
                                console.error('Verification error:', error);
                                alert('Payment verification failed: ' + error.message);
                                resetSubmitButton();
                            }
                        },
                        modal: {
                            ondismiss: function() {
                                console.log('Payment cancelled');
                                resetSubmitButton();
                            }
                        }
                    };

                    // Initialize Razorpay
                    const rzp = new Razorpay(options);
                    rzp.open();
                    
                } catch (error) {
                    console.error('Payment error:', error);
                    alert('Error processing payment: ' + error.message);
                    resetSubmitButton();
                }
            }
        });
    });

    // Debug Information 
    if (document.querySelector('input[name="payment_method"]:checked')) {
        console.log('Initial payment method:', document.querySelector('input[name="payment_method"]:checked').value);
    }

    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('Payment method changed to:', this.value);
        });
    });
    </script>

    <!-- Hidden form for payment verification -->
    <form id="payment-form" action="order_success.php" method="POST" style="display: none;">
        <input type="hidden" id="razorpay_payment_id" name="razorpay_payment_id">
        <input type="hidden" id="razorpay_order_id" name="razorpay_order_id">
        <input type="hidden" id="razorpay_signature" name="razorpay_signature">
    </form>

    <!-- Debugging code - visible only in debug mode -->
    <?php if(isset($_GET['debug'])): ?>
    <div class="debug-section">
        <h3>Debug Information</h3>
        <pre>
Session: <?php print_r($_SESSION); ?>

POST: <?php print_r($_POST); ?>

Cart: <?php echo isset($_SESSION['cart']) ? print_r($_SESSION['cart'], true) : 'Empty'; ?>
        </pre>
    </div>
    <?php endif; ?>
</body>
</html>