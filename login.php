<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Get the submitted userid and password
$userid = $_POST['userid'] ?? '';
$password = $_POST['password'] ?? '';

// Check for admin credentials
if ($userid === 'admin' && $password === '12345678') {
    $_SESSION['username'] = 'admin';
    header('Location: admin.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'matrimosys');

if ($conn->connect_error) {
    die("Connection failed: {$conn->connect_error}");
}

// First check if user exists in profiles table to get reg_id
$profile_stmt = $conn->prepare("SELECT reg_id FROM profiles WHERE userid = ?");
$profile_stmt->bind_param("s", $userid);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();

if ($profile_result->num_rows > 0) {
    $profile_data = $profile_result->fetch_assoc();
    $reg_id = $profile_data['reg_id'];

    // Now check tbl_login
    $stmt = $conn->prepare("SELECT * FROM tbl_login WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $login_user = $result->fetch_assoc();
        // Verify password using password_verify
        if (password_verify($password, $login_user['password'])) {
            // The last_login will update automatically due to ON UPDATE CURRENT_TIMESTAMP
            // No need for explicit update
        }
    } else {
        // User doesn't exist in tbl_login, insert new record with reg_id
        $insert_stmt = $conn->prepare("INSERT INTO tbl_login (userid, password, reg_id) VALUES (?, ?, ?)");
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_stmt->bind_param("ssi", $userid, $hashed_password, $reg_id);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $profile_stmt->close();
}

// Continue with existing profiles check
$stmt = $conn->prepare("SELECT * FROM profiles WHERE userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Verify password using password_verify
    if (password_verify($password, $user['password'])) {
        // Valid credentials - set session
        $_SESSION['username'] = $userid;
        $_SESSION['user_id'] = $user['id'];
        $stmt->close();
        $conn->close();
        
        // Check payment_id and redirect accordingly
        switch ($user['payment_id']) {
            case 1:
                header('Location: welcome.php');
                break;
            case 2:
                header('Location: normal_user.php');
                break;
            case 3:
                header('Location: premium_user.php');
                break;
            default:
                header('Location: welcome.html');
        }
        exit();
    } else {
        // Password doesn't match
        $_SESSION['error'] = "Incorrect password";
        $stmt->close();
        $conn->close();
        header('Location: login.html?error=' . urlencode("Incorrect password"));
        exit();
    }
} else {
    // UserID not found
    $_SESSION['error'] = "Username not found, create an account";
    $stmt->close();
    $conn->close();
    header('Location: login.html?error=' . urlencode("Username not found, User does not have an existing account"));
    exit();
} 
?>
