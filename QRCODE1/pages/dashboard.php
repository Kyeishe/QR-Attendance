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

// Get counts for dashboard
$user_id = $_SESSION["id"];
$role = $_SESSION["role"];

// Get classes count
if($role == "admin") {
    $classes_sql = "SELECT COUNT(*) as count FROM classes";
} else {
    $classes_sql = "SELECT COUNT(*) as count FROM classes WHERE teacher_id = $user_id";
}
$classes_result = mysqli_query($conn, $classes_sql);
$classes_count = mysqli_fetch_assoc($classes_result)["count"];

// Get students count
if($role == "admin") {
    $students_sql = "SELECT COUNT(*) as count FROM students";
} else {
    $students_sql = "SELECT COUNT(DISTINCT s.id) as count FROM students s
                    JOIN class_students cs ON s.id = cs.student_id
                    JOIN classes c ON cs.class_id = c.id
                    WHERE c.teacher_id = $user_id";
}
$students_result = mysqli_query($conn, $students_sql);
$students_count = mysqli_fetch_assoc($students_result)["count"];

// Get attendance sessions count
if($role == "admin") {
    $sessions_sql = "SELECT COUNT(*) as count FROM attendance_sessions";
} else {
    $sessions_sql = "SELECT COUNT(*) as count FROM attendance_sessions as a
                    JOIN classes c ON a.class_id = c.id
                    WHERE c.teacher_id = $user_id";
}
$sessions_result = mysqli_query($conn, $sessions_sql);
$sessions_count = mysqli_fetch_assoc($sessions_result)["count"];

// Get recent attendance sessions
if($role == "admin") {
    $recent_sql = "SELECT a.id, a.session_date, a.session_time, c.name as class_name,
                  (SELECT COUNT(*) FROM attendance_records WHERE session_id = a.id) as attendance_count
                  FROM attendance_sessions a
                  JOIN classes c ON a.class_id = c.id
                  ORDER BY a.session_date DESC, a.session_time DESC LIMIT 5";
} else {
    $recent_sql = "SELECT a.id, a.session_date, a.session_time, c.name as class_name,
                  (SELECT COUNT(*) FROM attendance_records WHERE session_id = a.id) as attendance_count
                  FROM attendance_sessions a
                  JOIN classes c ON a.class_id = c.id
                  WHERE c.teacher_id = $user_id
                  ORDER BY a.session_date DESC, a.session_time DESC LIMIT 5";
}
$recent_result = mysqli_query($conn, $recent_sql);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>! <small class="text-muted">(<?php echo ucfirst(htmlspecialchars($_SESSION["role"])); ?>)</small></p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Classes</h5>
                <h2 class="display-4"><?php echo $classes_count; ?></h2>
                <p class="card-text">Total classes</p>
                <a href="manage_classes.php" class="btn btn-light btn-sm">Manage Classes</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Students</h5>
                <h2 class="display-4"><?php echo $students_count; ?></h2>
                <p class="card-text">Total students</p>
                <a href="manage_students.php" class="btn btn-light btn-sm">Manage Students</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Attendance Sessions</h5>
                <h2 class="display-4"><?php echo $sessions_count; ?></h2>
                <p class="card-text">Total sessions</p>
                <a href="attendance.php" class="btn btn-light btn-sm">Take Attendance</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Recent Attendance Sessions</h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($recent_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Class</th>
                                <th>Attendance Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($recent_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["session_date"]); ?></td>
                                <td><?php echo htmlspecialchars($row["session_time"]); ?></td>
                                <td><?php echo htmlspecialchars($row["class_name"]); ?></td>
                                <td><?php echo htmlspecialchars($row["attendance_count"]); ?></td>
                                <td>
                                    <a href="view_attendance.php?id=<?php echo $row["id"]; ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>No recent attendance sessions found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
            <a href="attendance.php" class="btn btn-primary me-md-2">Take Attendance</a>
            <a href="reports.php" class="btn btn-success">View Reports</a>
        </div>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
