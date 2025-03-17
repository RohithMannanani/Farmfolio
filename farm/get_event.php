<?php
session_start();
include '../databse/connect.php';

if(isset($_GET['id']) && isset($_SESSION['userid'])) {
    $event_id = intval($_GET['id']);
    $farm_id = $_SESSION['farm_id'];
    
    $query = "SELECT * FROM tbl_events WHERE event_id = ? AND farm_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $event_id, $farm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    
    echo json_encode($event);
    exit();
}