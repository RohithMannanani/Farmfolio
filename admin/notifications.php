<?php
function sendNotification($email, $phone, $farm_name, $status, $message) {
    // Email configuration
    $to = $email;
    $subject = "Farm Status Update - FarmFolio";
    
    // Clean phone number (remove spaces, dashes, etc)
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    if (!str_starts_with($clean_phone, '91')) {
        $clean_phone = '91' . $clean_phone;
    }
    
    // Generate WhatsApp link
    $whatsapp_message = urlencode("Hello! Your farm '$farm_name' status has been updated to: $status\n\n$message");
    $whatsapp_link = "https://wa.me/$clean_phone?text=$whatsapp_message";
    
    // Create email body with HTML
    $email_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a4d2e; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { 
                background: #25D366;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
                display: inline-block;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>FarmFolio Status Update</h2>
            </div>
            <div class='content'>
                <h3>Dear Farm Owner,</h3>
                <p>Your farm '$farm_name' status has been updated to: <strong>$status</strong></p>
                <p>$message</p>
                <p>For quick assistance, click below to contact us on WhatsApp:</p>
                <a href='$whatsapp_link' class='button' target='_blank'>
                    Contact on WhatsApp
                </a>
                <p><small>Or open WhatsApp and message us at: $phone</small></p>
            </div>
        </div>
    </body>
    </html>";
    
    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: FarmFolio <noreply@farmfolio.com>\r\n";
    
    // Send email
    return mail($to, $subject, $email_body, $headers);
}