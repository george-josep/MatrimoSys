<?php
session_start();
include 'connect.php';

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch user preferences
$preferences_query = "SELECT * FROM tbl_preference WHERE reg_id = ?";
$preferences_stmt = $conn->prepare($preferences_query);
$preferences_stmt->bind_param('i', $_SESSION['reg_id']); // Assuming reg_id is stored in session
$preferences_stmt->execute();
$preferences_result = $preferences_stmt->get_result();
$user_preferences = $preferences_result->fetch_assoc();

// Debug: Log the fetched preferences
error_log("User Preferences: " . print_r($user_preferences, true));

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
    
    // Debug: Log the query
    error_log("Recommended SQL: " . $recommended_sql);
    
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
    
    // Debug: Log parameters
    error_log("Recommended Parameters: " . print_r($params, true));
    
    // Add this before preparing the statement
    error_log("Generated SQL Query: " . $recommended_sql);
    error_log("Parameters: " . print_r($params, true));
    error_log("Types string: " . $types);
    
    // Before the bind_param, add error checking for the prepare statement
    if ($recommended_stmt === false) {
        // Log the error and handle it gracefully
        error_log("Prepare failed: " . $conn->error);
        $recommended_matches = []; // Set empty array as fallback
    } else {
        // Only proceed with bind_param if prepare was successful
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
    }
    
    // Debug: Log the number of recommendations
    error_log("Number of recommended matches: " . count($recommended_matches));
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
    
    error_log("No preferences found. Showing random profiles. Count: " . count($recommended_matches));
}

// Handle search functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['search'])) {
        // Get search parameters
        $search_term = isset($_POST['search_term']) ? trim($_POST['search_term']) : '';

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

        // Add gender filter
        if ($logged_in_user_gender === 'male') {
            $sql .= " AND p.gender = 'female'";
        } elseif ($logged_in_user_gender === 'female') {
            $sql .= " AND p.gender = 'male'";
        }

        // Remove common filler words
        $fillerWords = ['who', 'is', 'are', 'the', 'a', 'an', 'and', 'or', 'with', 'looking', 'for'];
        $searchWords = array_filter(
            explode(' ', strtolower($search_term)),
            function($word) use ($fillerWords) {
                return !in_array($word, $fillerWords);
            }
        );

        $conditions = [];
        $params = [];
        $types = '';

        foreach ($searchWords as $word) {
            $wordConditions = [];
            
            // Check each field for the search term
            $wordConditions[] = "LOWER(p.username) LIKE LOWER(?)";
            $wordConditions[] = "LOWER(p.complexion) LIKE LOWER(?)";
            $wordConditions[] = "LOWER(rel.religion) LIKE LOWER(?)";
            $wordConditions[] = "LOWER(c.caste) LIKE LOWER(?)";
            $wordConditions[] = "LOWER(e.eduSub) LIKE LOWER(?)";
            $wordConditions[] = "LOWER(p.nativity) LIKE LOWER(?)";
            
            // Add parameters for each condition
            for ($i = 0; $i < 6; $i++) {
                $params[] = "%$word%";
                $types .= 's';
            }

            // Special handling for numeric values (age and height)
            if (is_numeric($word)) {
                $wordConditions[] = "p.age = ?";
                $params[] = intval($word);
                $types .= 'i';
                
                $wordConditions[] = "p.height LIKE ?";
                $params[] = "%$word%";
                $types .= 's';
            }

            if (!empty($wordConditions)) {
                $conditions[] = "(" . implode(" OR ", $wordConditions) . ")";
            }
        }

        // Add conditions to SQL if any exist
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        // Exclude current user
        $sql .= " AND p.userid != ?";
        $params[] = $_SESSION['username'];
        $types .= 's';

        // Debug: Log the query and parameters
        error_log("Search SQL: " . $sql);
        error_log("Search Parameters: " . print_r($params, true));

        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $bind_params = array_merge([$types], $params);
            call_user_func_array([$stmt, 'bind_param'], makeValuesReferenced($bind_params));
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $search_results = [];

        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }

        // Debug: Log the number of results
        error_log("Number of search results: " . count($search_results));
    } elseif (isset($_POST['clear'])) {
        // Clear the search results
        $search_results = [];
    }
}

// Prepare a single statement to fetch images for all search results
if (!empty($search_results)) {
    // Debug: Print out all search result userids
    $search_userids = array_column($search_results, 'userid');
    error_log("Search Result UserIDs: " . print_r($search_userids, true));
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

// Fetch castes with religion_id for dropdown
$castes = [];
$caste_query = "SELECT c.caste_id, c.caste, c.religion_id, r.religion 
                FROM tbl_caste c 
                JOIN tbl_religion r ON c.religion_id = r.religion_id";
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

// Use the same complexion options as in userdetails.php instead of fetching from database
$complexions = ['Fair', 'Light', 'Medium', 'Tan', 'Deep'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to MatrimoSys</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400&display=swap');
        @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

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

        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3c634;
        }

        .filter-header i {
            color: #f3c634;
        }

        .filter-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }

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

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #f3c634;
            box-shadow: 0 0 5px rgba(243, 198, 52, 0.3);
            outline: none;
        }

        .apply-filters-btn {
            background: linear-gradient(135deg, #f3c634, #FFB347);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        .apply-filters-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(243, 198, 52, 0.3);
        }

        .search-container {
            margin: 0;
            padding: 0;
            background: transparent;
            box-shadow: none;
        }

        .search-form {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-input-wrapper {
            position: relative;
            width: 100%;
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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

        .search-btn i, .clear-btn i {
            font-size: 18px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }

            .filter-sidebar {
                position: static;
                width: 100%;
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
            padding: 15px;
            background: linear-gradient(to bottom, #f9f9f9, #f0f0f0);
            display: flex;
            justify-content: center;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .view-profile-btn {
            width: 100%;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            background: linear-gradient(135deg, #f3c634, #FFB347);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(243, 198, 52, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .view-profile-btn i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .view-profile-btn:hover {
            background: linear-gradient(135deg, #FFB347, #f3c634);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 198, 52, 0.3);
        }

        .view-profile-btn:hover i {
            transform: scale(1.1);
        }

        .view-profile-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(243, 198, 52, 0.2);
        }

        /* Add shimmer effect */
        .view-profile-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to right,
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.3) 50%,
                rgba(255,255,255,0) 100%
            );
            transform: rotate(30deg);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .view-profile-btn:hover::after {
            opacity: 1;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: rotate(30deg) translateX(-100%);
            }
            100% {
                transform: rotate(30deg) translateX(100%);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .view-profile-btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .profile-card-actions {
                background: linear-gradient(to bottom, #2a2a2a, #222);
            }

            .view-profile-btn {
                background: linear-gradient(135deg, #f3c634, #e6a00d);
            }

            .view-profile-btn:hover {
                background: linear-gradient(135deg, #e6a00d, #f3c634);
            }
        }

        .no-results {
            text-align: center;
            padding: 50px;
            background: rgba(255,255,255,0.9);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

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

        @media (max-width: 768px) {
            .search-results {
                grid-template-columns: repeat(1, 1fr);
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .search-results {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Make sure the grid layout is consistent */
        .search-results {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 20px !important;
            padding: 20px !important;
        }
        
        /* Ensure profile card has consistent size */
        .profile-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
            height: auto;
            width: 100%;
        }
        
        .profile-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-position: center;
            background-color: #f0f0f0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .search-results {
                grid-template-columns: repeat(1, 1fr) !important;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .search-results {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        .clear-filters-btn {
            background: linear-gradient(135deg, #ff6b6b, #ff8787);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .clear-filters-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .clear-filters-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(255, 107, 107, 0.2);
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">MatrimoSys</div>
        <nav class="nav">
            <a href="request.php">Requests</a>
            <a href="preference.php">Preferences</a>
            <a href="premium_user.php">Home</a>
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

                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="button" class="apply-filters-btn" onclick="applyFilters()">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button type="button" class="clear-filters-btn" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Search Form -->
            <form method="POST" class="search-form">
                <div class="search-input-wrapper">
                    <input type="text" name="search_term" 
                           placeholder="Search by religion, caste, education, age, complexion..." 
                           required>
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

            <!-- Recommended Matches Section -->
            <?php if (!empty($recommended_matches) && empty($search_results) && !isset($_POST['search'])): ?>
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
                                <img src="<?php echo htmlspecialchars($profile_card_image); ?>" alt="Profile Image" class="profile-card-image" />
                                
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
                                    <button onclick="viewProfile('<?php echo htmlspecialchars($profile['userid']); ?>')" class="view-profile-btn">
                                        <i class="fas fa-user-circle"></i>
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
                            <?php 
                                // Determine image path with fallback
                                $profile_card_image = 'uploads/default.png'; // Default fallback
                                
                                if (!empty($profile['image'])) {
                                    $profile_card_image = $profile['image'];
                                }
                                
                                // Debug: Log the image path
                                error_log("Image path for user {$profile['userid']}: $profile_card_image");
                                
                                // Ensure the image path is valid
                                if (!file_exists($profile_card_image)) {
                                    error_log("Image file does not exist: $profile_card_image");
                                    $profile_card_image = 'uploads/default.png';
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($profile_card_image); ?>" alt="Profile Image" class="profile-card-image" />
                            
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
                                <button onclick="viewProfile('<?php echo htmlspecialchars($profile['userid']); ?>')" class="view-profile-btn">
                                    <i class="fas fa-user-circle"></i>
                                    <span>View Profile</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- No Results Message -->
            <?php if (isset($_POST['search']) && empty($search_results)): ?>
                <div class="no-results" id="noResultsMessage">
                    <p>No results found.</p>
                </div>
            <?php else: ?>
                <div class="no-results" id="noResultsMessage" style="display: none;">
                    <p>No results found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('clearButton').addEventListener('click', function() {
            // Clear the search term
            document.querySelector('input[name="search_term"]').value = '';
            
            // Show recommendations and hide results
            clearAllFilters();
            
            // Submit the form with clear parameter
            var form = document.querySelector('.search-form');
            var clearInput = document.createElement('input');
            clearInput.type = 'hidden';
            clearInput.name = 'clear';
            form.appendChild(clearInput);
            form.submit();
        });

        document.querySelector('.search-form').addEventListener('submit', function() {
            // Always hide the filtered results container when search is submitted
            document.getElementById('filteredResults').style.display = 'none';
            
            // Also hide the recommended section when search is submitted
            const recommendedSection = document.querySelector('.recommended-section');
            if (recommendedSection) {
                recommendedSection.style.display = 'none';
            }
            
            // Reset the filter form
            document.getElementById('filterForm').reset();
            
            // Note: searchResults visibility will be handled by PHP when the page reloads
        });

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
                const filteredCastes = allCastes.filter(caste => caste.religion_id == religionId);
                filteredCastes.forEach(caste => {
                    const option = document.createElement('option');
                    option.value = caste.caste_id;
                    option.textContent = caste.caste;
                    casteSelect.appendChild(option);
                });
            }
        }

        function applyFilters() {
            const formData = new FormData(document.getElementById('filterForm'));
            
            // Add the logged-in user's gender to help with gender-based filtering
            formData.append('logged_in_user_gender', '<?php echo htmlspecialchars($logged_in_user_gender); ?>');
            
            // Hide search results when filters are applied
            document.getElementById('searchResults').style.display = 'none';
            
            // Hide "No results found" message
            document.getElementById('noResultsMessage').style.display = 'none';
            
            // Show loading indicator
            const resultsContainer = document.getElementById('filteredResults');
            resultsContainer.innerHTML = '<div class="loading">Loading...</div>';
            
            // Hide recommendations
            const recommendedSection = document.querySelector('.recommended-section');
            if (recommendedSection) {
                recommendedSection.style.display = 'none';
            }
            
            // Make sure filtered results container is visible with grid layout
            resultsContainer.style.display = 'grid';
            resultsContainer.style.gridTemplateColumns = 'repeat(3, 1fr)';
            resultsContainer.style.gap = '20px';
            
            // Rest of the fetch logic...
            fetch('filter_profiles.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log("Received data:", data);
                resultsContainer.innerHTML = ''; // Clear existing results
                
                if (data.length === 0) {
                    // Show no results message and hide the filtered results container
                    document.getElementById('noResultsMessage').style.display = 'block';
                    resultsContainer.style.display = 'none';
                    return;
                }
                
                // Create profile cards for each result
                data.forEach(profile => {
                    const card = createProfileCard(profile);
                    resultsContainer.appendChild(card);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                resultsContainer.innerHTML = '<div class="error">Error loading results: ' + error.message + '</div>';
            });
        }

        function createProfileCard(profile) {
            // Create profile card HTML identical to the PHP template
            const card = document.createElement('div');
            card.className = 'profile-card';
            
            // Determine image path with fallback
            const imagePath = profile.image && profile.image.length > 0 ? profile.image : 'uploads/default.png';
            
            // Calculate age if needed
            let age = profile.age;
            if (!age && profile.dob) {
                const dob = new Date(profile.dob);
                const today = new Date();
                age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
            }
            
            // Create image element instead of using background-image
            const img = document.createElement('img');
            img.src = imagePath;
            img.alt = "Profile Image";
            img.className = "profile-card-image";
            // Set fixed image height to ensure consistency
            img.style.height = "200px";
            img.style.width = "100%";
            img.style.objectFit = "cover";
            card.appendChild(img);
            
            // Create content div
            const contentDiv = document.createElement('div');
            contentDiv.className = 'profile-card-content';
            
            // Add profile details
            contentDiv.innerHTML = `
                <h3>${profile.username}</h3>
                <p><strong>Age:</strong> ${age || 'N/A'} years</p>
                <p><strong>Religion:</strong> ${profile.religion || 'N/A'}</p>
                <p><strong>Caste:</strong> ${profile.caste || 'N/A'}</p>
                <p><strong>Education:</strong> ${profile.eduSub || 'N/A'}</p>
                <p><strong>Height:</strong> ${profile.height || 'N/A'}</p>
                <p><strong>Complexion:</strong> ${profile.complexion || 'N/A'}</p>
            `;
            card.appendChild(contentDiv);
            
            // Create actions div
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'profile-card-actions';
            
            // Create view profile button
            const viewBtn = document.createElement('button');
            viewBtn.className = 'view-profile-btn';
            viewBtn.innerHTML = '<i class="fas fa-user-circle"></i><span>View Profile</span>';
            viewBtn.onclick = function() {
                viewProfile(profile.userid);
            };
            
            actionsDiv.appendChild(viewBtn);
            card.appendChild(actionsDiv);
            
            return card;
        }

        // Add event listeners
        document.getElementById('filter_religion').addEventListener('change', function() {
            updateCastes(this.value);
        });

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Reset all filters on page load to ensure a clean state
            const form = document.getElementById('filterForm');
            form.reset();
            
            // Make sure caste dropdown is initialized
            updateCastes('');
        });

        // Modified clear function
        function clearAllFilters() {
            // Reset the filter form
            document.getElementById('filterForm').reset();
            
            // Hide filtered results
            document.getElementById('filteredResults').style.display = 'none';
            
            // Hide search results
            document.getElementById('searchResults').style.display = 'none';
            
            // Hide no results message
            document.getElementById('noResultsMessage').style.display = 'none';
            
            // Show recommendations
            const recommendedSection = document.querySelector('.recommended-section');
            if (recommendedSection) {
                recommendedSection.style.display = 'block';
            }
        }

        // Enhanced clearFilters function
        function clearFilters() {
            // Reset the filter form
            const form = document.getElementById('filterForm');
            form.reset();
            
            // Explicitly set all select elements to their empty/default options
            document.getElementById('filter_gender').value = '';
            document.getElementById('filter_religion').value = '';
            document.getElementById('filter_caste').value = '';
            document.getElementById('filter_complexion').value = '';
            
            // Clear all numeric inputs
            document.getElementById('filter_min_age').value = '';
            document.getElementById('filter_max_age').value = '';
            document.getElementById('filter_min_height').value = '';
            document.getElementById('filter_max_height').value = '';
            
            // Rebuild the caste dropdown with empty options
            updateCastes('');
            
            // Hide filtered results
            document.getElementById('filteredResults').style.display = 'none';
            
            // Hide no results message
            document.getElementById('noResultsMessage').style.display = 'none';
            
            // Show recommended matches if they exist
            const recommendedSection = document.querySelector('.recommended-section');
            if (recommendedSection) {
                recommendedSection.style.display = 'block';
            }
            
            console.log("Filters cleared");
        }
    </script>

</body>
</html>
