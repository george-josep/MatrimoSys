<?php
// Enable error reporting temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
session_start();

$phpmailer_path = $_SERVER['DOCUMENT_ROOT'] . '/mini/PHPMailer-master/src/';

// First check if PHPMailer files exist
if (!file_exists($phpmailer_path . 'PHPMailer.php')) {
    die(json_encode([
        'status' => 'error',
        'message' => 'PHPMailer files not found at: ' . $phpmailer_path
    ]));
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

try {
    require $phpmailer_path . 'Exception.php';
    require $phpmailer_path . 'PHPMailer.php';
    require $phpmailer_path . 'SMTP.php';

    $conn = new mysqli('localhost', 'root', '', 'matrimosys');

    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch($_POST['action']) {
            case 'send_otp':
                $userid = $_POST['userid'];
                $email = $_POST['email'];
                
                // Verify user exists and email matches
                $stmt = $conn->prepare("SELECT email FROM profiles WHERE userid = ? AND email = ?");
                $stmt->bind_param("ss", $userid, $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    echo json_encode(['status' => 'error', 'message' => 'User ID or email not found']);
                    exit;
                }
                
                // Generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_userid'] = $userid;
                $_SESSION['reset_time'] = time();
                
                // Send email using PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->SMTPDebug = 0;                                  // Disable debug output
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'georgejosephval@gmail.com';
                    $mail->Password   = 'swbv ubck rmhk exri';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    // Recipients
                    $mail->setFrom('georgejosephval@gmail.com', 'MatrimoSys');
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset OTP - MatrimoSys';
                    $mail->Body    = "Your OTP for password reset is: <b>$otp</b><br>This OTP will expire in 10 minutes.";
                    $mail->AltBody = "Your OTP for password reset is: $otp\nThis OTP will expire in 10 minutes.";

                    $mail->send();
                    echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully']);
                } catch (Exception $e) {
                    throw new Exception("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                }
                break;
            
            case 'verify_otp':
                if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_time']) || 
                    time() - $_SESSION['reset_time'] > 600) {
                    echo json_encode(['status' => 'error', 'message' => 'OTP expired or invalid']);
                    exit;
                }
                
                if ($_POST['otp'] == $_SESSION['reset_otp']) {
                    echo json_encode(['status' => 'success', 'message' => 'OTP verified']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
                }
                break;
            
            case 'reset_password':
                if (!isset($_SESSION['reset_userid'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid session']);
                    exit;
                }
                
                $userid = $_SESSION['reset_userid'];
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE profiles SET password = ? WHERE userid = ?");
                $stmt->bind_param("ss", $new_password, $userid);
                
                if ($stmt->execute()) {
                    // Clear session variables
                    unset($_SESSION['reset_otp']);
                    unset($_SESSION['reset_userid']);
                    unset($_SESSION['reset_time']);
                    
                    echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
                }
                break;
        }
    }

    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 