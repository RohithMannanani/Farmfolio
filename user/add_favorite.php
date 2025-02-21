<?php
session_start();
header('Content-Type: application/json');
include '../databse/connect.php';

// Debug logging
error_log("Request received in add_favorite.php");
error_log("POST data: " . print_r($_POST, true));
error_log("Session: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Please login to manage favorites']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['farm_id'])) {
    try {
        $user_id = (int)$_SESSION['userid'];
        $farm_id = (int)$_POST['farm_id'];
        $action = isset($_POST['action']) ? $_POST['action'] : 'add';
        
        error_log("Processing favorite for user_id: $user_id, farm_id: $farm_id, action: $action");

        // Verify farm exists
        $farm_check = mysqli_query($conn, "SELECT farm_id FROM tbl_farms WHERE farm_id = $farm_id");
        if (!$farm_check) {
            error_log("Farm query error: " . mysqli_error($conn));
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
        
        if(mysqli_num_rows($farm_check) === 0) {
            error_log("Farm not found: $farm_id");
            echo json_encode(['success' => false, 'message' => 'Farm not found']);
            exit;
        }

        // Check if already favorited
        $check_query = "SELECT favorite_id FROM tbl_favorites WHERE user_id = ? AND farm_id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $farm_id);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Remove from favorites
            $delete_query = "DELETE FROM tbl_favorites WHERE user_id = ? AND farm_id = ?";
            $stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $farm_id);
            
            if (mysqli_stmt_execute($stmt)) {
                error_log("Successfully removed from favorites");
                echo json_encode([
                    'success' => true, 
                    'message' => 'Removed from favorites',
                    'action' => 'removed'
                ]);
            } else {
                error_log("Error removing from favorites: " . mysqli_error($conn));
                echo json_encode(['success' => false, 'message' => 'Error removing from favorites']);
            }
        } else {
            // Add to favorites
            $insert_query = "INSERT INTO tbl_favorites (user_id, farm_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $farm_id);
            
            if (mysqli_stmt_execute($stmt)) {
                error_log("Successfully added to favorites");
                echo json_encode([
                    'success' => true, 
                    'message' => 'Added to favorites',
                    'action' => 'added'
                ]);
            } else {
                error_log("Error adding to favorites: " . mysqli_error($conn));
                echo json_encode(['success' => false, 'message' => 'Error adding to favorites']);
            }
        }
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    error_log("Invalid request or missing farm_id");
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?> 