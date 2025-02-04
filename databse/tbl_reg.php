<?php
include '../databse/connect.php';
$conn->select_db("farmfolio");
$sql="CREATE TABLE tbl_signup (
    userid INT AUTO_INCREMENT PRIMARY KEY, 
    username VARCHAR(50) NOT NULL UNIQUE,   
    mobile VARCHAR(15) NOT NULL UNIQUE,     
    email VARCHAR(100) NOT NULL UNIQUE,    
    house VARCHAR(255) NOT NULL,           
    state VARCHAR(100) NOT NULL,            
    district VARCHAR(100) NOT NULL,         
    pin CHAR(6) NOT NULL,                 
    password VARCHAR(255) NOT NULL,                 
    signup_time DATETIME DEFAULT CURRENT_TIMESTAMP 
)
";
if($conn->query($sql)){
   echo "table created ";
}else{
    echo mysqli_error($conn);
}
?>