<?php
session_start();
include "../databse/connect.php";

if(!isset($_SESSION['username'])){
    header("location: ../login/login.php");
}

// Get delivery boy ID from session
$delivery_boy_id = $_SESSION['userid'];

// Get today's deliveries count
$today_query = "SELECT 
    COUNT(*) as total_deliveries,
    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_deliveries
FROM tbl_orders 
WHERE delivery_boy_id = '$delivery_boy_id'
AND DATE(order_date) = CURDATE()";
$today_result = mysqli_query($conn, $today_query);
$today_stats = mysqli_fetch_assoc($today_result);

// Get weekly earnings (assuming there's a delivery charge column or using a percentage of total_amount)
$earnings_query = "SELECT COALESCE(SUM(total_amount * 0.1), 0) as weekly_earnings
FROM tbl_orders 
WHERE delivery_boy_id = '$delivery_boy_id'
AND order_status = 'delivered'
AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$earnings_result = mysqli_query($conn, $earnings_query);
$earnings = mysqli_fetch_assoc($earnings_result);

// Get active orders
$active_orders_query = "SELECT COUNT(*) as active_count
FROM tbl_orders 
WHERE delivery_boy_id = '$delivery_boy_id'
AND order_status IN ('processing', 'shipped')";
$active_result = mysqli_query($conn, $active_orders_query);
$active_orders = mysqli_fetch_assoc($active_result);

// Add after the active_orders_query
$pending_orders_query = "SELECT COUNT(*) as pending_count
FROM tbl_orders 
WHERE  order_status = 'pending'";
$pending_result = mysqli_query($conn, $pending_orders_query);
$pending_orders = mysqli_fetch_assoc($pending_result);

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
    ROUND(o.total_amount * 0.10, 2) as delivery_earnings,
    u.username as customer_name,
    f.farm_name,
    f.location as farm_location
FROM tbl_orders o
JOIN tbl_signup u ON o.user_id = u.userid
JOIN tbl_order_items oi ON o.order_id = oi.order_id
JOIN tbl_products p ON oi.product_id = p.product_id
JOIN tbl_farms f ON p.farm_id = f.farm_id
WHERE o.delivery_boy_id = '$delivery_boy_id' 
AND o.order_status IN ('processing', 'shipped', 'delivered')
GROUP BY o.order_id
ORDER BY o.updated_at DESC
LIMIT 5";
$current_deliveries_result = mysqli_query($conn, $current_deliveries_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delivery Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f7fa;
        }

        /* Header Styles */
        .header {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 70px;
            background: #ffffff;
            padding: 0 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            z-index: 100;
            transition: left 0.3s;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #1a4d2e, #2d6a4f);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 3px 6px rgba(26, 77, 46, 0.2);
        }

        .logo-section h2 {
            font-weight: 600;
            color: #1a4d2e;
            letter-spacing: 0.5px;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .user-section span {
            color: #4b5563;
            font-weight: 500;
        }

        .logout-btn {
            padding: 9px 18px;
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 3px 6px rgba(220, 38, 38, 0.2);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(220, 38, 38, 0.25);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #1a4d2e, #2d6a4f);
            color: white;
            padding: 80px 20px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: width 0.3s;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar.shrink {
            width: 70px;
            padding: 80px 15px 20px;
        }

        .sidebar .menu-btn {
            position: absolute;
            top: 25px;
            left: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        .sidebar-menu {
            list-style: none;
            margin-top: 15px;
        }

        .sidebar-menu li {
            margin: 8px 0;
        }

        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s;
            position: relative;
            font-weight: 500;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(3px);
        }

        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar.shrink .sidebar-menu span {
            display: none;
        }

        .sidebar.shrink .sidebar-menu i {
            margin-right: 0;
        }

        .active {
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            font-weight: 600 !important;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .badge {
            position: absolute;
            right: 10px;
            background: #ef4444;
            color: white;
            font-size: 0.7em;
            padding: 3px 8px;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3);
            font-weight: 600;
        }

        .sidebar.shrink .badge {
            right: 0;
            top: 0;
            font-size: 0.6em;
            padding: 2px 5px;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding-top: 70px;
            background: #f5f7fa;
            transition: margin-left 0.3s;
        }

        .main-content.shrink {
            margin-left: 70px;
        }

        .content-area {
            padding: 25px;
        }

        .content-area h1 {
            margin-bottom: 25px;
            color: #1a4d2e;
            font-weight: 600;
            font-size: 1.8rem;
            position: relative;
            padding-bottom: 10px;
        }

        .content-area h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #1a4d2e, #2d6a4f);
            border-radius: 3px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .stat-card h3 {
            color: #6b7280;
            font-size: 0.9em;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a4d2e;
            margin-bottom: 5px;
        }

        .stat-card small {
            color: #6b7280;
            font-size: 0.85em;
        }

        .stat-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #1a4d2e, #2d6a4f);
            border-radius: 15px 0 0 15px;
        }

        .delivery-list {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .delivery-list h2 {
            margin-bottom: 20px;
            color: #1a4d2e;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        .delivery-list h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #1a4d2e, #2d6a4f);
            border-radius: 3px;
        }

        .delivery-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            margin-bottom: 15px;
            background: #f9fafb;
            border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            border-left: 4px solid #1a4d2e;
        }

        .delivery-item:hover {
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transform: translateY(-3px);
        }

        .delivery-item:last-child {
            margin-bottom: 0;
        }

        .delivery-info {
            flex: 1;
        }

        .delivery-meta {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }

        .delivery-meta p {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4b5563;
            font-size: 0.95rem;
        }

        .delivery-meta i {
            width: 16px;
            color: #1a4d2e;
        }

        .delivery-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 0.8em;
            font-weight: 600;
            letter-spacing: 0.5px;
            min-width: 100px;
            text-align: center;
            box-shadow: 0 3px 6px rgba(0,0,0,0.08);
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

        .action-btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            justify-content: center;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.15);
        }

        .pickup-btn {
            background: linear-gradient(135deg, #1a4d2e, #2d6a4f);
            color: white;
        }

        .complete-btn {
            background: linear-gradient(135deg, #15803d, #22c55e);
            color: white;
        }

        .no-deliveries {
            padding: 30px;
            text-align: center;
            color: #6b7280;
            font-style: italic;
        }

        /* Toast Notifications */
        .toast-notification {
            position: fixed;
            bottom: 25px;
            right: 25px;
            padding: 15px 25px;
            border-radius: 10px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            font-weight: 500;
        }

        .toast-notification.success {
            background: linear-gradient(135deg, #15803d, #22c55e);
        }

        .toast-notification.error {
            background: linear-gradient(135deg, #dc2626, #ef4444);
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

        .action-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Footer Styles */
        .footer {
            background: white;
            color: #6b7280;
            padding: 20px;
            text-align: center;
            border-radius: 15px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .footer-items {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-top: 10px;
        }

        .footer-items a {
            color: #1a4d2e;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-items a:hover {
            color: #2d6a4f;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 20px;
            }
            
            .delivery-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .delivery-actions {
                width: 100%;
                flex-direction: row;
                margin-top: 15px;
                justify-content: flex-end;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .sidebar-menu span {
                display: none;
            }
            
            .sidebar .sidebar-menu i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .header {
                left: 70px;
            }
            
            .content-area {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .user-section span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="delivery.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li>
                <a href="assign.php">
                    <i class="fas fa-truck"></i>
                    <span>Assigned Deliveries</span>
                    <?php if($pending_orders['pending_count'] > 0): ?>
                        <span class="badge"><?php echo $pending_orders['pending_count']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="history.php"><i class="fas fa-history"></i><span>Delivery History</span></a></li>
            <li><a href="earning.php"><i class="fas fa-wallet"></i><span>Earnings</span></a></li>
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
            <button class="logout-btn" onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </header>

    <main class="main-content">
        <div class="content-area">
            <h1>Delivery Dashboard</h1>
            
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Today's Deliveries</h3>
                    <div class="value"><?php echo $today_stats['total_deliveries']; ?></div>
                    <small><?php echo $today_stats['completed_deliveries']; ?> completed</small>
                </div>
                <div class="stat-card">
                    <h3>Weekly Earnings</h3>
                    <div class="value">₹<?php echo number_format($earnings['weekly_earnings'], 2); ?></div>
                    <small>Last 7 days</small>
                </div>

                <div class="stat-card">
                    <h3>Active Orders</h3>
                    <div class="value"><?php echo $active_orders['active_count']; ?></div>
                    <small>In progress</small>
                </div>
            </div>

            <div class="delivery-list">
                <h2>Current Deliveries</h2>
                <?php 
                if(mysqli_num_rows($current_deliveries_result) > 0):
                    while($delivery = mysqli_fetch_assoc($current_deliveries_result)):
                        $status_class = 'status-' . $delivery['order_status'];
                ?>
                    <div class="delivery-item">
                        <div class="delivery-info">
                            <div class="delivery-meta">
                                <p><i class="fas fa-user"></i> <?php echo $delivery['customer_name']; ?></p>
                                <p><i class="fas fa-phone"></i> <?php echo $delivery['phone_number']; ?></p>
                                <?php if(isset($delivery['farm_name'])): ?>
                                    <p><i class="fas fa-store"></i> <?php echo $delivery['farm_name']; ?> 
                                       (<?php echo $delivery['farm_location']; ?>)</p>
                                <?php endif; ?>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo $delivery['delivery_address']; ?></p>
                                <p><i class="fas fa-money-bill"></i> Order Amount: ₹<?php echo number_format($delivery['total_amount'], 2); ?></p>
                                <p><i class="fas fa-hand-holding-usd"></i> Delivery Earnings: ₹<?php echo number_format($delivery['delivery_earnings'], 2); ?></p>
                                <p><i class="fas fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($delivery['order_date'])); ?></p>
                            </div>
                        </div>
                        <div class="delivery-actions">
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($delivery['order_status']); ?>
                            </span>
                            <?php if($delivery['order_status'] == 'processing'): ?>
                                <button onclick="updateStatus(<?php echo $delivery['order_id']; ?>, 'shipped')" 
                                        class="action-btn pickup-btn">
                                    <i class="fas fa-truck"></i> Start Delivery
                                </button>
                            <?php elseif($delivery['order_status'] == 'shipped'): ?>
                                <button onclick="updateStatus(<?php echo $delivery['order_id']; ?>, 'delivered')" 
                                        class="action-btn complete-btn">
                                    <i class="fas fa-check-circle"></i> Mark Delivered
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="no-deliveries">
                        <p>No current deliveries</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
    function updateStatus(orderId, newStatus) {
        if(!confirm('Are you sure you want to update this delivery status?')) {
            return;
        }

        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        button.disabled = true;

        let action;
        switch(newStatus) {
            case 'shipped':
                action = 'ship';
                break;
            case 'delivered':
                action = 'deliver';
                break;
            default:
                action = newStatus;
        }

        fetch('handle_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'toast-notification success';
                toast.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
                document.body.appendChild(toast);
                
                // Reload the page after 1 second
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                // Show error message
                const toast = document.createElement('div');
                toast.className = 'toast-notification error';
                toast.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message}`;
                document.body.appendChild(toast);
                
                // Reset button
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Show error message
            const toast = document.createElement('div');
            toast.className = 'toast-notification error';
            toast.innerHTML = `<i class="fas fa-exclamation-circle"></i> An error occurred`;
            document.body.appendChild(toast);
            
            // Reset button
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

    // Toggle sidebar
    document.querySelector('.menu-btn').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('shrink');
        document.querySelector('.main-content').classList.toggle('shrink');
        document.querySelector('.header').classList.toggle('shrink');
    });

    // Add click handlers for menu items
    document.querySelectorAll('.sidebar-menu a').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelector('.active').classList.remove('active');
        this.classList.add('active');
    });
});


    // Logout button handler
    document.querySelector('.logout-btn').addEventListener('click', function() {
        // Add logout logic here
        alert('Logging out...');
    });
    </script>
</body>
</html>