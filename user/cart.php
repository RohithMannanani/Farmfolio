<?php
session_start();
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

// Include database connection
include '../databse/connect.php';

$productDetails = [];
$totalCartPrice = 0;

if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    // Prepare a comma-separated list of product IDs
    $productIds = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    
    // Fetch product details from the database
    $query = "SELECT * FROM tbl_products WHERE product_id IN ($productIds)";
    $result = mysqli_query($conn, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $productDetails[] = $row;
    }
}

// Update quantity in session and database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'], $_POST['action'])) {
    $productId = intval($_POST['product_id']);
    $action = $_POST['action'];
    $userId = $_SESSION['user_id']; // Make sure user_id is in session

    // Initialize quantity if not set
    if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = 1; // Default quantity
    }

    // Update quantity based on action
    if ($action === 'increase') {
        $_SESSION['cart'][$productId]++;
        // Update database
        $query = "UPDATE tbl_cart SET quantity = quantity + 1 WHERE product_id = ? AND user_id = ?";
    } elseif ($action === 'decrease' && $_SESSION['cart'][$productId] > 1) {
        $_SESSION['cart'][$productId]--;
        // Update database
        $query = "UPDATE tbl_cart SET quantity = quantity - 1 WHERE product_id = ? AND user_id = ?";
    } elseif ($action === 'decrease' && $_SESSION['cart'][$productId] <= 1) {
        unset($_SESSION['cart'][$productId]);
        $query = "DELETE FROM tbl_cart WHERE product_id = ? AND user_id = ?";
    }

    // Execute the database update if quantity was changed
    if (isset($query)) {
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ii", $productId, $userId);
            if (!$stmt->execute()) {
                echo "Error executing statement: " . $stmt->error; // Log any execution errors
            }
        } else {
            echo "Error preparing statement: " . $conn->error; // Log any preparation errors
        }
    }
    
    // Redirect to avoid form resubmission
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard - Farmfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f0f2f5;
        }

        .sidebar {
            width: 250px;
            background: #1a4d2e;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: width 0.3s ease;
        }

        .sidebar.shrink {
            width: 80px;
        }

        .sidebar .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar .sidebar-header h2 {
            transition: opacity 0.3s ease, width 0.3s ease;
        }

        .sidebar.shrink .sidebar-header h2 {
            opacity: 0;
            visibility: hidden;
            width: 0;
        }

        .sidebar .menu img {
            width: 25px;
            height: 25px;
            cursor: pointer;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .sidebar-menu li {
            margin: 15px 0;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar-menu a:hover {
            background: #2d6a4f;
        }

        .sidebar-menu i {
            margin-right: 10px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .active {
            background: #2d6a4f;
            font-weight: 500;
        }
        .sidebar.shrink .sidebar-menu span {
            opacity: 0;
            visibility: hidden;
            width: 0;
            transition: opacity 0.3s ease, width 0.3s ease;
        }

        .sidebar.shrink .sidebar-menu i {
            margin-right: 0;
        }

        .pro {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-menu-container {
            position: relative;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            background-color: #1a4d2e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .profile-icon i {
            color: white;
            font-size: 1.2rem;
        }

        .profile-icon:hover {
            background-color: #2d6a4f;
        }

        .profile-popup {
            position: absolute;
            top: 120%;
            right: 0;
            width: 220px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .profile-popup.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-info {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .profile-name {
            color: #1f2937;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .profile-email {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .popup-logout-btn {
            width: 100%;
            padding: 12px 15px;
            text-align: left;
            background: none;
            border: none;
            color: #dc2626;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .popup-logout-btn:hover {
            background-color: #f3f4f6;
        }

        .popup-logout-btn i {
            font-size: 0.9rem;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            transition: margin-left 0.3s ease;
            padding: 20px;
            background-color: #f0f2f5;
        }

        .main-content.shrink {
            margin-left: 80px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                padding: 10px;
            }

            .main-content {
                margin-left: 60px;
            }

            .sidebar.shrink {
                width: 60px;
            }

            .main-content.shrink {
                margin-left: 60px;
            }
        }

        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: #1a4d2e;
            font-size: 1.8rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #4b5563;
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 600;
            color: #1a4d2e;
        }

        .chart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-card h2 {
            color: #1a4d2e;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .order-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .order-item:hover {
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .notification-item {
            padding: 15px;
            border-left: 4px solid #1a4d2e;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 0 8px 8px 0;
        }

        .notification-message {
            color: #1f2937;
            margin-bottom: 5px;
        }

        .notification-time {
            color: #6b7280;
        }

        .farm-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .farm-card:hover {
            transform: translateY(-5px);
        }

        .farm-card h3 {
            color: #1a4d2e;
            margin-bottom: 15px;
        }

        .view-farm {
            display: inline-block;
            padding: 8px 16px;
            background: #1a4d2e;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 15px;
            transition: background 0.3s ease;
        }

        .view-farm:hover {
            background: #2d6a4f;
        }

        .footer {
            background: white;
            color: #4b5563;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
            border-radius: 12px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .logout-btn {
            padding: 10px 20px;
            background: linear-gradient(to right, #dc2626, #ef4444);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 10px rgba(220,38,38,0.2);
        }

        @media (max-width: 1024px) {
            .chart-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: rgb(6, 133, 36);
            color: #f3f4f6;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
        }
        .quantity-controls button {
            margin: 0 5px;
            padding: 5px 10px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .checkout-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: rgb(6, 133, 36);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .checkout-btn:hover {
            background-color: rgb(5, 100, 27);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="userindex.php" ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse.php" ><i class="fas fa-store"></i><span>Browse Farms</span></a></li>
            <li><a href="cart.php" class="active" ><i class="fas fa-shopping-cart"></i><span>My Cart</span></a></li>
            <li><a href="orders.php" ><i class="fas fa-truck"></i><span>My Orders</span></a></li>
            <li><a href="favorite.php" ><i class="fas fa-heart"></i><span>Favorite Farms</span></a></li>
            <li><a href="events.php" ><i class="fas fa-calendar"></i><span>Farm Events</span></a></li>
            <li><a href="profile.php" ><i class="fas fa-user"></i><span>Profile</span></a></li>
            <!-- <li><a href="settings.php" ><i class="fas fa-cog"></i><span>Settings</span></a></li> -->
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="container">
            <div class="user-section">
                <div class="profile-menu-container">
                    <div class="pro">
                        <div class="head">
                            <h2>FarmFolio</h2>
                        </div>
                        <div class="profile-icon" id="profileIcon">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="profile-popup" id="profilePopup">
                        <div class="profile-info">
                        <p class="profile-name"><?php echo $_SESSION['username'];?></p>
                        <p class="profile-email"><?php echo $_SESSION['email'];?></p>
                        </div>
                        <button class="popup-logout-btn" onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            </div>

            <div class="container">
                <h1>My Cart</h1>
                <?php if (count($productDetails) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productDetails as $product): 
                                $quantity = isset($_SESSION['cart'][$product['product_id']]) ? $_SESSION['cart'][$product['product_id']] : 1;
                                $productTotal = $product['price'] * $quantity;
                                $totalCartPrice += $productTotal;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <form method="POST" class="quantity-controls">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                            <button type="submit" name="action" value="decrease">-</button>
                                            <span><?php echo $quantity; ?></span>
                                            <button type="submit" name="action" value="increase">+</button>
                                        </form>
                                    </td>
                                    <td>₹<?php echo number_format($productTotal, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Total Cart Value:</td>
                                <td>₹<?php echo number_format($totalCartPrice, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <form action="checkout.php" method="POST">
                        <input type="hidden" name="total_amount" value="<?php echo $totalCartPrice; ?>">
                        <button type="submit" class="checkout-btn">Proceed to Checkout</button>
                    </form>
                <?php else: ?>
                    <p>Your cart is empty.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2024 Farmfolio. All rights reserved.</p>
        </div>
    </div>

    <script src="profile.js"></script>
</body>
</html>