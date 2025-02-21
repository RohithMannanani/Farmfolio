<?php
session_start();
header('Content-Type: application/json');
include '../databse/connect.php';

if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage favorites']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['farm_id'])) {
    try {
        $user_id = (int)$_SESSION['userid'];
        $farm_id = (int)$_POST['farm_id'];
        
        // Remove from favorites
        $query = "DELETE FROM tbl_favorites WHERE user_id = ? AND farm_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $farm_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Removed from favorites'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error removing from favorites'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'An error occurred'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request'
    ]);
}
?> 