<?php
//0 active product 1-inactive product
include '../databse/connect.php';
session_start();
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
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
            grid-template-columns: repeat(3, minmax(240px, 1fr));
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
        .stat-card .order {
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
            position: relative;
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .favorite-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .favorite-btn i {
            color: #d1d5db;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .favorite-btn:hover {
            transform: scale(1.1);
        }

        .favorite-btn.active i {
            color: #e63946;
        }

        .favorite-btn:hover i {
            color: #e63946;
        }

        .favorite-btn.active:hover i {
            color: #d1d5db;
        }

        .farm-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #f3f4f6;
            position: relative;
        }

        .farm-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .farm-details {
            padding: 20px;
        }

        .farm-details h3 {
            color: #1a4d2e;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .location {
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
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
            display: inline-block;
            padding: 8px 16px;
            background: #1a4d2e;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s ease;
            width: 100%;
            text-align: center;
        }

        .view-farm:hover {
            background: #2d6a4f;
        }

        .no-farms {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 1.1rem;
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .farms-grid {
                grid-template-columns: 1fr;
                padding: 10px;
            }
        }

        .no-image {
            width: 100%;
            height: 100%;
            background: #f3f4f6;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6b7280;
        }

        .no-image i {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .no-image p {
            font-size: 0.9rem;
        }

        /* Ensure header/navbar has higher z-index */
        .header, 
        .navbar,
        .profile-section {
            z-index: 100; /* Higher z-index for header elements */
            position: relative;
        }

        .farm-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .stars {
            display: flex;
            gap: 2px;
        }

        .stars i {
            font-size: 14px;
        }

        .rating-count {
            color: #666;
            font-size: 0.9rem;
        }

        .farm-card {
            position: relative;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .farm-card:hover {
            transform: translateY(-5px);
        }

        .farm-details {
            padding: 15px;
        }

        .description {
            color: #666;
            margin: 10px 0;
            line-height: 1.4;
        }

        .farm-stats {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }

        .product-count {
            background: #e8f5e9;
            color: #1a4d2e;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
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
                       <button class="popup-logout-btn"  onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'">
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