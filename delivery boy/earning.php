<?php
session_start();
include "../databse/connect.php";

if(!isset($_SESSION['username'])){
    header("location: ../login/login.php");
}

// Get delivery boy ID from session
$delivery_boy_id = $_SESSION['userid'];

// Calculate total earnings (10% commission)
$total_earnings_query = "SELECT 
    COUNT(*) as total_deliveries,
    COALESCE(SUM(total_amount * 0.1), 0) as total_earnings,
    COALESCE(SUM(CASE 
        WHEN payment_status = 'paid' THEN total_amount * 0.1 
        ELSE 0 
    END), 0) as paid_earnings,
    COALESCE(SUM(CASE 
        WHEN payment_status = 'pending' THEN total_amount * 0.1 
        ELSE 0 
    END), 0) as pending_earnings
FROM tbl_orders 
WHERE delivery_boy_id = '$delivery_boy_id' 
AND order_status = 'delivered'";

$total_result = mysqli_query($conn, $total_earnings_query);
$earnings_data = mysqli_fetch_assoc($total_result);

// Get monthly earnings
$monthly_earnings_query = "SELECT 
    DATE_FORMAT(order_date, '%Y-%m') as month,
    COUNT(*) as deliveries,
    COALESCE(SUM(total_amount * 0.1), 0) as earnings
FROM tbl_orders 
WHERE delivery_boy_id = '$delivery_boy_id' 
AND order_status = 'delivered'
GROUP BY DATE_FORMAT(order_date, '%Y-%m')
ORDER BY month DESC
LIMIT 12";

$monthly_result = mysqli_query($conn, $monthly_earnings_query);

// Get daily earnings for current week
$daily_earnings_query = "SELECT 
    DATE(order_date) as date,
    COUNT(*) as deliveries,
    COALESCE(SUM(total_amount * 0.1), 0) as earnings
FROM tbl_orders 
WHERE delivery_boy_id = '$delivery_boy_id' 
AND order_status = 'delivered'
AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(order_date)
ORDER BY date DESC";

$daily_result = mysqli_query($conn, $daily_earnings_query);

// Get today's deliveries count
$today_query = "SELECT 
    COUNT(*) as total_deliveries,
    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_deliveries
FROM tbl_orders 
WHERE DATE(order_date) = CURDATE()";
$today_result = mysqli_query($conn, $today_query);
$today_stats = mysqli_fetch_assoc($today_result);

// Get weekly earnings (assuming there's a delivery charge column or using a percentage of total_amount)
$earnings_query = "SELECT COALESCE(SUM(total_amount * 0.1), 0) as weekly_earnings
FROM tbl_orders 
WHERE order_status = 'delivered'
AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$earnings_result = mysqli_query($conn, $earnings_query);
$earnings = mysqli_fetch_assoc($earnings_result);



// Get active orders
$active_orders_query = "SELECT COUNT(*) as active_count
FROM tbl_orders 
WHERE order_status IN ('processing', 'shipped')";
$active_result = mysqli_query($conn, $active_orders_query);
$active_orders = mysqli_fetch_assoc($active_result);

// Get current deliveries
$current_deliveries_query = "SELECT 
    o.order_id,
    o.delivery_address,
    o.order_status,
    o.total_amount,
    o.phone_number,
    o.order_date,
    o.payment_method,
    o.payment_status,
    u.username as customer_name,
    f.farm_name,
    f.location as farm_location
FROM tbl_orders o
JOIN tbl_signup u ON o.user_id = u.userid
JOIN tbl_order_items oi ON o.order_id = oi.order_id
JOIN tbl_products p ON oi.product_id = p.product_id
JOIN tbl_farms f ON p.farm_id = f.farm_id
WHERE o.order_status IN ('processing', 'shipped', 'delivered')
GROUP BY o.order_id
ORDER BY o.order_date DESC
LIMIT 5";
$current_deliveries_result = mysqli_query($conn, $current_deliveries_query);

// Fetch available orders (pending orders)
$orders_query = "SELECT 
    o.order_id,
    o.delivery_address,
    o.order_status,
    o.total_amount,
    o.phone_number,
    o.order_date,
    o.payment_method,
    o.payment_status,
    u.username as customer_name,
    GROUP_CONCAT(DISTINCT CONCAT(p.product_name, ' (', oi.quantity, ' ', p.unit, ')')) as order_items,
    GROUP_CONCAT(DISTINCT f.farm_name) as farms,
    GROUP_CONCAT(DISTINCT f.location) as farm_locations
FROM tbl_orders o
JOIN tbl_signup u ON o.user_id = u.userid
JOIN tbl_order_items oi ON o.order_id = oi.order_id
JOIN tbl_products p ON oi.product_id = p.product_id
JOIN tbl_farms f ON p.farm_id = f.farm_id
WHERE o.order_status = 'pending'
GROUP BY o.order_id
ORDER BY o.order_date DESC";

$orders_result = mysqli_query($conn, $orders_query);

// Add this query after your existing queries
$delivery_stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders
FROM tbl_orders 
WHERE delivery_boy_id = '$delivery_boy_id'";
$stats_result = mysqli_query($conn, $delivery_stats_query);
$delivery_stats = mysqli_fetch_assoc($stats_result);

// Get recent deliveries by this delivery boy
$my_deliveries_query = "SELECT 
    o.order_id,
    o.delivery_address,
    o.order_status,
    o.total_amount,
    o.order_date,
    o.payment_method,
    o.payment_status,
    u.username as customer_name,
    GROUP_CONCAT(DISTINCT p.product_name) as products
FROM tbl_orders o
JOIN tbl_signup u ON o.user_id = u.userid
JOIN tbl_order_items oi ON o.order_id = oi.order_id
JOIN tbl_products p ON oi.product_id = p.product_id
WHERE o.delivery_boy_id = '$delivery_boy_id'
GROUP BY o.order_id
ORDER BY o.order_date DESC";
$my_deliveries_result = mysqli_query($conn, $my_deliveries_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Orders - Delivery Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    display: flex;
    min-height: 100vh;
}

/* Header Styles */
.header {
    position: fixed;
    top: 0;
    right: 0;
    left: 250px;
    height: 60px;
    background: #ffffff;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 100;
    transition: left 0.3s;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo {
    width: 40px;
    height: 40px;
    background: #1a4d2e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}


.user-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

.logout-btn {
    padding: 8px 16px;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
}

.logout-btn:hover {
    background: #b91c1c;
}

/* Sidebar Styles */
.sidebar {
    width: 250px;
    background: #1a4d2e;
    color: white;
    padding: 80px 20px 20px;
    position: fixed;
    height: 100vh;
    left: 0;
    top: 0;
    transition: width 0.3s;
}



.sidebar .menu-btn {
    position: absolute;
    top: 20px;
    left: 20px;
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}

.sidebar-menu {
    list-style: none;
}

.sidebar-menu li {
    margin: 5px 0;
}

.sidebar-menu a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 12px;
    border-radius: 5px;
    transition: background 0.3s;
}

.sidebar-menu a:hover {
    background: #2d6a4f;
}

.sidebar-menu i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}


.active {
    background: #2d6a4f;
}

/* Main Content Styles */
.main-content {
    margin-left: 250px;
    flex: 1;
    padding-top: 60px;
    background: #f3f4f6;
    transition: margin-left 0.3s;
}



.content-area {
    padding: 20px;
    min-height: 100vh;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.stat-card .value {
    font-size: 1.8em;
    font-weight: bold;
    color: #1a4d2e;
}

.delivery-list {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.delivery-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.delivery-item:last-child {
    border-bottom: none;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: 500;
}

.status-pending {
    background-color: #fee2e2;
    color: #991b1b;
}

.status-processing {
    background-color: #fef3c7;
    color: #92400e;
}

.status-shipped {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-delivered {
    background-color: #dcfce7;
    color: #166534;
}

/* Footer Styles */
.footer {
    background: #1a4d2e;
    color: white;
    padding: 20px;
    text-align: center;
}

.footer-items {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 10px;
}

.footer-items a {
    color: white;
    text-decoration: none;
}
        /* Add these styles to your existing CSS */
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-processing {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-shipped {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-delivered {
            background-color: #dcfce7;
            color: #166534;
        }

        .delivery-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .delivery-info {
            flex: 1;
        }

        .delivery-info h3 {
            margin-bottom: 5px;
            color: #1a4d2e;
        }

        .delivery-meta {
            display: grid;
            gap: 8px;
            margin-top: 10px;
        }

        .delivery-meta p {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .delivery-meta i {
            width: 16px;
            color: #1a4d2e;
        }

        .delivery-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .pickup-btn {
            background-color: #1a4d2e;
            color: white;
        }

        .complete-btn {
            background-color: #15803d;
            color: white;
        }

        .order-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-size: 1.2em;
            font-weight: bold;
            color: #1a4d2e;
        }

        .order-date {
            color: #666;
            font-size: 0.9em;
        }

        .order-details {
            display: grid;
            gap: 10px;
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            align-items: start;
            gap: 10px;
        }

        .detail-row i {
            color: #1a4d2e;
            width: 20px;
            margin-top: 3px;
        }

        .detail-content {
            flex: 1;
        }

        .order-items {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .accept-btn, .reject-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }

        .accept-btn {
            background: #1a4d2e;
            color: white;
        }

        .reject-btn {
            background: #dc2626;
            color: white;
        }

        .accept-btn:hover, .reject-btn:hover {
            transform: translateY(-2px);
        }

        .payment-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .payment-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-paid {
            background: #dcfce7;
            color: #166534;
        }

        .payment-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .stats-section {
            margin-bottom: 30px;
        }

        .stats-section h2 {
            margin-bottom: 20px;
            color: #1a4d2e;
        }

        .delivery-table-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .delivery-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .delivery-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #1a4d2e;
            border-bottom: 2px solid #e5e7eb;
        }

        .delivery-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .delivery-table tr:last-child td {
            border-bottom: none;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 20px;
        }

        .my-deliveries-section {
            margin-top: 30px;
        }

        .my-deliveries-section h2 {
            margin-bottom: 20px;
            color: #1a4d2e;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }

        /* Add more status colors if needed */
        .status-processing {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-shipped {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-delivered {
            background-color: #dcfce7;
            color: #166534;
        }

        .earnings-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .earnings-section h2 {
            color: #1a4d2e;
            margin-bottom: 20px;
            font-size: 1.2em;
        }

        .table-container {
            overflow-x: auto;
        }

        .earnings-table {
            width: 100%;
            border-collapse: collapse;
        }

        .earnings-table th,
        .earnings-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .earnings-table th {
            background-color: #f8f9fa;
            color: #1a4d2e;
            font-weight: 600;
        }

        .earnings-table tr:last-child td {
            border-bottom: none;
        }

        .earnings-table tr:hover {
            background-color: #f8f9fa;
        }

        .stat-card {
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .value {
            color: #1a4d2e;
            font-size: 1.8em;
            font-weight: bold;
            margin: 10px 0;
        }

        small {
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        
        <ul class="sidebar-menu">
            <li><a href="delivery.php" ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="assign.php"><i class="fas fa-truck"></i><span>Assigned Deliveries</span></a></li>
            <li><a href="history.php"><i class="fas fa-history"></i><span>Delivery History</span></a></li>
            <li><a href="earning.php"class="active"><i class="fas fa-wallet"></i><span>Earnings</span></a></li>
            <!-- <li><a href="#"><i class="fas fa-user"></i><span>Profile</span></a></li> -->
        </ul>
    </nav>

    <header class="header">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-truck"></i>
            </div>
            <h2>Farmfolio</h2>
        </div>
        <div class="user-section">
             <span>Welcome, <?php echo ucfirst($_SESSION['username']);?></span>
            <button class="logout-btn" onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'"><i class="fas fa-sign-out-alt"  ></i> Logout</button>
        </div>
    </header>

    <main class="main-content">
        <div class="content-area">
            <h1 style="margin-bottom: 20px">My Earnings</h1>
            
            <!-- Earnings Overview -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Total Earnings</h3>
                    <div class="value">₹<?php echo number_format($earnings_data['total_earnings'], 2); ?></div>
                    <small><?php echo $earnings_data['total_deliveries']; ?> deliveries completed</small>
                </div>
              
                <!-- <div class="stat-card">
                    <h3>Pending Earnings</h3>
                    <div class="value">₹<?php echo number_format($earnings_data['pending_earnings'], 2); ?></div>
                    <small>Awaiting payments</small>
                </div> -->
            </div>

            <!-- Monthly Earnings -->
            <div class="earnings-section">
                <h2>Monthly Earnings</h2>
                <div class="table-container">
                    <table class="earnings-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Deliveries</th>
                                <th>Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($month = mysqli_fetch_assoc($monthly_result)): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                    <td><?php echo $month['deliveries']; ?></td>
                                    <td>₹<?php echo number_format($month['earnings'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daily Earnings -->
            <div class="earnings-section">
                <h2>Recent Daily Earnings</h2>
                <div class="table-container">
                    <table class="earnings-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Deliveries</th>
                                <th>Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($day = mysqli_fetch_assoc($daily_result)): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($day['date'])); ?></td>
                                    <td><?php echo $day['deliveries']; ?></td>
                                    <td>₹<?php echo number_format($day['earnings'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <footer class="footer">
        <p>© 2024 Farmfolio Delivery. All rights reserved.</p>
        <div class="footer-items">
            <a href="#">Terms of Service</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Contact Us</a>
            <a href="#">FAQ</a>
        </div>
    </footer>
    </main>

  

    <script>
    
    </script>
</body>
</html>
