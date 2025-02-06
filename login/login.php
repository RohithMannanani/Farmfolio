<!DOCTYPE html>
<html>
<head>
    <title>Farmfolio Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
</head>
<body>
<?php
session_start();
include "../databse/connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        echo "Email and password are required.";
        exit;
    }

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM tbl_login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        var_dump($row);
        $demail =trim( $row['email']);
        $dpassword = trim($row['password']);
        $type = trim($row['type']);
        $username = trim($row['username']);
         var_dump($username);

        // Verify the password
        if ($email === $demail && $password === $dpassword) {
            $_SESSION['username']="$username";
            // Redirect based on user type
            switch ($type) {
                case 0 :
                    header('Location: http://localhost/Mini%20project/user/html5up-massively/elements.html');
                    break;
                case 2:
                    header('Location: http://localhost/Mini%20project/delivery%20boy/delivery.php');
                    break;
                case 1 :
                    header('Location: http://localhost/Mini%20project/farm/farm.html');
                    break;
                case 4 :
                    header('Location: http://localhost/Mini%20project/admin/admin.html');
                    break;
                default:
                    echo "Invalid user type.";
                    exit;
            }
            exit; // Always exit after a redirect
        } else {
            echo "Incorrect email or password.";
            exit;
        }
    } 

 }
?>


    <div class="background-overlay"></div>
    <div class="home-button-container">
        <a href="/mini%20project/home/html5up-dimension/index.html" class="home-button">Home</a>
    </div>
    
    <div class="container">
        <div class="login-container">
            <h1 class="title">Login</h1>
            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="forgot-password">
                    <a href="../forget password/reading mail.php">Forgot Password?</a>
                </div>

                <button type="submit">Login</button>

                <div class="register-link">
                    Don't have an account? <a href="#" id="register-btn">Register</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Pop-up -->
    <div id="popup-overlay"></div>
    <div id="popup">
        <h2>REGISTER AS</h2>
        <div class="btn">
            <img src="../img/logs/farmer.png" alt="farmer">
            <a href="/Mini%20project/sign%20up/register_farm_owner.php"><button>FARM OWNER</button></a>
            <img src="../img/logs/customer.png" alt="customer">
            <a href="/Mini%20project/sign%20up/register_customer.php"><button>CUSTOMER</button></a>
            <img src="../img/logs/delivery.png" alt="delivery">
            <a href="/Mini%20project/sign%20up/register_delivery_boy.php"><button>DELIVERY BOY</button></a>
        </div>
    </div>
    <script>
    // Live validation for email
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('input', () => {
        const emailError = document.createElement('div');
        emailError.classList.add('error-message');
        emailError.style.color = 'red';

        // Remove any previous error message
        if (emailInput.nextSibling) {
            emailInput.parentNode.removeChild(emailInput.nextSibling);
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailInput.value)) {
            emailError.textContent = 'Please enter a valid email address.';
            emailInput.parentNode.appendChild(emailError);
        }
    });

    // Live validation for password
    const passwordInput = document.getElementById('password');
    passwordInput.addEventListener('input', () => {
        const passwordError = document.createElement('div');
        passwordError.classList.add('error-message');
        passwordError.style.color = 'red';

        // Remove any previous error message
        if (passwordInput.nextSibling) {
            passwordInput.parentNode.removeChild(passwordInput.nextSibling);
        }

        const password = passwordInput.value;
        if (password.length < 8) {
            passwordError.textContent = 'Password must be at least 8 characters long.';
        } else if (!/[A-Z]/.test(password)) {
            passwordError.textContent = 'Password must contain at least one uppercase letter.';
        } else if (!/[a-z]/.test(password)) {
            passwordError.textContent = 'Password must contain at least one lowercase letter.';
        } else if (!/[0-9]/.test(password)) {
            passwordError.textContent = 'Password must contain at least one number.';
        } else if (!/[@$!%*?&]/.test(password)) {
            passwordError.textContent = 'Password must contain at least one special character.';
        }

        if (passwordError.textContent) {
            passwordInput.parentNode.appendChild(passwordError);
        }
    });
</script>
    <script>
        // JavaScript for pop-up
        document.getElementById('register-btn').addEventListener('click', function () {
            document.getElementById('popup').style.display = 'block';
            document.getElementById('popup-overlay').style.display = 'block';
        });

        document.getElementById('popup-overlay').addEventListener('click', function () {
            document.getElementById('popup').style.display = 'none';
            document.getElementById('popup-overlay').style.display = 'none';
        });
    </script>
</body>
</html>
