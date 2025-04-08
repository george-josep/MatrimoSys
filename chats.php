<?php
session_start();
include 'connect.php';

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch all conversations where the current user is involved
$query = "SELECT 
            m.sender_id, 
            m.recipient_id, 
            m.message, 
            m.sent_time,
            m.is_read,
            p.username, 
            pi.image AS profile_pic
          FROM messages m
          JOIN (
              SELECT MAX(id) as last_msg_id
              FROM messages
              WHERE sender_id = ? OR recipient_id = ?
              GROUP BY 
                  CASE 
                      WHEN sender_id = ? THEN recipient_id 
                      ELSE sender_id 
                  END
          ) AS latest ON m.id = latest.last_msg_id
          JOIN profiles p ON (
              CASE 
                  WHEN m.sender_id = ? THEN m.recipient_id 
                  ELSE m.sender_id 
              END = p.userid
          )
          LEFT JOIN profile_images pi ON p.userid = pi.userid
          ORDER BY m.sent_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('ssss', $_SESSION['username'], $_SESSION['username'], $_SESSION['username'], $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$conversations = $result->fetch_all(MYSQLI_ASSOC);

// Count all unread messages
$unread_query = "SELECT COUNT(*) as unread_count FROM messages 
                 WHERE recipient_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param('s', $_SESSION['username']);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$total_unread = $unread_result->fetch_assoc()['unread_count'];

// Count unread messages per sender
$unread_by_sender = [];
$count_query = "SELECT sender_id, COUNT(*) as count FROM messages 
                WHERE recipient_id = ? AND is_read = 0 
                GROUP BY sender_id";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param('s', $_SESSION['username']);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
while ($row = $count_result->fetch_assoc()) {
    $unread_by_sender[$row['sender_id']] = $row['count'];
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Conversations - MatrimoSys</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400&family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            background-color: #f8f9fa;
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
            font-family: 'Cinzel Decorative', cursive;
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
            position: relative;
        }

        .nav a:hover {
            color: #FFD700;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .chat-container {
            max-width: 800px;
            margin: 100px auto 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #f3c634, #FFD700);
            color: white;
            padding: 25px;
            text-align: center;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: 0.5px;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .chat-header .badge {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            background: white;
            color: #f3c634;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 14px;
            font-weight: 700;
        }

        .conversations-list {
            max-height: 70vh;
            overflow-y: auto;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .conversation-item:hover {
            background: rgba(243, 198, 52, 0.05);
        }

        .conversation-item.unread {
            background: rgba(243, 198, 52, 0.1);
        }

        .conversation-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #f3c634;
        }

        .conversation-content {
            flex: 1;
            min-width: 0; /* Allows text truncation to work */
        }

        .conversation-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .conversation-preview {
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 400px;
            font-size: 14px;
        }

        .conversation-time {
            color: #999;
            font-size: 12px;
            min-width: 60px;
            text-align: right;
        }

        .unread-badge {
            background: #ff6b6b;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }

        .empty-state {
            padding: 50px 20px;
            text-align: center;
            color: #666;
        }

        .empty-state i {
            font-size: 60px;
            color: #f3c634;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 16px;
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Scrollbar styling */
        .conversations-list::-webkit-scrollbar {
            width: 6px;
        }

        .conversations-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .conversations-list::-webkit-scrollbar-thumb {
            background: #f3c634;
            border-radius: 3px;
        }

        /* Animation for new message notification */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .unread-badge {
            animation: pulse 1.5s infinite;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chat-container {
                border-radius: 0;
                margin-top: 70px;
                margin-bottom: 0;
                height: calc(100vh - 70px);
                max-width: 100%;
            }
            
            .chat-header {
                padding: 15px;
                font-size: 18px;
            }
            
            .conversation-avatar {
                width: 50px;
                height: 50px;
            }
            
            .conversation-preview {
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">MatrimoSys</div>
        <nav class="nav">
            <a href="preference.php">Preferences</a>
            <a href="<?php echo $home_link; ?>">Home</a>
            <a href="chats.php" style="position: relative;">
                Chats
                <?php if ($total_unread > 0): ?>
                    <span class="request-count"><?php echo $total_unread; ?></span>
                <?php endif; ?>
            </a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <div class="chat-container">
        <div class="chat-header">
            My Conversations
            <?php if ($total_unread > 0): ?>
                <div class="badge"><?php echo $total_unread; ?> new</div>
            <?php endif; ?>
        </div>

        <div class="conversations-list">
            <?php if (empty($conversations)): ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>No conversations yet</h3>
                    <p>Start connecting with matches to begin conversations</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $convo): ?>
                    <?php 
                        $other_user = ($convo['sender_id'] == $_SESSION['username']) ? $convo['recipient_id'] : $convo['sender_id'];
                        $is_unread = isset($unread_by_sender[$other_user]) && $unread_by_sender[$other_user] > 0;
                        $unread_count = $is_unread ? $unread_by_sender[$other_user] : 0;
                        
                        // Format time
                        $time = strtotime($convo['sent_time']);
                        $now = time();
                        $diff = $now - $time;
                        
                        if ($diff < 60) {
                            $time_display = "Just now";
                        } elseif ($diff < 3600) {
                            $time_display = floor($diff / 60) . "m ago";
                        } elseif ($diff < 86400) {
                            $time_display = floor($diff / 3600) . "h ago";
                        } elseif ($diff < 604800) {
                            $time_display = date('D', $time);
                        } else {
                            $time_display = date('M j', $time);
                        }
                        
                        // Get profile image
                        $profile_image = !empty($convo['profile_pic']) ? $convo['profile_pic'] : 'uploads/default.png';
                        if (!file_exists($profile_image)) {
                            $profile_image = 'uploads/default.png';
                        }
                    ?>
                    <div class="conversation-item <?php echo $is_unread ? 'unread' : ''; ?>" 
                         onclick="window.location.href='chat.php?userid=<?php echo urlencode($other_user); ?>'">
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="<?php echo htmlspecialchars($convo['username']); ?>" class="conversation-avatar">
                        <div class="conversation-content">
                            <div class="conversation-name">
                                <?php echo htmlspecialchars($convo['username']); ?>
                                <?php if ($is_unread): ?>
                                    <span class="unread-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="conversation-preview">
                                <?php if ($convo['sender_id'] == $_SESSION['username']): ?>
                                    <span style="color: #999;">You: </span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars(mb_substr($convo['message'], 0, 50)) . (mb_strlen($convo['message']) > 50 ? '...' : ''); ?>
                            </div>
                        </div>
                        <div class="conversation-time">
                            <?php echo $time_display; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here if needed
    </script>
</body>
</html> 