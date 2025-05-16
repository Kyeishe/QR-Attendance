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
$error_msg = "";

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
while($row = mysqli_fetch_assoc($attendance_result)){
    $attended_students[] = $row["student_id"];
}
// Reset the result pointer
mysqli_data_seek($attendance_result, 0);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Attendance Report</h2>
        <p>Session for: <strong><?php echo htmlspecialchars($session_data["class_name"]); ?></strong> on <?php echo htmlspecialchars($session_data["session_date"]); ?> at <?php echo htmlspecialchars($session_data["session_time"]); ?></p>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Attendance Summary</h5>
                <div>
                    <a href="export_attendance.php?id=<?php echo $session_id; ?>" class="btn btn-sm btn-success">Export to CSV</a>
                    <a href="print_attendance.php?id=<?php echo $session_id; ?>" class="btn btn-sm btn-primary" target="_blank">Print</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Total Students</h6>
                                <h3><?php echo mysqli_num_rows($students_result); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Present</h6>
                                <h3><?php echo count($attended_students); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h6>Absent</h6>
                                <h3><?php echo mysqli_num_rows($students_result) - count($attended_students); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Attendance List</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
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

                                while($student = mysqli_fetch_assoc($students_result)):
                                    $is_present = in_array($student["id"], $attended_students);
                                    $attendance_time = "";

                                    if($is_present){
                                        // Find the attendance record for this student
                                        mysqli_data_seek($attendance_result, 0);
                                        while($attendance = mysqli_fetch_assoc($attendance_result)){
                                            if($attendance["student_id"] == $student["id"]){
                                                $attendance_time = date("h:i A", strtotime($attendance["timestamp"]));
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student["student_id"]); ?></td>
                                    <td><?php echo htmlspecialchars($student["student_id"]); ?></td>
                                    <td>
                                        <?php if($is_present): ?>
                                            <span class="badge bg-success">Present</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Absent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $attendance_time; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
            <a href="attendance.php" class="btn btn-primary me-md-2">Take New Attendance</a>
            <a href="reports.php" class="btn btn-success">View All Reports</a>
        </div>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
