<?php
session_start();
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   
    $conn = new mysqli('localhost', 'root', '', 'farmfolio');
        
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        $error_message = "An error occurred during login. Please try again later.";
    } else {
        
        $email = $conn->real_escape_string(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)); 
        $sql = "SELECT * FROM tbl_login WHERE email='$email'";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            echo'<form id="form" method="POST" action="send_reset_otp.php">';
            echo '<input type="hidden" name="email" value="' . $email . '">';
            echo'</form>';
            echo'<script>document.getElementById("form").submit();</script>';

        } else {
            $error_message = "Invalid email";
        }
        
               $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmFolio - Forgot Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e0ffe0 0%, #f7fdf7 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.5s ease-out;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .task { color: #34a853;}
        .mate { color:  #34a853; }

        h2 {
            text-align: center;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        p.description {
            text-align: center;
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color:rgb(10, 158, 59);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background:rgb(7, 133, 32);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background:rgb(3, 77, 20);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
            color: #64748b;
        }

        .back-to-login a {
            color:rgb(5, 81, 33);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-to-login a:hover {
            color:rgb(4, 125, 37);
        }
        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <span class="task">Farm</span><span class="mate">Folio</span>
        </div>
        <h2>Forgot Password</h2>
        <p class="description">Enter your email to receive an otp.</p>
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="login-btn">Send An OTP</button>
            <p class="back-to-login">Remember your password? <a href="signin.php">Login here</a></p>
        </form>
    </div>
</body>
</html>