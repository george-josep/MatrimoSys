<?php
session_start();


// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MatrimoSys Admin</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
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

        .admin-layout {
            display: flex;
            margin-top: 80px;
        }

        .side-menu {
            width: 250px;
            background: rgba(255, 255, 255, 0.9);
            min-height: calc(100vh - 80px);
            padding: 2rem 0;
            position: fixed;
            left: 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .side-menu h3 {
            color: #333;
            padding: 0 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            border-bottom: 2px solid #f3c634;
            padding-bottom: 0.5rem;
        }

        .menu-item {
            padding: 1rem 1.5rem;
            color: #555;
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .menu-item:hover {
            background-color: #f3c634;
            color: #fff;
            padding-left: 2rem;
        }

        .admin-container {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
        }

        .admin-panel {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .admin-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .admin-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
        }

        .admin-header p {
            color: #666;
            font-size: 1.2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            backdrop-filter: blur(5px);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #f3c634;
            text-shadow: 0px 0px 10px rgba(243, 198, 52, 0.3);
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }

        .action-card h3 {
            color: #333;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f3c634;
        }

        button {
            padding: 1rem;
            font-size: 1.2rem;
            background-color: #FFD700;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        button:hover {
            background-color: #f3c634;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 198, 52, 0.3);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                padding: 15px 5%;
            }

            .nav {
                margin-top: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav a {
                margin: 5px 10px;
            }

            .side-menu {
                width: 100%;
                position: static;
                min-height: auto;
                margin-bottom: 1rem;
            }

            .admin-layout {
                flex-direction: column;
            }

            .admin-container {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">MatrimoSys Admin</div>
        <nav class="nav">
            <a href="admin.php">Dashboard</a>
            <a href="logout.html">Logout</a>
        </nav>
    </header>

    <div class="admin-layout">
        <div class="side-menu">
            <h3>Master Data</h3>
            <a href="users.php" class="menu-item">Users</a>
            <a href="insert-religion.php" class="menu-item">Insert Religion</a>
            <a href="insert-caste.php" class="menu-item">Insert Caste</a>
            <a href="insert-education.php" class="menu-item">Insert Education Qualifications</a>
            <a href="insert-sub-education.php" class="menu-item">Insert Sub Education Qualifications</a>
            <a href="insert-plans.php" class="menu-item">Insert Plans</a>
        </div>

        <div class="admin-container">
            <div class="admin-panel">
                <?php
                    // Enable error reporting for debugging
                    error_reporting(E_ALL);
                    ini_set('display_errors', 1);

                    // Database connection
                    $conn = mysqli_connect("localhost", "root", "", "matrimosys");
                    
                    if (!$conn) {
                        echo "<div style='color: red;'>Connection failed: " . mysqli_connect_error() . "</div>";
                    } else {
                        // Test if the profiles table exists
                        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'profiles'");
                        if (mysqli_num_rows($table_check) == 0) {
                            echo "<div style='color: red;'>Error: profiles table does not exist</div>";
                        } else {
                            // Initialize variables with default values
                            $total_users = 0;
                            $active_matches = 0;
                            $new_registrations = 0;
                            $success_stories = 0;

                            // Fetch total users
                            $query = "SELECT COUNT(*) as count FROM profiles";
                            $result = mysqli_query($conn, $query);
                            if ($result) {
                                $total_users = mysqli_fetch_assoc($result)['count'];
                            }

                            // Since there's no status column in the actual table structure,
                            // we'll count all users as active for now
                            $active_matches = $total_users;

                            // Count new registrations today
                            $query = "SELECT COUNT(*) as count FROM profiles WHERE DATE(dob) = CURDATE()";
                            $result = mysqli_query($conn, $query);
                            if ($result) {
                                $new_registrations = mysqli_fetch_assoc($result)['count'];
                            }

                            // Since there's no marital_status column, we'll set success stories to 0
                            $success_stories = 0;
                        }
                    }
                ?>
                
                <div class="admin-header">
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, Administrator</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <div class="number"><?php echo $total_users; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Active Matches</h3>
                        <div class="number"><?php echo $active_matches; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>New Registrations</h3>
                        <div class="number"><?php echo $new_registrations; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Success Stories</h3>
                        <div class="number"><?php echo $success_stories; ?></div>
                    </div>
                </div>

                <div class="admin-actions">
                    <div class="action-card">
                        <h3>User Management</h3>
                        <button onclick="location.href='users.php'">Manage Users</button>
                    </div>
                    
                    <div class="action-card">
                        <h3>Generate Reports</h3>
                        <form action="generate_report.php" method="post">
                            <select name="report_type" style="width: 100%; padding: 0.8rem; margin-bottom: 1rem; border-radius: 5px; border: 1px solid #ddd;">
                                <option value="all">All Users Report</option>
                                <option value="gender">Users by Gender</option>
                                <option value="age">Users by Age Group</option>
                                <option value="location">Users by Location</option>
                                <option value="religion">Users by Religion</option>
                                <option value="education">Users by Education</option>
                            </select>
                            <button type="submit">Generate Report</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 