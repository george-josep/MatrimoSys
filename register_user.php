<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Update these paths to the correct location
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// Initialize message variable
$message = '';
$message_type = '';

// Database connection details
$host = 'localhost'; // Update with your DB host if different
$user = 'root'; // Replace with your database username
$password = ''; // Replace with your database password
$database = 'matrimosys';

// Establish database connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    $response = array('status' => 'error', 'message' => "Connection failed: " . $conn->connect_error);
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['action'] == 'send_otp') {
        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_time'] = time();

        // Get email from form
        $email = trim($_POST['email']);
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'georgejosephval@gmail.com';  // Your Gmail address
            $mail->Password = 'swbv ubck rmhk exri';  // Your App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            
            // Disable SSL verification (only if needed for local testing)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom('georgejosephval@gmail.com', 'MatrimoSys');  // Your Gmail address
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP for MatrimoSys Registration';
            $mail->Body = "Your OTP for registration is: <b>$otp</b><br>This OTP will expire in 10 minutes.";

            $mail->send();
            $response = array('status' => 'success', 'message' => 'OTP sent successfully');
            echo json_encode($response);
            exit;

        } catch (Exception $e) {
            $response = array('status' => 'error', 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            echo json_encode($response);
            exit;
        }
    }
    
    else if ($_POST['action'] == 'verify_otp') {
        // Verify OTP
        if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_time'])) {
            $response = array('status' => 'error', 'message' => 'OTP session expired. Please try again.');
            echo json_encode($response);
            exit;
        }

        // Check if OTP is expired (10 minutes)
        if (time() - $_SESSION['otp_time'] > 600) {
            unset($_SESSION['otp']);
            unset($_SESSION['otp_time']);
            $response = array('status' => 'error', 'message' => 'OTP has expired. Please request a new one.');
            echo json_encode($response);
            exit;
        }

        if ($_POST['otp'] != $_SESSION['otp']) {
            $response = array('status' => 'error', 'message' => 'Invalid OTP. Please try again.');
            echo json_encode($response);
            exit;
        }

        // If OTP is valid, proceed with registration
        $userid = trim($_POST['temp_userid']);
        $username = trim($_POST['temp_username']);
        $email = trim($_POST['temp_email']);
        $phone = trim($_POST['temp_phone']);
        $dob = $_POST['temp_dob'];
        $password = $_POST['temp_password'];

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Check if userid already exists
        $check_sql = "SELECT userid FROM profiles WHERE userid = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt === false) {
            $response = array('status' => 'error', 'message' => "Error preparing statement: " . $conn->error);
            echo json_encode($response);
            exit;
        }

        $check_stmt->bind_param("s", $userid);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $response = array('status' => 'error', 'message' => "User ID already exists. Please choose a different one.");
            echo json_encode($response);
            exit;
        }

        // Insert user into database
        $sql = "INSERT INTO profiles (userid, username, email, dob, password, phone) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $response = array('status' => 'error', 'message' => "Error preparing statement: " . $conn->error);
            echo json_encode($response);
            exit;
        }

        $stmt->bind_param("ssssss", $userid, $username, $email, $dob, $hashed_password, $phone);

        if ($stmt->execute()) {
            // Clear OTP session
            unset($_SESSION['otp']);
            unset($_SESSION['otp_time']);
            
            // Redirect to login page
            $response = array('status' => 'success', 'message' => 'Registration successful!', 'redirect' => 'login.html');
            echo json_encode($response);
            exit;
        } else {
            $response = array('status' => 'error', 'message' => "Error: " . $stmt->error);
            echo json_encode($response);
            exit;
        }
    }
}

$conn->close();
?>
