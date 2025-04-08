<?php
require_once 'connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Check if religion_id is set
    if (!isset($_GET['religion_id'])) {
        throw new Exception('Religion ID not provided');
    }

    $religion_id = intval($_GET['religion_id']);
    if ($religion_id <= 0) {
        throw new Exception('Invalid religion ID');
    }

    // Simple direct query to get castes
    $sql = "SELECT c.caste_id, c.caste 
            FROM tbl_caste c 
            WHERE c.religion_id = ?";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $religion_id);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $castes = array();
    
    while ($row = $result->fetch_assoc()) {
        $castes[] = array(
            'caste_id' => $row['caste_id'],
            'caste' => $row['caste']
        );
    }
    
    echo json_encode(array(
        'status' => 'success',
        'religion_id' => $religion_id,
        'castes' => $castes
    ));

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>
