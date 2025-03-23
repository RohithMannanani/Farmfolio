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

.sidebar.shrink {
    width: 60px;
    padding: 80px 10px 20px;
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
    position: relative;
}

.sidebar-menu a:hover {
    background: #2d6a4f;
}

.sidebar-menu i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.sidebar.shrink .sidebar-menu span {
    display: none;
}

.sidebar.shrink .sidebar-menu i {
    margin-right: 0;
}

.active {
    background: #2d6a4f;
}

.badge {
    position: absolute;
    right: 1px;
    background: #dc2626;
    color: white;
    font-size: 0.8em;
    padding: 3px 6px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
    
}

.sidebar.shrink .badge {
    right: 5px;
    top: 5px;
    font-size: 0.7em;
    padding: 1px 4px;
}

/* Main Content Styles */
.main-content {
    margin-left: 250px;
    flex: 1;
    padding-top: 60px;
    background: #f3f4f6;
    transition: margin-left 0.3s;
}

.main-content.shrink {
    margin-left: 60px;
}

.content-area {
    padding: 20px;
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

        /* Add these to your existing CSS */
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 8px;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease-out;
            z-index: 1000;
        }

        .toast-notification.success {
            background-color: #166534;
        }

        .toast-notification.error {
            background-color: #dc2626;
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
            <h1 style="margin-bottom: 20px">Delivery Dashboard</h1>
            
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
                <h2 style="margin-bottom: 15px">Current Deliveries</h2>
                <?php 
                if(mysqli_num_rows($current_deliveries_result) > 0):
                    while($delivery = mysqli_fetch_assoc($current_deliveries_result)):
                        $status_class = 'status-' . $delivery['order_status'];
                ?>
                    <div class="delivery-item">
                        <div class="delivery-info">
                            <!-- <h3>Order #<?php echo $delivery['order_id']; ?></h3> -->
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
                                    Start Delivery
                                </button>
                            <?php elseif($delivery['order_status'] == 'shipped'): ?>
                                <button onclick="updateStatus(<?php echo $delivery['order_id']; ?>, 'delivered')" 
                                        class="action-btn complete-btn">
                                    Mark Delivered
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

        <!-- <footer class="footer">
            <p>© 2024 Farmfolio Delivery. All rights reserved.</p>
            <div class="footer-items">
                <a href="#">Terms of Service</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Contact Us</a>
                <a href="#">FAQ</a>
            </div>
        </footer> -->
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
