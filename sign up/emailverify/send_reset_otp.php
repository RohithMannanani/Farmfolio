<?php
session_start();
include 'connect.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

function generateVerificationCode($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function sendVerificationEmail($recipientEmail, $verificationCode) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'farmfoliomini@gmail.com';
        $mail->Password   = 'cuxg yguf zeyj tjzj'; // Secure storage needed
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('farmfoliomini@gmail.com', 'Farmfolio');
        $mail->addAddress($recipientEmail);
        $mail->Subject = 'Your Verification Code';
        $mail->Body    = "Your verification code is: $verificationCode\n\nThis code will expire in 10 minutes.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

$error_message = '';
$success_message = '';
$email = $_SESSION['email'] ?? null;

// Generate OTP on first page load
if ($email && !isset($_SESSION['verification_code'])) {
    $verificationCode = generateVerificationCode();
    $_SESSION['verification_code'] = $verificationCode;
    $_SESSION['otp_expiry'] = time() + 600;

    if (!sendVerificationEmail($email, $verificationCode)) {
        $error_message = "Failed to send verification code.";
    }
}

// Resend OTP
if ($email && isset($_POST['resend'])) {
    $_SESSION['verification_code'] = generateVerificationCode();
    $_SESSION['otp_expiry'] = time() + 600;

    if (!sendVerificationEmail($email, $_SESSION['verification_code'])) {
        $error_message = "Failed to resend OTP.";
    }
}

// Verify OTP
if (isset($_POST['verify'])) {
    if (!isset($_SESSION['verification_code']) || time() > $_SESSION['otp_expiry']) {
        $error_message = "OTP expired. Please resend.";
    } else {
        $enteredOTP = implode('', array_map('trim', [$_POST['otp1'], $_POST['otp2'], $_POST['otp3'], $_POST['otp4'], $_POST['otp5'], $_POST['otp6']]));

        if ($enteredOTP === $_SESSION['verification_code']) {
            unset($_SESSION['verification_code']); // Remove OTP after successful verification

            $conn->begin_transaction();
            $stmt1 = $conn->prepare("INSERT INTO tbl_signup (username, mobile, email, house, state, district, pin, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt1->bind_param("ssssssss", $_SESSION['username'], $_SESSION['mobile'], $_SESSION['email'], $_SESSION['house'], $_SESSION['state'], $_SESSION['district'], $_SESSION['pin'], $_SESSION['password']);
            $stmt1->execute();
            $userId = $conn->insert_id;
            $_SESSION['userid']=$userId;
            if (!$userId) die("Error: No userId generated.");

            $stmt2 = $conn->prepare("INSERT INTO tbl_login (email, password, type, username, userid) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("ssssi", $_SESSION['email'], $_SESSION['password'], $_SESSION['type'], $_SESSION['username'], $userId);
            $stmt2->execute();

            if ($stmt1->affected_rows > 0 && $stmt2->affected_rows > 0) {
                $conn->commit();
                switch ($_SESSION['type']) {
                    case 0 :
                        header('Location: http://localhost/Mini%20project/user/userindex.php');
                        break;
                    case 2:
                        header('Location: http://localhost/Mini%20project/delivery%20boy/delivery.php');
                        break;
                    case 1 :
                        header('Location: http://localhost/Mini%20project/farm/f.php');
                        break;
                    case 4 :
                        header('Location: http://localhost/Mini%20project/admin/admin.php');
                        break;
                    default:
                        echo "Invalid user type.";
                        exit;
                }
                exit;
            } else {
                $error_message = "Database transaction failed.";
            }
        } else {
            $error_message = "Incorrect OTP. Please try again.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmFolio - Email Verification</title>
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
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.6s ease-out;
        }

        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .task { color: #34a853; }
        .mate { color: #34a853; }

        h2 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .otp-inputs {
            display: flex;
            justify-content: space-between;
        }

        .otp-inputs input {
            width: 3.2rem;
            height: 3.2rem;
            text-align: center;
            font-size: 1.4rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .otp-inputs input:focus {
            outline: none;
            border-color: #34a853;
            box-shadow: 0 0 8px rgba(52, 168, 83, 0.3);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: #34a853;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: #2d8547;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(52, 168, 83, 0.2);
        }

        .resend-text {
            text-align: center;
            margin-top: 1.5rem;
            color: #6b7280;
        }

        .resend-text a {
            color: #34a853;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .resend-text a:hover {
            color: #2d8547;
        }

        .error-message {
            background-color: #fef2f2;
            color: #b91c1c;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid #fee2e2;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @media (max-width: 480px) {
            .otp-inputs input {
                width: 2.8rem;
                height: 2.8rem;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <span class="task">Farm</span><span class="mate">Folio</span>
        </div>
        <h2>OTP Verification</h2>
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <p style="text-align: center; color: #6b7280; margin-bottom: 1.5rem;">Enter the 6-digit code sent to your email.</p>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group otp-inputs">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp2')" id="otp1" name="otp1">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp3')" id="otp2" name="otp2">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp4')" id="otp3" name="otp3">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp5')" id="otp4" name="otp4">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp6')" id="otp5" name="otp5">
                <input type="text" maxlength="1" required id="otp6" name="otp6">
            </div>
            <button type="submit" class="login-btn" name="verify">Verify OTP</button>
            <p class="resend-text">Didn't receive the code? 
                <button type="submit" name="resend" style="border: none; background: none; color: #34a853; cursor: pointer; font-weight: 500;">Resend OTP</button>
            </p>
        </form>
    </div>

    <script>
        function moveToNext(current, nextFieldID) {
            if (current.value.length === 1) {
                document.getElementById(nextFieldID)?.focus();
            }
        }
    </script>
</body>
</html>
