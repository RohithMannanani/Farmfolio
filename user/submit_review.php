<?php
session_start();
include '../databse/connect.php';

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to submit a review']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $farm_id = (int)$data['farm_id'];
    $user_id = (int)$_SESSION['userid'];
    $rating = (int)$data['rating'];
    $comment = mysqli_real_escape_string($conn, $data['comment']);

    // Check if user has already reviewed this farm
    $check_query = "SELECT * FROM tbl_reviews WHERE farm_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $farm_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing review
        $update_query = "UPDATE tbl_reviews 
                        SET rating = ?, comment = ?, created_at = CURRENT_TIMESTAMP 
                        WHERE farm_id = ? AND user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("isii", $rating, $comment, $farm_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Review updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error updating review']);
        }
    } else {
        // Insert new review
        $insert_query = "INSERT INTO tbl_reviews (farm_id, user_id, rating, comment) 
                        VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiis", $farm_id, $user_id, $rating, $comment);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Review submitted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error submitting review']);
        }
    }
}
?>