<?php
session_start();
include "../databse/connect.php";

header('Content-Type: application/json');

if(!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if(!isset($_POST['order_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
$new_status = mysqli_real_escape_string($conn, $_POST['status']);

// Verify order exists and check current status
$verify_query = "SELECT order_status FROM tbl_orders WHERE order_id = $order_id";
$verify_result = mysqli_query($conn, $verify_query);

if(mysqli_num_rows($verify_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$current_status = mysqli_fetch_assoc($verify_result)['order_status'];

// Validate status transition
$valid_transitions = [
    'processing' => ['shipped'],
    'shipped' => ['delivered']
];

if(!isset($valid_transitions[$current_status]) || 
   !in_array($new_status, $valid_transitions[$current_status])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status transition']);
    exit;
}

// Update the order status
$update_query = "UPDATE tbl_orders 
                SET order_status = '$new_status'
                WHERE order_id = $order_id";

if(mysqli_query($conn, $update_query)) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
}
?> 