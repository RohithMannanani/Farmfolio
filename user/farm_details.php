<?php
//0 product active state 1-product deactive state 
session_start();
include '../databse/connect.php';

if(!isset($_SESSION['username'])) {
    header('location: http://localhost/mini%20project/login/login.php');
}

// Get farm ID from URL
$farm_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get farm details
$farm_query = "SELECT f.*, 
                      COUNT(DISTINCT CASE WHEN p.status = 1 THEN p.product_id END) AS product_count, 
                      p.unit, 
                      COUNT(DISTINCT fav.favorite_id) AS favorite_count, 
                      r.mobile 
               FROM tbl_farms f 
               LEFT JOIN tbl_products p ON f.farm_id = p.farm_id 
               LEFT JOIN tbl_favorites fav ON f.farm_id = fav.farm_id 
               LEFT JOIN tbl_signup r ON f.user_id = r.userid 
               WHERE f.farm_id = $farm_id 
               GROUP BY f.farm_id";

$farm_result = mysqli_query($conn, $farm_query);
$farm = mysqli_fetch_assoc($farm_result);
if($farm['unit'] == 'kg'){
    $unit = 'Kilogram (kg)';
}elseif($farm['unit'] == 'g'){
    $unit = 'Gram (g)';
}elseif($farm['unit'] == 'l'){
    $unit = 'Liter (l)';
}elseif($farm['unit'] == 'ml'){
    $unit = 'Milliliter (ml)';
}
// Get farm images
$images_query = "SELECT * FROM tbl_farm_image WHERE farm_id = $farm_id";
$images_result = mysqli_query($conn, $images_query);

// Get farm products
$products_query = "SELECT * FROM tbl_products WHERE farm_id = $farm_id AND status = '0'";
$products_result = mysqli_query($conn, $products_query);

if (!$products_result) {
    die("Query failed: " . mysqli_error($conn)); // Debugging message
}

// Add this query after your existing queries
$events_query = "SELECT * FROM tbl_events 
                WHERE farm_id = $farm_id 
                AND status = '1' 
                AND event_date >= CURDATE() 
                ORDER BY event_date ASC";
$events_result = mysqli_query($conn, $events_query);

// Get farm reviews
$reviews_query = "SELECT r.*, u.username, 
                 AVG(r2.rating) as average_rating,
                 COUNT(r2.review_id) as total_reviews
                 FROM tbl_reviews r2
                 LEFT JOIN tbl_reviews r ON r.farm_id = r2.farm_id
                 LEFT JOIN tbl_signup u ON r.user_id = u.userid
                 WHERE r2.farm_id = ?
                 GROUP BY r.review_id";
$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$reviews_result = $stmt->get_result();

// Get user's existing review if any
$user_review_query = "SELECT * FROM tbl_reviews 
                     WHERE farm_id = ? AND user_id = ?";
$stmt = $conn->prepare($user_review_query);
$stmt->bind_param("ii", $farm_id, $_SESSION['userid']);
$stmt->execute();
$user_review = $stmt->get_result()->fetch_assoc();

// Add function to check if user is already registered
function isUserRegistered($conn, $event_id, $user_id) {
    $query = "SELECT * FROM tbl_participants 
              WHERE event_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function addToCart($productId) {
    // Initialize the cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add the product to the cart
    if (!in_array($productId, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $productId;
    }
    
    // Optionally, you can return a message or handle further logic
    echo json_encode(['status' => 'success', 'message' => 'Product added to cart!']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($farm['farm_name']); ?> - Farmfolio</title>
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

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                padding: 10px;
            }

            .main-content {
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

        .farm-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .farm-header h1 {
            color: #1a4d2e;
            margin-bottom: 15px;
        }

        .farm-meta {
            display: flex;
            gap: 20px;
            color: #666;
            margin-bottom: 20px;
        }

        .farm-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .farm-description {
            line-height: 1.6;
            color: #444;
        }

        .farm-images {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .farm-image {
            height: 200px;
            border-radius: 8px;
            overflow: hidden;
        }

        .farm-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        /* Image Modal */
        .image-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-content {
    max-width: 100%;
    max-height: 100%;
    border-radius: 8px;
}

.close-btn {
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 40px;
    color: white;
    cursor: pointer;
}
.image-modal.show {
    display: flex;
}
.image-modal img {
    max-width: 90vw; /* 90% of viewport width */
    max-height: 90vh; /* 90% of viewport height */
    border-radius: 10px;
    box-shadow: 0px 0px 20px rgba(255, 255, 255, 0.3);
}


        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            height: 200px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details {
            padding: 15px;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a4d2e;
            margin-bottom: 10px;
        }

        .product-price {
            color: #2d6a4f;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .product-stock {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .add-to-cart {
            background: #1a4d2e;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease;
        }

        .add-to-cart:hover {
            background: #2d6a4f;
        }

        .section-title {
            color: #1a4d2e;
            margin: 30px 0 20px;
            font-size: 1.5rem;
        }

        .no-products {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        .user-section {
            margin-bottom: 30px;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .event-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }

        .event-date-badge {
            background: #1a4d2e;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }

        .event-details {
            padding: 20px;
        }

        .event-name {
            color: #1a4d2e;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .event-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .participate-btn {
            background: #1a4d2e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.3s ease;
        }

        .participate-btn:hover {
            background: #2d6a4f;
        }

        .registered-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: not-allowed;
        }

        .no-events {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        .reviews-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .rating-summary {
            text-align: center;
            margin-bottom: 20px;
        }

        .average-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .rating-number {
            font-size: 24px;
            font-weight: bold;
            color: #1a4d2e;
        }

        .stars {
            color: #ffd700;
        }

        .total-reviews {
            color: #666;
        }

        .review-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .star-rating {
            margin: 15px 0;
        }

        .star-rating i {
            font-size: 24px;
            color: #ffd700;
            cursor: pointer;
            margin-right: 5px;
        }

        .review-input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            resize: vertical;
        }

        .submit-review {
            background: #1a4d2e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .submit-review:hover {
            background: #2d6a4f;
        }

        .review-card {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: bold;
            color: #1a4d2e;
        }

        .review-date {
            color: #666;
            font-size: 0.9em;
        }

        .review-stars {
            margin: 5px 0;
        }

        .review-comment {
            color: #444;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="userindex.php" class="active" ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
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
                        <button class="popup-logout-btn" onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            </div>

            <!-- Farm Header -->
            <div class="farm-header">
                <h1><?php echo htmlspecialchars($farm['farm_name']); ?></h1>
                <div class="farm-meta">
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($farm['location']); ?></span>
                    <span><i class="fas fa-box"></i> <?php echo $farm['product_count']; ?> Products</span>
                    <span><i class="fas fa-heart"></i> <?php echo $farm['favorite_count']; ?> Favorites</span>
                    <span><i class="fas fa-mobile"></i> <?php echo $farm['mobile']; ?> Contact</span>
                </div>
                <p class="farm-description"><?php echo htmlspecialchars($farm['description']); ?></p>
            </div>

            <!-- Farm Images -->
            <?php if(mysqli_num_rows($images_result) > 0): ?>
            <h2 class="section-title">Farm Gallery</h2>
            <div class="farm-images">
    <?php while($image = mysqli_fetch_assoc($images_result)): ?>
        <div class="farm-image">
            <img src="../<?php echo htmlspecialchars($image['path']); ?>" 
                 alt="<?php echo htmlspecialchars($farm['farm_name']); ?>"
                 onclick="openModal('../<?php echo htmlspecialchars($image['path']); ?>')">
        </div>
    <?php endwhile; ?>
</div>

            <div id="imageModal" class="image-modal">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <img id="modalImage" class="modal-content">
</div>
            <?php endif; ?>

            <!-- Products Section -->
            <h2 class="section-title">Available Products</h2>
            <?php if (mysqli_num_rows($products_result) > 0): ?>
    <div class="products-grid">
        <?php 
        // Loop through the fetched result
        while ($product = mysqli_fetch_assoc($products_result)):
            // Check if the product status is 1 (active)
            if ($product['status'] == 0): ?>
                <div class="product-card">
                    <div class="product-details">
                        <!-- Display product name -->
                        <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <!-- Display price -->
                        <div class="product-price">₹<?php echo number_format($product['price'], 2); ?><small>/<?php echo $product['unit'];?></small></div>
                        <!-- Display stock count -->
                        <div class="product-stock">
                            Stock: <?php echo $product['stock']; ?> <?php echo $product['unit']; ?>
                        </div>
                        <!-- Add to cart button -->
                        <button class="add-to-cart" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="no-products">
        <i class="fas fa-box-open" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
        <p>No products available at the moment.</p>
    </div>
<?php endif; ?>

<!-- Events Section -->
<h2 class="section-title">Upcoming Events</h2>
<?php if (mysqli_num_rows($events_result) > 0): ?>
    <div class="events-grid">
        <?php while($event = mysqli_fetch_assoc($events_result)): 
            $is_registered = isUserRegistered($conn, $event['event_id'], $_SESSION['userid']);
        ?>
            <div class="event-card">
                <div class="event-date-badge">
                    <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                </div>
                <div class="event-details">
                    <h3 class="event-name"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                    <p class="event-description"><?php echo htmlspecialchars($event['event_description']); ?></p>
                    <?php if(!$is_registered): ?>
                        <button class="participate-btn" 
                                onclick="participateInEvent(<?php echo $event['event_id']; ?>)">
                            <i class="fas fa-calendar-check"></i> Participate
                        </button>
                    <?php else: ?>
                        <button class="registered-btn" disabled>
                            <i class="fas fa-check"></i> Registered
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="no-events">
        <i class="fas fa-calendar-times" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
        <p>No upcoming events at the moment.</p>
    </div>
<?php endif; ?>

<!-- Reviews Section -->
<h2 class="section-title">Farm Reviews</h2>
<div class="reviews-section">
    <?php 
    // Get average rating and total reviews
    $avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                  FROM tbl_reviews WHERE farm_id = ?";
    $stmt = $conn->prepare($avg_query);
    $stmt->bind_param("i", $farm_id);
    $stmt->execute();
    $avg_result = $stmt->get_result()->fetch_assoc();
    
    $average_rating = number_format($avg_result['avg_rating'] ?? 0, 1);
    $total_reviews = $avg_result['total_reviews'] ?? 0;

    // Check if user has already reviewed
    $user_review_query = "SELECT * FROM tbl_reviews 
                         WHERE farm_id = ? AND user_id = ?";
    $stmt = $conn->prepare($user_review_query);
    $stmt->bind_param("ii", $farm_id, $_SESSION['userid']);
    $stmt->execute();
    $user_review = $stmt->get_result()->fetch_assoc();
    ?>

    <!-- Average Rating Display -->
    <div class="rating-summary">
        <div class="average-rating">
            <span class="rating-number"><?php echo $average_rating; ?></span>
            <div class="stars">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star" style="color: <?php echo $i <= $average_rating ? '#ffd700' : '#ddd'; ?>"></i>
                <?php endfor; ?>
            </div>
            <span class="total-reviews">(<?php echo $total_reviews; ?> reviews)</span>
        </div>
    </div>

    <!-- Review Form or Edit Form based on whether user has already reviewed -->
    <?php if ($user_review): ?>
        <div class="review-form">
            <h3>Your Review</h3>
            <div class="star-rating" id="starRating">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="<?php echo $i <= $user_review['rating'] ? 'fas' : 'far'; ?> fa-star" 
                       data-rating="<?php echo $i; ?>"></i>
                <?php endfor; ?>
            </div>
            <textarea id="reviewComment" class="review-input"><?php echo htmlspecialchars($user_review['comment']); ?></textarea>
            <button onclick="submitReview()" class="submit-review">Update Review</button>
        </div>
    <?php else: ?>
        <div class="review-form">
            <h3>Write a Review</h3>
            <div class="star-rating" id="starRating">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="far fa-star" data-rating="<?php echo $i; ?>"></i>
                <?php endfor; ?>
            </div>
            <textarea id="reviewComment" class="review-input" placeholder="Share your experience with this farm..."></textarea>
            <button onclick="submitReview()" class="submit-review">Submit Review</button>
        </div>
    <?php endif; ?>

    <!-- Reviews List -->
    <div class="reviews-list">
        <?php
        $reviews_query = "SELECT r.*, u.username 
                         FROM tbl_reviews r 
                         JOIN tbl_signup u ON r.user_id = u.userid 
                         WHERE r.farm_id = ? 
                         ORDER BY r.created_at DESC";
        $stmt = $conn->prepare($reviews_query);
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        $reviews = $stmt->get_result();

        if($reviews->num_rows > 0):
            while($review = $reviews->fetch_assoc()):
        ?>
            <div class="review-card">
                <div class="review-header">
                    <span class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></span>
                    <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                </div>
                <div class="review-stars">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? '#ffd700' : '#ddd'; ?>"></i>
                    <?php endfor; ?>
                </div>
                <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
            </div>
        <?php 
            endwhile;
        else:
        ?>
            <div class="no-reviews">
                <p>No reviews yet. Be the first to review this farm!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

            <!-- Footer -->
            <div class="footer">
                <p>&copy; 2024 Farmfolio. All rights reserved.</p>
            </div>
        </div>
    </div>
<script src="profile.js"></script>
    <script>
document.addEventListener("DOMContentLoaded", function () {
    const imageModal = document.getElementById("imageModal");
    const modalImage = document.getElementById("modalImage");

    function openModal(imageSrc) {
        modalImage.src = imageSrc;
        imageModal.classList.add("show");
    }

    function closeModal() {
        imageModal.classList.remove("show");
    }

    // Ensure modal is hidden on page load
    imageModal.classList.remove("show");

    // Close the modal when clicking outside the image
    imageModal.addEventListener("click", function (e) {
        if (e.target === imageModal) {
            closeModal();
        }
    });

    // Expose functions globally
    window.openModal = openModal;
    window.closeModal = closeModal;
});
function addToCart(productId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ productId: productId }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            // Optionally refresh the page or update a cart counter
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        alert('Failed to add product to cart');
    });
}
function participateInEvent(eventId) {
    if(confirm('Are you sure you want to participate in this event?')) {
        fetch('participate_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                event_id: eventId
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                alert('Successfully registered for the event!');
                location.reload();
            } else {
                alert(data.message || 'Failed to register for the event');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while registering for the event');
        });
    }
}
let currentRating = <?php echo $user_review ? $user_review['rating'] : 0; ?>;

// Star rating functionality
document.querySelectorAll('#starRating i').forEach(star => {
    star.addEventListener('mouseover', function() {
        const rating = this.dataset.rating;
        updateStars(rating);
    });

    star.addEventListener('click', function() {
        currentRating = this.dataset.rating;
    });

    star.addEventListener('mouseout', function() {
        updateStars(currentRating);
    });
});

function updateStars(rating) {
    document.querySelectorAll('#starRating i').forEach(star => {
        if (star.dataset.rating <= rating) {
            star.classList.remove('far');
            star.classList.add('fas');
        } else {
            star.classList.remove('fas');
            star.classList.add('far');
        }
    });
}

function submitReview() {
    if (!currentRating) {
        alert('Please select a rating');
        return;
    }

    const comment = document.getElementById('reviewComment').value;
    
    fetch('submit_review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            farm_id: <?php echo $farm_id; ?>,
            rating: currentRating,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting your review');
    });
}
</script>

</body>
</html>