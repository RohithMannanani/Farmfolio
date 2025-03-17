<?php
include '../databse/connect.php';
// listed users
$stmt = "SELECT tbl_signup.username,tbl_signup.email,tbl_signup.mobile,tbl_signup.state,tbl_signup.district,tbl_login.type  FROM tbl_signup INNER JOIN tbl_login  ON tbl_signup.userid=tbl_login.userid AND tbl_login.type IN (0, 1, 2)";
$result = mysqli_query($conn, $stmt);

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



        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }
        .admin-table {
          width: 100%;
          border-collapse: collapse;
        }

        .admin-table th {
          background-color:#1a4d2e;
          color: white;
          padding: 12px;
          text-align: left;
          text-transform: uppercase;
          font-size: 13px;
        }

        .admin-table td {
          padding: 12px;
          border-bottom: 1px solid #eee;
          color: #232d39;
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
            <li><a href="admin.php"><i class="fas fa-home"></i><span>Home</span></a></li>
            <li><a href="user.php" class="active"><i class="fas fa-users"></i><span>Users</span></a></li>
            <li><a href="farm.php"><i class="fas fa-store"></i><span>Farms</span></a></li>
            <li><a href="category.php"><i class="fas fa-th-large"></i><span>category</span></a></li>
            <li><a href="#"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="#"><i class="fas fa-truck"></i><span>Deliveries</span></a></li>
            <li><a href="#"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="#"><i class="fas fa-chart-line"></i><span>Analytics</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i><span>Settings</span></a></li>
        </ul>
    </nav>

    <main class="main-content">
    <div class="admin-card">
        <h2>Total Users</h2>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Username</th>
              <th>Email</th>
              <th>Mobile</th>
              <th>State</th>
              <th>District</th>
              <th>Type</th>
            </tr>
          </thead>
          <tbody>
          <?php
            while ($row = mysqli_fetch_assoc($result)) {
                if($row['type']==0){
                 $role='Consumer';
                }
                elseif($row['type']==1){
                    $role='Farm';
                }
                else{
                    $role='Delivery Boy';
                }
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";
                echo "<td>" . ($row['state']) . "</td>";
                echo "<td>" . htmlspecialchars($row['district']) . "</td>";
               echo "<td>". $role ."</td>";
                echo "</tr>";
            }
            ?>
          </tbody>
        </table>
       
        <footer class="footer">
            <p>© 2025 Farmfolio Admin Panel. All rights reserved.</p>
        </footer>
    </main>
</body>
</html>
