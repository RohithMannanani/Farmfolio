<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['username'])){
    header('location: ../login/login.php');
}

if(isset($_SESSION['userid'])){
    $userid = $_SESSION['userid'];
   
    $farm = "SELECT * FROM tbl_farms WHERE user_id=?";
    $stmt = $conn->prepare($farm);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result && $row = $result->fetch_assoc()) {
        $farm_id = $row['farm_id'];
        $_SESSION['farm_id'] = $farm_id; // Store farm_id in session
        
        // Count products only if farm_id exists
        $pcount = "SELECT COUNT(product_id) AS product_count FROM tbl_products WHERE farm_id=?";
        $stmt_count = $conn->prepare($pcount);
        $stmt_count->bind_param("i", $farm_id);
        $stmt_count->execute();
        $count_result = $stmt_count->get_result();
        
        if($count_result) {
            $count_data = $count_result->fetch_assoc();
            $product_count = $count_data['product_count'];
        } else {
            $product_count = 0;
        }

        // Calculate average rating
        $rating_query = "SELECT 
            COUNT(*) as review_count,
            ROUND(AVG(rating), 1) as avg_rating 
        FROM tbl_reviews 
        WHERE farm_id = ?";
        
        $stmt_rating = $conn->prepare($rating_query);
        $stmt_rating->bind_param("i", $farm_id);
        $stmt_rating->execute();
        $rating_result = $stmt_rating->get_result();
        
        if($rating_result) {
            $rating_data = $rating_result->fetch_assoc();
            $avg_rating = $rating_data['avg_rating'] ?: 0;
            $review_count = $rating_data['review_count'];
        } else {
            $avg_rating = 0;
            $review_count = 0;
        }

        // Add after the rating query
        if($farm_id) {
            // Get distinct customer count
            $customer_query = "SELECT COUNT(DISTINCT o.user_id) as customer_count,
                              COUNT(DISTINCT CASE 
                                  WHEN o.order_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH) 
                                  THEN o.user_id 
                                  END) as new_customers
                              FROM tbl_orders o
                              JOIN tbl_order_items oi ON o.order_id = oi.order_id
                              JOIN tbl_products p ON oi.product_id = p.product_id
                              WHERE p.farm_id = ?";
            
            $stmt_customers = $conn->prepare($customer_query);
            $stmt_customers->bind_param("i", $farm_id);
            $stmt_customers->execute();
            $customer_result = $stmt_customers->get_result();
            
            if($customer_result) {
                $customer_data = $customer_result->fetch_assoc();
                $total_customers = $customer_data['customer_count'];
                $new_customers = $customer_data['new_customers'];
                $customer_growth = $total_customers > 0 ? 
                    round(($new_customers / $total_customers) * 100) : 0;
            } else {
                $total_customers = 0;
                $customer_growth = 0;
            }

            // Add after the customer query in the if($farm_id) block
            // Get upcoming events count and next event
            $events_query = "SELECT 
                COUNT(*) as event_count,
                MIN(DATEDIFF(event_date, CURRENT_DATE)) as days_until_next,
                MIN(event_date) as next_event_date,
                MIN(event_name) as next_event_name
            FROM tbl_events 
            WHERE farm_id = ? 
                AND event_date >= CURRENT_DATE 
                AND status = '1'";

            $stmt_events = $conn->prepare($events_query);
            $stmt_events->bind_param("i", $farm_id);
            $stmt_events->execute();
            $events_result = $stmt_events->get_result();

            if($events_result) {
                $events_data = $events_result->fetch_assoc();
                $event_count = $events_data['event_count'];
                $days_until_next = $events_data['days_until_next'];
                $next_event_date = $events_data['next_event_date'];
                $next_event_name = $events_data['next_event_name'];
            } else {
                $event_count = 0;
                $days_until_next = null;
                $next_event_name = null;
            }
        }
    } else {
        // No farm found for this user
        $farm_id = 0;
        $product_count = 0;
        $row = null;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Farm Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="farm.css">
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2>Farmfolio</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="farm.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="product.php" ><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="image.php"><i class="fas fa-image"></i><span>Farm Images</span></a></li>
            <li><a href="event.php"><i class="fas fa-calendar"></i><span>Events</span></a></li>
            <li><a href="review.php"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="orders.php"><i class="fas fa-truck"></i><span>Orders</span></a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i><span>Farm Details </span></a></li>
            <!-- <li><a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a></li> -->
        </ul>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="dashboard-header">
                <?php if(isset($row['farm_name'])&&isset($_SESSION['username'])){?>
                <h1><?php echo $row['farm_name'];?></h1>
                <div class="user-section">
                    <span>Welcome, <?php echo $_SESSION['username'];?></span>
                    <a href="../logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div>
                <?php }else{?>
                    <h1>Farm Dashboard</h1>
                <div class="user-section">
                    <span>Welcome,</span>
                    <a href="../logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div><?php }?>
            </div>
            
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Active Products</h3>
                    <div class="value"><?php echo $product_count ;?></div>
                 
                </div>
                <div class="stat-card">
                    <h3>Total Customers</h3>
                    <div class="value"><?php echo $total_customers; ?></div>
                    <small><?php echo $customer_growth; ?>% this month</small>
                </div>
                <div class="stat-card">
                    <h3>Average Rating</h3>
                    <div class="value"><?php echo number_format($avg_rating, 1); ?></div>
                    <small>From <?php echo $review_count; ?> reviews</small>
                </div>
                <div class="stat-card">
                    <h3>Upcoming Events</h3>
                    <div class="value"><?php echo $event_count; ?></div>
                    <?php if($days_until_next !== null && $next_event_name !== null): ?>
                        <small>
                            <i class="fas fa-calendar-alt"></i>
                            Next: <?php echo htmlspecialchars($next_event_name); ?> 
                            (in <?php echo $days_until_next; ?> days)
                        </small>
                    <?php else: ?>
                        <small><i class="fas fa-calendar-alt"></i> No upcoming events</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- <div class="chart-container">
                <div class="chart-card">
                    <h2>Sales Overview</h2>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-card">
                    <h2>Recent Notifications</h2>
                    <ul class="notifications" id="notificationsList"></ul>
                </div>
            </div> -->
        </div>

      
    </div>

    <script src="farm.js"></script>
   
</body>
</html>