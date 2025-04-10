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
        :root {
            --primary: #1a4d2e;
            --primary-light: #2d6a4f;
            --primary-dark: #0c3820;
            --secondary: #f59e0b;
            --accent: #0ea5e9;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --light: #f3f4f6;
            --dark: #1f2937;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --font-sans: 'Segoe UI', Arial, sans-serif;
            --transition: all 0.3s ease;
            --radius-sm: 4px;
            --radius: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-sans);
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--light);
            color: var(--dark);
        }

        /* Header Styles */
        .header {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 70px;
            background: var(--white);
            padding: 0 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
            z-index: 100;
            transition: var(--transition);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .logo-section h2 {
            font-weight: 600;
            color: var(--primary);
            letter-spacing: 0.5px;
            font-size: 1.3rem;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-section span {
            font-weight: 500;
            color: var(--dark);
        }

        .logout-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--danger), #b91c1c);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary-dark), var(--primary));
            color: var(--white);
            padding: 80px 20px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: var(--transition);
            box-shadow: var(--shadow-lg);
            z-index: 99;
        }

        .sidebar-menu {
            list-style: none;
            margin-top: 20px;
        }

        .sidebar-menu li {
            margin: 8px 0;
        }

        .sidebar-menu a {
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: var(--radius);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-menu a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--secondary);
            border-radius: 0 2px 2px 0;
        }

        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding-top: 90px;
            background: var(--light);
            transition: var(--transition);
        }

        .content-area {
            padding: 0 25px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .content-area h1 {
            color: var(--primary-dark);
            margin-bottom: 25px;
            font-size: 1.8rem;
            position: relative;
            display: inline-block;
        }

        .content-area h1::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -5px;
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 2px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--primary);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            right: -15px;
            bottom: -15px;
            width: 80px;
            height: 80px;
            background: var(--primary);
            opacity: 0.1;
            border-radius: 50%;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card h3 {
            color: var(--gray);
            font-size: 0.95rem;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-card .value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
            line-height: 1.1;
        }

        .stat-card small {
            color: var(--gray);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-card small i {
            color: var(--success);
        }

        .earnings-section {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 25px;
            margin-top: 35px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .earnings-section:hover {
            box-shadow: var(--shadow-md);
        }

        .earnings-section h2 {
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 1.3rem;
            position: relative;
            display: inline-block;
        }

        .earnings-section h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 40px;
            height: 3px;
            background: var(--primary);
            border-radius: 2px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }

        .earnings-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        .earnings-table th,
        .earnings-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        .earnings-table th {
            background-color: #f8f9fa;
            color: var(--primary);
            font-weight: 600;
            position: sticky;
            top: 0;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .earnings-table tr:nth-child(even) {
            background-color: rgba(243, 244, 246, 0.4);
        }

        .earnings-table tr:last-child td {
            border-bottom: none;
        }

        .earnings-table tr:hover {
            background-color: rgba(26, 77, 46, 0.05);
        }

        .earnings-table td:nth-child(3) {
            font-weight: 600;
            color: var(--primary);
        }

        /* Add these for mobile responsiveness */
        @media (max-width: 992px) {
            .header {
                left: 70px;
            }
            
            .sidebar {
                width: 70px;
                padding: 80px 10px 20px;
            }
            
            .sidebar-menu span {
                display: none;
            }
            
            .sidebar-menu i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 0 15px;
            }
            
            .content-area {
                padding: 0 15px 20px;
            }
            
            .user-section span {
                display: none;
            }
            
            .dashboard-grid {
                gap: 15px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .earnings-section {
                padding: 20px;
                margin-top: 25px;
            }
            
            .table-container {
                margin: 0 -15px;
                width: calc(100% + 30px);
                border-radius: 0;
            }
            
            .earnings-table th,
            .earnings-table td {
                padding: 12px 15px;
            }
        }

        /* Add some animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card {
            animation: fadeIn 0.5s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        .earnings-section {
            animation: fadeIn 0.5s ease forwards;
            animation-delay: 0.5s;
        }

        .earnings-section:nth-of-type(2) {
            animation-delay: 0.6s;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="delivery.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="assign.php"><i class="fas fa-truck"></i><span>Assigned Deliveries</span></a></li>
            <li><a href="history.php"><i class="fas fa-history"></i><span>Delivery History</span></a></li>
            <li><a href="earning.php" class="active"><i class="fas fa-wallet"></i><span>Earnings</span></a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li> 
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
            <button class="logout-btn" onclick="window.location.href='../logout/logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </header>

    <main class="main-content">
        <div class="content-area">
            <h1>My Earnings</h1>
            
            <!-- Earnings Overview -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Total Earnings</h3>
                    <div class="value">₹<?php echo number_format($earnings_data['total_earnings'], 2); ?></div>
                    <small><i class="fas fa-check-circle"></i> <?php echo $earnings_data['total_deliveries']; ?> deliveries completed</small>
                </div>
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
                            <?php if(mysqli_num_rows($monthly_result) > 0): ?>
                                <?php while($month = mysqli_fetch_assoc($monthly_result)): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                        <td><?php echo $month['deliveries']; ?></td>
                                        <td>₹<?php echo number_format($month['earnings'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 30px;">No monthly earnings data available yet</td>
                                </tr>
                            <?php endif; ?>
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
                            <?php if(mysqli_num_rows($daily_result) > 0): ?>
                                <?php while($day = mysqli_fetch_assoc($daily_result)): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($day['date'])); ?></td>
                                        <td><?php echo $day['deliveries']; ?></td>
                                        <td>₹<?php echo number_format($day['earnings'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 30px;">No daily earnings data available yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
