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
    <title>Insert Caste - MatrimoSys Admin</title>
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            color: #333;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #f3c634;
        }

        .submit-btn {
            padding: 1rem 2rem;
            font-size: 1.2rem;
            background-color: #FFD700;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #f3c634;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 198, 52, 0.3);
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
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

        .action-btn {
            padding: 5px 10px;
            margin: 0 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
        }
        .edit-btn {
            background-color: #4CAF50;
        }
        .delete-btn {
            background-color: #f44336;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 500px;
        }
        .close {
            float: right;
            cursor: pointer;
            font-size: 24px;
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
                <h2 style="margin-bottom: 2rem; text-align: center;">Insert Caste</h2>

                <?php
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $conn = mysqli_connect("localhost", "root", "", "matrimosys");
                    
                    if (!$conn) {
                        echo "<div class='message error'>Connection failed: " . mysqli_connect_error() . "</div>";
                    } else {
                        // Handle delete action
                        if (isset($_POST['delete_id'])) {
                            $delete_id = mysqli_real_escape_string($conn, $_POST['delete_id']);
                            $delete_sql = "DELETE FROM tbl_caste WHERE caste_id = '$delete_id'";
                            if (mysqli_query($conn, $delete_sql)) {
                                echo "<div class='message success'>Caste deleted successfully</div>";
                            } else {
                                echo "<div class='message error'>Error deleting caste: " . mysqli_error($conn) . "</div>";
                            }
                        }
                        // Handle edit action
                        else if (isset($_POST['edit_id']) && isset($_POST['edit_caste']) && isset($_POST['edit_religion_id'])) {
                            $edit_id = mysqli_real_escape_string($conn, $_POST['edit_id']);
                            $edit_caste = mysqli_real_escape_string($conn, trim($_POST['edit_caste']));
                            $edit_religion_id = mysqli_real_escape_string($conn, $_POST['edit_religion_id']);
                            
                            if (empty($edit_caste)) {
                                echo "<div class='message error'>Caste name cannot be empty!</div>";
                            } else {
                                $update_sql = "UPDATE tbl_caste SET caste = '$edit_caste', religion_id = '$edit_religion_id' WHERE caste_id = '$edit_id'";
                                if (mysqli_query($conn, $update_sql)) {
                                    echo "<div class='message success'>Caste updated successfully</div>";
                                } else {
                                    echo "<div class='message error'>Error updating caste: " . mysqli_error($conn) . "</div>";
                                }
                            }
                        }
                        // Existing code for adding new caste...
                        else if (isset($_POST['caste']) && isset($_POST['religion_id'])) {
                            $caste = mysqli_real_escape_string($conn, trim($_POST['caste']));
                            $religion_id = mysqli_real_escape_string($conn, trim($_POST['religion_id']));
                            
                            // Check if caste is empty
                            if (empty($caste)) {
                                echo "<div class='message error'>Caste name cannot be empty!</div>";
                            } else {
                                // Check if caste already exists for this religion
                                $check_sql = "SELECT * FROM tbl_caste WHERE caste = '$caste' AND religion_id = '$religion_id'";
                                $check_result = mysqli_query($conn, $check_sql);
                                
                                if (mysqli_num_rows($check_result) > 0) {
                                    echo "<div class='message error'>Caste already exists for this religion!</div>";
                                } else {
                                    // Insert new caste
                                    $sql = "INSERT INTO tbl_caste (caste, religion_id) VALUES ('$caste', '$religion_id')";
                                    
                                    if (mysqli_query($conn, $sql)) {
                                        echo "<div class='message success'>Caste added successfully</div>";
                                    } else {
                                        echo "<div class='message error'>Error: " . mysqli_error($conn) . "</div>";
                                    }
                                }
                            }
                        }
                        
                        mysqli_close($conn);
                    }
                }
                ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="religion_id">Select Religion:</label>
                        <select id="religion_id" name="religion_id" required>
                            <option value="">Select a religion</option>
                            <?php
                            $conn = mysqli_connect("localhost", "root", "", "matrimosys");
                            if ($conn) {
                                $sql = "SELECT * FROM tbl_religion ORDER BY religion";
                                $result = mysqli_query($conn, $sql);
                                
                                if ($result && mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='" . $row['religion_id'] . "'>" . htmlspecialchars($row['religion']) . "</option>";
                                    }
                                }
                                mysqli_close($conn);
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="caste">Caste Name:</label>
                        <input type="text" id="caste" name="caste" required 
                               maxlength="50" placeholder="Enter caste name">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="submit-btn">Add Caste</button>
                    </div>
                </form>

                <!-- Display existing castes -->
                <div style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Existing Castes</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #f3c634;">ID</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #f3c634;">Religion</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #f3c634;">Caste</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #f3c634;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $conn = mysqli_connect("localhost", "root", "", "matrimosys");
                            if ($conn) {
                                $sql = "SELECT c.caste_id, c.caste, r.religion, c.religion_id 
                                       FROM tbl_caste c 
                                       JOIN tbl_religion r ON c.religion_id = r.religion_id 
                                       ORDER BY r.religion, c.caste";
                                $result = mysqli_query($conn, $sql);
                                
                                if ($result && mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($row['caste_id']) . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($row['religion']) . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($row['caste']) . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>";
                                        echo "<button class='action-btn edit-btn' onclick='openEditModal(" . $row['caste_id'] . ", \"" . htmlspecialchars($row['caste'], ENT_QUOTES) . "\", " . $row['religion_id'] . ")'>Edit</button>";
                                        echo "<button class='action-btn delete-btn' onclick='deleteCaste(" . $row['caste_id'] . ")'>Delete</button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='padding: 10px; text-align: center;'>No castes found</td></tr>";
                                }
                                mysqli_close($conn);
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this modal for editing -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Edit Caste</h3>
            <form method="POST">
                <input type="hidden" id="edit_id" name="edit_id">
                <div class="form-group">
                    <label for="edit_religion_id">Select Religion:</label>
                    <select id="edit_religion_id" name="edit_religion_id" required>
                        <option value="">Select a religion</option>
                        <?php
                        $conn = mysqli_connect("localhost", "root", "", "matrimosys");
                        if ($conn) {
                            $sql = "SELECT * FROM tbl_religion ORDER BY religion";
                            $result = mysqli_query($conn, $sql);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo "<option value='" . $row['religion_id'] . "'>" . htmlspecialchars($row['religion']) . "</option>";
                                }
                            }
                            mysqli_close($conn);
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_caste">Caste Name:</label>
                    <input type="text" id="edit_caste" name="edit_caste" required maxlength="50">
                </div>
                <div class="form-group">
                    <button type="submit" class="submit-btn">Update Caste</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add this JavaScript before closing body tag -->
    <script>
        function openEditModal(id, caste, religionId) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_caste').value = caste;
            document.getElementById('edit_religion_id').value = religionId;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function deleteCaste(id) {
            if (confirm('Are you sure you want to delete this caste?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html> 