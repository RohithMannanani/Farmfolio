<?php
session_start();
include "../databse/connect.php";

header('Content-Type: application/json');

if(!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if(!isset($_POST['order_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
$action = mysqli_real_escape_string($conn, $_POST['action']);
$delivery_boy_id = $_SESSION['userid'];

try {
    // First check if the order exists and its current status
    $verify_query = "SELECT order_status FROM tbl_orders WHERE order_id = '$order_id'";
    $verify_result = mysqli_query($conn, $verify_query);
    
    if(!$verify_result || mysqli_num_rows($verify_result) == 0) {
        throw new Exception('Order not found');
    }

    $order = mysqli_fetch_assoc($verify_result);

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        switch($action) {
            case 'accept':
                if($order['order_status'] !== 'pending') {
                    throw new Exception('Order is no longer available');
                }
                
                $update_query = "UPDATE tbl_orders 
                                SET order_status = 'processing',
                                    delivery_boy_id = '$delivery_boy_id',
                                    updated_at = CURRENT_TIMESTAMP
                                WHERE order_id = '$order_id'";
                break;

            case 'deliver':
                if($order['order_status'] !== 'shipped') {
                    throw new Exception('Order must be shipped before delivery');
                }
                
                $update_query = "UPDATE tbl_orders 
                                SET order_status = 'delivered',
                                    payment_status = 'paid',
                                    updated_at = CURRENT_TIMESTAMP
                                WHERE order_id = '$order_id'";
                break;

            case 'ship':
                if($order['order_status'] !== 'processing') {
                    throw new Exception('Order must be processing before shipping');
                }
                
                $update_query = "UPDATE tbl_orders 
                                SET order_status = 'shipped',
                                    updated_at = CURRENT_TIMESTAMP
                                WHERE order_id = '$order_id'";
                break;

            default:
                throw new Exception('Invalid action');
        }

        if(!mysqli_query($conn, $update_query)) {
            throw new Exception('Failed to update order: ' . mysqli_error($conn));
        }

        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'Order ' . $action . 'ed successfully'
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?> 