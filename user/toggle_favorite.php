<?php
session_start();
header('Content-Type: application/json');
include '../databse/connect.php';

// Debug logging
error_log("Request received in toggle_favorite.php");

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Please login to manage favorites']);
    exit;
}

try {
    // Get POST data from JSON request
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Received data: " . print_r($data, true));

    if (!isset($data['farm_id']) || !isset($data['action'])) {
        error_log("Missing required parameters");
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    $user_id = (int)$_SESSION['userid'];
    $farm_id = (int)$data['farm_id'];
    $action = $data['action'];

    error_log("Processing favorite for user_id: $user_id, farm_id: $farm_id, action: $action");

    // Verify farm exists
    $farm_check = mysqli_query($conn, "SELECT farm_id FROM tbl_farms WHERE farm_id = $farm_id");
    if (!$farm_check || mysqli_num_rows($farm_check) === 0) {
        error_log("Farm not found or query error: " . mysqli_error($conn));
        echo json_encode(['success' => false, 'message' => 'Farm not found']);
        exit;
    }

    // Check if already favorited
    $check_query = "SELECT favorite_id FROM tbl_favorites WHERE user_id = ? AND farm_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $farm_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($check_result) > 0;

    if ($action === 'add' && !$exists) {
        // Add to favorites
        $insert_query = "INSERT INTO tbl_favorites (user_id, farm_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
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
    elseif ($action === 'remove' && $exists) {
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
    }
    else {
        // No action needed (already in desired state)
        echo json_encode([
            'success' => true,
            'message' => 'No action needed',
            'action' => $exists ? 'already_added' : 'already_removed'
        ]);
    }

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?> 