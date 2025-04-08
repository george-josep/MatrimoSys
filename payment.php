<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Handle Razorpay payment verification and database update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    $userid = $_SESSION['username'];
    $razorpay_payment_id = $_POST['razorpay_payment_id'];
    
    // Include the existing connection file
    require_once 'connect.php';
    
    // Create a new connection since connect.php closes its connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Update user to premium status
    $sql = "UPDATE profiles SET payment_id = 3 WHERE userid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userid);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo json_encode(['status' => 'success']);
        exit();
    } else {
        $stmt->close();
        $conn->close();
        echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - MatrimoSys</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Pacifico', cursive;
            overflow-x: hidden;
            color: #333;
            min-height: 100vh;
            position: relative;
        }

        .top {
            min-height: 100vh;
            width: 100%;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .top .slider {
            display: flex;
            width: 700%;
            animation: slide-animation 21s infinite;
        }

        .top .slide {
            min-width: 100%;
            height: 100vh;
            background-size: cover;
            background-position: center;
        }

        /* Assign images to slides - keeping the same as normal_user.php */
        .top .slide:nth-child(1) { background-image: url('2.png'); }
        .top .slide:nth-child(2) { background-image: url('3.png'); }
        .top .slide:nth-child(3) { background-image: url('4.png'); }
        .top .slide:nth-child(4) { background-image: url('5.png'); }
        .top .slide:nth-child(5) { background-image: url('6.png'); }
        .top .slide:nth-child(6) { background-image: url('7.png'); }
        .top .slide:nth-child(7) { background-image: url('8.png'); }

        @keyframes slide-animation {
            0% { transform: translateX(0); }
            14.285% { transform: translateX(-100%); }
            28.57% { transform: translateX(-200%); }
            42.855% { transform: translateX(-300%); }
            57.14% { transform: translateX(-400%); }
            71.425% { transform: translateX(-500%); }
            85.71% { transform: translateX(-600%); }
            100% { transform: translateX(0); }
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
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            max-width: 500px;
            padding: 20px;
            z-index: 15;
        }

        .payment-card {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            text-align: center;
        }

        .premium-details {
            margin-bottom: 20px;
            text-align: left;
        }

        .premium-feature {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .premium-price {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
            color: #333;
        }

        #razorpay-button {
            width: 100%;
            padding: 1rem;
            font-size: 1.5rem;
            background-color: #FFD700;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #razorpay-button:hover {
            background-color: #f3c634;
        }
    </style>
    <!-- Razorpay SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <div class="top">
        <div class="slider">
            <div class="slide"></div>
            <div class="slide"></div>
            <div class="slide"></div>
            <div class="slide"></div>
            <div class="slide"></div>
            <div class="slide"></div>
            <div class="slide"></div>
        </div>
    </div>

    <header class="header">
        <div class="logo">MatrimoSys</div>
        <nav class="nav">
            <a href="home.html">Home</a>
            <a href="profile.html">Profile</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <div class="container">
        <div class="payment-card">
            <h2>Upgrade to Premium</h2>
            <div class="premium-details">
            <div class="premium-feature">✓Advanced Search</div>
                <div class="premium-feature">✓ Unlimited Searches</div>
                <div class="premium-feature">✓ Connect with more matches</div>
                
                
            </div>
            <div class="premium-price">₹200.00</div>
            <button id="razorpay-button">Pay Now</button>
        </div>
    </div>

    <script>
        document.getElementById('razorpay-button').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Amount in paise (₹200 = 20000 paise)
            const amount = 20000;
            
            const options = {
                key: 'rzp_test_Yt5M3IWmdiaOQv',
                amount: amount,
                currency: 'INR',
                name: 'MatrimoSys',
                description: 'Premium Membership',
                image: '', // Add your logo URL here
                handler: function(response) {
                    // On payment success
                    if (response.razorpay_payment_id) {
                        // Send payment ID to server to verify and update database
                        const paymentData = {
                            razorpay_payment_id: response.razorpay_payment_id
                        };
                        
                        fetch('payment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams(paymentData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                window.location.href = 'premium_user.php';
                            } else {
                                alert('Payment processed but update failed. Please contact support.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                        });
                    }
                },
                prefill: {
                    name: '<?php echo $_SESSION["username"]; ?>',
                    email: '',
                    contact: ''
                },
                theme: {
                    color: '#F3C634'
                }
            };
            
            const razorpayInstance = new Razorpay(options);
            razorpayInstance.open();
        });
    </script>
</body>
</html>