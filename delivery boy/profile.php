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
        
        // Add JavaScript to reload the page after showing the success message
        echo "<script>
            setTimeout(function() {
                window.location.href = 'profile.php';
            }, 1500);
        </script>";
        $_SESSION['username'] = $username;
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Available Orders - Delivery Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1a4d2e;
            --primary-dark: #15402a;
            --primary-light: #2d6a4f;
            --accent: #4ade80;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1f2937;
            --medium: #4b5563;
            --light: #9ca3af;
            --lighter: #e5e7eb;
            --lightest: #f9fafb;
            --white: #ffffff;
            
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 10px 15px rgba(0,0,0,0.07), 0 4px 6px rgba(0,0,0,0.05);
            --shadow-lg: 0 20px 25px rgba(0,0,0,0.1), 0 10px 10px rgba(0,0,0,0.04);
            
            --radius-sm: 4px;
            --radius: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f3f4f6;
            color: var(--dark);
            line-height: 1.5;
        }

        /* Header Styles */
        .header {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 70px;
            background: var(--white);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
            z-index: 100;
            transition: left 0.3s;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            box-shadow: var(--shadow);
            font-size: 1.2rem;
        }

        .logo-section h2 {
            font-weight: 600;
            color: var(--primary);
            letter-spacing: 0.5px;
            font-size: 1.4rem;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .user-section span {
            color: var(--medium);
            font-weight: 500;
        }

        .logout-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 80px 20px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: width 0.3s;
            box-shadow: var(--shadow-md);
            z-index: 90;
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
            margin-top: 20px;
        }

        .sidebar-menu li {
            margin: 8px 0;
        }

        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: var(--radius);
            transition: var(--transition);
            font-weight: 500;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .active {
            background: rgba(255, 255, 255, 0.15) !important;
            color: white !important;
            font-weight: 600 !important;
            box-shadow: var(--shadow-sm);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding-top: 70px;
            transition: margin-left 0.3s;
            min-height: 100vh;
        }

        .content-area {
            padding: 30px;
        }

        /* Profile Styles */
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            z-index: 1;
        }

        .profile-container h1 {
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--lighter);
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-container h1::before {
            content: '\f007';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            font-size: 1.4rem;
            color: var(--primary);
            background: rgba(26, 77, 46, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 5px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--medium);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--lighter);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
            padding-right: 40px;
            background-color: var(--lightest);
        }

        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 77, 46, 0.1);
            background-color: var(--white);
        }

        .form-group.valid input {
            border-color: var(--success);
            background-color: rgba(34, 197, 94, 0.05);
        }

        .form-group.invalid input {
            border-color: var(--danger);
            background-color: rgba(239, 68, 68, 0.05);
        }

        .validation-message {
            display: block;
            font-size: 0.8rem;
            margin-top: 6px;
            min-height: 20px;
            color: var(--danger);
            font-weight: 500;
            transition: opacity 0.3s ease, color 0.3s ease;
        }

        .validation-message.success {
            color: var(--success);
        }
        
        .validation-message.warning {
            color: var(--warning);
        }

        .validation-icon {
            position: absolute;
            right: 15px;
            top: 42px;
            font-size: 1.1rem;
        }

        .form-group.valid .validation-icon {
            color: var(--success);
        }

        .form-group.invalid .validation-icon {
            color: var(--danger);
        }

        .form-actions {
            text-align: right;
            padding-top: 20px;
            border-top: 1px solid var(--lighter);
            display: flex;
            justify-content: flex-end;
        }

        .update-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 160px;
            position: relative;
            overflow: hidden;
        }

        .update-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.2) 50%, 
                rgba(255, 255, 255, 0) 100%
            );
            transition: left 0.7s ease;
        }

        .update-btn.enabled:hover::before {
            left: 100%;
        }

        .update-btn.enabled:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .update-btn:disabled {
            background: var(--light);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .alert {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert::before {
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            font-size: 1.2rem;
        }

        .alert.success {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert.success::before {
            content: '\f058'; /* check-circle */
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert.error::before {
            content: '\f057'; /* times-circle */
        }

        .readonly-input {
            background-color: #f3f4f6;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .hint {
            display: block;
            font-size: 0.8rem;
            color: var(--medium);
            margin-top: 5px;
            font-style: italic;
        }

        .form-group.focused input {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 77, 46, 0.1);
            background-color: white;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--medium);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group.focused label {
            color: var(--primary);
        }

        .form-group.focused label i {
            color: var(--primary);
        }

        .form-group label i {
            color: var(--medium);
            font-size: 0.9rem;
            width: 16px;
            text-align: center;
        }

        /* PIN Code validation specific styles */
        .form-group#pin-group .validation-icon.fa-circle-notch {
            color: var(--warning);
        }
        
        /* Loading animation for PIN verification */
        @keyframes pinVerifying {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .form-group.verifying input {
            background-size: 200% 200%;
            animation: pinVerifying 2s ease infinite;
            background-image: linear-gradient(270deg, var(--lightest), rgba(79, 173, 128, 0.1), var(--lightest));
            border-color: var(--warning);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .sidebar-menu span {
                display: none;
            }
            
            .sidebar .sidebar-menu i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .header {
                left: 70px;
            }
            
            .content-area {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .user-section span {
                display: none;
            }
            
            .profile-container {
                padding: 20px;
            }
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
                        <label for="username"><i class="fas fa-user-circle"></i> Username</label>
                        <input type="text" id="username" name="username" 
                            data-pattern="^[A-Za-z][A-Za-z\s]{1,49}$"
                            data-error="Username must start with a letter and contain only letters and spaces"
                            value="<?php echo htmlspecialchars($profile['username']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="mobile"><i class="fas fa-mobile-alt"></i> Mobile Number</label>
                        <input type="tel" id="mobile" name="mobile" 
                            data-pattern="^[6-9]\d{9}$"
                            data-error="Please enter a valid 10-digit mobile number starting with 6-9"
                            value="<?php echo htmlspecialchars($profile['mobile']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" 
                            value="<?php echo htmlspecialchars($profile['email']); ?>" 
                            readonly 
                            class="readonly-input">
                        <small class="hint">Email cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="house"><i class="fas fa-home"></i> House Name/Number</label>
                        <input type="text" id="house" name="house" 
                            data-pattern="^[a-zA-Z0-9\s,.-]{3,}$"
                            data-error="House name must be at least 3 characters long"
                            value="<?php echo htmlspecialchars($profile['house']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="state"><i class="fas fa-map"></i> State</label>
                        <input type="text" id="state" name="state" 
                            data-pattern="^[A-Za-z\s]{3,}$"
                            data-error="State name must contain only letters and spaces"
                            value="<?php echo htmlspecialchars($profile['state']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="district"><i class="fas fa-city"></i> District</label>
                        <input type="text" id="district" name="district" 
                            data-pattern="^[A-Za-z\s]{3,}$"
                            data-error="District name must contain only letters and spaces"
                            value="<?php echo htmlspecialchars($profile['district']); ?>" required>
                        <span class="validation-message"></span>
                    </div>

                    <div class="form-group" id="pin-group">
                        <label for="pin"><i class="fas fa-map-pin"></i> PIN Code</label>
                        <input type="text" id="pin" name="pin" 
                            data-pattern="^\d{6}$"
                            data-error="Please enter a valid 6-digit PIN code"
                            value="<?php echo htmlspecialchars($profile['pin']); ?>" required>
                        <span class="validation-message">We'll check if delivery is available in this area</span>
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
    const pinInput = document.getElementById('pin');
    let validPincodes = [];
    
    // Add loading indicator to update button
    submitBtn.innerHTML = '<span>Update Profile</span>';
    
    // Fetch valid pincodes from JSON file
    fetch('../sign up/pincode.json')
        .then(response => response.json())
        .then(data => {
            validPincodes = data.pincodes.map(String);
            console.log(`Loaded ${validPincodes.length} valid PIN codes`);
            // Validate PIN input if it already has a value
            if (pinInput.value.trim()) {
                validatePincode(pinInput);
            }
        })
        .catch(error => {
            console.error('Error loading PIN codes:', error);
        });
    
    function validatePincode(input) {
        const value = input.value.trim();
        const formGroup = input.closest('.form-group');
        const messageEl = formGroup.querySelector('.validation-message');
        
        // Remove existing validation icon if any
        const existingIcon = formGroup.querySelector('.validation-icon');
        if (existingIcon) existingIcon.remove();
        
        // Create new icon
        const icon = document.createElement('i');
        icon.classList.add('fas', 'validation-icon');
        
        // First check format using regex
        const patternValid = new RegExp(input.dataset.pattern).test(value);
        
        if (!patternValid) {
            formGroup.classList.remove('valid', 'verifying');
            formGroup.classList.add('invalid');
            messageEl.textContent = input.dataset.error;
            messageEl.style.opacity = '1';
            messageEl.className = 'validation-message';
            icon.classList.add('fa-exclamation-circle');
            formGroup.appendChild(icon);
            return false;
        }
        
        // If pincodes haven't loaded yet, show verifying state
        if (validPincodes.length === 0) {
            formGroup.classList.remove('valid', 'invalid');
            formGroup.classList.add('verifying');
            messageEl.textContent = "Checking PIN code availability...";
            messageEl.style.opacity = '1';
            messageEl.className = 'validation-message warning';
            icon.classList.add('fa-circle-notch', 'fa-spin');
            formGroup.appendChild(icon);
            return true;
        }
        
        // Check if it's in the valid pincodes list
        if (validPincodes.includes(value)) {
            formGroup.classList.remove('invalid', 'verifying');
            formGroup.classList.add('valid');
            messageEl.textContent = " ";
            messageEl.style.opacity = '1';
            messageEl.className = 'validation-message success';
            icon.classList.add('fa-check-circle');
            formGroup.appendChild(icon);
            return true;
        } else {
            formGroup.classList.remove('valid', 'verifying');
            formGroup.classList.add('invalid');
            messageEl.textContent = "Sorry, delivery is not available in this area";
            messageEl.style.opacity = '1';
            messageEl.className = 'validation-message';
            icon.classList.add('fa-exclamation-circle');
            formGroup.appendChild(icon);
            return false;
        }
    }
    
    function validateInput(input) {
        // Special handling for PIN code
        if (input.id === 'pin') {
            return validatePincode(input);
        }
        
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
            messageEl.style.opacity = '0';
            icon.classList.add('fa-check-circle');
            formGroup.appendChild(icon);
            return true;
        } else {
            formGroup.classList.remove('valid');
            formGroup.classList.add('invalid');
            messageEl.textContent = input.dataset.error;
            messageEl.style.opacity = '1';
            icon.classList.add('fa-exclamation-circle');
            formGroup.appendChild(icon);
            return false;
        }
    }

    function checkFormValidity() {
        const isValid = Array.from(inputs).every(input => validateInput(input));
        submitBtn.disabled = !isValid;
        
        // Add visual feedback on button
        if (isValid) {
            submitBtn.classList.add('enabled');
        } else {
            submitBtn.classList.remove('enabled');
        }
        
        return isValid;
    }

    // Add transition animations to input fields
    inputs.forEach(input => {
        // Add focus animation
        input.addEventListener('focus', () => {
            input.closest('.form-group').classList.add('focused');
        });
        
        input.addEventListener('blur', () => {
            input.closest('.form-group').classList.remove('focused');
        });
        
        // Add validation on input and blur
        ['input', 'blur'].forEach(eventType => {
            input.addEventListener(eventType, () => {
                validateInput(input);
                checkFormValidity();
            });
        });
    });

    // Validate all fields on initial load
    setTimeout(() => {
        checkFormValidity();
    }, 100);

    // Form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (checkFormValidity()) {
            // Add loading indicator
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Updating...</span>';
            submitBtn.disabled = true;
            
            // Submit the form after a small delay to show the loading state
            setTimeout(() => {
                this.submit();
            }, 400);
        } else {
            // Show error message at the top of the form
            const errorDiv = document.createElement('div');
            errorDiv.classList.add('alert', 'error');
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please fix all validation errors before submitting.';
            
            // Only add if no error message already exists
            if (!document.querySelector('.alert.error')) {
                form.insertBefore(errorDiv, form.firstChild);
                
                // Remove error message after 3 seconds with fade out
                setTimeout(() => {
                    errorDiv.style.opacity = '0';
                    setTimeout(() => errorDiv.remove(), 300);
                }, 3000);
            }
            
            // Scroll to first invalid input
            const firstInvalid = form.querySelector('.form-group.invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.querySelector('input').focus();
            }
        }
    });
});
</script>
</body>
</html>
