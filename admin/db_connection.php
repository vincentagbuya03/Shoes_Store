<?php
$servername = "localhost";  
$username = "root";         
$password = "vincentagbuya123";            
$dbname = "shoestore";     

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // Throw exception instead of using die() so API handlers can return JSON error responses
    throw new Exception("Database connection failed: " . $conn->connect_error);
}
?>
