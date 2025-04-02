<?php
session_start();
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

// Include database connection
include '../databse/connect.php';

// Add Razorpay configuration at the top with other includes
require_once('../razorpay-php/Razorpay.php');
use Razorpay\Api\Api;

$razorpay_key_id = 'rzp_test_46SrjQdO6MetdE'; // Replace with your actual Razorpay key ID
$razorpay_key_secret = '3fEscmmNDMEPbSvUX4rpYALy'; // Replace with your actual Razorpay key secret

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
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .summary-title {
            color: #1a4d2e;
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
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
        }

        .summary-item:hover {
            background: #f0f2f5;
        }

        .item-details h3 {
            color: #1a4d2e;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .item-quantity {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .item-price p {
            color: #1a4d2e;
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
            padding: 10px 15px;
            background: #1a4d2e;
            color: white;
            border-radius: 8px;
        }

        .total-amount h3 {
            font-size: 1.2rem;
        }

        .total-amount p {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .empty-cart {
            text-align: center;
            color: #6b7280;
            padding: 20px;
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
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .payment-info h3 {
            color: #1a4d2e;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: white;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            background: #f0f2f5;
        }

        .payment-option input[type="radio"] {
            accent-color: #1a4d2e;
        }

        .payment-option label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1a4d2e;
            font-weight: 500;
            cursor: pointer;
        }

        .payment-option i {
            font-size: 1.1rem;
            color: #1a4d2e;
        }

        @media (max-width: 768px) {
            .payment-info {
                padding: 15px;
            }

            .payment-option label {
                font-size: 0.9rem;
            }
        }

        /* Additional styles - will complement existing ones */
        
        /* Enhanced Form Fields */
        input[type="text"],
        input[type="tel"],
        textarea {
            transition: all 0.3s ease;
            border: 1px solid #ddd;
        }

        input[type="text"]:focus,
        input[type="tel"]:focus,
        textarea:focus {
            border-color: #1a4d2e;
            box-shadow: 0 0 0 2px rgba(26, 77, 46, 0.1);
            outline: none;
        }

        /* Error Message Styling */
        .error-message {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px 20px;
            margin: 10px 0;
            border-radius: 4px;
            animation: slideIn 0.3s ease;
        }

        /* Success Message Styling */
        .success-message {
            background-color: #dcfce7;
            border-left: 4px solid #22c55e;
            padding: 12px 20px;
            margin: 10px 0;
            border-radius: 4px;
            animation: slideIn 0.3s ease;
        }

        /* Enhanced Button Styles */
        .place_order {
            background: #1a4d2e;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .place_order:hover {
            background: #2d6a4f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 106, 79, 0.2);
        }

        /* Loading State */
        .place_order.loading {
            opacity: 0.8;
            cursor: wait;
        }

        /* Responsive Enhancements */
        @media (max-width: 768px) {
            input[type="text"],
            input[type="tel"],
            textarea {
                font-size: 16px; /* Prevents zoom on mobile */
            }

            .place_order {
                width: 100%; /* Full width on mobile */
                justify-content: center;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
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
        .form-group {
            animation: slideIn 0.3s ease;
            animation-fill-mode: both;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }

        /* Hover Effects */
        input[type="text"]:hover,
        input[type="tel"]:hover,
        textarea:hover {
            border-color: #2d6a4f;
        }

        /* Accessibility Improvements */
        .place_order:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 77, 46, 0.3);
        }

        /* Required Field Indicator */
        .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 4px;
        }

        /* Form Field Status */
        .field-success {
            border-color: #22c55e !important;
        }

        .field-error {
            border-color: #ef4444 !important;
        }

        /* Helper Text */
        .helper-text {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Total Amount Highlight */
        .total-amount {
            font-size: 1.25rem;
            color: #1a4d2e;
            font-weight: 600;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 8px;
            margin: 20px 0;
            text-align: right;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .order-summary {
                padding: 15px;
            }

            .summary-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .total-amount {
                flex-direction: column;
                gap: 5px;
                text-align: center;
            }
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
            
            <div class="form-group">
                <label for="house_name" class="required">House Name</label>
                <input type="text" id="house_name" name="house_name" required 
                    placeholder="Enter your house name"
                    autocomplete="address-line1">
            </div>

            <div class="form-group">
                <label for="post_office" class="required">Post Office</label>
                <input type="text" id="post_office" name="post_office" required 
                    placeholder="Enter your post office"
                    autocomplete="address-line2">
            </div>

            <div class="form-group">
                <label for="place" class="required">Place</label>
                <input type="text" id="place" name="place" required 
                    placeholder="Enter your place"
                    autocomplete="address-level2">
            </div>

            <div class="form-group">
                <label for="pin" class="required">PIN Code</label>
                <input type="text" id="pin" name="pin" required 
                    placeholder="Enter your PIN code"
                    pattern="[0-9]{6}" 
                    title="Please enter a valid 6-digit PIN code"
                    autocomplete="postal-code">
            </div>

            <div class="form-group">
                <label for="phone" class="required">Phone Number</label>
                <input type="tel" id="phone" name="phone" required 
                    placeholder="Enter your contact number"
                    pattern="[0-9]{10}" 
                    title="Please enter a valid 10-digit phone number"
                    autocomplete="tel">
            </div>

            <button type="submit" class="submit-btn" name="place_order">Place Order</button>
        </form>
    </div>

    <!-- Add Razorpay script -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        console.log('Form submitted with payment method:', paymentMethod);
        
        if (paymentMethod === 'cod') {
            // For Cash on Delivery, let the form submit normally
            console.log('COD selected - allowing normal form submission');
            document.getElementById('checkout-form').setAttribute('action', '');
            return true;
        } else {
            // For Razorpay, prevent the default form submission
            e.preventDefault();
            console.log('Razorpay selected - handling through AJAX');
            
            // Show loading state on button
            const btn = document.querySelector('.submit-btn');
            if (btn) {
                btn.textContent = 'Processing...';
                btn.disabled = true;
            }
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Razorpay data received:', data);
                
                const options = {
                    key: data.key,
                    amount: data.amount,
                    currency: data.currency,
                    name: data.name,
                    description: data.description,
                    order_id: data.order_id,
                    prefill: data.prefill,
                    handler: function(response) {
                        // Payment successful
                        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                        document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                        document.getElementById('razorpay_signature').value = response.razorpay_signature;
                        
                        // Submit the payment form
                        document.getElementById('payment-form').submit();
                    }
                };
                
                const rzp = new Razorpay(options);
                rzp.open();
                
                // Reset button state if user closes the Razorpay popup
                rzp.on('payment.failed', function(response){
                    alert('Payment failed: ' + response.error.description);
                    if (btn) {
                        btn.textContent = 'Place Order';
                        btn.disabled = false;
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing payment. Please try again.');
                if (btn) {
                    btn.textContent = 'Place Order';
                    btn.disabled = false;
                }
            });
        }
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
    <div style="background-color: #fef3c7; border-left: 4px solid #d97706; padding: 15px; margin: 20px auto; max-width: 800px; border-radius: 8px;">
        <h3 style="margin-top: 0; color: #92400e;">Debug Information</h3>
        <pre style="white-space: pre-wrap; overflow-x: auto;">
Session: <?php print_r($_SESSION); ?>

POST: <?php print_r($_POST); ?>

Cart: <?php echo isset($_SESSION['cart']) ? print_r($_SESSION['cart'], true) : 'Empty'; ?>
        </pre>
    </div>
    <?php endif; ?>
</body>
</html>