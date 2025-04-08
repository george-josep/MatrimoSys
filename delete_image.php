<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!isset($_POST['image_id'])) {
    echo json_encode(['success' => false, 'error' => 'No image ID provided']);
    exit;
}

$imgId = intval($_POST['image_id']);
$userId = $_SESSION['username'];
$uploadPath = 'C:/xampp/htdocs/mini/uploads/gallery/';

// Get the image path
$stmt = $conn->prepare("SELECT image FROM tbl_images WHERE img_id = ? AND userid = ?");
$stmt->bind_param("is", $imgId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $imagePath = $row['image'];
    if (strpos($imagePath, 'uploads/gallery/') !== false) {
        $imagePath = str_replace('uploads/gallery/', $uploadPath, $imagePath);
    }
    
    try {
        // Delete from database first
        $deleteStmt = $conn->prepare("DELETE FROM tbl_images WHERE img_id = ? AND userid = ?");
        $deleteStmt->bind_param("is", $imgId, $userId);
        
        if ($deleteStmt->execute()) {
            // Then try to delete the file
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete from database']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Image not found']);
}
?> 