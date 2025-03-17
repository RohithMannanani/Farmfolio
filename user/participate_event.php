<?php
session_start();
include '../databse/connect.php';

// Check if user is logged in
if(!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to participate']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['event_id'];
$user_id = $_SESSION['userid'];

// Check if already registered
$check_query = "SELECT * FROM tbl_participants 
                WHERE event_id = ? AND user_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'You are already registered for this event']);
    exit();
}

// Insert new participation
$insert_query = "INSERT INTO tbl_participants (event_id, user_id) 
                VALUES (?, ?)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("ii", $event_id, $user_id);

if($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to register for the event']);
}
?> 