<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard - Farmfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
 <link rel="stylesheet" href="user.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Farmfolio</h2>
            <div class="menu">
                <img src="menu-icon.png" alt="menu" id="menu-toggle">
            </div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="#"><i class="fas fa-store"></i><span>Browse Farms</span></a></li>
            <li><a href="#"><i class="fas fa-shopping-cart"></i><span>My Orders</span></a></li>
            <li><a href="#"><i class="fas fa-heart"></i><span>Favorite Farms</span></a></li>
            <li><a href="#"><i class="fas fa-calendar"></i><span>Farm Events</span></a></li>
            <li><a href="#"><i class="fas fa-user"></i><span>Profile</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i><span>Settings</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="container">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1>Welcome</h1>
                <div class="user-section">
                    <span></span>
                    <button class="logout-btn">Logout</button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="value">24</div>
                </div>
                <div class="stat-card">
                    <h3>Favorite Farms</h3>
                    <div class="value">12</div>
                </div>
                <div class="stat-card">
                    <h3>Upcoming Events</h3>
                    <div class="value">3</div>
                </div>
                <div class="stat-card">
                    <h3>Active Orders</h3>
                    <div class="value">2</div>
                </div>
            </div>

            <!-- Recent Orders and Notifications -->
            <div class="chart-container">
                <div class="chart-card">
                    <h2>Recent Orders</h2>
                    <div class="order-history">
                        <?php
                        // Fetch recent orders (mock data)
                        $recent_orders = [
                            ['id' => '1', 'farm' => 'Green Valley Farm', 'date' => '2024-02-04', 'status' => 'Delivered'],
                            ['id' => '2', 'farm' => 'Sunrise Organics', 'date' => '2024-02-03', 'status' => 'In Transit'],
                        ];

                        foreach($recent_orders as $order) {
                            echo "<div class='order-item'>
                                <h3>Order #{$order['id']} - {$order['farm']}</h3>
                                <p>Date: {$order['date']}</p>
                                <p>Status: {$order['status']}</p>
                            </div>";
                        }
                        ?>
                    </div>
                </div>
                <div class="chart-card">
                    <h2>Notifications</h2>
                    <ul class="notifications">
                        <li class="notification-item">
                            <div class="notification-message">New event at Green Valley Farm</div>
                            <div class="notification-time">2 hours ago</div>
                        </li>
                        <li class="notification-item">
                            <div class="notification-message">Your order #123 has been delivered</div>
                            <div class="notification-time">1 day ago</div>
                        </li>
                        <li class="notification-item">
                            <div class="notification-message">Season sale at Sunrise Organics</div>
                            <div class="notification-time">2 days ago</div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Favorite Farms -->
            <h2>Your Favorite Farms</h2>
            <div class="stats-grid">
                <?php
                // Fetch favorite farms (mock data)
                $favorite_farms = [
                    ['name' => 'Green Valley Farm', 'rating' => '4.5', 'products' => '15'],
                    ['name' => 'Sunrise Organics', 'rating' => '4.8', 'products' => '23'],
                    ['name' => 'Fresh Fields', 'rating' => '4.2', 'products' => '18']
                ];

                foreach($favorite_farms as $farm) {
                    echo "<div class='farm-card'>
                        <h3>{$farm['name']}</h3>
                        <p>Rating: {$farm['rating']} ⭐</p>
                        <p>Available Products: {$farm['products']}</p>
                        <a href='#' class='view-farm'>View Farm</a>
                    </div>";
                }
                ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2024 Farmfolio. All rights reserved.</p>
        </div>
    </div>

    <script>
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('shrink');
        document.getElementById('main-content').classList.toggle('shrink');
    });
    </script>
</body>
</html>