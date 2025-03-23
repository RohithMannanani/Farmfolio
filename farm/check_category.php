<?php
session_start();
include '../databse/connect.php';

if(!isset($_SESSION['userid']) || !isset($_POST['category']) || !isset($_POST['sub_category']) || !isset($_POST['farm_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$category = mysqli_real_escape_string($conn, trim($_POST['category']));
$sub_category = mysqli_real_escape_string($conn, trim($_POST['sub_category']));
$farm_id = intval($_POST['farm_id']);

$check_query = "SELECT c.* FROM tbl_category c 
                INNER JOIN tbl_fc fc ON c.category_id = fc.category_id 
                WHERE fc.farm_id = $farm_id 
                AND LOWER(c.category) = LOWER('$category') 
                AND LOWER(c.sub) = LOWER('$sub_category')";

$result = mysqli_query($conn, $check_query);
$exists = mysqli_num_rows($result) > 0;

echo json_encode(['exists' => $exists]);