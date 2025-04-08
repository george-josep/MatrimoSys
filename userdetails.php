<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'connect.php';

// Fetch religions from database
$religions = array();
$sql = "SELECT religion_id, religion FROM tbl_religion";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $religions[$row['religion_id']] = $row['religion'];
    }
}

// Fetch castes for all religions
$castes = array();
$sql = "SELECT caste_id, religion_id, caste FROM tbl_caste";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if (!isset($castes[$row['religion_id']])) {
            $castes[$row['religion_id']] = array();
        }
        $castes[$row['religion_id']][] = array(
            'id' => $row['caste_id'],
            'name' => $row['caste']
        );
    }
}

// Fetch education categories
$education_categories = array();
$sql = "SELECT education_id, education FROM tbl_education";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $education_categories[$row['education_id']] = $row['education'];
    }
}

// Fetch sub-education options
$sub_education = array();
$sql = "SELECT edusub_id, education_id, eduSub FROM tbl_subEducation";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if (!isset($sub_education[$row['education_id']])) {
            $sub_education[$row['education_id']] = array();
        }
        $sub_education[$row['education_id']][] = array(
            'id' => $row['edusub_id'],
            'name' => $row['eduSub']
        );
    }
}

// Fetch user's current data if exists
$userId = $_SESSION['username']; // This is the user ID
$userData = array();
$familyData = array();

$sql = "SELECT p.*, f.* FROM profiles p 
        LEFT JOIN tbl_family f ON p.family_id = f.family_id 
        WHERE p.userid = ?"; // Use the correct column name for user ID
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userId); // Change to "s" for string
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $userData = $row;
    $familyData = array(
        'father_name' => $row['father_name'],
        'father_job' => $row['father_job'],
        'mother_name' => $row['mother_name'],
        'mother_job' => $row['mother_job'],
        'family_name' => $row['family_name'],
        'sibling_name' => $row['sibling_name']
    );
} else {
    echo "User not found. Please check if you are logged in.";
    exit(); // Stop further execution if user is not found
}

// Determine the home link based on payment_id
$homeLink = "home.html"; // Default link
if (isset($userData['payment_id'])) {
    if ($userData['payment_id'] == 3) {
        $homeLink = "premium_user.php";
    } elseif ($userData['payment_id'] == 2) {
        $homeLink = "normal_user.php";
    }
}

// Handle image upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['user_images'])) {
    $uploadedFiles = $_FILES['user_images'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $uploadPath = 'uploads/gallery/';
    $userId = $_SESSION['username']; // Get the current user's ID
    
    // Debug information
    error_log("Starting image upload process");
    error_log("Upload path: " . $uploadPath);
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
        error_log("Created directory: " . $uploadPath);
    }

    $uploadSuccess = false; // Track if any images were uploaded successfully

    foreach ($uploadedFiles['tmp_name'] as $key => $tmp_name) {
        if ($uploadedFiles['error'][$key] === UPLOAD_ERR_OK) {
            $fileType = $uploadedFiles['type'][$key];
            $fileSize = $uploadedFiles['size'][$key];
            $originalName = $uploadedFiles['name'][$key];
            
            // Generate unique filename
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadPath . $fileName;
            
            error_log("Processing file: " . $originalName);
            error_log("File type: " . $fileType);
            error_log("File size: " . $fileSize);
            
            // Validate file type and size
            if (!in_array($fileType, $allowedTypes)) {
                error_log("Invalid file type: " . $fileType);
                continue;
            }
            
            if ($fileSize > $maxFileSize) {
                error_log("File too large: " . $fileSize);
                continue;
            }
            
            // Move uploaded file
            if (move_uploaded_file($tmp_name, $filePath)) {
                error_log("File moved successfully to: " . $filePath);
                
                // Insert into database
                $sql = "INSERT INTO tbl_images (userid, image) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $userId, $filePath);
                
                if ($stmt->execute()) {
                    error_log("Image inserted into database successfully");
                    $uploadSuccess = true; // At least one image was uploaded successfully
                } else {
                    error_log("Database insertion failed: " . $stmt->error);
                }
                $stmt->close();
            } else {
                error_log("Failed to move uploaded file");
            }
        } else {
            error_log("Upload error code: " . $uploadedFiles['error'][$key]);
        }
    }
    
    // Redirect to profile.php if at least one image was uploaded successfully
    if ($uploadSuccess) {
        header("Location: profile.php");
    } else {
        header("Location: userdetails.php");
    }
    exit();
}

// Fetch existing images for the user
$userImages = array();
$imageQuery = "SELECT img_id, image FROM tbl_images WHERE userid = ?";
$imageStmt = $conn->prepare($imageQuery);
$imageStmt->bind_param("s", $_SESSION['username']);
$imageStmt->execute();
$imageResult = $imageStmt->get_result();
while($row = $imageResult->fetch_assoc()) {
    $userImages[] = $row;
}

// Handle form submission for profile updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_FILES['user_images'])) {
    // Additional security: Verify the logged-in user
    if (!isset($_SESSION['username'])) {
        die("Unauthorized access");
    }

    // Ensure we're using the logged-in user's ID for updates
    $userId = $_SESSION['username'];

    // Get form data
    $age = $_POST['age'];
    $complexion = $_POST['complexion'];
    $height = $_POST['height'];
    $caste_id = $_POST['caste'];
    $edusub_id = $_POST['qualification'];
    $nativity = $_POST['nativity'];
    $gender = $_POST['gender'];
    $about = $_POST['about'];
    $father_name = $_POST['father_name'];
    $father_job = $_POST['father_job'];
    $mother_name = $_POST['mother_name'];
    $mother_job = $_POST['mother_job'];
    $family_name = $_POST['family_name'];
    $sibling_name = $_POST['sibling_name'];
    
    // Update or insert family data
    $family_sql = isset($userData['family_id']) 
        ? "UPDATE tbl_family SET father_name=?, father_job=?, mother_name=?, mother_job=?, family_name=?, sibling_name=? WHERE family_id=?"
        : "INSERT INTO tbl_family (father_name, father_job, mother_name, mother_job, family_name, sibling_name) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($family_sql);
    
    if (isset($userData['family_id'])) {
        $stmt->bind_param("ssssssi", $father_name, $father_job, $mother_name, $mother_job, $family_name, $sibling_name, $userData['family_id']);
        $stmt->execute();
        $family_id = $userData['family_id'];
    } else {
        $stmt->bind_param("ssssss", $father_name, $father_job, $mother_name, $mother_job, $family_name, $sibling_name);
        $stmt->execute();
        $family_id = $conn->insert_id;
    }

    // Update existing profile ONLY for the logged-in user
    $profile_sql = "UPDATE profiles SET 
        age = COALESCE(NULLIF(?, ''), age), 
        complexion = COALESCE(NULLIF(?, ''), complexion), 
        height = COALESCE(NULLIF(?, ''), height), 
        caste_id = COALESCE(NULLIF(?, ''), caste_id), 
        edusub_id = COALESCE(NULLIF(?, ''), edusub_id), 
        nativity = COALESCE(NULLIF(?, ''), nativity), 
        gender = COALESCE(NULLIF(?, ''), gender), 
        about = COALESCE(NULLIF(?, ''), about), 
        family_id = COALESCE(NULLIF(?, ''), family_id) 
        WHERE userid=?";
    $stmt = $conn->prepare($profile_sql);
    $stmt->bind_param("issiisssss", $age, $complexion, $height, $caste_id, $edusub_id, $nativity, $gender, $about, $family_id, $userId);
    $stmt->execute();
    
    header("Location: profile.php");
    exit();
}

// Update the image deletion handler (place this before ANY HTML output)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_image'])) {
    // Prevent any output
    ob_clean();
    
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    $imgId = intval($_POST['delete_image']);
    $userId = $_SESSION['username'];
    $uploadPath = 'C:/xampp/htdocs/mini/uploads/gallery/';
    
    try {
        // First, get the image path
        $stmt = $conn->prepare("SELECT image FROM tbl_images WHERE img_id = ? AND userid = ?");
        $stmt->bind_param("is", $imgId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $imagePath = $row['image'];
            if (strpos($imagePath, 'uploads/gallery/') !== false) {
                $imagePath = str_replace('uploads/gallery/', $uploadPath, $imagePath);
            }
            
            $conn->begin_transaction();
            
            $deleteStmt = $conn->prepare("DELETE FROM tbl_images WHERE img_id = ? AND userid = ?");
            $deleteStmt->bind_param("is", $imgId, $userId);
            
            if ($deleteStmt->execute()) {
                if (file_exists($imagePath)) {
                    if (unlink($imagePath)) {
                        $conn->commit();
                        echo json_encode(['success' => true]);
                    } else {
                        throw new Exception("Failed to delete file");
                    }
                } else {
                    $conn->commit();
                    echo json_encode(['success' => true]);
                }
            } else {
                throw new Exception("Failed to delete from database");
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Image not found']);
        }
    } catch (Exception $e) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackError) {
            // Silently handle rollback failure
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - MatrimoSys</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            color: #333;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('2.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            line-height: 1.6;
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

        .container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }

        form {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .section {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .section:hover {
            transform: translateY(-5px);
        }

        h2 {
            color: #f3c634;
            font-size: 1.8rem;
            margin-bottom: 25px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: #FFD700;
            border-radius: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fff;
            color: #2d3748;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
            outline: none;
        }

        button[type="submit"] {
            background: #FFD700;
            color: #333;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 40px auto 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        button[type="submit"]:hover {
            background: #f3c634;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .error-message {
            background: #fff5f5;
            color: #c53030;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c53030;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                margin-top: 100px;
                padding: 0 15px;
            }

            form {
                padding: 20px;
            }

            .section {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            h2 {
                font-size: 1.5rem;
            }
        }

        .gallery-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .image-upload-area {
            border: 2px dashed #FFD700;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            background: rgba(255, 215, 0, 0.05);
        }

        .upload-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .preview-item {
            position: relative;
            padding-bottom: 100%;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background: #f8f8f8;
        }

        .preview-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .upload-controls {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }

        .select-btn {
            background: #f3c634;
            color: #333;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(243, 198, 52, 0.2);
        }

        .select-btn:hover {
            transform: translateY(-2px);
            background: #FFD700;
            box-shadow: 0 4px 15px rgba(243, 198, 52, 0.3);
        }

        .select-btn i {
            font-size: 1.2rem;
        }

        .upload-btn {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(76, 175, 80, 0.2);
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .upload-info {
            color: #666;
            font-size: 0.9rem;
            text-align: center;
            margin: 5px 0;
        }

        .existing-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .image-card {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .image-card:hover {
            transform: translateY(-5px);
        }

        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .image-card:hover .image-overlay {
            opacity: 1;
        }

        .image-card:hover img {
            filter: blur(2px);
        }

        .delete-btn {
            background: #ff3b30;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .delete-btn i {
            font-size: 16px;
        }

        .delete-btn:hover {
            background: #ff1a1a;
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        /* Add animation for overlay */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .image-overlay {
            animation: fadeIn 0.3s ease;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <script>
        function deleteImage(imageId) {
            imageId = parseInt(imageId, 10); // Convert to integer
            if (!imageId || isNaN(imageId)) {
                console.error('Invalid image ID provided');
                return;
            }

            Swal.fire({
                title: 'Delete Image',
                text: 'Are you sure you want to delete this image?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3b30',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('image_id', imageId);

                    fetch('delete_image.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const imageCard = document.getElementById(`image-card-${imageId}`);
                            if (imageCard) {
                                imageCard.remove();
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Image has been deleted successfully.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            }
                        } else {
                            throw new Error(data.error || 'Failed to delete image');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Failed to delete the image. Please try again.',
                            icon: 'error'
                        });
                    });
                }
            });
        }

        // DOM content loaded event listener
        document.addEventListener('DOMContentLoaded', function() {
            const imageCards = document.querySelectorAll('.image-card');
            imageCards.forEach(card => {
                if (!card.id) {
                    console.error('Found image card without ID:', card);
                }
            });
        });
    </script>
</head>
<body>
    <header class="header">
        <div class="logo">MatrimoSys</div>
        <nav class="nav">
            <a href="<?php echo $homeLink; ?>">Home</a>
            <a href="profile.php">View Profile</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <div class="container">
        <form method="post">
            <?php if(isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="section">
                <h2>Personal Details</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="age">Age</label>
                        <select id="age" name="age">
                            <option value="">Select Age</option>
                            <?php
                            for($i = 18; $i <= 60; $i++) {
                                $selected = (isset($userData['age']) && $userData['age'] == $i) ? 'selected' : '';
                                echo "<option value='$i' $selected>$i years</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="complexion">Complexion</label>
                        <select id="complexion" name="complexion">
                            <option value="">Select Complexion</option>
                            <?php
                            $complexions = ['Fair', 'Light', 'Medium', 'Tan', 'Deep'];
                            foreach($complexions as $c) {
                                $selected = (isset($userData['complexion']) && $userData['complexion'] == $c) ? 'selected' : '';
                                echo "<option value='$c' $selected>$c</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="height">Height</label>
                        <div class="height-input-group" style="display: flex; gap: 10px;">
                            <select id="height_feet" name="height_feet" style="width: 50%;">
                                <option value="">Feet</option>
                                <?php
                                for($i = 4; $i <= 7; $i++) {
                                    $selected = '';
                                    if (isset($userData['height'])) {
                                        $height_parts = explode("'", $userData['height']);
                                        if (isset($height_parts[0]) && trim($height_parts[0]) == $i) {
                                            $selected = 'selected';
                                        }
                                    }
                                    echo "<option value='$i' $selected>$i ft</option>";
                                }
                                ?>
                            </select>
                            <select id="height_inches" name="height_inches" style="width: 50%;">
                                <option value="">Inches</option>
                                <?php
                                for($i = 0; $i <= 11; $i++) {
                                    $selected = '';
                                    if (isset($userData['height'])) {
                                        $height_parts = explode("'", $userData['height']);
                                        if (isset($height_parts[1]) && trim(str_replace('"', '', $height_parts[1])) == $i) {
                                            $selected = 'selected';
                                        }
                                    }
                                    echo "<option value='$i' $selected>$i in</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <input type="hidden" id="height" name="height">
                    </div>
                    <div class="form-group">
                        <label for="religion">Religion</label>
                        <select id="religion" name="religion" onchange="updateCastes(this.value)">
                            <option value="">Select Religion</option>
                            <?php foreach($religions as $religion_id => $religion): ?>
                                <?php 
                                    $selected = (isset($userData['religion_id']) && $userData['religion_id'] == $religion_id) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $religion_id; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($religion); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="caste">Caste</label>
                        <select id="caste" name="caste">
                            <option value="">Select Religion First</option>
                            <?php if(isset($userData['caste_id'])): ?>
                                <?php foreach($castes as $religion_id => $caste_list): ?>
                                    <?php foreach($caste_list as $caste): ?>
                                        <?php $selected = ($userData['caste_id'] == $caste['id']) ? 'selected' : ''; ?>
                                        <option value="<?php echo $caste['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($caste['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="education_category">Education Category</label>
                        <select id="education_category" name="education_category" onchange="updateQualifications(this.value)">
                            <option value="">Select Education Category</option>
                            <?php foreach($education_categories as $edu_id => $education): ?>
                                <?php 
                                    $selected = '';
                                    if (isset($userData['edusub_id']) && isset($sub_education[$edu_id])) {
                                        foreach ($sub_education[$edu_id] as $sub) {
                                            if ($sub['id'] == $userData['edusub_id']) {
                                                $selected = 'selected';
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <option value="<?php echo $edu_id; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($education); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="qualification">Qualification</label>
                        <select id="qualification" name="qualification">
                            <option value="">Select Education Category First</option>
                            <?php 
                            if (isset($userData['edusub_id'])) {
                                foreach ($sub_education as $edu_subs) {
                                    foreach ($edu_subs as $sub) {
                                        $selected = ($userData['edusub_id'] == $sub['id']) ? 'selected' : '';
                                        echo '<option value="' . $sub['id'] . '" ' . $selected . '>' . 
                                             htmlspecialchars($sub['name']) . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nativity">Nativity</label>
                        <input type="text" id="nativity" name="nativity" 
                               value="<?php echo isset($userData['nativity']) ? htmlspecialchars($userData['nativity']) : ''; ?>"
                               placeholder="Enter your nativity">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo (isset($userData['gender']) && $userData['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo (isset($userData['gender']) && $userData['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Family Details Section -->
            <div class="section">
                <h2>Family Details</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="father_name">Father's Name</label>
                        <input type="text" id="father_name" name="father_name" 
                               value="<?php echo isset($familyData['father_name']) ? htmlspecialchars($familyData['father_name']) : ''; ?>"
                               placeholder="Enter father's name">
                    </div>
                    <div class="form-group">
                        <label for="father_job">Father's Occupation</label>
                        <input type="text" id="father_job" name="father_job" 
                               value="<?php echo isset($familyData['father_job']) ? htmlspecialchars($familyData['father_job']) : ''; ?>"
                               placeholder="Enter father's occupation">
                    </div>
                    <div class="form-group">
                        <label for="mother_name">Mother's Name</label>
                        <input type="text" id="mother_name" name="mother_name" 
                               value="<?php echo isset($familyData['mother_name']) ? htmlspecialchars($familyData['mother_name']) : ''; ?>"
                               placeholder="Enter mother's name">
                    </div>
                    <div class="form-group">
                        <label for="mother_job">Mother's Occupation</label>
                        <input type="text" id="mother_job" name="mother_job" 
                               value="<?php echo isset($familyData['mother_job']) ? htmlspecialchars($familyData['mother_job']) : ''; ?>"
                               placeholder="Enter mother's occupation">
                    </div>
                    <div class="form-group">
                        <label for="family_name">Family Name</label>
                        <input type="text" id="family_name" name="family_name" 
                               value="<?php echo isset($familyData['family_name']) ? htmlspecialchars($familyData['family_name']) : ''; ?>"
                               placeholder="Enter family name">
                    </div>
                    <div class="form-group">
                        <label for="sibling_name">Sibling's Name</label>
                        <input type="text" id="sibling_name" name="sibling_name" 
                               value="<?php echo isset($familyData['sibling_name']) ? htmlspecialchars($familyData['sibling_name']) : ''; ?>"
                               placeholder="Enter sibling's name">
                    </div>
                </div>
            </div>

            <!-- Move the About Section here -->
            <div class="section">
                <h2>About</h2>
                <div class="form-group">
                    <label for="about">Brief Description</label>
                    <textarea id="about" name="about" rows="4" style="width: 100%;" placeholder="Tell us something about yourself"><?php echo isset($userData['about']) ? htmlspecialchars($userData['about']) : ''; ?></textarea>
                </div>
            </div>

            <button type="submit">Update Profile</button>
        </form>

        <!-- Photo Gallery Section - Separate form for image uploads -->
        <div class="section">
            <h2>Photo Gallery</h2>
            <div class="gallery-container">
                <div class="image-upload-area">
                    <form method="POST" enctype="multipart/form-data" id="imageUploadForm">
                        <div class="upload-preview" id="imagePreviewContainer">
                            <!-- Preview images will be displayed here -->
                        </div>
                        <div class="upload-controls" id="upload-controls">
                            <label for="user_images" class="select-btn">
                                <i class="fas fa-images"></i>
                                Select Images
                            </label>
                            <input type="file" id="user_images" name="user_images[]" 
                                   accept="image/jpeg,image/png,image/jpg" multiple 
                                   style="display: none" onchange="previewImages(this)">
                            <p class="upload-info">
                                Max 5 images, each under 5MB (JPG, PNG)
                            </p>
                            <button type="submit" class="upload-btn">
                                <i class="fas fa-cloud-upload-alt"></i> Upload Images
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="existing-images">
                    <?php foreach($userImages as $image): ?>
                        <div class="image-card" id="image-card-<?php echo (int)$image['img_id']; ?>">
                            <img src="<?php echo htmlspecialchars($image['image']); ?>" 
                                 alt="User uploaded image">
                            <div class="image-overlay">
                                <button type="button" class="delete-btn" 
                                        onclick="deleteImage(<?php echo (int)$image['img_id']; ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                    Delete Image
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this hidden form for delete functionality -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_image" id="deleteImageId">
    </form>

    <script>
        // Store preselected values from PHP
        const preselectedCasteId = <?php echo isset($userData['caste_id']) ? $userData['caste_id'] : 'null'; ?>;
        const preselectedEdusubId = <?php echo isset($userData['edusub_id']) ? $userData['edusub_id'] : 'null'; ?>;
        const preselectedHeight = '<?php echo isset($userData['height']) ? $userData['height'] : ''; ?>';
        const preselectedReligionId = <?php echo isset($userData['religion_id']) ? $userData['religion_id'] : 'null'; ?>;

        // Function to update castes based on selected religion
        function updateCastes(religionId) {
            const casteSelect = document.getElementById('caste');
            casteSelect.innerHTML = '<option value="">Select Caste</option>';
            
            if (!religionId) return;

            // Get castes for the selected religion
            const castes = <?php echo json_encode($castes); ?>;
            const religionCastes = castes[religionId] || [];
            
            religionCastes.forEach(caste => {
                const option = document.createElement('option');
                option.value = caste.id;
                option.textContent = caste.name;
                if (preselectedCasteId && caste.id == preselectedCasteId) {
                    option.selected = true;
                }
                casteSelect.appendChild(option);
            });
        }

        // Function to update qualifications based on selected education category
        function updateQualifications(educationId) {
            const qualificationSelect = document.getElementById('qualification');
            qualificationSelect.innerHTML = '<option value="">Select Qualification</option>';
            
            if (!educationId) return;

            // Get sub-education options for the selected education category
            const subEducation = <?php echo json_encode($sub_education); ?>;
            const educationQualifications = subEducation[educationId] || [];
            
            educationQualifications.forEach(qualification => {
                const option = document.createElement('option');
                option.value = qualification.id;
                option.textContent = qualification.name;
                if (preselectedEdusubId && qualification.id == preselectedEdusubId) {
                    option.selected = true;
                }
                qualificationSelect.appendChild(option);
            });
        }

        // Initialize all dropdowns when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize height dropdown
            if (preselectedHeight) {
                const heightSelect = document.getElementById('height');
                Array.from(heightSelect.options).forEach(option => {
                    if (option.value === preselectedHeight) {
                        option.selected = true;
                    }
                });
            }

            // Initialize religion and caste dropdowns
            if (preselectedReligionId) {
                const religionSelect = document.getElementById('religion');
                Array.from(religionSelect.options).forEach(option => {
                    if (option.value == preselectedReligionId) {
                        option.selected = true;
                    }
                });
                // Update caste options based on selected religion
                updateCastes(preselectedReligionId);
            }

            // Initialize education category and qualification
            const educationSelect = document.getElementById('education_category');
            if (preselectedEdusubId) {
                // Find the correct education category for the preselected qualification
                const subEducation = <?php echo json_encode($sub_education); ?>;
                for (const [eduId, qualifications] of Object.entries(subEducation)) {
                    for (const qual of qualifications) {
                        if (qual.id == preselectedEdusubId) {
                            // Set the education category
                            Array.from(educationSelect.options).forEach(option => {
                                if (option.value === eduId) {
                                    option.selected = true;
                                }
                            });
                            // Update qualifications dropdown
                            updateQualifications(eduId);
                            break;
                        }
                    }
                }
            }

            // Set preselected values for age
            if (<?php echo isset($userData['age']) ? 'true' : 'false'; ?>) {
                const ageSelect = document.getElementById('age');
                const preselectedAge = <?php echo isset($userData['age']) ? $userData['age'] : 'null'; ?>;
                Array.from(ageSelect.options).forEach(option => {
                    if (option.value == preselectedAge) {
                        option.selected = true;
                    }
                });
            }

            // Set preselected values for complexion
            if (<?php echo isset($userData['complexion']) ? 'true' : 'false'; ?>) {
                const complexionSelect = document.getElementById('complexion');
                const preselectedComplexion = '<?php echo isset($userData['complexion']) ? $userData['complexion'] : ''; ?>';
                Array.from(complexionSelect.options).forEach(option => {
                    if (option.value === preselectedComplexion) {
                        option.selected = true;
                    }
                });
            }
        });

        // Add event listeners for dependent dropdowns
        document.getElementById('religion').addEventListener('change', function() {
            updateCastes(this.value);
        });

        document.getElementById('education_category').addEventListener('change', function() {
            updateQualifications(this.value);
        });

        function previewImages(input) {
            const container = document.getElementById('imagePreviewContainer');
            container.innerHTML = '';
            
            if (input.files) {
                const files = Array.from(input.files);
                
                files.forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        const preview = document.createElement('div');
                        preview.className = 'preview-item';
                        
                        reader.onload = function(e) {
                            preview.innerHTML = `
                                <img src="${e.target.result}" alt="Preview">
                            `;
                        }
                        
                        reader.readAsDataURL(file);
                        container.appendChild(preview);
                    }
                });
            }
        }

        // Function to update the hidden height input
        function updateHeight() {
            const feet = document.getElementById('height_feet').value;
            const inches = document.getElementById('height_inches').value;
            const heightInput = document.getElementById('height');
            
            if (feet && inches !== '') {
                heightInput.value = `${feet}'${inches}"`;
            } else if (feet) {
                heightInput.value = `${feet}'0"`;
            } else {
                heightInput.value = '';
            }
        }

        // Add event listeners for height selects
        document.getElementById('height_feet').addEventListener('change', updateHeight);
        document.getElementById('height_inches').addEventListener('change', updateHeight);

        // Initialize height if there's existing data
        if (<?php echo isset($userData['height']) ? 'true' : 'false'; ?>) {
            const existingHeight = '<?php echo isset($userData['height']) ? $userData['height'] : ''; ?>';
            if (existingHeight) {
                const heightParts = existingHeight.split("'");
                if (heightParts.length > 0) {
                    const feet = heightParts[0];
                    const inches = heightParts[1] ? heightParts[1].replace('"', '') : '0';
                    
                    const feetSelect = document.getElementById('height_feet');
                    const inchesSelect = document.getElementById('height_inches');
                    
                    Array.from(feetSelect.options).forEach(option => {
                        if (option.value === feet) {
                            option.selected = true;
                        }
                    });
                    
                    Array.from(inchesSelect.options).forEach(option => {
                        if (option.value === inches) {
                            option.selected = true;
                        }
                    });
                    
                    updateHeight();
                }
            }
        }
    </script>
</body>
</html>