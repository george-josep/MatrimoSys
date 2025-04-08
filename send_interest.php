<?php
session_start();
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo "You must be logged in to send interest.";
    exit();
}

// Get the logged-in user's ID and the target user's ID
$loggedInUserId = $_SESSION['username']; // Assuming username is used as user ID
$targetUserId = $_POST['userid'] ?? null;

if ($targetUserId) {
    // Fetch the reg_id of the target user
    $reg_id_query = "SELECT reg_id FROM profiles WHERE userid = ?";
    $stmt = $conn->prepare($reg_id_query);
    $stmt->bind_param('s', $targetUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $reg_id = $row['reg_id'];

        // Insert into tbl_request
        $insert_query = "INSERT INTO tbl_request (userid_sender, userid_receiver) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('ss', $loggedInUserId, $targetUserId);

        if ($insert_stmt->execute()) {
            echo "Interest sent successfully!";
        } else {
            echo "Error sending interest: " . $conn->error;
        }
    } else {
        echo "Target user not found.";
    }
} else {
    echo "No user ID provided.";
}

// Close connection
$conn->close();
?> 