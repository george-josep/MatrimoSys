<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['username']) || !isset($_POST['request_id']) || !isset($_POST['status'])) {
    exit(json_encode(['success' => false]));
}

$request_id = $_POST['request_id'];
$status = $_POST['status'];

// Different verification based on the status
if ($status == 3) {
    // For cancellation/removal, verify the user is either sender or receiver
    $verify_query = "SELECT * FROM tbl_request WHERE request_id = ? AND (userid_sender = ? OR userid_receiver = ?)";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param('iss', $request_id, $_SESSION['username'], $_SESSION['username']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows === 0) {
        exit(json_encode(['success' => false, 'message' => 'Unauthorized - You are not associated with this request']));
    }
    
    // Delete the request
    $delete_query = "DELETE FROM tbl_request WHERE request_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('i', $request_id);
    
    if ($delete_stmt->execute()) {
        exit(json_encode(['success' => true]));
    } else {
        exit(json_encode(['success' => false, 'message' => 'Error deleting request: ' . $delete_stmt->error]));
    }
} else {
    // For other status updates, verify the user is the recipient
    $verify_query = "SELECT * FROM tbl_request WHERE request_id = ? AND userid_receiver = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param('is', $request_id, $_SESSION['username']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows === 0) {
        exit(json_encode(['success' => false, 'message' => 'Unauthorized - You are not the recipient of this request']));
    }
    
    // Update the request status
    $update_query = "UPDATE tbl_request SET status = ? WHERE request_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ii', $status, $request_id);
    
    if ($update_stmt->execute()) {
        exit(json_encode(['success' => true]));
    } else {
        exit(json_encode(['success' => false, 'message' => 'Error updating request status: ' . $update_stmt->error]));
    }
} 