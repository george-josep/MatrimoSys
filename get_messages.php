<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['username']) || !isset($_GET['recipient_id'])) {
    exit(json_encode([]));
}

$sender_id = $_SESSION['username'];
$recipient_id = $_GET['recipient_id'];

// First, mark messages as read
$update_query = "UPDATE messages 
                SET is_read = 1 
                WHERE sender_id = ? AND recipient_id = ? AND is_read = 0";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param('ss', $recipient_id, $sender_id);
$update_stmt->execute();

// Then fetch all messages
$query = "SELECT * FROM messages 
          WHERE (sender_id = ? AND recipient_id = ?) 
          OR (sender_id = ? AND recipient_id = ?)
          ORDER BY sent_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('ssss', $sender_id, $recipient_id, $recipient_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'sender_id' => $row['sender_id'],
        'message' => htmlspecialchars($row['message']),
        'sent_time' => date('H:i', strtotime($row['sent_time']))
    ];
}

echo json_encode($messages);