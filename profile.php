<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'];

// SQL query to fetch user details from profiles table along with related data
$sql = "SELECT p.*, 
               f.family_name, 
               f.father_name, 
               f.mother_name, 
               f.sibling_name, 
               c.caste, 
               e.eduSub,
               p.gender,
               p.about
        FROM profiles p 
        LEFT JOIN tbl_family f ON p.family_id = f.family_id 
        LEFT JOIN tbl_caste c ON p.caste_id = c.caste_id 
        LEFT JOIN tbl_subEducation e ON p.edusub_id = e.edusub_id 
        WHERE p.userid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows > 0) {
    // Fetch user details
    $user = $result->fetch_assoc();
} else {
    echo "<p>User not found.</p>";
    exit();
}

// Fetch the payment_id of the logged-in user
$payment_query = "SELECT payment_id FROM profiles WHERE userid = ?";
$payment_stmt = $conn->prepare($payment_query);
$payment_stmt->bind_param('s', $_SESSION['username']);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$payment_id = $payment_result->fetch_assoc()['payment_id'];

// Determine the home link based on payment_id
$home_link = ($payment_id == 3) ? 'premium_user.php' : (($payment_id == 2) ? 'normal_user.php' : 'home.html');
$payment_stmt->close();

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profileImage'])) {
    $image = $_FILES['profileImage'];
    
    // Check for upload errors
    if ($image['error'] !== UPLOAD_ERR_OK) {
        echo "<p>Error uploading file. Please try again.</p>";
        exit();
    }

    $imagePath = 'uploads/' . basename($image['name']); // Path to save the image

    // Move the uploaded file to the desired directory
    if (move_uploaded_file($image['tmp_name'], $imagePath)) {
        // Check if an image already exists for this user
        $checkImageSql = "SELECT image FROM profile_images WHERE userid = ?";
        $checkImageStmt = $conn->prepare($checkImageSql);
        $checkImageStmt->bind_param("s", $username);
        $checkImageStmt->execute();
        $checkImageResult = $checkImageStmt->get_result();

        if ($checkImageResult->num_rows > 0) {
            // Image exists, update it
            $updateSql = "UPDATE profile_images SET image = ? WHERE userid = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ss", $imagePath, $username);
            
            if ($updateStmt->execute()) {
                echo "<p>Profile image updated successfully!</p>";
            } else {
                echo "<p>Failed to update image in the database.</p>";
            }
            
            $updateStmt->close();
        } else {
            // Insert new image record
            $insertSql = "INSERT INTO profile_images (userid, image) VALUES (?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("ss", $username, $imagePath);
            
            if ($insertStmt->execute()) {
                echo "<p>Profile image inserted successfully!</p>";
            } else {
                echo "<p>Failed to insert image into the database.</p>";
            }
            
            $insertStmt->close();
        }
    } else {
        echo "<p>Failed to upload image. Please check your file and try again.</p>";
    }
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteImage'])) {
    // Fetch the current image path from the database
    $fetchImageSql = "SELECT image FROM profile_images WHERE userid = ?";
    $fetchImageStmt = $conn->prepare($fetchImageSql);
    $fetchImageStmt->bind_param("s", $username);
    $fetchImageStmt->execute();
    $fetchImageResult = $fetchImageStmt->get_result();

    if ($fetchImageResult->num_rows > 0) {
        $imageRow = $fetchImageResult->fetch_assoc();
        $imagePath = $imageRow['image'];

        // Delete the image file from the server
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Remove the image record from the database
        $deleteImageSql = "DELETE FROM profile_images WHERE userid = ?";
        $deleteImageStmt = $conn->prepare($deleteImageSql);
        $deleteImageStmt->bind_param("s", $username);
        if ($deleteImageStmt->execute()) {
            echo "<p>Profile image deleted successfully!</p>";
        } else {
            echo "<p>Failed to delete image from the database.</p>";
        }
        $deleteImageStmt->close();
    } else {
        echo "<p>No image found to delete.</p>";
    }
}

// Fetch the current image path from the database
$fetchImageSql = "SELECT image FROM profile_images WHERE userid = ?";
$fetchImageStmt = $conn->prepare($fetchImageSql);
$fetchImageStmt->bind_param("s", $username);
$fetchImageStmt->execute();
$fetchImageResult = $fetchImageStmt->get_result();
$imagePath = '';

if ($fetchImageResult->num_rows > 0) {
    $imageRow = $fetchImageResult->fetch_assoc();
    $imagePath = $imageRow['image'];
}

// Close connection
$conn->close();

// After fetching the username from the session
echo "<!-- Debug: Current session username: " . $username . " -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - MatrimoSys</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            padding: 2rem;
            position: relative;
        }

        .profile-image-section {
            display: flex;
            align-items: flex-start;
            gap: 2rem;
            max-width: 800px;
            margin: 0 auto 1.5rem;
        }

        .profile-image-container {
            position: relative;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 5px solid white;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: filter 0.3s ease;
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-image-container:hover .image-overlay {
            opacity: 1;
        }

        .profile-image-container:hover .profile-image {
            filter: blur(2px);
        }

        .image-overlay i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .image-controls {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-top: 1rem;
        }

        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .upload-btn {
            background: white;
            color: #333;
            padding: 10px 20px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .upload-btn-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .save-btn, .delete-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .save-btn {
            background: #4CAF50;
            color: white;
        }

        .delete-btn {
            background: #ff4444;
            color: white;
        }

        .save-btn:hover, .delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .save-btn:hover {
            background: #45a049;
        }

        .delete-btn:hover {
            background: #cc0000;
        }

        .profile-name {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .profile-content {
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-group {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            transition: transform 0.2s;
            border: 1px solid #f3c634;
        }

        .info-group:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            color: #f3c634;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-value {
            font-size: 1.1rem;
            color: #1e293b;
        }

        .icon {
            width: 20px;
            text-align: center;
            color: #f3c634;
        }

        /* Add styles for the Go Back button */
        .navigation-button {
            margin: 1rem 0; /* Add some margin for spacing */
            display: flex; /* Use flexbox for alignment */
            justify-content: flex-start; /* Align to the left */
        }

        .go-back-btn {
            background-color: #f3c634; /* Attractive background color */
            color: white; /* Text color */
            padding: 10px 20px; /* Padding for the button */
            border: none; /* Remove default border */
            border-radius: 25px; /* Rounded corners */
            font-size: 1rem; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s ease; /* Smooth transition */
        }

        .go-back-btn:hover {
            background-color: #e0b32e; /* Darker shade on hover */
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

        /* Add these styles to the existing <style> section */
        .gallery-container {
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .gallery-image {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border: 2px solid #f3c634;
        }

        .gallery-image:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .no-images {
            text-align: center;
            color: #666;
            font-style: italic;
            grid-column: 1 / -1;
            padding: 2rem;
            font-size: 1.1rem;
        }

        .update-profile-btn {
            background: #f3c634;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .update-profile-btn:hover {
            background: #e0b32e;
            transform: translateY(-2px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo">MatrimoSys</div>
    <nav class="nav">
        <a href="<?php echo $home_link; ?>">Home</a>
        <a href="userdetails.php">Update Profile</a>
        <a href="logout.php">Log Out</a>
    </nav>
</header>

<div class="navigation-button" style="margin-top: 100px;">
    <button class="go-back-btn" onclick="window.history.back()">Go Back</button>
</div>

<div class="container">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-image-section">
                <div class="profile-image-container">
                    <img class="profile-image" src="<?php echo htmlspecialchars($imagePath ?: 'uploads/default.png'); ?>" 
                         alt="Profile Image" id="profileImagePreview" />
                    <div class="image-overlay">
                        <i class="fas fa-camera"></i>
                        <span>Change Photo</span>
                    </div>
                </div>
                <div class="image-controls">
                    <form method="POST" enctype="multipart/form-data" id="imageUploadForm">
                        <div class="upload-btn-wrapper">
                            <button class="upload-btn" type="button">
                                <i class="fas fa-upload"></i> Choose Photo
                            </button>
                            <input type="file" name="profileImage" id="profileImageUpload" 
                                   accept="image/*" onchange="previewImage(event)">
                        </div>
                        <div class="action-buttons">
                            <button type="submit" class="save-btn">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <?php if($imagePath): ?>
                            <button type="submit" name="deleteImage" class="delete-btn" 
                                    onclick="return confirm('Are you sure you want to delete your profile picture?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
        </div>
        
        <div class="profile-content">
            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-user"></i>Username
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['username']); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-envelope"></i>Email
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['email']); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-phone"></i>Phone
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['phone']); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-calendar-alt"></i>Date of Birth
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['dob']); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-ruler-vertical"></i>Height
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['height']); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-palette"></i>Complexion
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['complexion']); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-info-circle"></i>About
                </div>
                <div class="info-value">
                    <?php echo nl2br(htmlspecialchars($user['about'])); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-user-tie"></i>Father's Name
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['father_name']); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-user-tie"></i>Mother's Name
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['mother_name']); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-home"></i>Family Name
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['family_name']); ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">
                    <i class="icon fas fa-users"></i>Siblings
                </div>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['sibling_name']); ?>
                </div>
            </div>

            <div class="info-group" style="grid-column: 1 / -1;">
                <div class="info-label">
                    <i class="icon fas fa-edit"></i>Profile Actions
                </div>
                <div class="info-value" style="display: flex; gap: 1rem;">
                    <a href="userdetails.php" class="update-profile-btn">
                        <i class="fas fa-user-edit"></i> Update Profile Details
                    </a>
                    <a href="userdetails.php#upload-controls" class="update-profile-btn">
                        <i class="fas fa-images"></i> Add Images
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this before the closing </div> of the container -->
    <div class="profile-card" style="margin-top: 2rem;">
        <div class="profile-header">
            <h2 style="color: white;">Photo Gallery</h2>
        </div>
        <div class="gallery-container">
            <?php
            // Reopen connection since it was closed earlier
            include 'connect.php';
            
            // Start session if not already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            // Check if username is set in the session
            if (!isset($_SESSION['username'])) {
                echo '<p class="no-images">User not logged in. Please log in to view your gallery.</p>';
                exit; // Stop further execution
            }

            // Use the username from the session
            $username = $_SESSION['username'];
            
            // Debug output
            echo "<!-- Debug: Current session username: " . $username . " -->";
            
            // Fetch the user ID from the profiles table
            $profile_stmt = $conn->prepare("SELECT userid FROM profiles WHERE userid = ?");
            $profile_stmt->bind_param("s", $username);
            $profile_stmt->execute();
            $profile_result = $profile_stmt->get_result();

            if ($profile_result->num_rows > 0) {
                $profile_data = $profile_result->fetch_assoc();
                $userid = $profile_data['userid']; // Get the user ID

                // Fetch all images for this user from tbl_images
                $gallery_sql = "SELECT * FROM tbl_images WHERE userid = ?";
                $gallery_stmt = $conn->prepare($gallery_sql);
                $gallery_stmt->bind_param("s", $userid);
                
                try {
                    $gallery_stmt->execute();
                    $gallery_result = $gallery_stmt->get_result();
                    
                    if ($gallery_result && $gallery_result->num_rows > 0) {
                        while ($image = $gallery_result->fetch_assoc()) {
                            // Construct the image path
                            $imagePath = 'uploads/gallery/' . htmlspecialchars(basename($image['image']));
                            echo '<div class="gallery-image">';
                            echo '<img src="' . $imagePath . '" 
                                      alt="Gallery Image" 
                                      onerror="this.onerror=null; this.src=\'uploads/default.png\';">';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="no-images">No additional images uploaded yet.</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="no-images">Error loading gallery images.</p>';
                }
            } else {
                echo '<p class="no-images">User not found in profiles.</p>';
            }

            $profile_stmt->close();
            $conn->close();
            ?>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const preview = document.getElementById('profileImagePreview');
        preview.src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}

// Optional: Add form submission feedback
document.getElementById('imageUploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('profileImageUpload');
    if(fileInput.files.length > 0) {
        const fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
        if(fileSize > 5) { // 5MB limit
            e.preventDefault();
            alert('File size should not exceed 5MB');
        }
    }
});

// Add this JavaScript to help debug image loading issues
document.addEventListener('DOMContentLoaded', function() {
    console.log('Checking gallery images...');
    document.querySelectorAll('.gallery-image img').forEach((img, index) => {
        console.log(`Image ${index + 1} src:`, img.src);
        img.addEventListener('error', function() {
            console.error('Failed to load image:', this.src);
        });
    });
});
</script>

</body>
</html>