<?php
session_start();
include 'connect.php';

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get the user ID from the query string
$userid = $_GET['userid'] ?? null;

if ($userid) {
    // Fetch user details including family information
    $user_query = "SELECT p.*, c.caste, rel.religion, e.eduSub, f.father_name, f.mother_name, f.family_name, f.sibling_name
                   FROM profiles p
                   LEFT JOIN tbl_caste c ON p.caste_id = c.caste_id 
                   LEFT JOIN tbl_religion rel ON c.religion_id = rel.religion_id
                   LEFT JOIN tbl_subEducation e ON p.edusub_id = e.edusub_id
                   LEFT JOIN tbl_family f ON p.family_id = f.family_id
                   WHERE p.userid = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param('s', $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_details = $result->fetch_assoc();
    
    // Fetch user image
    $image_query = "SELECT image FROM profile_images WHERE userid = ?";
    $image_stmt = $conn->prepare($image_query);
    $image_stmt->bind_param('s', $userid);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();
    $image_row = $image_result->fetch_assoc();
    $user_image = $image_row['image'] ?? 'uploads/default.png';

    // Fetch additional user images from tbl_images
    $gallery_query = "SELECT image FROM tbl_images WHERE userid = ?";
    $gallery_stmt = $conn->prepare($gallery_query);
    $gallery_stmt->bind_param('s', $userid);
    $gallery_stmt->execute();
    $gallery_result = $gallery_stmt->get_result();
    $gallery_images = [];
    while ($row = $gallery_result->fetch_assoc()) {
        $gallery_images[] = $row['image'];
    }
} else {
    // Redirect if no user ID is provided
    header("Location: premium_user.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
            background: #f3c634;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .profile-image-container {
            width: 200px;
            height: 200px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            border: 5px solid white;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        @media (max-width: 768px) {
            .container {
                margin: 1rem auto;
            }

            .profile-image-container {
                width: 150px;
                height: 150px;
            }

            .profile-content {
                grid-template-columns: 1fr;
            }
        }

        .chat-button {
            display: flex;
            justify-content: center;
            margin: 2rem 0;
            padding-bottom: 2rem;
        }

        .btn-chat {
            background-color: #f3c634;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 30px;
            font-size: 1.1rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-chat:hover {
            background-color: #e0b32e;
            transform: scale(1.05);
        }
        
        /* Gallery Styles */
        .gallery-section {
            padding: 2rem;
            background: white;
            border-radius: 15px;
            margin-top: 2rem;
            border: 1px solid #f3c634;
        }
        
        .gallery-title {
            color: #f3c634;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }
        
        .gallery-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .gallery-item {
            border-radius: 10px;
            overflow: hidden;
            height: 200px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
        }
        
        .gallery-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gallery-empty {
            text-align: center;
            padding: 2rem;
            color: #777;
            grid-column: 1 / -1;
        }
        
        /* Lightbox styles */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .lightbox-content {
            max-width: 90%;
            max-height: 90%;
        }
        
        .lightbox-image {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
        }
        
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }
        
        .lightbox-nav {
            position: absolute;
            color: white;
            font-size: 30px;
            cursor: pointer;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .lightbox-prev {
            left: 30px;
        }
        
        .lightbox-next {
            right: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($user_details): ?>
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-image-container">
                        <img class="profile-image" src="<?php echo htmlspecialchars($user_image); ?>" alt="Profile Image" />
                    </div>
                    <h1 class="profile-name"><?php echo htmlspecialchars($user_details['username']); ?></h1>
                </div>
                
                <div class="profile-content">
                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-user"></i>Username
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['username']); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-envelope"></i>Email
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['email']); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-phone"></i>Phone
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['phone']); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-calendar-alt"></i>Date of Birth
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['dob']); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-ruler-vertical"></i>Height
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['height'] ?? 'N/A'); ?> feet
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-palette"></i>Complexion
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['complexion'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-info-circle"></i>About
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['about'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-user-tie"></i>Father's Name
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['father_name'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-user-tie"></i>Mother's Name
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['mother_name'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-home"></i>Family Name
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['family_name'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-users"></i>Siblings
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['sibling_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Photo Gallery Section -->
                <div class="gallery-section">
                    <h2 class="gallery-title">Photo Gallery</h2>
                    <div class="gallery-container">
                        <?php if (!empty($gallery_images)): ?>
                            <?php foreach ($gallery_images as $index => $image): ?>
                                <div class="gallery-item" onclick="openLightbox(<?php echo $index; ?>)">
                                    <img class="gallery-image" src="<?php echo htmlspecialchars($image); ?>" alt="Gallery Image" />
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="gallery-empty">No additional photos available</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="chat-button">
                    <a href="chat.php?userid=<?php echo urlencode($user_details['userid']); ?>" class="btn-chat">Chat Now</a>
                </div>
            </div>
        <?php else: ?>
            <div class="profile-card">
                <div class="profile-content">
                    <p style="text-align: center; padding: 2rem;">User details not found.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Lightbox for gallery images -->
    <div class="lightbox" id="lightbox">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <span class="lightbox-nav lightbox-prev" onclick="changeImage(-1)">&#10094;</span>
        <div class="lightbox-content">
            <img class="lightbox-image" id="lightbox-img" src="" alt="Enlarged Image">
        </div>
        <span class="lightbox-nav lightbox-next" onclick="changeImage(1)">&#10095;</span>
    </div>

    <script>
        // Gallery lightbox functionality
        let galleryImages = <?php echo json_encode($gallery_images); ?>;
        let currentImageIndex = 0;
        
        function openLightbox(index) {
            currentImageIndex = index;
            document.getElementById('lightbox-img').src = galleryImages[index];
            document.getElementById('lightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent scrolling when lightbox is open
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
        }
        
        function changeImage(direction) {
            currentImageIndex += direction;
            
            // Handle wrapping around the gallery
            if (currentImageIndex >= galleryImages.length) {
                currentImageIndex = 0;
            } else if (currentImageIndex < 0) {
                currentImageIndex = galleryImages.length - 1;
            }
            
            document.getElementById('lightbox-img').src = galleryImages[currentImageIndex];
        }
        
        // Close lightbox when clicking outside the image
        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (document.getElementById('lightbox').style.display === 'flex') {
                if (e.key === 'Escape') {
                    closeLightbox();
                } else if (e.key === 'ArrowLeft') {
                    changeImage(-1);
                } else if (e.key === 'ArrowRight') {
                    changeImage(1);
                }
            }
        });
    </script>
</body>
</html>