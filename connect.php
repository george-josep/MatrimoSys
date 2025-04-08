<?php
$host = "dpg-cvqc991r0fns73emthf0-a.oregon-postgres.render.com";
$port = "5432";
$dbname = "matrimosys";
$user = "matrimosys_user";
$password = "aLjlKc2xo0XQzbkZQYalgU8z7I4XOTPo";

// Create connection string
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";

// Establish connection
$conn = pg_connect($conn_string);

// Check connection
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}
?>
