<?php
session_start();
include '../databse/connect.php';

if (!isset($_SESSION['userid']) || !isset($_GET['order_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$orderId = intval($_GET['order_id']);
$userId = $_SESSION['userid'];

// Get order tracking information
$query = "SELECT 
    order_status as status,
    order_date as pending_date,
    CASE 
        WHEN order_status IN ('processing', 'shipped', 'delivered') THEN updated_at
        ELSE NULL 
    END as processing_date,
    CASE 
        WHEN order_status IN ('shipped', 'delivered') THEN updated_at
        ELSE NULL 
    END as shipped_date,
    CASE 
        WHEN order_status = 'delivered' THEN updated_at
        ELSE NULL 
    END as delivered_date
    FROM tbl_orders 
    WHERE order_id = ? AND user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Format dates
    $row['pending_date'] = date('d M Y, h:i A', strtotime($row['pending_date']));
    if ($row['processing_date']) {
        $row['processing_date'] = date('d M Y, h:i A', strtotime($row['processing_date']));
    }
    if ($row['shipped_date']) {
        $row['shipped_date'] = date('d M Y, h:i A', strtotime($row['shipped_date']));
    }
    if ($row['delivered_date']) {
        $row['delivered_date'] = date('d M Y, h:i A', strtotime($row['delivered_date']));
    }
    
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Order not found']);
}