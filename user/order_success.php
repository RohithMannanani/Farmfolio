<?php
session_start();
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
echo $orderId;

// Include database connection
include '../databse/connect.php';

// Fetch order details
$orderQuery = "SELECT * FROM tbl_orders WHERE order_id = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderResult = $stmt->get_result();
$order = $orderResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Farmfolio</title>
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
            align-items: center;
            justify-content: center;
        }

        .success-container {
            max-width: 600px;
            margin: 20px;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            color: #22c55e;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-title {
            color: #1a4d2e;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .order-number {
            color: #1a4d2e;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .order-info {
            color: #666;
            margin-bottom: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #1a4d2e;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2d6a4f;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #1a4d2e;
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1 class="success-title">Order Placed Successfully!</h1>
        
        <div class="order-details">
            <p class="order-number">Order #<?php echo $orderId; ?></p>
            <p class="order-info">Total Amount: ₹<?php echo number_format($order['total_amount'], 2); ?></p>
            <p class="order-info">Payment Method: Cash on Delivery</p>
            <p class="order-info">Delivery Address: <?php echo htmlspecialchars($order['delivery_address']); ?></p>
        </div>

        <p>Thank you for your purchase! We'll send you an email with your order details.</p>
        
        <div class="action-buttons">
            <a href="orders.php" class="btn btn-primary">View Orders</a>
            <a href="browse.php" class="btn btn-secondary">Continue Shopping</a>
        </div>
    </div>
</body>
</html> 