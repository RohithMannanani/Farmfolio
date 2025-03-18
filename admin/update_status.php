<?php
include '../databse/connect.php';
header('Content-Type: application/json');

if (isset($_POST['farm_id']) && isset($_POST['status'])) {
    $farm_id = mysqli_real_escape_string($conn, $_POST['farm_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // First get farm details
    $farm_query = "SELECT f.farm_name, s.email, s.username 
                   FROM tbl_farms f 
                   JOIN tbl_signup s ON f.user_id = s.userid 
                   WHERE f.farm_id = ?";
    
    $stmt = $conn->prepare($farm_query);
    $stmt->bind_param("i", $farm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $farm_data = $result->fetch_assoc();

    if (!$farm_data) {
        echo json_encode([
            'success' => false,
            'message' => "Farm not found"
        ]);
        exit;
    }

    // Update status
    $update_query = "UPDATE tbl_farms SET status = ? WHERE farm_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $farm_id);
    
    if ($stmt->execute()) {
        $response = ['success' => true];
        
        // Send email if status is rejected
        if ($status === 'rejected') {
            // Email configuration
            $to = $farm_data['email'];
            $subject = "Farm Registration Status Update - Rejected";
            
            // Use PHPMailer for more reliable email sending
            require 'PHPMailer-master/src/PHPMailer.php';
            require 'PHPMailer-master/src/SMTP.php';
            require_once 'PHPMailer-master/src/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  // Use Gmail SMTP
                $mail->SMTPAuth = true;
                $mail->Username = 'farmfoliomini@gmail.com'; // Your Gmail address
                $mail->Password = 'cuxg yguf zeyj tjzj'; // Your Gmail app password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                // Recipients
                $mail->setFrom('farmfoliomini@gmail.com', 'FarmFolio');
                $mail->addAddress($to);

                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { padding: 20px; }
                        .header { color: #1a4d2e; font-size: 24px; margin-bottom: 20px; }
                        .content { margin-bottom: 20px; }
                        .footer { color: #666; font-size: 14px; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>Farm Registration Update</div>
                        <div class='content'>
                            <p>Dear {$farm_data['username']},</p>
                            <p>We regret to inform you that your farm registration for <strong>{$farm_data['farm_name']}</strong> has been rejected.</p>
                            <p>This could be due to one or more of the following reasons:</p>
                            <ul>
                                <li>Incomplete or incorrect documentation</li>
                                <li>Non-compliance with our farming guidelines</li>
                                <li>Unable to verify farm details</li>
                            </ul>
                            <p>Please contact our support team for more information and guidance on how to proceed with a new application.</p>
                        </div>
                        <div class='footer'>
                            <p>Best regards,<br>FarmFolio Team</p>
                        </div>
                    </div>
                </body>
                </html>";

                $mail->send();
                $response['message'] = "Status updated and notification email sent successfully!";
            } catch (Exception $e) {
                $response['message'] = "Status updated but failed to send email. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $response['message'] = "Status updated successfully!";
        }
    } else {
        $response = [
            'success' => false,
            'message' => "Error updating status: " . $stmt->error
        ];
    }
    
    echo json_encode($response);
    $stmt->close();
}
?>
