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
    <title>MatrimoSys Admin - Users</title>
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

        /* Your existing table styles */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-table th, .user-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .user-table th {
            background-color: #f3c634;
            color: #333;
        }

        .user-table tr:hover {
            background-color: rgba(243, 198, 52, 0.1);
        }

        button {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            background-color: #FFD700;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 5px;
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

            .user-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php
    // Database connection
    $conn = mysqli_connect("localhost", "root", "", "matrimosys");
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    ?>

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
                <div class="admin-header">
                    <h1>User Management</h1>
                    <p>View and manage user profiles</p>
                </div>

                <!-- Search Form -->
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search by username" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>

                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Reg ID</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>DOB</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Initialize the query
                        $query = "SELECT * FROM profiles";
                        
                        // Check if a search term is provided
                        if (isset($_GET['search']) && !empty($_GET['search'])) {
                            $searchTerm = mysqli_real_escape_string($conn, $_GET['search']);
                            $query .= " WHERE username LIKE '%$searchTerm%'";
                        }
                        
                        $query .= " ORDER BY reg_id DESC";
                        $result = mysqli_query($conn, $query);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['reg_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['userid']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['dob']) . "</td>";
                                echo "<td>
                                        <button onclick=\"location.href='edit_user.php?id=" . $row['reg_id'] . "'\">Edit</button>
                                        <button onclick=\"if(confirm('Are you sure?')) location.href='delete_user.php?id=" . $row['reg_id'] . "'\">Delete</button>
                                    </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No users found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>