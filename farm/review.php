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
    <title>Farm Products</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="farm.css">
    <style>
        .product-container {
            padding: 20px;
            max-width: 1200px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .add-product-btn {
            padding: 10px 20px;
            background: #1a4d2e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-product-btn:hover {
            background: #2d6a4f;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-details {
            padding: 15px;
        }

        .product-title {
            font-size: 1.1em;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-price {
            color: #2563eb;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-stock {
            color: #666;
            font-size: 0.9em;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-btn {
            background: #1a4d2e;
            color: white;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn {
            background: #1a4d2e;
            color: white;
        }

        .cancel-btn {
            background: #666;
            color: white;
        }

        .inactive-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 70vh;
            text-align: center;
            color: #666;
        }

        .inactive-message h2 {
            font-size: 24px;
            margin-bottom: 16px;
            color: #1a4d2e;
        }

        .inactive-message p {
            font-size: 16px;
            max-width: 600px;
            line-height: 1.6;
        }

        .inactive-icon {
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
            <li><a href="product.php" ><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="image.php"><i class="fas fa-image"></i><span>Farm Images</span></a></li>
            <li><a href="event.php"><i class="fas fa-calendar"></i><span>Events</span></a></li>
            <li><a href="review.php" class="active"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="orders.php"><i class="fas fa-truck"></i><span>Orders</span></a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a></li>
        </ul>
    </nav>

    <div class="main-content">
    <div class="dashboard-header">
                <?php if(isset($row['farm_name'])&&isset($_SESSION['username'])){?>
                <h1><?php echo $row['farm_name'];?>Farm</h1>
                <div class="user-section">
                    <span>Welcome, <?php echo $_SESSION['username'];?></span>
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div>
                <?php }else{?>
                    <h1>Farm Dashboard</h1>
                <div class="user-section">
                    <span>Welcome,</span>
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div><?php }?>
            </div>
        <?php if($is_farm_active): ?>
            
                

        <?php else: ?>
            <div class="inactive-message">
                <i class="fas fa-store-slash inactive-icon"></i>
                <h2>Farm Not Active</h2>
                <p>Your farm is currently inactive. Please contact the administrator to activate your farm account before managing products.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>