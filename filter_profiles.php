<?php
session_start();
include 'connect.php';

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// Get filter parameters from request
$filter_gender = $_POST['filter_gender'] ?? '';
$filter_min_age = !empty($_POST['filter_min_age']) ? intval($_POST['filter_min_age']) : null;
$filter_max_age = !empty($_POST['filter_max_age']) ? intval($_POST['filter_max_age']) : null;
$filter_religion = $_POST['filter_religion'] ?? '';
$filter_caste = $_POST['filter_caste'] ?? '';
$filter_complexion = $_POST['filter_complexion'] ?? '';
$filter_min_height = !empty($_POST['filter_min_height']) ? floatval($_POST['filter_min_height']) : null;
$filter_max_height = !empty($_POST['filter_max_height']) ? floatval($_POST['filter_max_height']) : null;
$logged_in_user_gender = $_POST['logged_in_user_gender'] ?? '';

// Base query with joins
$sql = "SELECT DISTINCT p.*, 
        c.caste, 
        c.religion_id,
        e.eduSub, 
        rel.religion,
        img.image 
        FROM profiles p
        LEFT JOIN tbl_caste c ON p.caste_id = c.caste_id 
        LEFT JOIN tbl_religion rel ON c.religion_id = rel.religion_id
        LEFT JOIN tbl_subEducation e ON p.edusub_id = e.edusub_id 
        LEFT JOIN profile_images img ON p.userid = img.userid
        WHERE 1=1";

// Add gender filter based on user preference or logged in user's gender
if (!empty($filter_gender)) {
    $sql .= " AND p.gender = ?";
} else {
    // If no gender filter is specified, show opposite gender based on logged in user
    if ($logged_in_user_gender === 'male') {
        $sql .= " AND p.gender = 'female'";
    } else if ($logged_in_user_gender === 'female') {
        $sql .= " AND p.gender = 'male'";
    }
}

// Add age filter if provided
if (!empty($filter_min_age)) {
    $sql .= " AND p.age >= ?";
}
if (!empty($filter_max_age)) {
    $sql .= " AND p.age <= ?";
}

// Add religion filter if provided
if (!empty($filter_religion)) {
    $sql .= " AND rel.religion_id = ?";
}

// Add caste filter if provided
if (!empty($filter_caste)) {
    $sql .= " AND p.caste_id = ?";
}

// Add complexion filter if provided
if (!empty($filter_complexion)) {
    $sql .= " AND p.complexion = ?";
}

// Add height filter if provided
if (!empty($filter_min_height)) {
    $sql .= " AND CAST(SUBSTRING_INDEX(p.height, ' ', 1) AS DECIMAL(10,2)) >= ?";
}
if (!empty($filter_max_height)) {
    $sql .= " AND CAST(SUBSTRING_INDEX(p.height, ' ', 1) AS DECIMAL(10,2)) <= ?";
}

// Exclude current user
$sql .= " AND p.userid != ?";

// Add order by
$sql .= " ORDER BY RAND() LIMIT 50";

// Prepare statement
$stmt = $conn->prepare($sql);

// Create parameter array and types string
$params = [];
$types = '';

// Add parameters based on which filters are active
if (!empty($filter_gender)) {
    $params[] = $filter_gender;
    $types .= 's';
}

if (!empty($filter_min_age)) {
    $params[] = $filter_min_age;
    $types .= 'i';
}

if (!empty($filter_max_age)) {
    $params[] = $filter_max_age;
    $types .= 'i';
}

if (!empty($filter_religion)) {
    $params[] = $filter_religion;
    $types .= 's';
}

if (!empty($filter_caste)) {
    $params[] = $filter_caste;
    $types .= 's';
}

if (!empty($filter_complexion)) {
    $params[] = $filter_complexion;
    $types .= 's';
}

if (!empty($filter_min_height)) {
    $params[] = $filter_min_height;
    $types .= 'd';
}

if (!empty($filter_max_height)) {
    $params[] = $filter_max_height;
    $types .= 'd';
}

// Add current user parameter
$params[] = $_SESSION['username'];
$types .= 's';

// Bind parameters if any exist
if (!empty($params)) {
    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], makeValuesReferenced($bind_params));
}

// Execute query
$stmt->execute();
$result = $stmt->get_result();

// Fetch results
$filtered_results = [];
while ($row = $result->fetch_assoc()) {
    $filtered_results[] = $row;
}

// Helper function to create references for bind_param
function makeValuesReferenced(&$arr){
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs;
}

// Return results as JSON
header('Content-Type: application/json');
echo json_encode($filtered_results);
?>