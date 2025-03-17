<?php
require_once '../databse/connect.php';
session_start();

// Remove strict session check temporarily for testing
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
//     http_response_code(403);
//     exit('Unauthorized');
// }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $participant_id = $_POST['participant_id'];
    $status = $_POST['status'];
    
    $valid_statuses = ['Pending', 'Confirmed', 'Cancelled'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        exit('Invalid status');
    }

    $query = "UPDATE tbl_participants SET status = ? WHERE participant_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $participant_id);
    
    if ($stmt->execute()) {
        echo 'Success';
    } else {
        http_response_code(500);
        echo 'Error updating status';
    }
}
?> 