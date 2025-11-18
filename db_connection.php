<?php
$servername = "localhost";  
$username = "root";         
$password = "vincentagbuya123";            
$dbname = "shoestore";     

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
