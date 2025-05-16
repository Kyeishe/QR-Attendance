<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "student"){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Get student information
$student_id = $_SESSION["id"];
$student_code = $_SESSION["username"];

// Get classes the student is enrolled in
$classes_sql = "SELECT c.* FROM classes c
                JOIN class_students cs ON c.id = cs.class_id
                WHERE cs.student_id = ?";
$classes_stmt = mysqli_prepare($conn, $classes_sql);
mysqli_stmt_bind_param($classes_stmt, "i", $student_id);
mysqli_stmt_execute($classes_stmt);
$classes_result = mysqli_stmt_get_result($classes_stmt);

// Get recent attendance records
$attendance_sql = "SELECT ar.*, a.session_date, a.session_time, c.name as class_name
                  FROM attendance_records ar
                  JOIN attendance_sessions a ON ar.session_id = a.id
                  JOIN classes c ON a.class_id = c.id
                  WHERE ar.student_id = ?
                  ORDER BY ar.timestamp DESC LIMIT 10";
$attendance_stmt = mysqli_prepare($conn, $attendance_sql);
mysqli_stmt_bind_param($attendance_stmt, "i", $student_id);
mysqli_stmt_execute($attendance_stmt);
$attendance_result = mysqli_stmt_get_result($attendance_stmt);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Student Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($student_code); ?>!</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Your Classes</h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($classes_result) > 0): ?>
                <div class="list-group">
                    <?php while($class = mysqli_fetch_assoc($classes_result)): ?>
                    <div class="list-group-item">
                        <h6 class="mb-1"><?php echo htmlspecialchars($class["name"]); ?></h6>
                        <?php
                        // Get teacher name
                        $teacher_sql = "SELECT username FROM users WHERE id = ?";
                        $teacher_stmt = mysqli_prepare($conn, $teacher_sql);
                        mysqli_stmt_bind_param($teacher_stmt, "i", $class["teacher_id"]);
                        mysqli_stmt_execute($teacher_stmt);
                        $teacher_result = mysqli_stmt_get_result($teacher_stmt);
                        $teacher_name = mysqli_fetch_assoc($teacher_result)["username"];
                        ?>
                        <small>Teacher: <?php echo htmlspecialchars($teacher_name); ?></small>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p>You are not enrolled in any classes yet.</p>
                <?php endif; ?>

                <div class="mt-3">
                    <a href="student_enroll.php" class="btn btn-primary">Browse & Enroll in Classes</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Your Attendance</h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($attendance_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Class</th>
                                <th>Marked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($attendance = mysqli_fetch_assoc($attendance_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attendance["session_date"]); ?></td>
                                <td><?php echo htmlspecialchars($attendance["session_time"]); ?></td>
                                <td><?php echo htmlspecialchars($attendance["class_name"]); ?></td>
                                <td><?php echo date("h:i A", strtotime($attendance["timestamp"])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>No attendance records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Mark Attendance</h5>
            </div>
            <div class="card-body text-center">
                <p>To mark your attendance, scan the QR code provided by your teacher or enter the session code below.</p>

                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <form action="student_mark_attendance.php" method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="session_code" class="form-label">Session Code</label>
                                <input type="text" name="session_code" id="session_code" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Mark Attendance</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12 text-center">
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
