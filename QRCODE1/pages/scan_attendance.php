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
$session_id = $student_id = "";
$success_msg = $error_msg = "";

// Check if session_id is provided
if(!isset($_GET["session_id"]) || empty($_GET["session_id"])){
    header("location: attendance.php");
    exit;
}

$session_id = $_GET["session_id"];

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
        header("location: attendance.php");
        exit;
    }

    $session_data = mysqli_fetch_assoc($session_result);

    // Check if user has permission
    if($_SESSION["role"] != "admin" && $session_data["teacher_id"] != $_SESSION["id"]){
        // User doesn't have permission
        header("location: attendance.php");
        exit;
    }

    mysqli_stmt_close($stmt);
}

// Process form submission for manual entry
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["student_id"]) && !empty($_POST["student_id"])){
        $student_id = trim($_POST["student_id"]);

        // Check if student exists
        $student_sql = "SELECT * FROM students WHERE student_id = ?";
        if($stmt = mysqli_prepare($conn, $student_sql)){
            mysqli_stmt_bind_param($stmt, "s", $student_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if(mysqli_stmt_num_rows($stmt) == 0){
                $error_msg = "Student ID not found.";
            } else {
                // Get student ID
                mysqli_stmt_bind_result($stmt, $student_db_id, $student_id_val, $student_password, $student_created);
                mysqli_stmt_fetch($stmt);

                // Check if student is already marked present
                $check_sql = "SELECT * FROM attendance_records WHERE session_id = ? AND student_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "ii", $session_id, $student_db_id);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);

                if(mysqli_stmt_num_rows($check_stmt) > 0){
                    $error_msg = "Student already marked present.";
                } else {
                    // Mark attendance
                    $insert_sql = "INSERT INTO attendance_records (session_id, student_id) VALUES (?, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($insert_stmt, "ii", $session_id, $student_db_id);

                    if(mysqli_stmt_execute($insert_stmt)){
                        $success_msg = "Attendance marked for student ID: " . $student_id_val . ".";
                    } else {
                        $error_msg = "Error marking attendance.";
                    }

                    mysqli_stmt_close($insert_stmt);
                }

                mysqli_stmt_close($check_stmt);
            }

            mysqli_stmt_close($stmt);
        }
    } elseif(isset($_POST["qr_result"]) && !empty($_POST["qr_result"])) {
        // Process QR code scan
        $qr_data = trim($_POST["qr_result"]);

        // Verify QR code matches session
        if($qr_data != $session_data["qr_code_data"]){
            $error_msg = "Invalid QR code.";
        } else {
            // Redirect to student login page for this session
            header("location: student_attendance.php?session_id=" . $session_id);
            exit;
        }
    }
}

// Get current attendance for this session
$attendance_sql = "SELECT ar.*, s.student_id as student_code
                  FROM attendance_records ar
                  JOIN students s ON ar.student_id = s.id
                  WHERE ar.session_id = ?
                  ORDER BY ar.timestamp DESC";
$attendance_stmt = mysqli_prepare($conn, $attendance_sql);
mysqli_stmt_bind_param($attendance_stmt, "i", $session_id);
mysqli_stmt_execute($attendance_stmt);
$attendance_result = mysqli_stmt_get_result($attendance_stmt);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Scan Attendance</h2>
        <p>Session for: <strong><?php echo htmlspecialchars($session_data["class_name"]); ?></strong> on <?php echo htmlspecialchars($session_data["session_date"]); ?> at <?php echo htmlspecialchars($session_data["session_time"]); ?></p>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>QR Code Scanner</h5>
            </div>
            <div class="card-body">
                <p>Scan the QR code shown to students to enable them to mark their attendance.</p>

                <div class="scanner-container">
                    <div id="reader"></div>
                </div>

                <form id="scan-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?session_id=" . $session_id); ?>" method="post">
                    <input type="hidden" id="qr-result" name="qr_result">
                </form>

                <div class="mt-3">
                    <a href="student_attendance.php?session_id=<?php echo $session_id; ?>" class="btn btn-primary">Go to Student Login Page</a>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5>Manual Entry</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?session_id=" . $session_id); ?>" method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student ID</label>
                        <input type="text" name="student_id" id="student_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Mark Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Current Attendance</h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($attendance_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($attendance_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["student_code"]); ?></td>
                                <td><?php echo htmlspecialchars($row["student_code"]); ?></td>
                                <td><?php echo date("h:i A", strtotime($row["timestamp"])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>No attendance records yet.</p>
                <?php endif; ?>

                <div class="mt-3">
                    <a href="view_attendance.php?id=<?php echo $session_id; ?>" class="btn btn-info">View Full Attendance</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include HTML5 QR Code Scanner library -->
<script src="https://unpkg.com/html5-qrcode@2.0.9/dist/html5-qrcode.min.js"></script>

<?php
// Include footer
include_once "../includes/footer.php";
?>
