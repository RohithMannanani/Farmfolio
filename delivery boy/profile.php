<?php
session_start();
include "../databse/connect.php";

if(!isset($_SESSION['username'])){
    header("location: ../login/login.php");
}

// Get delivery boy ID from session
$delivery_boy_id = $_SESSION['userid'];
$query = "SELECT * FROM tbl_signup WHERE userid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $delivery_boy_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $house = mysqli_real_escape_string($conn, trim($_POST['house']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $district = mysqli_real_escape_string($conn, trim($_POST['district']));
    $pin = mysqli_real_escape_string($conn, trim($_POST['pin']));

    $update_query = "UPDATE tbl_signup SET 
                    username = ?, 
                    mobile = ?, 
                    house = ?, 
                    state = ?, 
                    district = ?, 
                    pin = ? 
                    WHERE userid = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssssi", $username, $mobile, $house, $state, $district, $pin, $delivery_boy_id);
    
    if ($stmt->execute()) {
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}

// Get today's deliveries count
$today_query = "SELECT 
    COUNT(*) as total_deliveries,
    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_deliveries
FROM tbl_orders 
WHERE DATE(order_date) = CURDATE()";
$today_result = mysqli_query($conn, $today_query);
$today_stats = mysqli_fetch_assoc($today_result);

// Get weekly earnings (assuming there's a delivery charge column or using a percentage of total_amount)
$earnings_query = "SELECT COALESCE(SUM(total_amount * 0.1), 0) as weekly_earnings
FROM tbl_orders 
WHERE order_status = 'delivered'
AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$earnings_result = mysqli_query($conn, $earnings_query);
$earnings = mysqli_fetch_assoc($earnings_result);



// Get active orders
$active_orders_query = "SELECT COUNT(*) as active_count
FROM tbl_orders 
WHERE order_status IN ('processing', 'shipped')";
$active_result = mysqli_query($conn, $active_orders_query);
$active_orders = mysqli_fetch_assoc($active_result);

// Get current deliveries
$current_deliveries_query = "SELECT 
    o.order_id,
    o.delivery_address,
    o.order_status,
    o.total_amount,
    o.phone_number,
    o.order_date,
    o.payment_method,
    o.payment_status,
    u.username as customer_name,
    f.farm_name,
    f.location as farm_location
FROM tbl_orders o
JOIN tbl_signup u ON o.user_id = u.userid
JOIN tbl_order_items oi ON o.order_id = oi.order_id
JOIN tbl_products p ON oi.product_id = p.product_id
JOIN tbl_farms f ON p.farm_id = f.farm_id
WHERE o.order_status IN ('processing', 'shipped', 'delivered')
GROUP BY o.order_id
ORDER BY o.order_date DESC
LIMIT 5";
$current_deliveries_result = mysqli_query($conn, $current_deliveries_query);

// Fetch available orders (pending orders)
$orders_query = "SELECT 
    o.order_id,
    o.delivery_address,
    o.order_status,
    o.total_amount,
    o.phone_number,
    o.order_date,
    o.payment_method,
    o.payment_status,
    u.username as customer_name,
    GROUP_CONCAT(DISTINCT CONCAT(p.product_name, ' (', oi.quantity, ' ', p.unit, ')')) as order_items,
    GROUP_CONCAT(DISTINCT f.farm_name) as farms,
    GROUP_CONCAT(DISTINCT f.location) as farm_locations
FROM tbl_orders o
JOIN tbl_signup u ON o.user_id = u.userid
JOIN tbl_order_items oi ON o.order_id = oi.order_id
JOIN tbl_products p ON oi.product_id = p.product_id
JOIN tbl_farms f ON p.farm_id = f.farm_id
WHERE o.order_status = 'pending'
GROUP BY o.order_id
ORDER BY o.order_date DESC";

$orders_result = mysqli_query($conn, $orders_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Orders - Delivery Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    display: flex;
    min-height: 100vh;
}

/* Header Styles */
.header {
    position: fixed;
    top: 0;
    right: 0;
    left: 250px;
    height: 60px;
    background: #ffffff;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 100;
    transition: left 0.3s;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo {
    width: 40px;
    height: 40px;
    background: #1a4d2e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}


.user-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

.logout-btn {
    padding: 8px 16px;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
}

.logout-btn:hover {
    background: #b91c1c;
}

/* Sidebar Styles */
.sidebar {
    width: 250px;
    background: #1a4d2e;
    color: white;
    padding: 80px 20px 20px;
    position: fixed;
    height: 100vh;
    left: 0;
    top: 0;
    transition: width 0.3s;
}



.sidebar .menu-btn {
    position: absolute;
    top: 20px;
    left: 20px;
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}

.sidebar-menu {
    list-style: none;
}

.sidebar-menu li {
    margin: 5px 0;
}

.sidebar-menu a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 12px;
    border-radius: 5px;
    transition: background 0.3s;
}

.sidebar-menu a:hover {
    background: #2d6a4f;
}

.sidebar-menu i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}


.active {
    background: #2d6a4f;
}

/* Main Content Styles */
.main-content {
    margin-left: 250px;
    flex: 1;
    padding-top: 60px;
    background: #f3f4f6;
    transition: margin-left 0.3s;
}



content-area {
    padding: 20px;
    min-height: 100vh;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.stat-card .value {
    font-size: 1.8em;
    font-weight: bold;
    color: #1a4d2e;
}

.delivery-list {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.delivery-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.delivery-item:last-child {
    border-bottom: none;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: 500;
}

.status-pending {
    background-color: #fee2e2;
    color: #991b1b;
}

.status-processing {
    background-color: #fef3c7;
    color: #92400e;
}

.status-shipped {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-delivered {
    background-color: #dcfce7;
    color: #166534;
}

/* Footer Styles */
.footer {
    background: #1a4d2e;
    color: white;
    padding: 20px;
    text-align: center;
}

.footer-items {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 10px;
}

.footer-items a {
    color: white;
    text-decoration: none;
}
        /* Add these styles to your existing CSS */
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-processing {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-shipped {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-delivered {
            background-color: #dcfce7;
            color: #166534;
        }

        .delivery-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .delivery-info {
            flex: 1;
        }

        .delivery-info h3 {
            margin-bottom: 5px;
            color: #1a4d2e;
        }

        .delivery-meta {
            display: grid;
            gap: 8px;
            margin-top: 10px;
        }

        .delivery-meta p {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .delivery-meta i {
            width: 16px;
            color: #1a4d2e;
        }

        .delivery-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .pickup-btn {
            background-color: #1a4d2e;
            color: white;
        }

        .complete-btn {
            background-color: #15803d;
            color: white;
        }

        .order-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-size: 1.2em;
            font-weight: bold;
            color: #1a4d2e;
        }

        .order-date {
            color: #666;
            font-size: 0.9em;
        }

        .order-details {
            display: grid;
            gap: 10px;
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            align-items: start;
            gap: 10px;
        }

        .detail-row i {
            color: #1a4d2e;
            width: 20px;
            margin-top: 3px;
        }

        .detail-content {
            flex: 1;
        }

        .order-items {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .accept-btn, .reject-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }

        .accept-btn {
            background: #1a4d2e;
            color: white;
        }

        .reject-btn {
            background: #dc2626;
            color: white;
        }

        .accept-btn:hover, .reject-btn:hover {
            transform: translateY(-2px);
        }

        .payment-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .payment-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-paid {
            background: #dcfce7;
            color: #166534;
        }

        .payment-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .profile-container h1 {
            color: #1a4d2e;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #374151;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            padding-right: 35px;
        }

        .form-group input:focus {
            border-color: #1a4d2e;
            outline: none;
            box-shadow: 0 0 0 2px rgba(26, 77, 46, 0.1);
        }

        .form-group.valid input {
            border-color: #22c55e;
            background-color: #f0fdf4;
        }

        .form-group.invalid input {
            border-color: #dc2626;
            background-color: #fef2f2;
        }

        .validation-message {
            display: block;
            font-size: 0.8rem;
            margin-top: 5px;
            min-height: 20px;
            color: #dc2626;
        }

        .validation-icon {
            position: absolute;
            right: 10px;
            top: 38px;
            font-size: 1rem;
        }

        .form-group.valid .validation-icon {
            color: #22c55e;
        }

        .form-group.invalid .validation-icon {
            color: #dc2626;
        }

        .form-actions {
            text-align: right;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .update-btn {
            padding: 10px 20px;
            background: #1a4d2e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .update-btn:hover {
            background: #2d6a4f;
        }

        .update-btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #6ee7b7;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        input:invalid {
            border-color: #dc2626;
        }

        .readonly-input {
            background-color: #f3f4f6;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .hint {
            display: block;
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        
        <ul class="sidebar-menu">
            <li><a href="delivery.php" ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="assign.php"><i class="fas fa-truck"></i><span>Assigned Deliveries</span></a></li>
            <li><a href="history.php"><i class="fas fa-history"></i><span>Delivery History</span></a></li>
            <li><a href="earning.php"><i class="fas fa-wallet"></i><span>Earnings</span></a></li>
            <li><a href="profile.php"class="active"><i class="fas fa-user"></i><span>Profile</span></a></li> 
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
            <button class="logout-btn" onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'"><i class="fas fa-sign-out-alt"  ></i> Logout</button>
        </div>
    </header>

    <main class="main-content">
    <div class="content-area">
        <div class="profile-container">
            <h1>Profile Settings</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" class="profile-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" 
                            data-pattern="^[A-Za-z][A-Za-z\s]{1,49}$"
                            data-error="Username must start with a letter and can contain only letters and spaces"
                            value="<?php echo htmlspecialchars($profile['username']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number</label>
                        <input type="tel" id="mobile" name="mobile" 
                            data-pattern="^[6-9]\d{9}$"
                            data-error="Please enter a valid 10-digit mobile number starting with 6-9"
                            value="<?php echo htmlspecialchars($profile['mobile']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                            value="<?php echo htmlspecialchars($profile['email']); ?>" 
                            readonly 
                            class="readonly-input">
                        <small class="hint">Email cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="house">House Name/Number</label>
                        <input type="text" id="house" name="house" 
                            data-pattern="^[a-zA-Z0-9\s,.-]{3,}$"
                            data-error="House name must be at least 3 characters long and can contain letters, numbers, spaces, commas, dots and hyphens"
                            value="<?php echo htmlspecialchars($profile['house']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" 
                            data-pattern="^[A-Za-z\s]{3,}$"
                            data-error="State name must contain only letters and spaces"
                            value="<?php echo htmlspecialchars($profile['state']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="district">District</label>
                        <input type="text" id="district" name="district" 
                            data-pattern="^[A-Za-z\s]{3,}$"
                            data-error="District name must contain only letters and spaces"
                            value="<?php echo htmlspecialchars($profile['district']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="pin">PIN Code</label>
                        <input type="text" id="pin" name="pin" 
                            data-pattern="^[0-9]{6}$"
                            data-error="Please enter a valid 6-digit PIN code"
                            value="<?php echo htmlspecialchars($profile['pin']); ?>" required>
                        <span class="validation-message"></span>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="update-btn">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</main>
    </main>

  

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.profile-form');
    const submitBtn = document.querySelector('.update-btn');
    const inputs = form.querySelectorAll('input[data-pattern]');

    function validateInput(input) {
        const pattern = new RegExp(input.dataset.pattern);
        const value = input.value.trim();
        const formGroup = input.closest('.form-group');
        const messageEl = formGroup.querySelector('.validation-message');
        
        // Remove existing validation icon if any
        const existingIcon = formGroup.querySelector('.validation-icon');
        if (existingIcon) existingIcon.remove();
        
        // Create new icon
        const icon = document.createElement('i');
        icon.classList.add('fas', 'validation-icon');
        
        if (pattern.test(value)) {
            formGroup.classList.remove('invalid');
            formGroup.classList.add('valid');
            messageEl.textContent = '';
            icon.classList.add('fa-check-circle');
            formGroup.appendChild(icon);
            return true;
        } else {
            formGroup.classList.remove('valid');
            formGroup.classList.add('invalid');
            messageEl.textContent = input.dataset.error;
            icon.classList.add('fa-exclamation-circle');
            formGroup.appendChild(icon);
            return false;
        }
    }

    function checkFormValidity() {
        const isValid = Array.from(inputs).every(input => validateInput(input));
        submitBtn.disabled = !isValid;
        return isValid;
    }

    // Add validation listeners to all inputs
    inputs.forEach(input => {
        ['input', 'blur'].forEach(eventType => {
            input.addEventListener(eventType, () => {
                validateInput(input);
                checkFormValidity();
            });
        });
    });

    // Validate all fields on initial load
    checkFormValidity();

    // Form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (checkFormValidity()) {
            this.submit();
        } else {
            // Show error message at the top of the form
            const errorDiv = document.createElement('div');
            errorDiv.classList.add('alert', 'error');
            errorDiv.textContent = 'Please fix all validation errors before submitting.';
            form.insertBefore(errorDiv, form.firstChild);
            
            // Remove error message after 3 seconds
            setTimeout(() => errorDiv.remove(), 3000);
            
            // Scroll to first invalid input
            const firstInvalid = form.querySelector('.form-group.invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});
</script>
</body>
</html>
