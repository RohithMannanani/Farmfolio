<!DOCTYPE html>
<html>
<head>
    <title>Delivery Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="delivery.css">
</head>
<body>
    <?php
    session_start();
    include "../databse/connect.php";
    echo $_SESSION['username'];
    ?>
    <nav class="sidebar">
        <button class="menu-btn"><i class="fas fa-bars"></i></button>
        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="#"><i class="fas fa-truck"></i><span>Assigned Deliveries</span></a></li>
            <li><a href="#"><i class="fas fa-history"></i><span>Delivery History</span></a></li>
            <li><a href="#"><i class="fas fa-wallet"></i><span>Earnings</span></a></li>
            <li><a href="#"><i class="fas fa-bell"></i><span>Notifications</span></a></li>
            <li><a href="#"><i class="fas fa-user"></i><span>Profile</span></a></li>
            <li><a href="#"><i class="fas fa-headset"></i><span>Support</span></a></li>
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
            <button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </header>

    <main class="main-content">
        <div class="content-area">
            <h1 style="margin-bottom: 20px">Delivery Dashboard</h1>
            
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Today's Deliveries</h3>
                    <div class="value">8</div>
                    <small>3 completed</small>
                </div>
                <div class="stat-card">
                    <h3>Total Earnings</h3>
                    <div class="value">$156</div>
                    <small>This week</small>
                </div>
                <div class="stat-card">
                    <h3>Rating</h3>
                    <div class="value">4.8</div>
                    <small>From 45 deliveries</small>
                </div>
                <div class="stat-card">
                    <h3>Active Orders</h3>
                    <div class="value">3</div>
                    <small>In progress</small>
                </div>
            </div>

            <div class="delivery-list">
                <h2 style="margin-bottom: 15px">Current Deliveries</h2>
                <div class="delivery-item">
                    <div>
                        <h3>Order #12345</h3>
                        <p>123 Farm Street, Rural Area</p>
                    </div>
                    <span class="status-badge status-in-progress">In Progress</span>
                </div>
                <div class="delivery-item">
                    <div>
                        <h3>Order #12346</h3>
                        <p>456 Country Road, Green Valley</p>
                    </div>
                    <span class="status-badge status-pending">Pending</span>
                </div>
                <div class="delivery-item">
                    <div>
                        <h3>Order #12347</h3>
                        <p>789 Harvest Lane, Farmville</p>
                    </div>
                    <span class="status-badge status-completed">Completed</span>
                </div>
            </div>
        </div>

        <footer class="footer">
            <p>© 2025 Farmfolio Delivery. All rights reserved.</p>
            <div class="footer-items">
                <a href="#">Terms of Service</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Contact Us</a>
                <a href="#">FAQ</a>
            </div>
        </footer>
    </main>

    <script>
        // Toggle sidebar
        document.querySelector('.menu-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('shrink');
            document.querySelector('.main-content').classList.toggle('shrink');
            document.querySelector('.header').classList.toggle('shrink');
        });

        // Add click handlers for menu items
        document.querySelectorAll('.sidebar-menu a').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
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
