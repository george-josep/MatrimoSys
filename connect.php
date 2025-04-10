<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$servername = "localhost"; // Usually localhost if using a local server
$username = "root";        // MySQL username (default is root)
$password = "";            // MySQL password (default is empty)
$dbname = "matrimosys";    // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

