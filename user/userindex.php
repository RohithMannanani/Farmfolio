<?php
//0 active product 1-inactive product
include '../databse/connect.php';
session_start();
if(!isset($_SESSION['username'])){
    header('location: ../login/login.php');
}
$user_id=$_SESSION['userid'];
// Modified query to show all active farms, even without images
$farms_query = "SELECT 
    f.farm_id,
    f.farm_name,
    f.location,
    f.description,
    f.created_at,
    f.status,
    COUNT(DISTINCT CASE WHEN p.status ='0' THEN p.product_id END) AS product_count,
    MIN(fi.path) as farm_image  -- Get first image if exists
FROM tbl_farms f
LEFT JOIN tbl_products p ON f.farm_id = p.farm_id
LEFT JOIN tbl_farm_image fi ON f.farm_id = fi.farm_id
WHERE f.status = 'active'
GROUP BY f.farm_id
ORDER BY f.created_at DESC";

$farms_result = mysqli_query($conn, $farms_query);
//favorite count
$fev_count = "SELECT COUNT(favorite_id) AS fev_count FROM tbl_favorites WHERE user_id = $user_id";

// Execute the query
$fev_result = $conn->query($fev_count);
// Check if the query was successful
if ($fev_result) {
    // Fetch the result
    $fev = $fev_result->fetch_assoc();
} else {
    // Handle error if the query failed
    echo "Error: " . $conn->error;
}

// Add after the existing favorite count query
$order_count_query = "SELECT COUNT(order_id) AS order_count 
                     FROM tbl_orders 
                     WHERE user_id = ?";

$stmt = $conn->prepare($order_count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order_data = $order_result->fetch_assoc();
$total_orders = $order_data['order_count'] ?? 0;

// Add after the order count query
$events_query = "SELECT COUNT(*) as event_count 
                FROM tbl_events e 
                JOIN tbl_farms f ON e.farm_id = f.farm_id
                WHERE e.event_date >= CURRENT_DATE 
                AND e.status = '1'
                AND f.status = 'active'";

$events_result = $conn->query($events_query);
$events_data = $events_result->fetch_assoc();
$upcoming_events = $events_data['event_count'] ?? 0;

// Check if farm is favorited by current user
function isFarmFavorited($conn, $farm_id, $user_id) {
    $query = "SELECT * FROM tbl_favorites WHERE farm_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $farm_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}
?>

?><!DOCTYPE html>
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

        /* ... existing styles ... */

.farm-card {
    position: relative;
    /* ... existing farm-card styles ... */
}

/* Add these new styles for the favorite button */
.favorite-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.favorite-btn i {
    font-size: 1.2rem;
    color:white;
    transition: all 0.3s ease;
}

.favorite-btn:hover {
    transform: scale(1.1);
    
}

.favorite-btn.active {
    background: #dc2626;
}

.favorite-btn.active i {
    color: white;
}

.favorite-btn.loading {
    pointer-events: none;
    opacity: 0.7;
}

.favorite-btn.loading i {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* ... rest of existing styles ... */
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #4b5563;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .stat-card .value,
        .stat-card .order {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a4d2e;
            margin-bottom: 10px;
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
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .farm-card:hover {
            transform: translateY(-5px);
        }

        .farm-card h3 {
            font-size: 1.4rem;
            margin-bottom: 12px;
            color: #1a4d2e;
        }

        .farm-image {
            height: 220px;
            position: relative;
            overflow: hidden;
        }

        .farm-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .farm-card:hover .farm-image img {
            transform: scale(1.05);
        }

        .farm-details {
            padding: 20px;
        }

        .farm-details h3 {
            font-size: 1.4rem;
            margin-bottom: 12px;
            color: #1a4d2e;
        }

        .farm-rating {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 8px;
            margin: 12px 0;
        }

        .stars {
            display: flex;
            gap: 2px;
        }

        .stars i {
            font-size: 16px;
            color: #ffd700;
        }

        .location {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            margin: 12px 0;
            font-size: 0.95rem;
        }

        .location i {
            color: #1a4d2e;
        }

        .description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .farm-stats {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .product-count {
            background: #e8f5e9;
            color: #1a4d2e;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-farm {
            background: #1a4d2e;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-align: center;
            display: block;
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .view-farm:hover {
            background: #2d6a4f;
            transform: translateY(-2px);
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
                padding: 10px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-card .value,
            .stat-card .order {
                font-size: 2rem;
            }

            .farms-grid {
                grid-template-columns: 1fr;
                padding: 10px;
            }

            .farm-image {
                height: 200px;
            }
        }
        #dynamic-content {
            flex: 1;
            padding: 20px;
            box-sizing: border-box;
        }

        .recent-farms {
            padding: 20px;
            margin-bottom: 30px;
        }

        .recent-farms h2 {
            color: #1a4d2e;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .farms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(26, 77, 46, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card h3 {
            color: #1a4d2e;
            font-size: 1.2rem;
            margin-bottom: 20px;
            position: relative;
        }

        .stat-card .value,
        .stat-card .order {
            font-size: 2.8rem;
            font-weight: 700;
            color: #2d6a4f;
            margin-bottom: 15px;
            position: relative;
        }

        /* Enhanced Farm Cards */
        .farms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }

        .farm-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.4s ease;
            position: relative;
        }

        .farm-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }

        .farm-image {
            height: 250px;
            position: relative;
            overflow: hidden;
        }

        .farm-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .farm-card:hover .farm-image img {
            transform: scale(1.1);
        }

        .farm-details {
            padding: 25px;
            position: relative;
        }

        .farm-details h3 {
            font-size: 1.5rem;
            color: #1a4d2e;
            margin-bottom: 15px;
            transition: color 0.3s ease;
        }

        .farm-rating {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 12px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stars {
            display: flex;
            gap: 4px;
        }

        .stars i {
            font-size: 18px;
            color: #ffd700;
        }

        .view-farm {
            background: linear-gradient(135deg, #1a4d2e, #2d6a4f);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            text-align: center;
            display: block;
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .view-farm:hover {
            background: linear-gradient(135deg, #2d6a4f, #1a4d2e);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 77, 46, 0.2);
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1200px) {
            .farms-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                padding: 15px;
            }

            .farm-card {
                margin: 0 15px;
            }

            .farm-details {
                padding: 20px;
            }

            .farm-image {
                height: 200px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-card .value,
            .stat-card .order {
                font-size: 2.2rem;
            }
        }

        /* Enhanced Profile Menu */
        .profile-popup {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .profile-info {
            padding: 20px;
        }

        .popup-logout-btn {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            padding: 15px 20px;
            border-radius: 0 0 15px 15px;
            transition: all 0.3s ease;
        }

        .popup-logout-btn:hover {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        /* Enhanced No Image Placeholder */
        .no-image {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            padding: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .no-image i {
            font-size: 4rem;
            color: #9ca3af;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="userindex.php" class="active"  ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse.php" ><i class="fas fa-store"></i><span>Browse Farms</span></a></li>
            <li><a href="cart.php" ><i class="fas fa-shopping-cart"></i><span>My Cart</span></a></li>
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
                       <button class="popup-logout-btn"  onclick="window.location.href='../logout/logout.php'">
                       <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            </div>

             <!-- Stats Grid  -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="order"><?php echo $total_orders; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Favorite Farms</h3>
                    <div class="value"><?php echo  $fev['fev_count'];?></div>
                </div>
                <div class="stat-card">
                    <h3>Upcoming Events</h3>
                    <div class="value"><?php echo $upcoming_events; ?></div>
                    <small><i class="fas fa-calendar-alt"></i> Active events</small>
                </div>
            </div>

            


             <!-- Favorite Farms 
            <h2>Your Favorite Farms</h2>
            <div class="stats-grid">
                 <?php
                ?> 
            </div>  -->

            <!-- Add this section where you want to display farms -->
            <div class="recent-farms">
                <h2>Available Farms</h2>
                <div class="farms-grid">
                    <?php
                    if(mysqli_num_rows($farms_result) > 0) {
                        while($farm = mysqli_fetch_assoc($farms_result)) {
                            // Get average rating and total reviews for this farm
                            $review_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                                           FROM tbl_reviews WHERE farm_id = ?";
                            $stmt = $conn->prepare($review_query);
                            $stmt->bind_param("i", $farm['farm_id']);
                            $stmt->execute();
                            $review_result = $stmt->get_result()->fetch_assoc();
                            
                            $avg_rating = number_format($review_result['avg_rating'] ?? 0, 1);
                            $total_reviews = $review_result['total_reviews'] ?? 0;
                            
                            $is_favorited = isFarmFavorited($conn, $farm['farm_id'], $_SESSION['userid']);
                            ?>
                            <div class="farm-card">
                                <button class="favorite-btn <?php echo $is_favorited ? 'active' : ''; ?>" 
                                        data-farm-id="<?php echo $farm['farm_id']; ?>"
                                        onclick="addToFavorites(this, <?php echo $farm['farm_id']; ?>)">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <div class="farm-image">
                                    <?php if($farm['farm_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($farm['farm_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($farm['farm_name']); ?>">
                                    <?php else: ?>
                                        <!-- Default image when no farm image is available -->
                                        <div class="no-image">
                                            <i class="fas fa-farm"></i>
                                            <p>No Image Available</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="farm-details">
                                    <h3><?php echo htmlspecialchars($farm['farm_name']); ?></h3>
                                    <div class="farm-rating">
                                        <div class="stars">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star" style="color: <?php echo $i <= $avg_rating ? '#ffd700' : '#ddd'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-count">(<?php echo $total_reviews; ?> reviews)</span>
                                    </div>
                                    <p class="location">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?php echo htmlspecialchars($farm['location']); ?>
                                    </p>
                                    <p class="description">
                                        <?php echo htmlspecialchars(substr($farm['description'], 0, 100)) . '...'; ?>
                                    </p>
                                    <div class="farm-stats">
                                        <span class="product-count">
                                            <i class="fas fa-box"></i> 
                                            <?php echo $farm['product_count']; ?> Products
                                        </span>
                                    </div>
                                    <a href="farm_details.php?id=<?php echo $farm['farm_id']; ?>" class="view-farm">
                                        View Farm
                                    </a>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<div class='no-farms'>No farms available at the moment</div>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2024 Farmfolio. All rights reserved.</p>
        </div>
    </div>

    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const profileIcon = document.getElementById('profileIcon');
            const profilePopup = document.getElementById('profilePopup');
            let timeoutId;

            function showPopup() {
                profilePopup.classList.add('show');
            }

            function hidePopup() {
                profilePopup.classList.remove('show');
            }

            profileIcon.addEventListener('mouseenter', () => {
                clearTimeout(timeoutId);
                showPopup();
            });

            profileIcon.addEventListener('mouseleave', () => {
                timeoutId = setTimeout(() => {
                    if (!profilePopup.matches(':hover')) {
                        hidePopup();
                    }
                }, 300);
            });

            profilePopup.addEventListener('mouseenter', () => {
                console.log("Mouse entered icon");
                clearTimeout(timeoutId);
                showPopup(); 
            });

            profilePopup.addEventListener('mouseleave', () => {
                
                timeoutId = setTimeout(hidePopup, 300);
            });

            profileIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                profilePopup.classList.toggle('show');
            });

            document.addEventListener('click', (e) => {
                if (!profileIcon.contains(e.target) && !profilePopup.contains(e.target)) {
                    hidePopup();
                }
            });
        })

           </script>

    <script>
    async function addToFavorites(button, farmId) {
        try {
            // Add loading state
            button.classList.add('loading');
            
            console.log('Managing favorites for farm:', farmId);
            
            const response = await fetch('add_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `farm_id=${farmId}`
            });

            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Response data:', data);
            
            // Remove loading state
            button.classList.remove('loading');
            
            if (data.success) {
                // Toggle the active class based on the action
                if (data.action === 'added') {
                    button.classList.add('active');
                } else if (data.action === 'removed') {
                    button.classList.remove('active');
                }
                
                // Update favorite count if it exists
                const favoriteCount = document.querySelector('.value');
                if (favoriteCount) {
                    let count = parseInt(favoriteCount.textContent);
                    count = data.action === 'added' ? count + 1 : count - 1;
                    favoriteCount.textContent = count;
                }
            } else {
                throw new Error(data.message || 'Error managing favorites');
            }
        } catch (error) {
            // Remove loading state
            button.classList.remove('loading');
            
            console.error('Error:', error);
            alert(error.message || 'An error occurred while managing favorites');
        }
    }
    </script>

</body>
</html>