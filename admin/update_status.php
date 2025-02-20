<?php
include '../databse/connect.php';

if (isset($_POST['farm_id']) && isset($_POST['status'])) {
    $farm_id = mysqli_real_escape_string($conn, $_POST['farm_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $query = "UPDATE tbl_farms SET status = '$status' WHERE farm_id = '$farm_id'";
    if (mysqli_query($conn, $query)) {
        echo "Status updated successfully!";
    } else {
        echo "Error updating status: " . mysqli_error($conn);
    }
}
?>
