<?php
include '../databse/connect.php';
$conn->select_db("farmfolio");
$sql="CREATE TABLE tbl_login (
    login_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    userid INT NOT NULL,
    FOREIGN KEY (userid) REFERENCES tbl_signup(userid)
)"; 
if($conn->query($sql)){
   echo "table created ";
}else{
    echo mysqli_error($conn);
}
?>