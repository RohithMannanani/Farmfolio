<?php
session_start();
include '../databse/connect.php';

if(!isset($_SESSION['username'])){
    header('location: ../login/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> - Farmfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
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
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 10px rgba(220,38,38,0.2);
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

        .search-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 40px;
        }

        .search-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .search-box {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: #1a4d2e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26,77,46,0.1);
        }

        .search-box select {
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            min-width: 150px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-box select:focus {
            border-color: #1a4d2e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26,77,46,0.1);
        }

        .search-box button {
            padding: 15px 30px;
            background: linear-gradient(135deg, #1a4d2e, #2d6a4f);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .search-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26,77,46,0.2);
        }

        .filter-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-options select {
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            min-width: 180px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-options select:focus {
            border-color: #1a4d2e;
            outline: none;
        }

        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 20px 0;
        }

        .result-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .result-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, #1a4d2e, #2d6a4f);
        }

        .result-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .result-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a4d2e;
        }

        .result-location, 
        .result-farm, 
        .result-category {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 0.95rem;
        }

        .result-location i,
        .result-farm i,
        .result-category i {
            color: #1a4d2e;
            font-size: 1.1rem;
        }

        .result-price {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2d6a4f;
            margin: 12px 0;
        }

        .result-description {
            color: #666;
            line-height: 1.6;
            margin: 10px 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .result-stock {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 0.95rem;
            margin: 8px 0;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, #1a4d2e, #2d6a4f);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26,77,46,0.2);
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 1.1rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        @media (max-width: 1024px) {
            .search-results {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .search-box {
                flex-direction: column;
            }

            .search-box select,
            .search-box button {
                width: 100%;
            }

            .filter-options {
                flex-direction: column;
            }

            .filter-options select {
                width: 100%;
            }

            .result-card {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .search-section {
                padding: 20px;
                margin: 15px;
            }

            .result-name {
                font-size: 1.2rem;
            }

            .result-price {
                font-size: 1.3rem;
            }

            .add-to-cart-btn {
                padding: 10px 15px;
            }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .loading {
            animation: shimmer 2s infinite linear;
            background: linear-gradient(to right, #f6f7f8 8%, #edeef1 18%, #f6f7f8 33%);
            background-size: 2000px 100%;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-card {
            animation: fadeIn 0.5s ease;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="userindex.php" ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse.php" class="active"><i class="fas fa-store"></i><span>Browse Farms</span></a></li>
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
        <!-- Add this after the user-section div and before the footer -->
        <div class="search-section">
            <div class="search-container">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search farms or products...">
                    <select id="searchType">
                        <option value="all">All</option>
                        <option value="farms">Farms Only</option>
                        <option value="products">Products Only</option>
                    </select>
                    <button onclick="performSearch()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div class="filter-options">
                    <select id="locationFilter">
                        <option value="">All Locations</option>
                        <?php
                        $location_query = "SELECT DISTINCT location FROM tbl_farms WHERE status='active'";
                        $location_result = mysqli_query($conn, $location_query);
                        while($location = mysqli_fetch_assoc($location_result)) {
                            echo "<option value='" . htmlspecialchars($location['location']) . "'>" 
                                 . htmlspecialchars($location['location']) . "</option>";
                        }
                        ?>
                    </select>
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php
                        $category_query = "SELECT DISTINCT category FROM tbl_category WHERE status='1'";
                        $category_result = mysqli_query($conn, $category_query);
                        while($category = mysqli_fetch_assoc($category_result)) {
                            echo "<option value='" . htmlspecialchars($category['category']) . "'>" 
                                 . htmlspecialchars($category['category']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div id="searchResults" class="search-results">
                <!-- Results will be loaded here -->
            </div>
        </div>
          <!-- Footer -->
         
    </div>
    <!-- <div class="footer">
            <p>&copy; 2024 Farmfolio. All rights reserved.</p>
        </div> -->

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

    <!-- Add this JavaScript before the closing body tag -->
    <script>
    let debounceTimer;

    function debounce(func, delay) {
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => func.apply(context, args), delay);
        }
    }

    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', debounce(performSearch, 300));

    document.getElementById('searchType').addEventListener('change', performSearch);
    document.getElementById('locationFilter').addEventListener('change', performSearch);
    document.getElementById('categoryFilter').addEventListener('change', performSearch);

    function performSearch() {
        const searchTerm = document.getElementById('searchInput').value;
        const searchType = document.getElementById('searchType').value;
        const location = document.getElementById('locationFilter').value;
        const category = document.getElementById('categoryFilter').value;

        fetch('search_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=${encodeURIComponent(searchTerm)}&type=${searchType}&location=${location}&category=${category}`
        })
        .then(response => response.json())
        .then(data => {
            displayResults(data);
        })
        .catch(error => console.error('Error:', error));
    }

    function displayResults(data) {
        const resultsContainer = document.getElementById('searchResults');
        resultsContainer.innerHTML = '';

        if (!data.success || !data.results || data.results.length === 0) {
            resultsContainer.innerHTML = '<div class="no-results">No results found</div>';
            return;
        }

        data.results.forEach(result => {
            const card = document.createElement('div');
            card.className = 'result-card';
            
            if (result.type === 'farm') {
                card.innerHTML = `
                    <div class="result-details">
                        <div class="result-name">${result.name}</div>
                        <div class="result-location">
                            <i class="fas fa-map-marker-alt"></i> ${result.location}
                        </div>
                        <div class="result-description">${result.description || 'No description available'}</div>
                        <div class="result-products">
                            <i class="fas fa-box"></i> ${result.product_count} Products
                        </div>
                        <div class="result-date">
                            <i class="fas fa-calendar"></i> Joined ${new Date(result.created_at).toLocaleDateString()}
                        </div>
                        <a href="farm_details.php?id=${result.id}" class="view-farm">
                            View Farm <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                `;
            } else {
                card.innerHTML = `
                    <div class="result-details">
                        <div class="result-name">${result.name}</div>
                        <div class="result-category">
                            <i class="fas fa-tag"></i> ${result.category} - ${result.subcategory}
                        </div>
                        <div class="result-farm">
                            <i class="fas fa-store"></i> ${result.farm_name}
                            <span class="farm-location">
                                <i class="fas fa-map-marker-alt"></i> ${result.farm_location}
                            </span>
                        </div>
                        <div class="result-description">${result.description || 'No description available'}</div>
                        <div class="result-stock">
                            <i class="fas fa-cube"></i> ${result.stock} ${result.unit} available
                        </div>
                        <div class="result-price">₹${result.price}</div>
                        <button onclick="addToCart(${result.id})" class="add-to-cart-btn">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                `;
            }
            
            resultsContainer.appendChild(card);
        });
    }

   // Updated addToCart function for the JavaScript
function addToCart(productId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ productId: productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            // Optionally update cart count in UI if you have a cart counter
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the product to cart');
    });
}

    // Initial search on page load
    performSearch();
    </script>
</body>