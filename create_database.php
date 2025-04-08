<?php
$servername = "localhost"; // Usually localhost if using a local server
$username = "root";        // Default MySQL username is root
$password = "";            // Default MySQL password is empty (or your MySQL password)

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create database
$sql = "CREATE DATABASE matrimosys";

if ($conn->query($sql) === TRUE) {
    echo "Database 'matrimosys' created successfully!";
} else {
    echo "Error creating database: " . $conn->error;
}

// Close the connection
$conn->close();
?>
