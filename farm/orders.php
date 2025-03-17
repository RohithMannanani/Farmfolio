<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

// Check farm status
$is_farm_active = false;
if(isset($_SESSION['userid'])){
    $userid = $_SESSION['userid'];
    $farm = "SELECT * FROM tbl_farms WHERE user_id=$userid";
    $result = mysqli_query($conn, $farm);
    $row = $result->fetch_assoc();
    
    if($row && $row['status'] == 'active') {
        $is_farm_active = true;
        $farm_id = $row['farm_id'];
    }
}

// Only proceed with other queries if farm is active
if($is_farm_active) {
    // Fetch categories from database
    $category_query = "SELECT * FROM tbl_category";
    $category_result = mysqli_query($conn, $category_query);

    // Fetch products for the current farm
    $products_query = "SELECT p.*, c.category 
                      FROM tbl_products p 
                      JOIN tbl_category c ON p.category_id = c.category_id 
                      WHERE p.farm_id = $farm_id 
                      ORDER BY p.created_at DESC";
    $products_result = mysqli_query($conn, $products_query);

    // Fetch orders for the current farm
    $orders_query = "SELECT o.*, u.username 
                    FROM tbl_orders o
                    JOIN tbl_login u ON o.user_id = u.userid
                    JOIN tbl_order_items oi ON o.order_id = oi.order_id
                    JOIN tbl_products p ON oi.product_id = p.product_id
                    WHERE p.farm_id = $farm_id
                    GROUP BY o.order_id
                    ORDER BY o.order_date DESC";
    $orders_result = mysqli_query($conn, $orders_query);
}

// Add this PHP code at the top of the file after database connection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    // Update order status
    $updateQuery = "UPDATE tbl_orders SET order_status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $status, $orderId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Farm Products</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="farm.css">
    <style>
        .product-container {
            padding: 20px;
            max-width: 1200px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .add-product-btn {
            padding: 10px 20px;
            background: #1a4d2e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-product-btn:hover {
            background: #2d6a4f;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-details {
            padding: 15px;
        }

        .product-title {
            font-size: 1.1em;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-price {
            color: #2563eb;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-stock {
            color: #666;
            font-size: 0.9em;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-btn {
            background: #1a4d2e;
            color: white;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn {
            background: #1a4d2e;
            color: white;
        }

        .cancel-btn {
            background: #666;
            color: white;
        }

        .inactive-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 70vh;
            text-align: center;
            color: #666;
        }

        .inactive-message h2 {
            font-size: 24px;
            margin-bottom: 16px;
            color: #1a4d2e;
        }

        .inactive-message p {
            font-size: 16px;
            max-width: 600px;
            line-height: 1.6;
        }

        .inactive-icon {
            font-size: 48px;
            color: #1a4d2e;
            margin-bottom: 20px;
        }

        .orders-container {
            padding: 20px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .orders-table th, .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .orders-table th {
            background: #1a4d2e;
            color: white;
        }

        .status-update {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.success {
            background-color: #22c55e;
        }

        .notification.error {
            background-color: #ef4444;
        }

        /* Status styles */
        .status-pending {
            color: #856404;
            background-color: #fff3cd;
        }

        .status-processing {
            color: #004085;
            background-color: #cce5ff;
        }

        .status-shipped {
            color: #155724;
            background-color: #d4edda;
        }

        .status-delivered {
            color: #155724;
            background-color: #c3e6cb;
        }

        /* Style for the status select dropdown */
        .status-update {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-update:hover {
            border-color: #1a4d2e;
        }

        .status-update:focus {
            outline: none;
            border-color: #1a4d2e;
            box-shadow: 0 0 0 2px rgba(26, 77, 46, 0.2);
        }

        /* Add hover effect to table rows */
        .orders-table tbody tr {
            transition: background-color 0.3s ease;
        }

        .orders-table tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2>Farmfolio</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="farm.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="product.php" ><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="image.php"><i class="fas fa-image"></i><span>Farm Images</span></a></li>
            <li><a href="event.php"><i class="fas fa-calendar"></i><span>Events</span></a></li>
            <li><a href="review.php"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="orders.php" class="active"><i class="fas fa-truck"></i><span>Orders</span></a></li>
        </ul>
    </nav>

    <div class="main-content">
    <div class="dashboard-header">
                <?php if(isset($row['farm_name'])&&isset($_SESSION['username'])){?>
                <h1><?php echo $row['farm_name'];?>Farm</h1>
                <div class="user-section">
                    <span>Welcome, <?php echo $_SESSION['username'];?></span>
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div>
                <?php }else{?>
                    <h1>Farm Dashboard</h1>
                <div class="user-section">
                    <span>Welcome,</span>
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div><?php }?>
            </div>
        <?php if($is_farm_active): ?>
            
            <div class="orders-container">
                <h2>Orders</h2>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Payment Method</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo $order['username']; ?></td>
                            <td>₹<?php echo $order['total_amount']; ?></td>
                            <td><?php echo ucfirst($order['order_status']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $order['delivery_address']; ?></td>
                            <td><?php echo $order['phone_number']; ?></td>
                            <td><?php echo strtoupper($order['payment_method']); ?></td>
                            <td><?php echo ucfirst($order['payment_status']); ?></td>
                            <td>
                                <select class="status-update" data-order-id="<?php echo $order['order_id']; ?>">
                                    <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                </select>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="inactive-message">
                <i class="fas fa-store-slash inactive-icon"></i>
                <h2>Farm Not Active</h2>
                <p>Your farm is currently inactive. Please contact the administrator to activate your farm account before managing products.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add this before closing body tag -->
    <script>
    document.querySelectorAll('.status-update').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const status = this.value;
            const row = this.closest('tr');

            // Send AJAX request
            fetch('orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showNotification('Order status updated successfully', 'success');
                    
                    // Update status cell color
                    const statusCell = row.querySelector('td:nth-child(4)');
                    statusCell.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    updateStatusStyle(statusCell, status);
                } else {
                    showNotification('Failed to update order status', 'error');
                }
            })
            .catch(error => {
                showNotification('An error occurred', 'error');
                console.error('Error:', error);
            });
        });
    });

    function updateStatusStyle(element, status) {
        // Remove existing status classes
        element.className = '';
        
        // Add new status class
        element.classList.add('status-' + status);
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    </script>
</body>
</html>