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

        // Get reviews for this farm
        $reviews_query = "SELECT r.*, u.username, u.email 
                         FROM tbl_reviews r 
                         JOIN tbl_signup u ON r.user_id = u.userid 
                         WHERE r.farm_id = ? 
                         ORDER BY r.created_at DESC";
        $stmt = $conn->prepare($reviews_query);
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        $reviews_result = $stmt->get_result();

        // Get average rating
        $avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                     FROM tbl_reviews WHERE farm_id = ?";
        $stmt = $conn->prepare($avg_query);
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        $avg_result = $stmt->get_result()->fetch_assoc();
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
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>Farm Reviews</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="farm.css">
    <style>
        .reviews-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .reviews-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .rating-summary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }

        .average-rating {
            font-size: 48px;
            font-weight: bold;
            color: #1a4d2e;
        }

        .rating-stars {
            color: #ffd700;
            font-size: 24px;
        }

        .total-reviews {
            color: #666;
            font-size: 18px;
        }

        .reviews-grid {
            display: grid;
            gap: 20px;
        }

        .review-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .reviewer-info {
            display: flex;
            flex-direction: column;
        }

        .reviewer-name {
            font-weight: bold;
            color: #1a4d2e;
        }

        .reviewer-email {
            color: #666;
            font-size: 0.9em;
        }

        .review-date {
            color: #666;
            font-size: 0.9em;
        }

        .review-rating {
            color: #ffd700;
            margin: 10px 0;
        }

        .review-comment {
            color: #444;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .no-reviews i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .inactive-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .inactive-message i {
            font-size: 48px;
            color: #1a4d2e;
            margin-bottom: 20px;
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
            <li><a href="product.php"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="image.php"><i class="fas fa-image"></i><span>Farm Images</span></a></li>
            <li><a href="event.php"><i class="fas fa-calendar"></i><span>Events</span></a></li>
            <li><a href="review.php" class="active"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="orders.php"><i class="fas fa-truck"></i><span>Orders</span></a></li>
            <!-- <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a></li> -->
        </ul>
    </nav>

    <div class="main-content">
        <div class="dashboard-header">
            <?php if(isset($row['farm_name'])&&isset($_SESSION['username'])){?>
                <h1><?php echo $row['farm_name'];?> Farm</h1>
                <div class="user-section">
                    <span>Welcome, <?php echo $_SESSION['username'];?></span>
                    <a href="http://localhost/mini%20project/logout/logout.php">
                        <button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </a>
                </div>
            <?php }else{?>
                <h1>Farm Dashboard</h1>
                <div class="user-section">
                    <span>Welcome,</span>
                    <a href="http://localhost/mini%20project/logout/logout.php">
                        <button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </a>
                </div>
            <?php }?>
        </div>

        <?php if($is_farm_active): ?>
            <div class="reviews-container">
                <div class="reviews-header">
                    <h2>Farm Reviews</h2>
                    <div class="rating-summary">
                        <span class="average-rating">
                            <?php echo number_format($avg_result['avg_rating'] ?? 0, 1); ?>
                        </span>
                        <div class="rating-stars">
                            <?php 
                            $avg_rating = $avg_result['avg_rating'] ?? 0;
                            for($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="fas fa-star" style="color: <?php echo $i <= $avg_rating ? '#ffd700' : '#ddd'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="total-reviews">
                            (<?php echo $avg_result['total_reviews'] ?? 0; ?> reviews)
                        </span>
                    </div>
                </div>

                <?php if($reviews_result->num_rows > 0): ?>
                    <div class="reviews-grid">
                        <?php while($review = $reviews_result->fetch_assoc()): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <span class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></span>
                                        <span class="reviewer-email"><?php echo htmlspecialchars($review['email']); ?></span>
                                    </div>
                                    <span class="review-date">
                                        <?php echo date('F d, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? '#ffd700' : '#ddd'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-reviews">
                        <i class="fas fa-star"></i>
                        <h3>No Reviews Yet</h3>
                        <p>Your farm hasn't received any reviews yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="inactive-message">
                <i class="fas fa-store-slash"></i>
                <h2>Farm Not Active</h2>
                <p>Your farm is currently inactive. Please contact the administrator to activate your farm account.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>