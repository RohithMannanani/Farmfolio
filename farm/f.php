<?php 
session_start();
include '../databse/connect.php';

if(isset($_POST['submit'])) {
    $farm_name = mysqli_real_escape_string($conn, $_POST['farm_name']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $user_id = $_SESSION['userid'];
    
    // Insert into tbl_farms
    $insert_query = "INSERT INTO tbl_farms (user_id, farm_name, location, description, created_at, status) 
                     VALUES ('$user_id', '$farm_name', '$location', '$description', NOW(), 'pending')";
    
    if(mysqli_query($conn, $insert_query)) {
        $_SESSION['success_message'] = "Farm registered successfully!";
        header("Location: http://localhost/mini%20project/farm/farm.php");
        exit();
    } else {
        echo "<script>alert('Error registering farm: " . mysqli_error($conn) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Farm Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="farm.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0px; background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        textarea { height: 100px; resize: vertical; }
        .error { color: #dc3545; font-size: 14px; margin-top: 5px; display: none; }
        button { background-color: rgb(16, 159, 61); color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
        button:hover { background-color: rgb(4, 158, 63); }
        .success-message { color: #28a745; text-align: center; margin-top: 20px; display: <?= isset($_SESSION['success_message']) ? 'block' : 'none'; ?>; }
        .main-content {
    margin-left: 250px;
    flex: 1;
    flex-direction: column;
    min-height: 100vh;
}
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2>Farmfolio</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="#"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="#"><i class="fas fa-cart-plus"></i><span>Add Product</span></a></li>
            <li><a href="#"><i class="fas fa-calendar"></i><span>Events</span></a></li>
            <li><a href="#"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="#"><i class="fas fa-truck"></i><span>Orders</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            <li><a href="#"><i class="fas fa-info-circle"></i><span>About</span></a></li>
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
    <div class="container">
    
        <b><p style="color: red; font-size:20px; margin-bottom:25px;">*kindly please complete this form before accesing this site</p></b>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <form id="farmForm" action="" method="POST">
            <div class="form-group">
                <label for="farm_name">Farm Name*</label>
                <input type="text" id="farm_name" name="farm_name" required>
                <div class="error" id="farm_name_error"></div>
            </div>

            <div class="form-group">
                <label for="location">Location*</label>
                <input type="text" id="location" name="location" required>
                <div class="error" id="location_error"></div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
                <div class="error" id="description_error"></div>
            </div>

            <button type="submit" name="submit">Register Farm</button>
        </form>
    </div>


        <footer class="footer">
            <p>© 2025 Farmfolio. All rights reserved.</p>
            <p style="margin-top: 5px; font-size: 0.9em;">Connecting Farms to Communities</p>
        </footer>
    </div>

    <script>
        document.getElementById('farmForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault(); // Prevent form submission if validation fails
            }
        });

        function validateForm() {
            let isValid = true;

            // Farm Name validation
            const farmName = document.getElementById('farm_name').value.trim();
            if (farmName.length === 0) {
                showError('farm_name_error', 'Farm name is required');
                isValid = false;
            } else if (farmName.length > 255) {
                showError('farm_name_error', 'Farm name must be less than 255 characters');
                isValid = false;
            } else {
                hideError('farm_name_error');
            }

            // Location validation
            const location = document.getElementById('location').value.trim();
            if (location.length === 0) {
                showError('location_error', 'Location is required');
                isValid = false;
            } else if (location.length > 255) {
                showError('location_error', 'Location must be less than 255 characters');
                isValid = false;
            } else {
                hideError('location_error');
            }

            return isValid;
        }

        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }

        function hideError(elementId) {
            document.getElementById(elementId).style.display = 'none';
        }
    </script>
    <script src="farm.js"></script>
</body>
</html>
