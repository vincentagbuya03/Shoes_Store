<?php
// Detect if running on local or production server
$is_local = (
    $_SERVER['SERVER_NAME'] === 'localhost' || 
    $_SERVER['SERVER_NAME'] === '127.0.0.1' || 
    strpos($_SERVER['SERVER_NAME'], '.local') !== false ||
    strpos($_SERVER['SERVER_NAME'], 'localhost') !== false
);

if ($is_local) {
    // LOCAL (Laragon) credentials
    $servername = "localhost";  
    $username = "root";         
    $password = "vincentagbuya123";            
    $dbname = "shoestore";
} else {
    // PRODUCTION (Ezyro) credentials
    $servername = "sql213.ezyro.com";  
    $username = "ezyro_40615053";         
    $password = "8c754213262";            
    $dbname = "ezyro_40615053_shoestore_db";
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>