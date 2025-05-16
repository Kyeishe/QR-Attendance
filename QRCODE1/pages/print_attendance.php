<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Define variables
$session_id = "";

// Check if session_id is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: dashboard.php");
    exit;
}

$session_id = $_GET["id"];

// Verify session exists and user has permission
$session_sql = "SELECT a.*, c.name as class_name, c.teacher_id
                FROM attendance_sessions a
                JOIN classes c ON a.class_id = c.id
                WHERE a.id = ?";
if($stmt = mysqli_prepare($conn, $session_sql)){
    mysqli_stmt_bind_param($stmt, "i", $session_id);
    mysqli_stmt_execute($stmt);
    $session_result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($session_result) == 0){
        // Session not found
        header("location: dashboard.php");
        exit;
    }

    $session_data = mysqli_fetch_assoc($session_result);

    // Check if user has permission
    if($_SESSION["role"] != "admin" && $session_data["teacher_id"] != $_SESSION["id"]){
        // User doesn't have permission
        header("location: dashboard.php");
        exit;
    }

    mysqli_stmt_close($stmt);
}

// Get all students in this class
$students_sql = "SELECT s.* FROM students s
                JOIN class_students cs ON s.id = cs.student_id
                WHERE cs.class_id = ?
                ORDER BY s.student_id";
$students_stmt = mysqli_prepare($conn, $students_sql);
mysqli_stmt_bind_param($students_stmt, "i", $session_data["class_id"]);
mysqli_stmt_execute($students_stmt);
$students_result = mysqli_stmt_get_result($students_stmt);

// Get attendance records for this session
$attendance_sql = "SELECT ar.*, s.student_id as student_code
                  FROM attendance_records ar
                  JOIN students s ON ar.student_id = s.id
                  WHERE ar.session_id = ?
                  ORDER BY ar.timestamp";
$attendance_stmt = mysqli_prepare($conn, $attendance_sql);
mysqli_stmt_bind_param($attendance_stmt, "i", $session_id);
mysqli_stmt_execute($attendance_stmt);
$attendance_result = mysqli_stmt_get_result($attendance_stmt);

// Create an array of student IDs who attended
$attended_students = array();
$attendance_times = array();
while($row = mysqli_fetch_assoc($attendance_result)){
    $attended_students[] = $row["student_id"];
    $attendance_times[$row["student_id"]] = $row["timestamp"];
}

// Count present and absent
$present_count = count($attended_students);
$total_students = mysqli_num_rows($students_result);
$absent_count = $total_students - $present_count;
$attendance_percentage = ($total_students > 0) ? round(($present_count / $total_students) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - <?php echo htmlspecialchars($session_data["class_name"]); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .summary {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .present {
            color: green;
            font-weight: bold;
        }
        .absent {
            color: red;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Attendance Report</h2>
        <h3><?php echo htmlspecialchars($session_data["class_name"]); ?></h3>
        <p>Date: <?php echo htmlspecialchars($session_data["session_date"]); ?> | Time: <?php echo htmlspecialchars($session_data["session_time"]); ?></p>
    </div>

    <div class="summary">
        <table>
            <tr>
                <th>Total Students</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Attendance Percentage</th>
            </tr>
            <tr>
                <td><?php echo $total_students; ?></td>
                <td class="present"><?php echo $present_count; ?></td>
                <td class="absent"><?php echo $absent_count; ?></td>
                <td><?php echo $attendance_percentage; ?>%</td>
            </tr>
        </table>
    </div>

    <div class="attendance-list">
        <h4>Attendance List</h4>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Reset the students result pointer
                mysqli_data_seek($students_result, 0);

                $count = 1;
                while($student = mysqli_fetch_assoc($students_result)):
                    $is_present = in_array($student["id"], $attended_students);
                    $attendance_time = $is_present ? date("h:i A", strtotime($attendance_times[$student["id"]])) : "";
                ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td><?php echo htmlspecialchars($student["student_id"]); ?></td>
                    <td><?php echo htmlspecialchars($student["student_id"]); ?></td>
                    <td class="<?php echo $is_present ? 'present' : 'absent'; ?>">
                        <?php echo $is_present ? 'Present' : 'Absent'; ?>
                    </td>
                    <td><?php echo $attendance_time; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print</button>
        <a href="view_attendance.php?id=<?php echo $session_id; ?>" class="btn btn-secondary">Back</a>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
