<?php
session_start();
include 'connect.php';

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get the recipient's userid from URL
$recipient_id = $_GET['userid'] ?? null;

if (!$recipient_id) {
    header("Location: matches.php");
    exit();
}

// Fetch recipient's details
$recipient_query = "SELECT p.username, p.userid, pi.image AS profile_pic 
                   FROM profiles p 
                   LEFT JOIN profile_images pi ON p.userid = pi.userid 
                   WHERE p.userid = ?";
$stmt = $conn->prepare($recipient_query);
$stmt->bind_param('s', $recipient_id);
$stmt->execute();
$recipient = $stmt->get_result()->fetch_assoc();

// If recipient not found, redirect
if (!$recipient) {
    header("Location: matches.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($recipient['username']); ?></title>
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
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: #f3c634;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .recipient-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .recipient-name {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .back-button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.5rem;
            padding: 0.5rem;
        }

        .chat-container {
            flex: 1;
            padding: 1rem;
            margin-top: 80px;
            margin-bottom: 80px;
            overflow-y: auto;
        }

        .message {
            max-width: 70%;
            margin: 0.5rem;
            padding: 0.8rem;
            border-radius: 15px;
            position: relative;
        }

        .message-sent {
            background: #f3c634;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }

        .message-received {
            background: white;
            color: #333;
            margin-right: auto;
            border-bottom-left-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .chat-input-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            display: flex;
            gap: 1rem;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
        }

        .chat-input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 1rem;
        }

        .send-button {
            background: #f3c634;
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .send-button:hover {
            transform: scale(1.1);
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 0.3rem;
            text-align: right;
        }

        /* Add smooth scrolling */
        .chat-container {
            scroll-behavior: smooth;
        }

        /* Style the scrollbar */
        .chat-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: #f3c634;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="chat-header">
        <button class="back-button" onclick="window.location.href='request.php'">
            <i class="fas fa-arrow-left"></i>
        </button>
        <img src="<?php echo htmlspecialchars($recipient['profile_pic'] ?? 'uploads/default.png'); ?>" 
             alt="Profile" 
             class="recipient-image">
        <span class="recipient-name"><?php echo htmlspecialchars($recipient['username']); ?></span>
    </div>

    <div class="chat-container" id="chatContainer">
        <!-- Messages will be loaded here dynamically -->
    </div>

    <div class="chat-input-container">
        <input type="text" 
               class="chat-input" 
               id="messageInput" 
               placeholder="Type a message..."
               onkeypress="if(event.key === 'Enter') sendMessage()">
        <button class="send-button" onclick="sendMessage()">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>

    <script>
        const chatContainer = document.getElementById('chatContainer');
        const messageInput = document.getElementById('messageInput');
        const recipientId = '<?php echo $recipient_id; ?>';

        // Load messages when page loads
        loadMessages();

        // Load messages every few seconds
        setInterval(loadMessages, 3000);

        function loadMessages() {
            fetch('get_messages.php?recipient_id=' + recipientId)
                .then(response => response.json())
                .then(messages => {
                    chatContainer.innerHTML = '';
                    messages.forEach(message => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${message.sender_id === '<?php echo $_SESSION['username']; ?>' ? 'message-sent' : 'message-received'}`;
                        messageDiv.innerHTML = `
                            ${message.message}
                            <div class="message-time">${message.sent_time}</div>
                        `;
                        chatContainer.appendChild(messageDiv);
                    });
                    // Scroll to bottom
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                });
        }

        function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `recipient_id=${recipientId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    loadMessages();
                }
            });
        }
    </script>
</body>
</html> 