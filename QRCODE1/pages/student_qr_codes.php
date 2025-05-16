<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "student"){
    header("location: login.php");
    exit;
}

// Include database connection and timezone configuration
require_once "../config/database.php";
require_once "../config/timezone.php";

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

// Get class IDs the student is enrolled in
$class_ids = [];
while($class = mysqli_fetch_assoc($classes_result)) {
    $class_ids[] = $class["id"];
}
mysqli_data_seek($classes_result, 0); // Reset pointer

// Get today's date
$today = date("Y-m-d");

// Get attendance sessions for the student's classes
$sessions_result = false;

// Only query for sessions if the student is enrolled in at least one class
if (!empty($class_ids)) {
    // Get all sessions for the student's classes, including past sessions
    // This makes it easier to debug and ensures students can see all relevant QR codes
    $sessions_sql = "SELECT a.*, c.name as class_name
                    FROM attendance_sessions a
                    JOIN classes c ON a.class_id = c.id
                    WHERE a.class_id IN (" . implode(",", $class_ids) . ")
                    ORDER BY a.session_date DESC, a.session_time DESC";
    $sessions_stmt = mysqli_prepare($conn, $sessions_sql);
    mysqli_stmt_execute($sessions_stmt);
    $sessions_result = mysqli_stmt_get_result($sessions_stmt);
}

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>QR Codes for Your Classes</h2>
        <p>View QR codes for attendance sessions in your classes. Scan these to mark your attendance.</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
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
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>All Attendance Sessions</h5>
            </div>
            <div class="card-body">
                <?php if($sessions_result && mysqli_num_rows($sessions_result) > 0): ?>
                <div class="row">
                    <?php while($session = mysqli_fetch_assoc($sessions_result)): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><?php echo htmlspecialchars($session["class_name"]); ?></h6>
                            </div>
                            <div class="card-body text-center">
                                <p>Date: <?php echo htmlspecialchars($session["session_date"]); ?></p>
                                <p>Time: <?php echo htmlspecialchars($session["session_time"]); ?></p>

                                <?php
                                // Check if session is in the past (using both date and time with Philippines timezone)
                                $now = get_philippines_datetime();
                                $session_time = $session["session_time"];

                                // Use the timezone function to check if session is expired
                                $is_past = is_session_expired($session["session_date"], $session_time);

                                // Ensure time is in HH:MM:SS format for display
                                if(preg_match('/^\d{2}:\d{2}$/', $session_time)) {
                                    $session_time = $session_time . ":00";
                                }

                                $session_datetime = $session["session_date"] . " " . $session_time;
                                if($is_past):
                                ?>
                                <div class="alert alert-warning">
                                    <strong>Expired Session</strong><br>
                                    <small>Attendance cannot be marked</small>
                                </div>
                                <?php endif; ?>

                                <div class="qr-code-container">
                                    <div id="qrcode-<?php echo $session["id"]; ?>" class="qr-code"></div>
                                    <input type="hidden" id="qr-data-<?php echo $session["id"]; ?>" value="<?php echo $session["qr_code_data"]; ?>">
                                </div>

                                <div class="mt-2">
                                    <p><small>Session Code: <strong><?php echo $session["qr_code_data"]; ?></strong></small></p>
                                </div>

                                <div class="mt-3">
                                    <?php
                                    // Check if student has already marked attendance
                                    $check_sql = "SELECT * FROM attendance_records WHERE session_id = ? AND student_id = ?";
                                    $check_stmt = mysqli_prepare($conn, $check_sql);
                                    mysqli_stmt_bind_param($check_stmt, "ii", $session["id"], $student_id);
                                    mysqli_stmt_execute($check_stmt);
                                    mysqli_stmt_store_result($check_stmt);
                                    $has_attendance = mysqli_stmt_num_rows($check_stmt) > 0;
                                    mysqli_stmt_close($check_stmt);
                                    ?>

                                    <?php if($is_past && $has_attendance): ?>
                                    <div>
                                        <span class="badge bg-warning">Expired</span>
                                        <span class="badge bg-success">Attendance Marked</span>
                                    </div>
                                    <?php elseif($is_past): ?>
                                    <span class="badge bg-warning">Expired</span>
                                    <?php elseif($has_attendance): ?>
                                    <span class="badge bg-success">Attendance Marked</span>
                                    <?php else: ?>
                                    <a href="student_mark_attendance.php?session_code=<?php echo $session["qr_code_data"]; ?>" class="btn btn-sm btn-primary">Mark Attendance</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <p>No attendance sessions found for your classes.</p>
                    <p>Possible reasons:</p>
                    <ul>
                        <li>Your teacher hasn't created any attendance sessions yet</li>
                        <li>You're not enrolled in any classes with active attendance sessions</li>
                    </ul>
                    <p>Please contact your teacher if you believe this is an error.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- QR Code JS Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate QR codes for each session
    <?php
    if ($sessions_result && mysqli_num_rows($sessions_result) > 0) {
        mysqli_data_seek($sessions_result, 0);
        while($session = mysqli_fetch_assoc($sessions_result)):
        ?>
        new QRCode(document.getElementById("qrcode-<?php echo $session["id"]; ?>"), {
            text: document.getElementById("qr-data-<?php echo $session["id"]; ?>").value,
            width: 128,
            height: 128,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        <?php endwhile;
    }
    ?>
});
</script>

<?php
// Include footer
include_once "../includes/footer.php";
?>
