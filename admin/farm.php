<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['type'])){
    header('location: http://localhost/mini%20project/login/login.php');
}
// listed farms
$stmt = "SELECT 
    tbl_farms.farm_id,
    tbl_farms.farm_name,
    tbl_farms.location,
    tbl_farms.status,
    tbl_signup.username,
    tbl_signup.email,
    tbl_signup.mobile,
    tbl_signup.state,
    tbl_signup.district,
    COUNT(tbl_products.product_id) as product_count
FROM tbl_signup 
INNER JOIN tbl_login ON tbl_signup.userid=tbl_login.userid  
INNER JOIN tbl_farms ON tbl_signup.userid=tbl_farms.user_id
LEFT JOIN tbl_products ON tbl_farms.farm_id=tbl_products.farm_id
GROUP BY tbl_farms.farm_id";
$result = $conn->query($stmt);
if($result){
    echo "success";
}else{
    echo $conn->error;
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
            min-height: 100vh;
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

        /* Updated styles for the status dropdown */
        .status-dropdown {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
            color: white;
        }

        .status-dropdown[data-status="pending"] {
            background-color: #3498db;
        }

        .status-dropdown[data-status="active"] {
            background-color: #2ecc71;
        }

        .status-dropdown[data-status="rejected"] {
            background-color: #e74c3c;
        }

        .product-count {
            background-color: #1a4d2e;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="head"><h1>🌱 FarmFolio</h1></div>
        <div class="admin-controls">
            <h2>Welcome, Admin</h2>
            <!-- <button class="icon-btn" data-tooltip="Notifications"><i class="fas fa-bell"></i></button>
            <button class="icon-btn" data-tooltip="Messages"><i class="fas fa-envelope"></i></button>
            <button class="icon-btn" data-tooltip="Profile"><i class="fas fa-user-circle"></i></button> -->
            <button class="logout-btn" onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'">Logout</button>
        </div>
    </header>

    <nav class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="admin.php" ><i class="fas fa-home"></i><span>Home</span></a></li>
            <li><a href="user.php"><i class="fas fa-users"></i><span>Users</span></a></li>
            <li><a href="farm.php" class="active"><i class="fas fa-store"></i><span>Farms</span></a></li>
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
    <div class="admin-card">
        <h2>Total Farms Listed</h2>
        <table class="admin-table">
          <thead>
            <tr>
            <th>Farm_name</th>
            <th>location</th>
            <th>Username</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>State</th>
            <th>District</th>
            <th>Products</th>
            <th>Status</th>
            </tr>
          </thead>
          <tbody>
    <?php
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlentities($row['farm_name']) . "</td>";
        echo "<td>" . htmlentities($row['location']) . "</td>";
        echo "<td>" . htmlentities($row['username']) . "</td>";
        echo "<td>" . htmlentities($row['email']) . "</td>";
        echo "<td>" . htmlentities($row['mobile']) . "</td>";
        echo "<td>" . htmlspecialchars($row['state']) . "</td>";
        echo "<td>" . htmlspecialchars($row['district']) . "</td>";
        echo "<td><span class='product-count'>" . $row['product_count'] . "</span></td>";
        
        // Updated dropdown code
        echo "<td>";
        echo "<select class='status-dropdown' data-status='" . $row['status'] . "' data-farm-id='" . $row['farm_id'] . "'>";
        $statuses = ['pending', 'active', 'rejected'];
        foreach ($statuses as $status) {
            $selected = ($row['status'] == $status) ? "selected" : "";
            echo "<option value='$status' $selected>$status</option>";
        }
        echo "</select>";
        echo "</td>";

        echo "</tr>";
    }
    ?>
</tbody>

        </table>
       
        <!-- <footer class="footer">
            <p>© 2025 Farmfolio Admin Panel. All rights reserved.</p>
        </footer> -->
    </main>
</body>
<script>
document.querySelectorAll('.status-dropdown').forEach(select => {
    // Initial color set
    updateDropdownColor(select);

    select.addEventListener('change', function() {
        let farmId = this.getAttribute('data-farm-id');
        let newStatus = this.value;
        
        // Update the data-status attribute and color
        this.setAttribute('data-status', newStatus);
        updateDropdownColor(this);

        fetch('update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'farm_id=' + farmId + '&status=' + newStatus
        })
        .then(response => response.text())
        .then(data => alert(data))
        .catch(error => console.error('Error:', error));
    });
});

function updateDropdownColor(select) {
    select.style.backgroundColor = {
        'pending': '#3498db',
        'active': '#2ecc71',
        'rejected': '#e74c3c'
    }[select.value] || '#ddd';
}
document.querySelectorAll('.status-dropdown').forEach(select => {
    // Initial color set
    updateDropdownColor(select);

    select.addEventListener('change', function() {
        let farmId = this.getAttribute('data-farm-id');
        let newStatus = this.value;
        let previousStatus = this.getAttribute('data-status');
        
        // Confirm before rejecting
        if (newStatus === 'rejected') {
            if (!confirm('Are you sure you want to reject this farm? An email notification will be sent to the farm owner.')) {
                this.value = previousStatus;
                updateDropdownColor(this);
                return;
            }
        }

        // Show loading state
        select.disabled = true;
        select.style.opacity = '0.7';

        fetch('update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `farm_id=${farmId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                this.setAttribute('data-status', newStatus);
                updateDropdownColor(this);
            } else {
                alert('Error: ' + data.message);
                this.value = previousStatus;
                updateDropdownColor(this);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the status');
            this.value = previousStatus;
            updateDropdownColor(this);
        })
        .finally(() => {
            select.disabled = false;
            select.style.opacity = '1';
        });
    });
});
</script>
</html>
