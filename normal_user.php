<?php
session_start();
include 'connect.php';

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Initialize search limit and timestamp if not set
if (!isset($_SESSION['search_limit'])) {
    $_SESSION['search_limit'] = 10; // Set initial search limit
}

if (!isset($_SESSION['last_reset'])) {
    $_SESSION['last_reset'] = time(); // Set current time as last reset
}

// Check if 24 hours have passed since the last reset
if (time() - $_SESSION['last_reset'] > 86400) { // 86400 seconds in 24 hours
    $_SESSION['search_limit'] = 10; // Reset search limit
    $_SESSION['last_reset'] = time(); // Update last reset time
}

// Add membership update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_membership'])) {
    $userid = $_SESSION['username']; // Using userid since that's your column name
    
    // Create a new connection since connect.php closes its connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    $sql = "UPDATE profiles SET payment_id = 2 WHERE userid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userid); // Using "s" since userid is VARCHAR
    
    if (!$stmt->execute()) {
        // Handle error case
        die("Error updating membership: " . $conn->error);
    }
    
    $stmt->close();
    $conn->close();
}

// Initialize search results and recommended matches arrays
$search_results = [];
$recommended_matches = [];

// Fetch the gender of the logged-in user
$user_gender_query = "SELECT gender FROM profiles WHERE userid = ?";
$user_gender_stmt = $conn->prepare($user_gender_query);
$user_gender_stmt->bind_param('s', $_SESSION['username']);
$user_gender_stmt->execute();
$user_gender_result = $user_gender_stmt->get_result();
$user_gender_row = $user_gender_result->fetch_assoc();
$logged_in_user_gender = $user_gender_row['gender'] ?? null;

// Check if user has premium membership
$premium_check_query = "SELECT payment_id FROM profiles WHERE userid = ?";
$premium_check_stmt = $conn->prepare($premium_check_query);
$premium_check_stmt->bind_param('s', $_SESSION['username']);
$premium_check_stmt->execute();
$premium_check_result = $premium_check_stmt->get_result();
$premium_check_row = $premium_check_result->fetch_assoc();
$is_premium = ($premium_check_row['payment_id'] == 2); // Assuming payment_id 2 is premium

// Fetch user preferences if user is premium
if ($is_premium) {
    $preferences_query = "SELECT * FROM tbl_preference WHERE reg_id = ?";
    $preferences_stmt = $conn->prepare($preferences_query);
    $preferences_stmt->bind_param('i', $_SESSION['reg_id']); // Assuming reg_id is stored in session
    $preferences_stmt->execute();
    $preferences_result = $preferences_stmt->get_result();
    $user_preferences = $preferences_result->fetch_assoc();

    // Fetch recommended matches based on user preferences
    if ($user_preferences) {
        // Base query with joins
        $recommended_sql = "SELECT DISTINCT p.*, 
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
        
        // Add gender filter based on user preference
        // If gender preference is NULL, include all genders except the user's own
        if (!empty($user_preferences['gender'])) {
            $recommended_sql .= " AND p.gender = ?";
        } else if ($logged_in_user_gender === 'male') {
            $recommended_sql .= " AND p.gender = 'female'";
        } else if ($logged_in_user_gender === 'female') {
            $recommended_sql .= " AND p.gender = 'male'";
        }
        
        // Add age filter if preferences exist
        // If min_age or max_age is NULL, don't filter by age
        if (!empty($user_preferences['min_age']) && !empty($user_preferences['max_age'])) {
            $recommended_sql .= " AND p.age BETWEEN ? AND ?";
        }
        
        // Add religion filter if preference exists
        // If religion is NULL, include all religions
        if (!empty($user_preferences['religion'])) {
            $recommended_sql .= " AND rel.religion = ?";
        }
        
        // Add caste filter if preference exists
        // If caste_id is NULL, include all castes
        if (!empty($user_preferences['caste_id'])) {
            $recommended_sql .= " AND p.caste_id = ?";
        }
        
        // Add height filter if preference exists
        // If height is NULL, include all heights
        if (!empty($user_preferences['height'])) {
            $recommended_sql .= " AND CAST(SUBSTRING_INDEX(p.height, ' ', 1) AS DECIMAL(10,2)) >= ?";
        }
        
        // Exclude current user
        $recommended_sql .= " AND p.userid != ?";
        
        // Order by most matching preferences first (optional)
        $recommended_sql .= " ORDER BY RAND()";
        
        // Limit to 6 recommendations
        $recommended_sql .= " LIMIT 6";
        
        // Prepare statement
        $recommended_stmt = $conn->prepare($recommended_sql);
        
        // Create parameter array and types string
        $params = [];
        $types = '';
        
        // Add parameters based on which filters are active
        if (!empty($user_preferences['gender'])) {
            $params[] = $user_preferences['gender'];
            $types .= 's';
        } else if ($logged_in_user_gender === 'male' || $logged_in_user_gender === 'female') {
            // This is handled in the SQL directly, no parameter needed
        }
        
        if (!empty($user_preferences['min_age']) && !empty($user_preferences['max_age'])) {
            $params[] = $user_preferences['min_age'];
            $params[] = $user_preferences['max_age'];
            $types .= 'ii';
        }
        
        if (!empty($user_preferences['religion'])) {
            $params[] = $user_preferences['religion'];
            $types .= 's';
        }
        
        if (!empty($user_preferences['caste_id'])) {
            $params[] = $user_preferences['caste_id'];
            $types .= 'i';
        }
        
        if (!empty($user_preferences['height'])) {
            $params[] = $user_preferences['height'];
            $types .= 'd';
        }
        
        // Add current user parameter
        $params[] = $_SESSION['username'];
        $types .= 's';
        
        // Bind parameters if any exist
        if (!empty($params)) {
            $bind_params = array_merge([$types], $params);
            call_user_func_array([$recommended_stmt, 'bind_param'], makeValuesReferenced($bind_params));
        }
        
        // Execute query
        $recommended_stmt->execute();
        $recommended_result = $recommended_stmt->get_result();
        
        // Fetch results
        while ($row = $recommended_result->fetch_assoc()) {
            $recommended_matches[] = $row;
        }
    } else {
        // If no preferences exist at all, just show random profiles of opposite gender
        $recommended_sql = "SELECT DISTINCT p.*, 
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
                    WHERE p.userid != ?";
        
        // Add gender filter based on logged-in user's gender
        if ($logged_in_user_gender === 'male') {
            $recommended_sql .= " AND p.gender = 'female'";
        } else if ($logged_in_user_gender === 'female') {
            $recommended_sql .= " AND p.gender = 'male'";
        }
        
        $recommended_sql .= " ORDER BY RAND() LIMIT 6";
        
        $recommended_stmt = $conn->prepare($recommended_sql);
        $recommended_stmt->bind_param('s', $_SESSION['username']);
        $recommended_stmt->execute();
        $recommended_result = $recommended_stmt->get_result();
        
        while ($row = $recommended_result->fetch_assoc()) {
            $recommended_matches[] = $row;
        }
    }
}

// Handle search functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['search'])) {
        // Check search limit
        if ($_SESSION['search_limit'] > 0) {
            // Get search parameters
            $search_term = $_POST['search_term'];
            $search_type = $_POST['search_type'];

            // Prepare the base query with an additional join for images
            $sql = "SELECT p.*, 
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

            // Add gender filter based on the logged-in user's gender
            if ($logged_in_user_gender === 'male') {
                $sql .= " AND p.gender = 'female'";
            } elseif ($logged_in_user_gender === 'female') {
                $sql .= " AND p.gender = 'male'";
            }

            // Prepare parameters array
            $params = [];
            $types = '';

            // Build dynamic search query based on search type
            switch ($search_type) {
                case 'name':
                    $sql .= " AND p.username LIKE ?";
                    $params[] = "%$search_term%";
                    $types .= 's';
                    break;
                case 'religion':
                    $sql .= " AND rel.religion LIKE ?";
                    $params[] = "%$search_term%";
                    $types .= 's';
                    break;
                case 'caste':
                    $sql .= " AND c.caste LIKE ?";
                    $params[] = "%$search_term%";
                    $types .= 's';
                    break;
                case 'education':
                    $sql .= " AND e.eduSub LIKE ?";
                    $params[] = "%$search_term%";
                    $types .= 's';
                    break;
                case 'height':
                    $sql .= " AND p.height = ?";
                    $params[] = $search_term;
                    $types .= 'i';
                    break;
                case 'age':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) = ?";
                    $params[] = $search_term;
                    $types .= 'i';
                    break;
                case 'complexion':
                    $sql .= " AND p.complexion LIKE ?";
                    $params[] = "%$search_term%";
                    $types .= 's';
                    break;
            }

            // Exclude the current user from search results
            $sql .= " AND p.userid != ?";
            $params[] = $_SESSION['username'];
            $types .= 's';

            // Prepare and execute the statement
            $stmt = $conn->prepare($sql);
            
            if (!empty($params)) {
                // Bind parameters dynamically
                $bind_params = array_merge([$types], $params);
                call_user_func_array([$stmt, 'bind_param'], makeValuesReferenced($bind_params));
            }

            $stmt->execute();
            $result = $stmt->get_result();

            // Fetch search results
            while ($row = $result->fetch_assoc()) {
                // Determine image path with fallback
                $profile_card_image = 'uploads/default.png'; // Default fallback
                
                if (!empty($row['image'])) {
                    $profile_card_image = $row['image'];
                }
                
                // Ensure the image path is valid
                if (!file_exists($profile_card_image)) {
                    $profile_card_image = 'uploads/default.png';
                }
                
                $row['profile_image'] = $profile_card_image;
                $search_results[] = $row;
            }

            // Decrease the search limit
            $_SESSION['search_limit']--;
        } else {
            // Set a flag to show the message
            $show_limit_message = true;
        }
    } elseif (isset($_POST['clear'])) {
        // Clear the search results
        $search_results = [];
    }
}

// Helper function to create references for bind_param
function makeValuesReferenced(&$arr){
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs;
}

// Fetch religions for dropdown
$religions = [];
$religion_query = "SELECT religion_id, religion FROM tbl_religion";
$religion_result = $conn->query($religion_query);
while ($row = $religion_result->fetch_assoc()) {
    $religions[] = $row;
}

// Fetch castes for dropdown
$castes = [];
$caste_query = "SELECT caste_id, caste, religion_id FROM tbl_caste";
$caste_result = $conn->query($caste_query);
while ($row = $caste_result->fetch_assoc()) {
    $castes[] = $row;
}

// Fetch education categories for dropdown
$education_categories = [];
$edu_query = "SELECT edusub_id, eduSub FROM tbl_subEducation";
$edu_result = $conn->query($edu_query);
while ($row = $edu_result->fetch_assoc()) {
    $education_categories[] = $row;
}

// Add these queries after the existing education categories fetch
// Fetch complexions (unique values from profiles table)
$complexions = [];
$complexion_query = "SELECT DISTINCT complexion FROM profiles WHERE complexion IS NOT NULL";
$complexion_result = $conn->query($complexion_query);
while ($row = $complexion_result->fetch_assoc()) {
    $complexions[] = $row['complexion'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to MatrimoSys</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Pacifico', cursive;
            color: #333;
            background-color: #f4f4f4;
        }

        .top {
            display: none;
        }

        .header {
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.5);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            backdrop-filter: blur(10px);
        }

        .logo {
            font-size: clamp(24px, 4vw, 36px);
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #f3c634;
            text-shadow: 0px 0px 10px rgba(255, 255, 255, 0.5);
        }

        .nav {
            display: flex;
            align-items: center;
        }

        .nav a {
            color: rgb(255, 255, 255);
            text-decoration: none;
            margin-left: 20px;
            font-weight: 600;
            font-size: clamp(16px, 3vw, 18px);
            transition: color 0.3s ease, text-shadow 0.3s ease;
        }

        .nav a:hover {
            color: #FFD700;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
        }

        .search-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin: 120px auto 20px;
            max-width: 1000px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .search-input-group {
            display: flex;
            gap: 15px;
        }

        .search-input-group select {
            width: 200px;
            padding: 15px;
            border: 2px solid #f3c634;
            border-radius: 12px;
            font-size: 16px;
            background: #fff;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath fill='%23f3c634' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .search-input-group select:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 15px rgba(243, 198, 52, 0.3);
        }

        .search-input-wrapper {
            flex-grow: 1;
            position: relative;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #f3c634;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
            color: #333;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 15px rgba(243, 198, 52, 0.3);
            transform: translateY(-2px);
        }

        .search-input-wrapper input::placeholder {
            color: #999;
            font-style: italic;
        }

        .search-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .search-btn, .clear-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .search-btn {
            background: linear-gradient(135deg, #f3c634, #FFD700);
            color: #fff;
            box-shadow: 0 4px 15px rgba(243, 198, 52, 0.3);
        }

        .clear-btn {
            background: linear-gradient(135deg, #ff6b6b, #ff8787);
            color: #fff;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .search-btn:hover, .clear-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .search-btn:active, .clear-btn:active {
            transform: translateY(1px);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .search-input-group {
                flex-direction: column;
            }

            .search-input-group select {
                width: 100%;
            }

            .search-buttons {
                flex-direction: column;
            }

            .search-btn, .clear-btn {
                width: 100%;
                justify-content: center;
            }
        }

        .search-results {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 20px;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .profile-card:hover {
            transform: scale(1.05);
        }

        .profile-card-image {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            background-color: #f0f0f0;
        }

        .profile-card-content {
            padding: 10px;
            text-align: left;
        }

        .profile-card-content h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            color: #333;
        }

        .profile-card-content p {
            margin: 5px 0;
            color: #666;
            font-size: 0.9rem;
        }

        .profile-card-actions {
            display: flex;
            padding: 15px;
            background: rgba(243, 198, 52, 0.1);
            border-top: 1px solid rgba(243, 198, 52, 0.2);
            justify-content: center; /* Center the single button */
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 80%; /* Make the button wider since it's alone */
        }

        .view-btn {
            background: linear-gradient(135deg, #f3c634, #FFD700);
            color: white;
            box-shadow: 0 4px 15px rgba(243, 198, 52, 0.3);
        }

        .view-btn:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #FFD700, #f3c634);
            box-shadow: 0 6px 20px rgba(243, 198, 52, 0.4);
        }

        .action-btn i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .action-btn:hover i {
            transform: scale(1.2);
        }

        .no-results {
            text-align: center;
            padding: 50px;
            background: rgba(255,255,255,0.9);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 10px;
            text-align: center;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .upgrade-button {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #FFD700;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .upgrade-button:hover {
            background-color: #f3c634;
            transform: scale(1.05);
        }

        .profile-image {
            width: 100%;
            height: auto;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .floating-box {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 0;
            border-radius: 15px;
            cursor: pointer;
            z-index: 99;
            transition: all 0.3s ease;
        }

        .floating-box-content {
            background: linear-gradient(135deg, #f3c634, #FFD700);
            padding: 15px 25px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(243, 198, 52, 0.3);
            position: relative;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .floating-box i {
            font-size: 20px;
            color: white;
            animation: crown-bounce 2s infinite;
        }

        .pulse-ring {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 15px;
            animation: pulse 2s infinite;
            background: rgba(243, 198, 52, 0.4);
            z-index: 1;
        }

        .floating-box:hover .floating-box-content {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #FFD700, #f3c634);
            box-shadow: 0 6px 20px rgba(243, 198, 52, 0.4);
        }

        .floating-box:hover .pulse-ring {
            animation-play-state: paused;
        }

        .floating-box:active .floating-box-content {
            transform: translateY(1px);
        }

        @keyframes crown-bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-3px);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.6;
            }
            70% {
                transform: scale(1.05);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 0;
            }
        }

        /* Add shine effect */
        .floating-box-content::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 0%,
                rgba(255, 255, 255, 0.1) 45%,
                rgba(255, 255, 255, 0.5) 50%,
                rgba(255, 255, 255, 0.1) 55%,
                transparent 100%
            );
            transform: rotate(30deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% {
                left: -50%;
            }
            100% {
                left: 100%;
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .floating-box {
                top: auto;
                bottom: 20px;
                right: 50%;
                transform: translateX(50%);
            }

            .floating-box-content {
                padding: 12px 20px;
                font-size: 14px;
            }
        }

        /* Add these new styles for recommendations */
        .recommended-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .section-title i {
            color: #f3c634;
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-style: italic;
        }

        .profile-card.recommended {
            position: relative;
            border: 2px solid #f3c634;
            box-shadow: 0 10px 25px rgba(243, 198, 52, 0.2);
        }

        .recommendation-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #f3c634, #FFB347);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .recommendation-badge i {
            color: #fff;
            font-size: 0.9rem;
        }

        .filter-sidebar {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-header i {
            color: #f3c634;
        }

        .filter-form {
            display: grid;
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .filter-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .filter-btn.apply {
            background: #f3c634;
            color: white;
        }

        .filter-btn.clear {
            background: #ff6b6b;
            color: white;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Add these new styles for the main container layout */
        .main-container {
            display: flex;
            gap: 20px;
            padding: 20px;
            margin-top: 80px;
        }

        .filter-sidebar {
            flex: 0 0 300px;
            position: sticky;
            top: 100px;
            height: fit-content;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .content-area {
            flex: 1;
        }

        .filter-section {
            margin-bottom: 0;
            box-shadow: none;
            padding: 0;
        }

        /* Update search container styles */
        .search-container {
            margin-top: 0; /* Remove top margin as it's handled by main-container */
        }

        /* Update filter form styles */
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .filter-group {
            margin-bottom: 15px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">MatrimoSys</div>
        <nav class="nav">
        <a href="request.php">Requests</a>
            <a href="preference.php">Preferences</a>
            <a href="normal_user.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <div class="main-container">
        <!-- Filter Sidebar -->
        <div class="filter-sidebar">
            <div class="filter-header">
                <i class="fas fa-filter"></i>
                <h3>Filter Profiles</h3>
            </div>
            <form id="filterForm" class="filter-form">
                <div class="filter-group">
                    <label for="filter_gender">Gender:</label>
                    <select name="filter_gender" id="filter_gender">
                        <option value="" selected>All</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Age Range:</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" id="filter_min_age" name="filter_min_age" placeholder="Min Age" min="18" max="100">
                        <input type="number" id="filter_max_age" name="filter_max_age" placeholder="Max Age" min="18" max="100">
                    </div>
                </div>

                <div class="filter-group">
                    <label for="filter_religion">Religion:</label>
                    <select name="filter_religion" id="filter_religion">
                        <option value="" selected>All</option>
                        <?php foreach ($religions as $religion): ?>
                            <option value="<?php echo htmlspecialchars($religion['religion_id']); ?>">
                                <?php echo htmlspecialchars($religion['religion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter_caste">Caste:</label>
                    <select name="filter_caste" id="filter_caste">
                        <option value="" selected>All</option>
                        <!-- Will be populated via JavaScript -->
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter_complexion">Complexion:</label>
                    <select name="filter_complexion" id="filter_complexion">
                        <option value="" selected>All</option>
                        <?php foreach ($complexions as $complexion): ?>
                            <option value="<?php echo htmlspecialchars($complexion); ?>">
                                <?php echo htmlspecialchars($complexion); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Height Range (ft):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" id="filter_min_height" name="filter_min_height" step="0.1" min="4" max="7" placeholder="Min">
                        <input type="number" id="filter_max_height" name="filter_max_height" step="0.1" min="4" max="7" placeholder="Max">
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="button" onclick="applyFilters()" class="filter-btn apply">
                        <i class="fas fa-check"></i> Apply Filters
                    </button>
                    <button type="button" onclick="clearFilters()" class="filter-btn clear">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Search Form -->
            <form method="POST" class="search-form">
                <div class="search-input-group">
                    <select name="search_type" required>
                        <option value="">Search By</option>
                        <option value="name">Name</option>
                        <option value="religion">Religion</option>
                        <option value="caste">Caste</option>
                        <option value="complexion">Complexion</option>
                    </select>
                    <div class="search-input-wrapper">
                        <input type="text" name="search_term" placeholder="Enter search term" required>
                    </div>
                </div>
                <div class="search-buttons">
                    <button type="submit" name="search" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <button type="button" id="clearButton" class="clear-btn">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </form>

            <!-- Results Container -->
            <div id="resultsContainer">
                <!-- Recommended Matches Section -->
                <?php if ($is_premium && !empty($recommended_matches) && empty($search_results)): ?>
                    <div class="recommended-section">
                        <h2 class="section-title"><i class="fas fa-heart"></i> Recommended Matches</h2>
                        <p class="section-subtitle">Based on your preferences</p>
                        
                        <div class="search-results">
                            <?php foreach ($recommended_matches as $profile): ?>
                                <div class="profile-card recommended">
                                    <?php 
                                        // Determine image path with fallback
                                        $profile_card_image = 'uploads/default.png'; // Default fallback
                                        
                                        if (!empty($profile['image'])) {
                                            $profile_card_image = $profile['image'];
                                        }
                                        
                                        // Ensure the image path is valid
                                        if (!file_exists($profile_card_image)) {
                                            $profile_card_image = 'uploads/default.png';
                                        }
                                    ?>
                                    <div class="recommendation-badge">
                                        <i class="fas fa-star"></i> Match
                                    </div>
                                    <img src="<?php echo htmlspecialchars($profile_card_image); ?>" alt="Profile Image" class="profile-image" />
                                    
                                    <div class="profile-card-content">
                                        <h3><?php echo htmlspecialchars($profile['username']); ?></h3>
                                        <p><strong>Age:</strong> <?php 
                                            $dob = new DateTime($profile['dob']);
                                            $today = new DateTime('today');
                                            echo $dob->diff($today)->y; 
                                        ?> years</p>
                                        <p><strong>Religion:</strong> <?php echo htmlspecialchars($profile['religion'] ?? 'N/A'); ?></p>
                                        <p><strong>Caste:</strong> <?php echo htmlspecialchars($profile['caste'] ?? 'N/A'); ?></p>
                                        <p><strong>Education:</strong> <?php echo htmlspecialchars($profile['eduSub'] ?? 'N/A'); ?></p>
                                        <p><strong>Height:</strong> <?php echo htmlspecialchars($profile['height'] ?? 'N/A'); ?> feet</p>
                                        <p><strong>Complexion:</strong> <?php echo htmlspecialchars($profile['complexion'] ?? 'N/A'); ?></p>
                                    </div>
                                    
                                    <div class="profile-card-actions">
                                        <button class="action-btn view-btn" onclick="viewProfile('<?php echo htmlspecialchars($profile['userid']); ?>')">
                                            <i class="fas fa-eye"></i>
                                            <span>View Profile</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filtered Results Section -->
                <div id="filteredResults" class="search-results" style="display: none;">
                    <!-- Results will be loaded here by JavaScript -->
                </div>

                <!-- Search Results Section -->
                <div id="searchResults" class="search-results" style="display: <?php echo !empty($search_results) ? 'grid' : 'none'; ?>">
                    <?php if (!empty($search_results)): ?>
                        <?php foreach ($search_results as $profile): ?>
                            <div class="profile-card">
                                <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="Profile Image" class="profile-card-image" />
                                <div class="profile-card-content">
                                    <h3><?php echo htmlspecialchars($profile['username']); ?></h3>
                                    <p><strong>Age:</strong> <?php 
                                        $dob = new DateTime($profile['dob']);
                                        $today = new DateTime('today');
                                        echo $dob->diff($today)->y; 
                                    ?> years</p>
                                    <p><strong>Religion:</strong> <?php echo htmlspecialchars($profile['religion'] ?? 'N/A'); ?></p>
                                    <p><strong>Caste:</strong> <?php echo htmlspecialchars($profile['caste'] ?? 'N/A'); ?></p>
                                    <p><strong>Education:</strong> <?php echo htmlspecialchars($profile['eduSub'] ?? 'N/A'); ?></p>
                                    <p><strong>Height:</strong> <?php echo htmlspecialchars($profile['height'] ?? 'N/A'); ?> feet</p>
                                    <p><strong>Complexion:</strong> <?php echo htmlspecialchars($profile['complexion'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="profile-card-actions">
                                    <button class="action-btn view-btn" onclick="viewProfile('<?php echo htmlspecialchars($profile['userid']); ?>')">
                                        <i class="fas fa-eye"></i>
                                        <span>View Profile</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- No Results Message -->
                <div class="no-results" id="noResultsMessage" style="display: none;">
                    <p>No results found.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for search limit message -->
    <div id="limitModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Free trial search for 24 hours used up.</p>
            <a href="payment.php" class="upgrade-button">Upgrade to Premium</a>
        </div>
    </div>

    <!-- Floating box for updating plan -->
    <div class="floating-box" onclick="redirectToWelcome()">
        <div class="floating-box-content">
            <i class="fas fa-crown"></i>
            <span>Upgrade to Premium</span>
        </div>
        <div class="pulse-ring"></div>
    </div>

    <script>
        document.getElementById('clearButton').addEventListener('click', function() {
            // Clear the search form
            document.querySelector('input[name="search_term"]').value = '';
            document.querySelector('select[name="search_type"]').selectedIndex = 0;
            
            // Clear filters
            clearFilters();
            
            // Show recommended section if it exists
            const recommendedSection = document.querySelector('.recommended-section');
            if (recommendedSection) {
                recommendedSection.style.display = 'block';
            }
            
            // Hide results sections
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('filteredResults').style.display = 'none';
            document.getElementById('noResultsMessage').style.display = 'none';
        });

        // Show the modal if the search limit is reached
        <?php if (isset($show_limit_message) && $show_limit_message): ?>
            document.getElementById('limitModal').style.display = 'flex';
        <?php endif; ?>

        // Close the modal when the close button is clicked
        document.querySelector('.close').onclick = function() {
            document.getElementById('limitModal').style.display = 'none';
        };

        // Close the modal when clicking outside of the modal content
        window.onclick = function(event) {
            if (event.target == document.getElementById('limitModal')) {
                document.getElementById('limitModal').style.display = 'none';
            }
        };

        // Function to redirect to welcome.php
        function redirectToWelcome() {
            window.location.href = 'welcome.php';
        }

        function viewProfile(userid) {
            window.location.href = 'view_profile.php?userid=' + userid; // Redirect to view_profile.php with userid
        }

        // Store all castes data
        const allCastes = <?php echo json_encode($castes); ?>;

        // Function to update castes based on selected religion
        function updateCastes(religionId) {
            const casteSelect = document.getElementById('filter_caste');
            casteSelect.innerHTML = '<option value="">All</option>';
            
            if (religionId) {
                // Convert religionId to number for comparison
                const religionIdNum = parseInt(religionId);
                const filteredCastes = allCastes.filter(caste => parseInt(caste.religion_id) === religionIdNum);
                
                filteredCastes.forEach(caste => {
                    const option = document.createElement('option');
                    option.value = caste.caste_id;
                    option.textContent = caste.caste;
                    casteSelect.appendChild(option);
                });
            }
        }

        function applyFilters() {
            // Hide search results and recommended section
            document.getElementById('searchResults').style.display = 'none';
            const recommendedSection = document.querySelector('.recommended-section');
            if (recommendedSection) {
                recommendedSection.style.display = 'none';
            }

            // Clear search form
            document.querySelector('input[name="search_term"]').value = '';
            document.querySelector('select[name="search_type"]').selectedIndex = 0;

            const formData = new FormData(document.getElementById('filterForm'));
            formData.append('logged_in_user_gender', '<?php echo htmlspecialchars($logged_in_user_gender); ?>');
            
            fetch('filter_profiles.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultsContainer = document.getElementById('filteredResults');
                const noResultsMessage = document.getElementById('noResultsMessage');
                
                if (data.length > 0) {
                    resultsContainer.innerHTML = data.map(profile => `
                        <div class="profile-card">
                            <img src="${profile.image || 'uploads/default.png'}" alt="Profile Image" class="profile-card-image" />
                            <div class="profile-card-content">
                                <h3>${profile.username}</h3>
                                <p><strong>Age:</strong> ${profile.age} years</p>
                                <p><strong>Religion:</strong> ${profile.religion || 'N/A'}</p>
                                <p><strong>Caste:</strong> ${profile.caste || 'N/A'}</p>
                                <p><strong>Education:</strong> ${profile.eduSub || 'N/A'}</p>
                                <p><strong>Height:</strong> ${profile.height || 'N/A'} feet</p>
                                <p><strong>Complexion:</strong> ${profile.complexion || 'N/A'}</p>
                            </div>
                            <div class="profile-card-actions">
                                <button class="action-btn view-btn" onclick="viewProfile('${profile.userid}')">
                                    <i class="fas fa-eye"></i>
                                    <span>View Profile</span>
                                </button>
                            </div>
                        </div>
                    `).join('');
                    
                    resultsContainer.style.display = 'grid';
                    noResultsMessage.style.display = 'none';
                } else {
                    resultsContainer.style.display = 'none';
                    noResultsMessage.style.display = 'block';
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Update the search form submission
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            // Hide filtered results and recommended section
            document.getElementById('filteredResults').style.display = 'none';
            const recommendedSection = document.querySelector('.recommended-section');
            if (recommendedSection) {
                recommendedSection.style.display = 'none';
            }
            
            // Clear filters
            clearFilters(false); // Pass false to prevent showing recommended section
        });

        // Update the clearFilters function
        function clearFilters(showRecommended = true) {
            const form = document.getElementById('filterForm');
            form.reset();
            
            // Reset all filter fields
            document.getElementById('filter_gender').value = '';
            document.getElementById('filter_religion').value = '';
            document.getElementById('filter_caste').value = '';
            document.getElementById('filter_complexion').value = '';
            document.getElementById('filter_min_age').value = '';
            document.getElementById('filter_max_age').value = '';
            document.getElementById('filter_min_height').value = '';
            document.getElementById('filter_max_height').value = '';
            
            updateCastes('');
            
            document.getElementById('filteredResults').style.display = 'none';
            document.getElementById('noResultsMessage').style.display = 'none';
            
            // Only show recommended section if showRecommended is true
            if (showRecommended && document.querySelector('.recommended-section')) {
                document.querySelector('.recommended-section').style.display = 'block';
            }
        }

        // Add event listener for religion select
        document.getElementById('filter_religion').addEventListener('change', function() {
            updateCastes(this.value);
        });
    </script>

</body>
</html>