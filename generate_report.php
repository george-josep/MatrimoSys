<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = mysqli_connect("localhost", "root", "", "matrimosys");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Prepare query based on report type
$report_type = isset($_POST['report_type']) ? $_POST['report_type'] : 'all';

switch ($report_type) {
    case 'gender':
        $query = "SELECT username, email, dob, gender FROM profiles WHERE gender IS NOT NULL ORDER BY gender";
        $headers = array('Username', 'Email', 'Date of Birth', 'Gender');
        $title = "Users by Gender Report";
        break;
    case 'age':
        $query = "SELECT username, email, dob, age FROM profiles ORDER BY age";
        $headers = array('Username', 'Email', 'Date of Birth', 'Age');
        $title = "Users by Age Report";
        break;
    case 'location':
        $query = "SELECT username, email, dob, nativity FROM profiles WHERE nativity IS NOT NULL ORDER BY nativity";
        $headers = array('Username', 'Email', 'Date of Birth', 'Location');
        $title = "Users by Location Report";
        break;
    case 'religion':
        $query = "SELECT p.username, p.email, p.dob, r.religion 
                 FROM profiles p 
                 LEFT JOIN tbl_caste c ON p.caste_id = c.caste_id 
                 LEFT JOIN tbl_religion r ON c.religion_id = r.religion_id 
                 ORDER BY r.religion";
        $headers = array('Username', 'Email', 'Date of Birth', 'Religion');
        $title = "Users by Religion Report";
        break;
    case 'education':
        $query = "SELECT p.username, p.email, p.dob, e.education, s.eduSub 
                 FROM profiles p 
                 LEFT JOIN tbl_subEducation s ON p.edusub_id = s.edusub_id 
                 LEFT JOIN tbl_education e ON s.education_id = e.education_id 
                 ORDER BY e.education, s.eduSub";
        $headers = array('Username', 'Email', 'Date of Birth', 'Education', 'Specialization');
        $title = "Users by Education Report";
        break;
    default: // 'all'
        $query = "SELECT username, email, dob FROM profiles ORDER BY username";
        $headers = array('Username', 'Email', 'Date of Birth');
        $title = "All Users Report";
        break;
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Preview - MatrimoSys</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f7fa;
        }

        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3c634;
        }

        .report-header h1 {
            color: #333;
            margin: 0;
            font-size: 24px;
        }

        .report-meta {
            margin: 10px 0;
            color: #666;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .report-table th {
            background: #f3c634;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .report-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        .report-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .report-footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
        }

        .action-buttons {
            text-align: center;
            margin-top: 20px;
        }

        .download-btn {
            background: #f3c634;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }

        .download-btn:hover {
            background: #e0b52e;
        }

        @media print {
            .action-buttons {
                display: none;
            }
            body {
                padding: 0;
                background: white;
            }
            .report-container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <h1><?php echo $title; ?></h1>
            <div class="report-meta">
                <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
                <p>Generated by: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?php echo htmlspecialchars($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td><?php echo htmlspecialchars($value); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="report-footer">
            <p>This is a system-generated report from MatrimoSys.</p>
        </div>

        <div class="action-buttons">
            <form action="download_report.php" method="post" style="display: inline-block; margin: 0 10px;">
                <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
                <input type="hidden" name="format" value="csv">
                <button type="submit" class="download-btn">Download as CSV</button>
            </form>
            <form action="download_report.php" method="post" style="display: inline-block; margin: 0 10px;">
                <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
                <input type="hidden" name="format" value="pdf">
                <button type="submit" class="download-btn">Download as PDF</button>
            </form>
            <form action="download_report.php" method="post" style="display: inline-block; margin: 0 10px;">
                <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
                <input type="hidden" name="format" value="excel">
                <button type="submit" class="download-btn">Download as Excel</button>
            </form>
        </div>
    </div>
</body>
</html>