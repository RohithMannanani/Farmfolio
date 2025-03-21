<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
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
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div>
                <?php }else{?>
                    <h1>Farm Dashboard</h1>
                <div class="user-section">
                    <span>Welcome,</span>
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div><?php }?>
            </div>
            
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Active Products</h3>
                    <div class="value"><?php echo $product_count ;?></div>
                   <?php echo $_SESSION['userid'];?>
                </div>
                <div class="stat-card">
                    <h3>Total Customers</h3>
                    <div class="value">0</div>
                    <small>+12% this month</small>
                </div>
                <div class="stat-card">
                    <h3>Average Rating</h3>
                    <div class="value">4.8</div>
                    <small>From 45 reviews</small>
                </div>
                <div class="stat-card">
                    <h3>Upcoming Events</h3>
                    <div class="value">0</div>
                    <small>Next event in 2 days</small>
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

        <!-- <footer class="footer">
            <p>© 2025 Farmfolio. All rights reserved.</p>
            <p style="margin-top: 5px; font-size: 0.9em;">Connecting Farms to Communities</p>
        </footer> -->
    </div>

    <script src="farm.js"></script>
   
</body>
</html>