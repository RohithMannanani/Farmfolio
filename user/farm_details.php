<?php
session_start();
include '../databse/connect.php';

if(!isset($_SESSION['username'])) {
    header('location: http://localhost/mini%20project/login/login.php');
}

// Get farm ID from URL
$farm_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get farm details
$farm_query = "SELECT f.*, COUNT(DISTINCT p.product_id) as product_count, 
               COUNT(DISTINCT fav.favorite_id) as favorite_count
               FROM tbl_farms f 
               LEFT JOIN tbl_products p ON f.farm_id = p.farm_id
               LEFT JOIN tbl_favorites fav ON f.farm_id = fav.farm_id
               WHERE f.farm_id = $farm_id
               GROUP BY f.farm_id";
$farm_result = mysqli_query($conn, $farm_query);
$farm = mysqli_fetch_assoc($farm_result);

// Get farm images
$images_query = "SELECT * FROM tbl_farm_image WHERE farm_id = $farm_id";
$images_result = mysqli_query($conn, $images_query);

// Get farm products
$products_query = "SELECT * FROM tbl_products WHERE farm_id = $farm_id ";
$products_result = mysqli_query($conn, $products_query);
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="userindex.php" ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse.php" ><i class="fas fa-store"></i><span>Browse Farms</span></a></li>
            <li><a href="orders.php" ><i class="fas fa-shopping-cart"></i><span>My Orders</span></a></li>
            <li><a href="favorite.php" ><i class="fas fa-heart"></i><span>Favorite Farms</span></a></li>
            <li><a href="events.php" ><i class="fas fa-calendar"></i><span>Farm Events</span></a></li>
            <li><a href="profile.php" ><i class="fas fa-user"></i><span>Profile</span></a></li>
            <li><a href="settings.php" class="active"><i class="fas fa-cog"></i><span>Settings</span></a></li>
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
                             alt="<?php echo htmlspecialchars($farm['farm_name']); ?>">
                    </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>

            <!-- Products Section -->
            <h2 class="section-title">Available Products</h2>
            <?php if(mysqli_num_rows($products_result) > 0): ?>
                <div class="products-grid">
                    <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                        <div class="product-card">
                            
                            <div class="product-details">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                <div class="product-stock">
                                    Stock: <?php echo $product['stock']; ?> units
                                </div>
                                <button class="add-to-cart" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-products">
                    <i class="fas fa-box-open" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                    <p>No products available at the moment.</p>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="footer">
                <p>&copy; 2024 Farmfolio. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileIcon = document.getElementById('profileIcon');
            const profilePopup = document.getElementById('profilePopup');
            let timeoutId;

            if (!profileIcon || !profilePopup) {
                console.error('Profile elements not found');
                return;
            }

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
        });

        // Add to cart functionality can be implemented here
        function addToCart(productId) {
            // Implement your add to cart logic
            alert('Product added to cart!');
        }
    </script>
</body>
</html>