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

// Fetch the payment_id of the logged-in user
$payment_id_query = "SELECT payment_id FROM profiles WHERE userid = ?";
$payment_id_stmt = $conn->prepare($payment_id_query);
$payment_id_stmt->bind_param('s', $_SESSION['username']);
$payment_id_stmt->execute();
$payment_id_result = $payment_id_stmt->get_result();
$payment_id_row = $payment_id_result->fetch_assoc();
$payment_id = $payment_id_row['payment_id'] ?? null;

// Check if the logged-in user has already sent an interest to this user
$interest_check_query = "SELECT * FROM tbl_request WHERE userid_sender = ? AND userid_receiver = ?";
$interest_check_stmt = $conn->prepare($interest_check_query);
$interest_check_stmt->bind_param('ss', $_SESSION['username'], $userid);
$interest_check_stmt->execute();
$interest_check_result = $interest_check_stmt->get_result();
$has_sent_interest = $interest_check_result->num_rows > 0;

// Determine the home link based on payment_id
$home_link = ($payment_id == 3) ? 'premium_user.php' : (($payment_id == 2) ? 'normal_user.php' : 'home.html');

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

        /* Add styles for the Send Interest button */
        .send-interest-btn {
            background-color: #4CAF50; /* Green background color */
            color: white; /* Text color */
            padding: 12px 24px; /* Increased padding for a larger button */
            border: none; /* Remove default border */
            border-radius: 30px; /* More rounded corners */
            font-size: 1.1rem; /* Slightly larger font size */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth transition */
        }

        .send-interest-btn:hover {
            background-color: #45a049; /* Darker green on hover */
            transform: scale(1.05); /* Slightly enlarge the button on hover */
        }
        
        /* Header and navigation styles from profile.php */
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
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">MatrimoSys</div>
        <nav class="nav">
            <a href="<?php echo $home_link; ?>">Home</a>
            <a href="profile.php">My Profile</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <div class="navigation-button" style="margin-top: 100px;">
        <button class="go-back-btn" onclick="redirectToUserPage()">Go Back</button>
    </div>

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
                            <i class="icon fas fa-calendar-alt"></i>Age
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['age'] ?? 'N/A'); ?>
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

                    <div class="info-group">
                        <div class="info-label">
                            <i class="icon fas fa-phone"></i>Phone
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_details['phone']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Add Send Interest button -->
                <div class="navigation-button" style="justify-content: center;">
                    <?php if ($has_sent_interest): ?>
                        <button class="send-interest-btn" style="background-color: #9e9e9e;" disabled>Interest Already Sent</button>
                    <?php else: ?>
                        <button class="send-interest-btn" style="background-color: #ffeb3b;" onclick="sendInterest('<?php echo htmlspecialchars($userid); ?>')">Send Interest</button>
                    <?php endif; ?>
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

    <script>
        function redirectToUserPage() {
            var paymentId = <?php echo json_encode($payment_id); ?>; // Get payment_id from PHP
            if (paymentId == 3) {
                window.location.href = 'premium_user.php'; // Redirect to premium_user.php
            } else if (paymentId == 2) {
                window.location.href = 'normal_user.php'; // Redirect to normal_user.php
            } else {
                alert("Invalid payment status."); // Handle unexpected payment_id
            }
        }

        function sendInterest(userid) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "send_interest.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    alert(xhr.responseText); // Show response from the server
                    // Disable the button and change its text after successful interest sending
                    if (xhr.responseText.includes("successfully")) {
                        var button = document.querySelector(".send-interest-btn");
                        button.disabled = true;
                        button.style.backgroundColor = "#9e9e9e";
                        button.textContent = "Interest Already Sent";
                    }
                }
            };
            xhr.send("userid=" + encodeURIComponent(userid));
        }
    </script>
</body>
</html>