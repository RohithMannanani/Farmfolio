<?php
session_start();
include '../databse/connect.php';

if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['userid'];

    $query = "UPDATE tbl_notifications 
              SET is_read = 1 
              WHERE user_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} 