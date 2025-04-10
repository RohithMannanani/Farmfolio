<?php
session_start();
if(!isset($_SESSION['username'])){
    header('location: ../login/login.php');
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

// Replace the existing order processing code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $userId = $_SESSION['user_id'];
    $house_name = mysqli_real_escape_string($conn, trim($_POST['house_name']));
    $post_office = mysqli_real_escape_string($conn, trim($_POST['post_office']));
    $place = mysqli_real_escape_string($conn, trim($_POST['place']));
    $pin = mysqli_real_escape_string($conn, trim($_POST['pin']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    
    // Add server-side PIN validation
    $pincode_json = file_get_contents('../signup/pincode.json');
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

    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create order in orders table with delivery details
        $orderQuery = "INSERT INTO tbl_orders (user_id, total_amount, order_status, order_date, delivery_address, phone_number, payment_method) 
                      VALUES (?, ?, 'pending', NOW(), ?, ?, 'cod')";
        $stmt = $conn->prepare($orderQuery);
        $stmt->bind_param("idss", $userId, $totalAmount, $full_address, $phone);
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
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .payment-info p {
            color: #1a4d2e;
            margin: 0;
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
            <p><i class="fas fa-info-circle"></i> Payment Method: Cash on Delivery</p>
        </div>

        <form method="POST" action="checkout.php">
            <input type="hidden" name="total_amount" value="<?php echo $totalCartPrice; ?>">
            
            <div class="form-group">
                <label for="house_name">House Name *</label>
                <input type="text" id="house_name" name="house_name" required 
                    placeholder="Enter your house name">
            </div>

            <div class="form-group">
                <label for="post_office">Post Office *</label>
                <input type="text" id="post_office" name="post_office" required 
                    placeholder="Enter your post office">
            </div>

            <div class="form-group">
                <label for="place">Place *</label>
                <input type="text" id="place" name="place" required 
                    placeholder="Enter your place">
            </div>

            <div class="form-group">
                <label for="pin">PIN Code *</label>
                <input type="text" id="pin" name="pin" required 
                    placeholder="Enter your PIN code"
                    pattern="[0-9]{6}" 
                    title="Please enter a valid 6-digit PIN code">
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load PIN codes from JSON file
        let validPincodes = [];
        fetch('../sign up/pincode.json')
            .then(response => response.json())
            .then(data => {
                validPincodes = data.pincodes.map(String); // Convert numbers to strings for comparison
                console.log('Loaded pincodes:', validPincodes); // Debug log
            })
            .catch(error => console.error('Error loading PIN codes:', error));

        // Form elements
        const form = document.querySelector('form');
        const inputs = {
            house_name: document.querySelector('input[name="house_name"]'),
            post_office: document.querySelector('input[name="post_office"]'),
            place: document.querySelector('input[name="place"]'),
            pin: document.querySelector('input[name="pin"]'),
            phone: document.querySelector('input[name="phone"]')
        };

        // Validation rules
        const validations = {
            house_name: {
                pattern: /^[a-zA-Z0-9\s,.-]{3,50}$/,
                message: 'House name should be 3-50 characters long'
            },
            post_office: {
                pattern: /^[a-zA-Z\s]{3,30}$/,
                message: 'Enter a valid post office name'
            },
            place: {
                pattern: /^[a-zA-Z\s]{3,30}$/,
                message: 'Enter a valid place name'
            },
            pin: {
                pattern: /^\d{6}$/,
                message: 'Enter a valid 6-digit PIN code'
            },
            phone: {
                pattern: /^[6-9]\d{9}$/,
                message: 'Enter a valid 10-digit mobile number'
            }
        };

        // Create error message element
        function createErrorElement(message) {
            const error = document.createElement('div');
            error.className = 'validation-error';
            error.textContent = message;
            error.style.color = '#dc2626';
            error.style.fontSize = '0.875rem';
            error.style.marginTop = '4px';
            return error;
        }

        // Validate single field
        function validateField(input, validation) {
            const field = input.name;
            const value = input.value.trim();
            const errorElement = input.parentElement.querySelector('.validation-error');
            
            // Remove existing error message
            if (errorElement) {
                errorElement.remove();
            }

            // Remove existing status classes
            input.classList.remove('field-success', 'field-error');

            // Check if empty
            if (!value) {
                input.classList.add('field-error');
                input.parentElement.appendChild(
                    createErrorElement(`${field.replace('_', ' ')} is required`)
                );
                return false;
            }

            // Check pattern
            if (!validation.pattern.test(value)) {
                input.classList.add('field-error');
                input.parentElement.appendChild(
                    createErrorElement(validation.message)
                );
                return false;
            }

            // Update the PIN validation part inside validateField function
            if (field === 'pin') {
                console.log('Checking PIN:', value); // Debug log
                console.log('Valid PINs:', validPincodes); // Debug log
                const pinExists = validPincodes.includes(value);
                if (!pinExists) {
                    input.classList.add('field-error');
                    input.parentElement.appendChild(
                        createErrorElement('This PIN code is not serviceable')
                    );
                    return false;
                }
            }

            // If all validations pass
            input.classList.add('field-success');
            return true;
        }

        // Live validation on input
        Object.keys(inputs).forEach(field => {
            const input = inputs[field];
            input.addEventListener('input', () => {
                validateField(input, validations[field]);
            });

            // Also validate on blur
            input.addEventListener('blur', () => {
                validateField(input, validations[field]);
            });
        });

        // Form submission
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Validate all fields
            Object.keys(inputs).forEach(field => {
                if (!validateField(inputs[field], validations[field])) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Show error message at the top of the form
                const formError = document.createElement('div');
                formError.className = 'error-message';
                formError.textContent = 'Please correct the errors in the form';
                form.insertBefore(formError, form.firstChild);

                // Scroll to first error
                const firstError = document.querySelector('.field-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
    </script>
</body>
</html>