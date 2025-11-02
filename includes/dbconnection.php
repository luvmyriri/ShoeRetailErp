<?php
// Define database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');          // CHANGE THIS to your database username
define('DB_PASSWORD', '');              // CHANGE THIS to your database password
define('DB_NAME', 'shoeretailer');  // CHANGE THIS to your actual database name


// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);


// Check connection
if ($conn->connect_error) {
    // Stop the script and report an error if connection fails
    die("ERROR: Could not connect to the database. " . $conn->connect_error);
}
?>

