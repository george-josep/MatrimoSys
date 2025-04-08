<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['username']) || !isset($_POST['recipient_id']) || !isset($_POST['message'])) {
    exit(json_encode(['success' => false]));
}

$sender_id = $_SESSION['username'];
$recipient_id = $_POST['recipient_id'];
$message = trim($_POST['message']);

if (empty($message)) {
    exit(json_encode(['success' => false]));
}

$query = "INSERT INTO messages (sender_id, recipient_id, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('sss', $sender_id, $recipient_id, $message);

$success = $stmt->execute();
echo json_encode(['success' => $success]); 