<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "username", "password", "database_name");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

$userid = $_POST['userid'];
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['exists' => $row['count'] > 0]);

$stmt->close();
$conn->close();
?> 