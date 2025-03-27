<?php
session_start();
include '../databse/connect.php';

if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    $user_id = $_SESSION['userid'];

    $query = "UPDATE tbl_notifications 
              SET is_read = 1 
              WHERE notification_id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} 