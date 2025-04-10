<!DOCTYPE html>
<html>
<head>
    <title>Farmfolio Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('farm.jpeg');
            background-size: 100%;
            background-repeat: no-repeat;
            background-position: center;
            background-color: #f0fdf4;
        }

        .home-button-container {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }

        .home-button {
            padding: 0.8rem 1.2rem;
            background: #0b5d2c;
            color: white;
            font-size: 1.1rem;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .home-button:hover {
            background: #2d6a4f;
        }

        .background-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }

        .container {
            position: relative;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin-top: 8vh;
        }

        .login-container {
            background: rgba(21, 21, 21, 0.8);
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            color: #e1eaf8;
        }

        .title {
            font-size: 2.2rem;
            font-weight: bold;
            color: #00ff66;
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 45px;
            color: #6b7280;
            font-size: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.6rem;
            color: #e1eaf8;
            font-size: 1.1rem;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem 0.8rem 0.8rem 2.8rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1.1rem;
            background-color: rgba(255, 255, 255, 0.9);
        }

        input:focus {
            border-color: #00ff66;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 255, 102, 0.2);
        }

        .error {
            color: #dc2626;
            font-size: 0.9rem;
            margin-top: 0.3rem;
            display: none;
        }

        button {
            width: 100%;
            padding: 1rem;
            background: #0b5d2c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #2d6a4f;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 1.8rem;
        }

        .forgot-password a {
            color: #00ff66;
            text-decoration: none;
            font-size: 1rem;
        }

        .register-link {
            margin-top: 1.8rem;
            text-align: center;
            color: #6b7280;
            font-size: 1rem;
        }

        .register-link a {
            color: #00ff66;
            text-decoration: none;
            font-weight: 500;
        }
/* Pop-up styles */
#popup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 500px;
    height: 500px;
    background-color: rgb(0, 0, 0);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    padding: 20px;
    z-index: 1000;
    color: #1b1f22;
}
#popup h2 {
    margin: 0 0 20px;
    font-size: 18px;
    text-align: center;
    color: #ffffff;
}
#popup button {
    width: 100%;
    padding: 5px;
    margin-top: 0px ;
    border: none;
    background-color: #4CAF50;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    box-shadow: none;
    font-weight: 600;

}
#popup button:hover {
    background-color: #45a049;
}
#popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}
.btn{
    justify-content: center;
    text-align: center;
}
.btn img{
    height: 50px;
    width: 50px;
    margin-top: 10px;
    margin-bottom: 10px;
}

@media (max-width: 480px) {
    .login-container {
        margin: 1rem;
        padding: 1.5rem;
    }
}
    </style>
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
                    header('Location: ../user/userindex.php');
                    break;
                case 2:
                    header('Location: ../delivery%20boy/delivery.php');
                    break;
                case 1 :
                    header('Location: ../farm/farm.php');
                    break;
                case 4 :
                    header('Location: ../admin/admin.php');
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
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="forgot-password">
                    <a href="forget password/reading mail.php">Forgot Password?</a>
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
