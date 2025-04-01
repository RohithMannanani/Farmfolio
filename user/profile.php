<?php
session_start();
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard - Farmfolio</title>
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
            grid-template-columns: repeat(3, minmax(240px, 1fr));
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
        #dynamic-content {
            flex: 1;
            padding: 20px;
            box-sizing: border-box;
        }
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .error-message {
            color: #dc2626;
            font-size: 0.8rem;
            margin-top: 5px;
            min-height: 20px;
        }

        input.valid {
            border-color: #22c55e;
            background-color: #f0fdf4;
        }

        input.invalid {
            border-color: #dc2626;
            background-color: #fef2f2;
        }

        .validation-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
        }

        .validation-icon.valid {
            color: #22c55e;
        }

        .validation-icon.invalid {
            color: #dc2626;
        }

        #submit-btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="userindex.php" ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse.php" ><i class="fas fa-store"></i><span>Browse Farms</span></a></li>
            <li><a href="cart.php" ><i class="fas fa-shopping-cart"></i><span>My Cart</span></a></li>
            <li><a href="orders.php" ><i class="fas fa-truck"></i><span>My Orders</span></a></li>
            <li><a href="favorite.php" ><i class="fas fa-heart"></i><span>Favorite Farms</span></a></li>
            <li><a href="events.php" ><i class="fas fa-calendar"></i><span>Farm Events</span></a></li>
            <li><a href="profile.php" class="active" ><i class="fas fa-user"></i><span>Profile</span></a></li>
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

             <!-- Stats Grid  -->
             <?php 

include '../databse/connect.php';

// Ensure user is logged in
// if (!isset($_SESSION['userid'])) {
//     die("Unauthorized access.");
// }

$userid =36;
$errors = [];
$data = [];

// Fetch existing user data
$stmt = "SELECT tbl_signup.username, tbl_signup.email, mobile, house, state, district, pin FROM tbl_signup 
         INNER JOIN tbl_login ON tbl_signup.userid = tbl_login.userid 
         WHERE tbl_signup.userid = ?";
$query = $conn->prepare($stmt);
if (!$query) {
    die("Query preparation failed: " . $conn->error);
}
$query->bind_param("i", $userid);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc() ?? [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    function validate($field, $value, $pattern, $errorMessage) {
        global $errors, $data;
        $value = trim($value);
        if (!preg_match($pattern, $value)) {
            $errors[$field] = $errorMessage;
        } else {
            $data[$field] = htmlspecialchars($value);
        }
    }

    validate("username", $_POST["username"], "/^[A-Za-z][A-Za-z\s.'-]{1,49}$/", "Invalid username.");
    validate("mobile", $_POST["mobile"], "/^[6-9]\d{9}$/", "Invalid mobile number.");
    validate("house", $_POST["house"], "/^[a-zA-Z0-9 .,-]+$/", "Invalid house name.");
    validate("pin", $_POST["pin"], "/^\d{6}$/", "Invalid PIN code.");


    if (empty($errors)) {
        // Update user data in database
        $stmt1 = "UPDATE tbl_signup 
        SET username = ?, mobile = ?, house = ?, pin = ?
        WHERE userid = ?";

$query1 = $conn->prepare($stmt1);

if (!$query1) {
   die("Query preparation failed: " . $conn->error);
}

// Bind parameters (s = string, i = integer)
$query1->bind_param("ssssi", 
   $data['username'], 
   $data['mobile'], 
   $data['house'], 
   $data['pin'],
   $userid
);

$stmt = "UPDATE tbl_login 
SET username=?
WHERE userid = ?";

$query = $conn->prepare($stmt);

if (!$query) {
die("Query preparation failed: " . $conn->error);
}

// Bind parameters (s = string, i = integer)
$query->bind_param("si", 
$data['username'], 
$userid
);

if ($query->execute()&&$query1->execute()) {
echo "Profile updated successfully!";
$_SESSION['username']=$data['username'];
} else {
echo "Error updating profile: " . $query->error;
}

}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmfolio - Edit Profile</title>
    <link rel="stylesheet" href="c.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="edit.css">
</head>
<body>
    <div class="left-container">
        <h1 class="title">Edit Profile</h1>

        <form method="POST" action="" id="edit-profile-form">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" 
                    data-pattern="^[A-Za-z][A-Za-z\s.'-]{1,49}$"
                    data-error="Username must start with a letter and can include letters, spaces, dots, hyphens and apostrophes"
                    value="<?= htmlspecialchars($_POST['username'] ?? $user['username'] ?? '') ?>" required>
                <div class="error-message"></div>
            </div>
            <div class="form-group">
                <label for="mobile"><i class="fas fa-phone"></i> Mobile Number</label>
                <input type="tel" id="mobile" name="mobile" 
                    data-pattern="^[6-9]\d{9}$"
                    data-error="Please enter a valid 10-digit mobile number starting with 6-9"
                    value="<?= htmlspecialchars($_POST['mobile'] ?? $user['mobile'] ?? '') ?>" required>
                <div class="error-message"></div>
            </div>
            <div class="form-group">
                <label for="house"><i class="fas fa-home"></i> House Name</label>
                <input type="text" id="house" name="house" 
                    data-pattern="^[a-zA-Z0-9 .,-]+$"
                    data-error="House name can only contain letters, numbers, spaces, dots, commas and hyphens"
                    value="<?= htmlspecialchars($_POST['house'] ?? $user['house'] ?? '') ?>" required>
                <div class="error-message"></div>
            </div>
            <div class="form-group">
                <label for="pin"><i class="fas fa-map-pin"></i> PIN Code</label>
                <input type="text" id="pin" name="pin" 
                    data-pattern="^\d{6}$"
                    data-error="Please enter a valid 6-digit PIN code"
                    value="<?= htmlspecialchars($_POST['pin'] ?? $user['pin'] ?? '') ?>" required>
                <div class="error-message"></div>
            </div>
            <button type="submit" id="submit-btn">Update Profile</button>
        </form>
    </div>
</body>
</html>


        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2024 Farmfolio. All rights reserved.</p>
        </div>
    </div>

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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-profile-form');
    const inputs = form.querySelectorAll('input[data-pattern]');
    const submitBtn = document.getElementById('submit-btn');
    
    // Function to validate a single input
    function validateInput(input) {
        const pattern = new RegExp(input.dataset.pattern);
        const value = input.value.trim();
        const errorDiv = input.nextElementSibling;
        const isValid = pattern.test(value);
        
        // Remove existing validation icon if any
        const existingIcon = input.parentElement.querySelector('.validation-icon');
        if (existingIcon) existingIcon.remove();
        
        // Create and add new validation icon
        const icon = document.createElement('i');
        icon.classList.add('fas', 'validation-icon');
        
        if (isValid) {
            input.classList.remove('invalid');
            input.classList.add('valid');
            errorDiv.textContent = '';
            icon.classList.add('fa-check-circle', 'valid');
        } else {
            input.classList.remove('valid');
            input.classList.add('invalid');
            errorDiv.textContent = input.dataset.error;
            icon.classList.add('fa-exclamation-circle', 'invalid');
        }
        
        input.parentElement.appendChild(icon);
        return isValid;
    }
    
    // Add validation listeners to all inputs
    inputs.forEach(input => {
        ['input', 'blur'].forEach(eventType => {
            input.addEventListener(eventType, () => {
                validateInput(input);
                
                // Check if all inputs are valid
                const allValid = Array.from(inputs).every(input => {
                    const pattern = new RegExp(input.dataset.pattern);
                    return pattern.test(input.value.trim());
                });
                
                // Enable/disable submit button
                submitBtn.disabled = !allValid;
            });
        });
    });
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const allValid = Array.from(inputs).every(input => validateInput(input));
        
        if (allValid) {
            this.submit();
        } else {
            // Show error message at the top of the form
            const errorMessage = document.createElement('div');
            errorMessage.classList.add('error-message');
            errorMessage.textContent = 'Please fix the errors before submitting.';
            form.insertBefore(errorMessage, form.firstChild);
            
            // Remove error message after 3 seconds
            setTimeout(() => errorMessage.remove(), 3000);
        }
    });
});
</script>
</body>
</html>