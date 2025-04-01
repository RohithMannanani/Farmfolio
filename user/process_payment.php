<?php
session_start();
include '../databse/connect.php';
require_once('../razorpay-php/Razorpay.php');
use Razorpay\Api\Api;

// Ensure this file only returns JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

try {
    // Get form data
    $house_name = $_POST['house_name'] ?? '';
    $post_office = $_POST['post_office'] ?? '';
    $place = $_POST['place'] ?? '';
    $pin = $_POST['pin'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $total_amount = $_POST['total_amount'] ?? 0;

    // Validate inputs
    if (empty($house_name) || empty($post_office) || empty($place) || empty($pin) || empty($phone)) {
        throw new Exception('All fields are required');
    }

    // Format address
    $delivery_address = "$house_name, $post_office, $place, PIN: $pin";

    // Store checkout details in session
    $_SESSION['checkout_details'] = [
        'userid' => $_SESSION['userid'],
        'amount' => $total_amount,
        'address' => $delivery_address,
        'phone' => $phone
    ];

    // Create Razorpay order
    $razorpayOrder = [
        'amount' => $total_amount * 100, // Convert to paise
        'currency' => 'INR',
        'payment_capture' => 1
    ];

    // Store order ID in session
    $_SESSION['razorpay_order_temp'] = [
        'amount' => $total_amount * 100,
        'currency' => 'INR'
    ];

    // Return success response
    echo json_encode([
        'status' => 'success',
        'amount' => $total_amount * 100,
        'currency' => 'INR',
        'key' => 'rzp_test_46SrjQdO6MetdE', // Your Razorpay key
        'name' => 'Farmfolio',
        'description' => 'Order Payment',
        'prefill' => [
            'name' => $_SESSION['username'],
            'contact' => $phone
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit; 