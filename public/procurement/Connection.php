<?php
$servername = "localhost";   // or 127.0.0.1
$username   = "root";        // your MySQL username
$password   = "admin123";            // your MySQL password
$dbname     = "shoeretailerp";  // name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
