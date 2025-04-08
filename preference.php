<?php
session_start();
include 'connect.php';

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's payment_id
$userid = $_SESSION['username'];
$payment_query = "SELECT payment_id FROM profiles WHERE userid = ?";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("s", $userid);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment_id = $payment_result->fetch_assoc()['payment_id'] ?? null;

// Determine home link based on payment_id
$home_link = "home.html"; // Default link
if ($payment_id == 3) {
    $home_link = "premium_user.php";
} elseif ($payment_id == 2) {
    $home_link = "normal_user.php";
}

// Fetch religions from database
$religions = array();
$sql = "SELECT religion_id, religion FROM tbl_religion";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $religions[$row['religion_id']] = $row['religion'];
    }
}

// Fetch castes for all religions
$castes = array();
$sql = "SELECT caste_id, religion_id, caste FROM tbl_caste";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if (!isset($castes[$row['religion_id']])) {
            $castes[$row['religion_id']] = array();
        }
        $castes[$row['religion_id']][] = array(
            'id' => $row['caste_id'],
            'name' => $row['caste']
        );
    }
}

// Get the current user's reg_id and existing preferences
$userid = $_SESSION['username'];
$reg_id_query = "SELECT p.reg_id, pref.gender, pref.min_age, pref.max_age, pref.height, pref.religion, pref.caste_id 
                FROM profiles p 
                LEFT JOIN tbl_preference pref ON p.reg_id = pref.reg_id 
                WHERE p.userid = ?";
$stmt = $conn->prepare($reg_id_query);
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();

$reg_id = null;
$existing_preferences = [
    'gender' => '',
    'min_age' => '',
    'max_age' => '',
    'height' => '',
    'religion' => '',
    'caste_id' => ''
];

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $reg_id = $row['reg_id'];
    $existing_preferences['gender'] = $row['gender'];
    $existing_preferences['min_age'] = $row['min_age'];
    $existing_preferences['max_age'] = $row['max_age'];
    $existing_preferences['height'] = $row['height'];
    $existing_preferences['religion'] = $row['religion'];
    $existing_preferences['caste_id'] = $row['caste_id'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data, keeping existing values if new values are null
    $gender = $_POST['gender'] !== "" ? $_POST['gender'] : $existing_preferences['gender'];
    $min_age = $_POST['min_age'] !== "" ? $_POST['min_age'] : $existing_preferences['min_age'];
    $max_age = $_POST['max_age'] !== "" ? $_POST['max_age'] : $existing_preferences['max_age'];
    $height = $_POST['height'] !== "" ? $_POST['height'] : $existing_preferences['height'];
    $religion_id = $_POST['religion'] !== "" ? $_POST['religion'] : $existing_preferences['religion'];
    $caste_id = $_POST['caste'] !== "" ? $_POST['caste'] : $existing_preferences['caste_id'];
    
    // If "null" is selected, set the value to NULL
    if ($gender === "null") $gender = null;
    if ($min_age === "null") $min_age = null;
    if ($max_age === "null") $max_age = null;
    if ($height === "null") $height = null;
    if ($religion_id === "null") $religion_id = null;
    if ($caste_id === "null") $caste_id = null;

    if ($reg_id) {
        // Check if preference already exists for this user
        $check_pref_query = "SELECT pref_id FROM tbl_preference WHERE reg_id = ?";
        $check_stmt = $conn->prepare($check_pref_query);
        $check_stmt->bind_param("i", $reg_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Update existing preference
            $sql = "UPDATE tbl_preference SET 
                    gender = ?, 
                    min_age = ?, 
                    max_age = ?, 
                    religion = ?, 
                    caste_id = ?, 
                    height = ? 
                    WHERE reg_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siisiis", 
                $gender, $min_age, $max_age, $religion_id, $caste_id, $height, $reg_id);
        } else {
            // Insert new preference
            $sql = "INSERT INTO tbl_preference 
                    (reg_id, gender, min_age, max_age, religion, caste_id, height) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isiisis", 
                $reg_id, $gender, $min_age, $max_age, $religion_id, $caste_id, $height);
        }

        if ($stmt->execute()) {
            // Redirect to profile.php after successful save
            header("Location: profile.php");
            exit();
        } else {
            echo "<script>alert('Error saving preferences: " . $conn->error . "');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('User profile not found.');</script>";
    }
}

// Handle AJAX request for fetching castes
if (isset($_GET['religion'])) {
    $religion_id = $_GET['religion'];

    // Fetch castes based on the selected religion
    $sql = "SELECT caste_id, caste FROM tbl_caste WHERE religion_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $religion_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $caste_options = array();
    while ($row = $result->fetch_assoc()) {
        $caste_options[] = array(
            'id' => $row['caste_id'],
            'name' => $row['caste']
        );
    }

    echo json_encode($caste_options);
    exit(); // Ensure no further code is executed after the AJAX response
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Preferences - MatrimoSys</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            color: #333;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('2.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            line-height: 1.6;
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
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }

        form {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .section {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .section:hover {
            transform: translateY(-5px);
        }

        h2 {
            color: #f3c634;
            font-size: 1.8rem;
            margin-bottom: 25px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: #FFD700;
            border-radius: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fff;
            color: #2d3748;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
            outline: none;
        }

        button[type="submit"] {
            background: #FFD700;
            color: #333;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 40px auto 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        button[type="submit"]:hover {
            background: #f3c634;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .error-message {
            background: #fff5f5;
            color: #c53030;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c53030;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                margin-top: 100px;
                padding: 0 15px;
            }

            form {
                padding: 20px;
            }

            .section {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">MatrimoSys</div>
        <nav class="nav">
            <a href="<?php echo $home_link; ?>">Home</a>
            <a href="profile.php">View Profile</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <div class="container">
        <form method="post">
            <h2>User Preferences</h2>
            <div class="form-group">
                <label for="gender">Preferred Gender</label>
                <select id="gender" name="gender">
                    <option value="">Select Gender</option>
                    <option value="null">No Preference</option>
                    <option value="Male" <?php if($existing_preferences['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                    <option value="Female" <?php if($existing_preferences['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                    <option value="Both" <?php if($existing_preferences['gender'] == 'Both') echo 'selected'; ?>>Both</option>
                </select>
            </div>
            <div class="form-group">
                <label for="min_age">Preferred Age Range</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <select id="min_age" name="min_age" style="flex: 1;">
                        <option value="">Minimum Age</option>
                        <option value="null">No Preference</option>
                        <?php for($i = 18; $i <= 60; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if($existing_preferences['min_age'] == $i) echo 'selected'; ?>><?php echo $i; ?> years</option>
                        <?php endfor; ?>
                    </select>
                    <span>to</span>
                    <select id="max_age" name="max_age" style="flex: 1;">
                        <option value="">Maximum Age</option>
                        <option value="null">No Preference</option>
                        <?php for($i = 18; $i <= 60; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if($existing_preferences['max_age'] == $i) echo 'selected'; ?>><?php echo $i; ?> years</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="height">Preferred Height</label>
                <select id="height" name="height">
                    <option value="">Select Height</option>
                    <option value="null">No Preference</option>
                    <option value="4" <?php if($existing_preferences['height'] == '4') echo 'selected'; ?>>4 feet and above</option>
                    <option value="5" <?php if($existing_preferences['height'] == '5') echo 'selected'; ?>>5 feet and above</option>
                    <option value="6" <?php if($existing_preferences['height'] == '6') echo 'selected'; ?>>6 feet and above</option>
                </select>
            </div>
            <div class="form-group">
                <label for="religion">Preferred Religion</label>
                <select id="religion" name="religion">
                    <option value="">Select Religion</option>
                    <option value="null">No Preference</option>
                    <?php foreach ($religions as $religion_id => $religion): ?>
                        <option value="<?php echo $religion_id; ?>" <?php if($existing_preferences['religion'] == $religion_id) echo 'selected'; ?>><?php echo htmlspecialchars($religion); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="caste">Preferred Caste</label>
                <select id="caste" name="caste">
                    <option value="">Select Caste</option>
                    <option value="null">No Preference</option>
                    <?php 
                    // If religion is already selected, load the corresponding castes
                    if ($existing_preferences['religion'] && 
                        is_numeric($existing_preferences['religion']) && 
                        isset($castes[$existing_preferences['religion']])) {
                        foreach ($castes[$existing_preferences['religion']] as $caste) {
                            $selected = ($existing_preferences['caste_id'] == $caste['id']) ? 'selected' : '';
                            echo '<option value="' . $caste['id'] . '" ' . $selected . '>' . htmlspecialchars($caste['name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <button type="submit">Save Preferences</button>
        </form>
    </div>

    <script>
        document.getElementById('religion').addEventListener('change', function() {
            const religionId = this.value;
            const casteSelect = document.getElementById('caste');
            
            // Reset caste dropdown
            casteSelect.innerHTML = '<option value="">Select Caste</option><option value="null">No Preference</option>';
            
            // If "No Preference" or empty is selected, don't fetch castes
            if (religionId === "" || religionId === "null") {
                return;
            }

            // Fetch castes based on the selected religion
            fetch(`preference.php?religion=${religionId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(caste => {
                        const option = document.createElement('option');
                        option.value = caste.id;
                        option.textContent = caste.name;
                        casteSelect.appendChild(option);
                    });
                });
        });
    </script>
</body>
</html>