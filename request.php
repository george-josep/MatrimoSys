<?php
session_start();
include 'connect.php';

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch received requests
$received_query = "SELECT r.*, r.request_date, r.response_date, p.username AS sender_username, pi.image AS profile_pic, p.dob, 
                  p.height, p.complexion, 
                  rel.religion, cas.caste, edu.eduSub,
                  CASE 
                    WHEN r.status = 0 THEN 'Pending'
                    WHEN r.status = 1 THEN 'Accepted'
                    WHEN r.status = 2 THEN 'Rejected'
                  END as request_status
                  FROM tbl_request r 
                  JOIN profiles p ON r.userid_sender = p.userid 
                  LEFT JOIN profile_images pi ON p.userid = pi.userid 
                  LEFT JOIN tbl_caste cas ON p.caste_id = cas.caste_id 
                  LEFT JOIN tbl_subEducation edu ON p.edusub_id = edu.edusub_id 
                  LEFT JOIN tbl_religion rel ON cas.religion_id = rel.religion_id 
                  WHERE r.userid_receiver = ?";

$received_stmt = $conn->prepare($received_query);
$received_stmt->bind_param('s', $_SESSION['username']);
$received_stmt->execute();
$received_results = $received_stmt->get_result();
$received_requests = $received_results->fetch_all(MYSQLI_ASSOC);
$received_stmt->close();

// Fetch sent requests
$sent_query = "SELECT r.*, r.request_date, r.response_date, p.username AS receiver_username, pi.image AS profile_pic, p.dob, 
               p.height, p.complexion, 
               rel.religion, cas.caste, edu.eduSub,
               CASE 
                 WHEN r.status = 0 THEN 'Pending'
                 WHEN r.status = 1 THEN 'Accepted'
                 WHEN r.status = 2 THEN 'Rejected'
               END as request_status
               FROM tbl_request r 
               JOIN profiles p ON r.userid_receiver = p.userid 
               LEFT JOIN profile_images pi ON p.userid = pi.userid 
               LEFT JOIN tbl_caste cas ON p.caste_id = cas.caste_id 
               LEFT JOIN tbl_subEducation edu ON p.edusub_id = edu.edusub_id 
               LEFT JOIN tbl_religion rel ON cas.religion_id = rel.religion_id 
               WHERE r.userid_sender = ?";

$sent_stmt = $conn->prepare($sent_query);
$sent_stmt->bind_param('s', $_SESSION['username']);
$sent_stmt->execute();
$sent_results = $sent_stmt->get_result();
$sent_requests = $sent_results->fetch_all(MYSQLI_ASSOC);
$sent_stmt->close();

// Fetch the payment_id of the logged-in user
$payment_query = "SELECT payment_id FROM profiles WHERE userid = ?";
$payment_stmt = $conn->prepare($payment_query);
$payment_stmt->bind_param('s', $_SESSION['username']);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$payment_id = $payment_result->fetch_assoc()['payment_id'];

// Count unread messages
$unread_query = "SELECT COUNT(*) as unread_count FROM messages 
                 WHERE recipient_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param('s', $_SESSION['username']);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];

// Determine the home link based on payment_id
$home_link = ($payment_id == 3) ? 'premium_user.php' : (($payment_id == 2) ? 'normaluser.php' : 'home.html');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests - MatrimoSys</title>
   
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

        .requests-container {
            margin-top: 100px;
            padding: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .requests-nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f5f5f5;
            color: #666;
            position: relative;
        }

        .nav-btn i {
            font-size: 18px;
        }

        .nav-btn.active {
            background: linear-gradient(135deg, #f3c634, #FFD700);
            color: white;
            box-shadow: 0 4px 15px rgba(243, 198, 52, 0.3);
            transform: translateY(-2px);
        }

        .request-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff6b6b;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .requests-section {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .requests-section.active {
            display: block;
        }

        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 20px;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(243, 198, 52, 0.2);
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .profile-card-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-bottom: 3px solid #f3c634;
        }

        .profile-card-content {
            padding: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            position: relative;
        }

        .date-info {
            display: block;
            font-size: 12px;
            font-weight: normal;
            margin-top: 4px;
            opacity: 0.9;
        }

        .status-pending {
            background: linear-gradient(135deg, #ffd700, #ffc107);
            color: #000;
        }

        .status-accepted {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }

        .status-rejected {
            background: linear-gradient(135deg, #ff6b6b, #ff5252);
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            color: #666;
        }

        .no-results i {
            color: #f3c634;
            margin-bottom: 20px;
        }

        .no-results p {
            font-size: 18px;
            font-weight: 500;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .requests-nav {
                flex-direction: column;
                gap: 10px;
            }

            .nav-btn {
                width: 100%;
                justify-content: center;
            }

            .search-results {
                grid-template-columns: 1fr;
            }
        }

        .profile-card-actions {
            display: flex;
            gap: 10px;
            padding: 15px;
            background: rgba(243, 198, 52, 0.1);
            border-top: 1px solid rgba(243, 198, 52, 0.2);
        }

        .action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-btn i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .action-btn:hover i {
            transform: scale(1.2);
        }

        .view-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .view-btn:hover {
            background: linear-gradient(135deg, #2980b9, #2573a7);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .chat-btn {
            background: linear-gradient(135deg, #f3c634, #FFD700);
            color: white;
            box-shadow: 0 4px 15px rgba(243, 198, 52, 0.3);
        }

        .chat-btn:hover {
            background: linear-gradient(135deg, #FFD700, #f3c634);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 198, 52, 0.4);
        }

        .accept-btn {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }

        .accept-btn:hover {
            background: linear-gradient(135deg, #27ae60, #219a52);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
        }

        .reject-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .reject-btn:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        /* Add ripple effect */
        .action-btn {
            position: relative;
            overflow: hidden;
        }

        .action-btn:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .action-btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.3;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-card-actions {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
            }
        }

        /* Add hover effect for the entire card */
        .profile-card:hover .action-btn {
            transform: translateY(-2px);
        }

        .profile-card:hover .profile-card-actions {
            background: rgba(243, 198, 52, 0.15);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">MatrimoSys</div>
        <nav class="nav">
            <?php
            // Fetch the payment_id of the logged-in user
            $payment_query = "SELECT payment_id FROM profiles WHERE userid = ?";
            $payment_stmt = $conn->prepare($payment_query);
            $payment_stmt->bind_param('s', $_SESSION['username']);
            $payment_stmt->execute();
            $payment_result = $payment_stmt->get_result();
            $payment_id = $payment_result->fetch_assoc()['payment_id'];
            
            // Determine the home link based on payment_id
            $home_link = ($payment_id == 3) ? 'premium_user.php' : (($payment_id == 2) ? 'normal_user.php' : 'home.html');
            
            // Count unread messages
            $unread_query = "SELECT COUNT(*) as unread_count FROM messages 
                             WHERE recipient_id = ? AND is_read = 0";
            $unread_stmt = $conn->prepare($unread_query);
            $unread_stmt->bind_param('s', $_SESSION['username']);
            $unread_stmt->execute();
            $unread_result = $unread_stmt->get_result();
            $unread_count = $unread_result->fetch_assoc()['unread_count'];
            ?>
            
            <a href="preference.php">Preferences</a>
            <a href="<?php echo $home_link; ?>">Home</a>
            <a href="chats.php" style="position: relative;">
                Chats
                <?php if ($unread_count > 0): ?>
                    <span class="request-count" style="position: absolute; top: -8px; right: -8px;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <div class="requests-container">
        <!-- Requests Navigation -->
        <div class="requests-nav">
            <button class="nav-btn active" data-tab="received">
                <i class="fas fa-inbox"></i> Received Requests
                <?php if (!empty($received_requests)): ?>
                    <span class="request-count"><?php echo count($received_requests); ?></span>
                <?php endif; ?>
            </button>
            <button class="nav-btn" data-tab="sent">
                <i class="fas fa-paper-plane"></i> Sent Requests
                <?php if (!empty($sent_requests)): ?>
                    <span class="request-count"><?php echo count($sent_requests); ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Received Requests Section -->
        <div class="requests-section active" id="received-requests">
            <?php if (!empty($received_requests)): ?>
                <div class="search-results">
                    <?php foreach ($received_requests as $request): ?>
                        <div class="profile-card">
                            <?php 
                                $request_image = !empty($request['profile_pic']) ? $request['profile_pic'] : 'uploads/default.png';
                                if (!file_exists($request_image)) {
                                    $request_image = 'uploads/default.png';
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($request_image); ?>" alt="Profile Image" class="profile-card-image" style="width: 100%; height: auto;" />
                            
                            <div class="profile-card-content">
                                <div class="status-badge status-<?php echo strtolower($request['request_status']); ?>">
                                    <?php echo htmlspecialchars($request['request_status']); ?>
                                    <?php if ($request['status'] == 0): ?>
                                        <span class="date-info">Received: <?php echo date('M d, Y', strtotime($request['request_date'])); ?></span>
                                    <?php elseif ($request['status'] == 1 || $request['status'] == 2): ?>
                                        <span class="date-info">
                                            <?php echo ($request['status'] == 1 ? 'Accepted: ' : 'Rejected: '); ?>
                                            <?php 
                                                if (!empty($request['response_date']) && $request['response_date'] != '0000-00-00 00:00:00' && strtotime($request['response_date']) > 0) {
                                                    echo date('M d, Y', strtotime($request['response_date']));
                                                } else {
                                                    echo date('M d, Y'); // Show today's date if response_date is invalid
                                                }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($request['sender_username']); ?></h3>
                                <p><strong>Age:</strong> <?php 
                                    $dob = new DateTime($request['dob']); // Assuming dob is available in the request
                                    $today = new DateTime('today');
                                    echo $dob->diff($today)->y; 
                                ?> years</p>
                                <p><strong>Religion:</strong> <?php echo htmlspecialchars($request['religion'] ?? 'N/A'); ?></p>
                                <p><strong>Caste:</strong> <?php echo htmlspecialchars($request['caste'] ?? 'N/A'); ?></p>
                                <p><strong>Education:</strong> <?php echo htmlspecialchars($request['eduSub'] ?? 'N/A'); ?></p>
                                <p><strong>Height:</strong> <?php echo htmlspecialchars($request['height'] ?? 'N/A'); ?> feet</p>
                                <p><strong>Complexion:</strong> <?php echo htmlspecialchars($request['complexion'] ?? 'N/A'); ?></p>
                                <div class="profile-card-actions">
                                    <?php if ($request['status'] == 0): ?>
                                        <button class="action-btn accept-btn" onclick="handleRequest('<?php echo $request['request_id']; ?>', 1)">
                                            <i class="fas fa-check"></i>
                                            <span>Accept</span>
                                        </button>
                                        <button class="action-btn reject-btn" onclick="handleRequest('<?php echo $request['request_id']; ?>', 2)">
                                            <i class="fas fa-times"></i>
                                            <span>Reject</span>
                                        </button>
                                        <button class="action-btn reject-btn" onclick="handleRequest('<?php echo $request['request_id']; ?>', 3, this)">
                                            <i class="fas fa-trash"></i>
                                            <span>Remove</span>
                                        </button>
                                    <?php elseif ($request['status'] == 1): ?>
                                        <button class="action-btn view-btn" onclick="window.location.href='view_match.php?userid=<?php echo urlencode($request['userid_sender']); ?>'">
                                            <i class="fas fa-eye"></i>
                                            <span>View Profile</span>
                                        </button>
                                        <button class="action-btn chat-btn" onclick="window.location.href='chat.php?userid=<?php echo urlencode($request['userid_sender']); ?>'">
                                            <i class="fas fa-comment-dots"></i>
                                            <span>Chat Now</span>
                                        </button>
                                        <button class="action-btn reject-btn" onclick="handleRequest('<?php echo $request['request_id']; ?>', 3, this)">
                                            <i class="fas fa-trash"></i>
                                            <span>Remove</span>
                                        </button>
                                    <?php elseif ($request['status'] == 2): ?>
                                        <button class="action-btn view-btn" onclick="window.location.href='view_match.php?userid=<?php echo urlencode($request['userid_sender']); ?>'">
                                            <i class="fas fa-eye"></i>
                                            <span>View Profile</span>
                                        </button>
                                        <button class="action-btn reject-btn" onclick="handleRequest('<?php echo $request['request_id']; ?>', 3, this)">
                                            <i class="fas fa-trash"></i>
                                            <span>Remove</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-inbox fa-3x"></i>
                    <p>No received requests yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sent Requests Section -->
        <div class="requests-section" id="sent-requests">
            <?php if (!empty($sent_requests)): ?>
                <div class="search-results">
                    <?php foreach ($sent_requests as $request): ?>
                        <div class="profile-card">
                            <?php 
                                $request_image = !empty($request['profile_pic']) ? $request['profile_pic'] : 'uploads/default.png';
                                if (!file_exists($request_image)) {
                                    $request_image = 'uploads/default.png';
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($request_image); ?>" alt="Profile Image" class="profile-card-image" style="width: 100%; height: auto;" />
                            
                            <div class="profile-card-content">
                                <div class="status-badge status-<?php echo strtolower($request['request_status']); ?>">
                                    <?php echo htmlspecialchars($request['request_status']); ?>
                                    <?php if ($request['status'] == 0): ?>
                                        <span class="date-info">Sent: <?php echo date('M d, Y', strtotime($request['request_date'])); ?></span>
                                    <?php elseif ($request['status'] == 1 || $request['status'] == 2): ?>
                                        <span class="date-info">
                                            <?php echo ($request['status'] == 1 ? 'Accepted: ' : 'Rejected: '); ?>
                                            <?php 
                                                if (!empty($request['response_date']) && $request['response_date'] != '0000-00-00 00:00:00' && strtotime($request['response_date']) > 0) {
                                                    echo date('M d, Y', strtotime($request['response_date']));
                                                } else {
                                                    echo date('M d, Y'); // Show today's date if response_date is invalid
                                                }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($request['receiver_username']); ?></h3>
                                <p><strong>Age:</strong> <?php 
                                    $dob = new DateTime($request['dob']); // Assuming dob is available in the request
                                    $today = new DateTime('today');
                                    echo $dob->diff($today)->y; 
                                ?> years</p>
                                <p><strong>Religion:</strong> <?php echo htmlspecialchars($request['religion'] ?? 'N/A'); ?></p>
                                <p><strong>Caste:</strong> <?php echo htmlspecialchars($request['caste'] ?? 'N/A'); ?></p>
                                <p><strong>Education:</strong> <?php echo htmlspecialchars($request['eduSub'] ?? 'N/A'); ?></p>
                                <p><strong>Height:</strong> <?php echo htmlspecialchars($request['height'] ?? 'N/A'); ?> feet</p>
                                <p><strong>Complexion:</strong> <?php echo htmlspecialchars($request['complexion'] ?? 'N/A'); ?></p>
                                <div class="profile-card-actions">
                                    <?php if ($request['status'] == 0): ?>
                                        <button class="action-btn view-btn" onclick="window.location.href='view_match.php?userid=<?php echo urlencode($request['userid_receiver']); ?>'">
                                            <i class="fas fa-eye"></i>
                                            <span>View Profile</span>
                                        </button>
                                        <button class="action-btn reject-btn" onclick="handleRequest('<?php echo $request['request_id']; ?>', 3, this)">
                                            <i class="fas fa-times"></i>
                                            <span>Cancel Request</span>
                                        </button>
                                    <?php elseif ($request['status'] == 1): ?>
                                        <button class="action-btn view-btn" onclick="window.location.href='view_match.php?userid=<?php echo urlencode($request['userid_receiver']); ?>'">
                                            <i class="fas fa-eye"></i>
                                            <span>View Profile</span>
                                        </button>
                                        <button class="action-btn chat-btn" onclick="window.location.href='chat.php?userid=<?php echo urlencode($request['userid_receiver']); ?>'">
                                            <i class="fas fa-comment-dots"></i>
                                            <span>Chat Now</span>
                                        </button>
                                        <button class="action-btn reject-btn" onclick="handleRequest('<?php echo $request['request_id']; ?>', 3, this)">
                                            <i class="fas fa-times"></i>
                                            <span>Cancel Request</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-paper-plane fa-3x"></i>
                    <p>No sent requests yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const navButtons = document.querySelectorAll('.nav-btn');
            
            navButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and add to clicked button
                    navButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Get the tab to show
                    const tabToShow = this.getAttribute('data-tab');
                    
                    // Hide all sections
                    document.querySelectorAll('.requests-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    
                    // Show the selected section
                    if (tabToShow === 'received') {
                        document.getElementById('received-requests').classList.add('active');
                    } else if (tabToShow === 'sent') {
                        document.getElementById('sent-requests').classList.add('active');
                    }
                });
            });
        });

        function handleRequest(requestId, status, buttonElement = null) {
            fetch('handle_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `request_id=${requestId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (status == 3) {
                        // Find the profile card containing this button and remove it
                        const profileCard = buttonElement.closest('.profile-card');
                        if (profileCard) {
                            profileCard.style.opacity = '0';
                            setTimeout(() => {
                                profileCard.remove();
                                
                                // Check if there are no more cards in this section
                                const section = buttonElement.closest('.requests-section');
                                const remainingCards = section.querySelectorAll('.profile-card');
                                if (remainingCards.length === 0) {
                                    // Show "no results" message
                                    const noResults = document.createElement('div');
                                    noResults.className = 'no-results';
                                    noResults.innerHTML = `
                                        <i class="fas fa-paper-plane fa-3x"></i>
                                        <p>No sent requests yet.</p>
                                    `;
                                    section.appendChild(noResults);
                                }
                            }, 300);
                        }
                    } else if (status == 1 || status == 2) {
                        // For accept/reject, reload the page to show updated status
                        location.reload();
                    }
                } else {
                    alert('An error occurred: ' + (data.message || 'Please try again later.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
        }
    </script>
</body>
</html>