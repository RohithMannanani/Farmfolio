<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmfolio - Delivery Boy Sign Up</title>
    <link rel="stylesheet" href="c.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="home-button-container">
        <a href="http://localhost/mini%20project/home/html5up-dimension/index.html" class="home-button">Home</a>
    </div>

    <div class="left-container">
        <h1 class="title">Delivery Boy <br>Sign Up</h1>

        <?php
        include '../databse/connect.php';
        
        $errors = [];
        $data = [];

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
            validate("email", $_POST["email"], "/^[^\s@]+@[^\s@]+\.[^\s@]+$/", "Invalid email.");
            validate("house", $_POST["house"], "/^[a-zA-Z0-9 .,-]+$/", "Invalid house name.");
            validate("pin", $_POST["pin"], "/^\d{6}$/", "Invalid PIN code.");
        
            if (empty($_POST["state"])) {
                $errors["state"] = "State is required.";
            } else {
                $data["state"] = htmlspecialchars($_POST["state"]);
            }
        
            if (empty($_POST["district"])) {
                $errors["district"] = "District is required.";
            } else {
                $data["district"] = htmlspecialchars($_POST["district"]);
            }
        
            $password = $_POST["password"];
            $confirmPassword = $_POST["confirm-password"];
            if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $password)) {
                $errors["password"] = "Password must be at least 8 characters long and include a mix of uppercase, lowercase, numbers, and special characters.";
            } elseif ($password !== $confirmPassword) {
                $errors["confirm-password"] = "Passwords do not match.";
            } else {
                $data["password"] =$password;
            }
        
            if (empty($errors)) {
                  $_SESSION['username']= $_POST["username"];
                  $_SESSION['email']= $_POST["email"];
                  $_SESSION['mobile']=$_POST["mobile"];
                  $_SESSION['house']=$_POST["house"];
                  $_SESSION['state']=$_POST["state"];
                  $_SESSION['district']=$_POST["district"];
                  $_SESSION['pin']= $_POST["pin"];
                  $_SESSION['password']= $_POST["password"];
                  $_SESSION['type'] =  1;//0-custemer,1-farm,2-delivery 4-admin
                    // Redirect to login page
                    header('Location: http://localhost/mini%20project/sign%20up/emailverify/send_reset_otp.php');
                    exit;
                
            }
        }
        ?>

        <form method="POST" action="" id="signup-form">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($data['username'] ?? '') ?>" required>
                <div class="error"><?= $errors['username'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="mobile"><i class="fas fa-phone"></i> Mobile Number</label>
                <input type="tel" id="mobile" name="mobile" value="<?= htmlspecialchars($data['mobile'] ?? '') ?>" required>
                <div class="error"><?= $errors['mobile'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
                <div class="error"><?= $errors['email'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="house"><i class="fas fa-home"></i> House Name</label>
                <input type="text" id="house" name="house" value="<?= htmlspecialchars($data['house'] ?? '') ?>" required>
                <div class="error"><?= $errors['house'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="state"><i class="fas fa-map-marker-alt"></i> State</label>
                <select id="state" name="state" required>
                    <option value="">Select State</option>
                </select>
                <div class="error"><?= $errors['state'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="district"><i class="fas fa-map"></i> District</label>
                <select id="district" name="district" required>
                    <option value="">Select District</option>
                </select>
                <div class="error"><?= $errors['district'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="pin"><i class="fas fa-map-pin"></i> PIN Code</label>
                <input type="text" id="pin" name="pin" value="<?= htmlspecialchars($data['pin'] ?? '') ?>" required>
                <div class="error"><?= $errors['pin'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required>
                <div class="error"><?= $errors['password'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="confirm-password"><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" required>
                <div class="error"><?= $errors['confirm-password'] ?? '' ?></div>
            </div>
            <button type="submit">Sign Up</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="http://localhost/Mini%20project/login/login.php">Login</a></p>
        </div>
    </div>

    <script src="validation.js">


</script>
    
</body>
</html>
