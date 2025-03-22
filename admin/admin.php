<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['type'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

// user count
$stmt = "SELECT COUNT(*) AS total FROM tbl_login WHERE type IN (0, 1, 2)";
$count_result = mysqli_query($conn, $stmt);

if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    $user_count = $row['total'];
}
//farm count
$stmt1 = "SELECT COUNT(*) AS total FROM tbl_farms";
$count_result1 = mysqli_query($conn, $stmt1);
if ($count_result1) {
    $row1 = mysqli_fetch_assoc($count_result1);
    $farm_count = $row1['total'];
}
// Product count
$stmt2 = "SELECT COUNT(*) AS total FROM tbl_products";
$count_result2 = mysqli_query($conn, $stmt2);
if ($count_result2) {
    $row2 = mysqli_fetch_assoc($count_result2);
    $product_count = $row2['total'];
}

// Category count
$stmt3 = "SELECT COUNT(*) AS total FROM tbl_category";
$count_result3 = mysqli_query($conn, $stmt3);
if ($count_result3) {
    $row3 = mysqli_fetch_assoc($count_result3);
    $category_count = $row3['total'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Farmfolio Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
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
            background: #f8fafc;
            color: #334155;
        }

        .header {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 70px;
            background: #ffffff;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .head h1 {
            color: #1a4d2e;
            font-size: 24px;
            font-weight: 600;
        }

        .admin-controls {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .admin-controls h2 {
            font-size: 16px;
            color: #64748b;
        }

        .icon-btn {
            background: none;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
            color: #64748b;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .icon-btn:hover {
            background: #f1f5f9;
            color: #1a4d2e;
        }
        /* logout */
        .logout-btn {
        background-color: #d9534f;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: 0.2s ease-in-out;
    }

    .logout-btn:hover {
        background-color: #c9302c;
        transform: translateY(-2px);
    }

        .sidebar {
            width: 200px;
            background: #1a4d2e;
            color: white;
            padding: 10px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 10;
            overflow: hidden;
        }

        .sidebar-menu {
            list-style: none;
            margin-top: 20px;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }

        .sidebar-menu a:hover {
            background: #2d6a4f;
            transform: translateX(4px);
        }

        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
        }

        .active {
            background: #2d6a4f;
            font-weight: 500;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 90px 24px 24px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 18px;
            color: #1a4d2e;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #334155;
        }

        .stat-card button {
            margin-top: 10px;
            padding: 8px 16px;
            background: #1a4d2e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.2s;
        }

        .stat-card button:hover {
    background: #2d6a4f;
    transform: translateY(-10px);
    transition: transform 0.5s ease-in-out, background 0.2s ease-in-out;
}


        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="head"><h1>🌱 FarmFolio</h1></div>
    <div class="admin-controls">
        <h2>Welcome, Admin</h2>
        <button class="icon-btn" data-tooltip="Notifications"><i class="fas fa-bell"></i></button>
        <button class="icon-btn" data-tooltip="Messages"><i class="fas fa-envelope"></i></button>
        <button class="icon-btn" data-tooltip="Profile"><i class="fas fa-user-circle"></i></button>
        <button class="logout-btn" onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'">Logout</button>
    </div>
</header>


    <nav class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="admin.php" class="active"><i class="fas fa-home"></i><span>Home</span></a></li>
            <li><a href="user.php"><i class="fas fa-users"></i><span>Users</span></a></li>
            <li><a href="farm.php"><i class="fas fa-store"></i><span>Farms</span></a></li>
            <li><a href="product.php"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="category.php"><i class="fas fa-th-large"></i><span>category</span></a></li>
            <!-- <li><a href="#"><i class="fas fa-box"></i><span>Products</span></a></li> -->
            <!-- <li><a href="delivery.php"><i class="fas fa-truck"></i><span>Deliveries</span></a></li> -->
            <!-- <li><a href="#"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="#"><i class="fas fa-chart-line"></i><span>Analytics</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i><span>Settings</span></a></li> -->
        </ul>
    </nav>

    <main class="main-content">
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value"><?php echo  $user_count; ?></div>
                <a href="user.php"><button id="user">View</button></a>
            </div>
            <div class="stat-card">
                <h3>Farms</h3>
                <div class="value"><?php echo  $farm_count; ?></div>
               <a href="farm.php"><button id="farm">View</button></a> 
            </div>
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="value"><?php echo $product_count; ?></div>
                <a href="product.php"><button id="product">View</button></a>
            </div>
            <div class="stat-card">
                <h3>Category</h3>
                <div class="value"><?php echo $category_count; ?></div>
                <a href="category.php"><button id="category">View</button></a>
            </div>
        </div>
        <!-- <footer class="footer">
            <p>© 2025 Farmfolio Admin Panel. All rights reserved.</p>
        </footer> -->
    </main>
</body>
</html>
