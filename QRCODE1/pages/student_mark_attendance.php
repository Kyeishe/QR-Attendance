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

// Define variables
$session_code = "";
$success_msg = $error_msg = "";
$student_id = $_SESSION["id"];

// Get session code from URL parameter if available
if(isset($_GET["session_code"]) && !empty($_GET["session_code"])){
    $session_code = trim($_GET["session_code"]);

    // Automatically process the session code if provided via URL
    $auto_process = true;
} else {
    $auto_process = false;
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["session_code"]) && !empty($_POST["session_code"])){
        $session_code = trim($_POST["session_code"]);

        // Check if session code exists
        $session_sql = "SELECT * FROM attendance_sessions WHERE qr_code_data = ?";
        if($stmt = mysqli_prepare($conn, $session_sql)){
            mysqli_stmt_bind_param($stmt, "s", $session_code);
            mysqli_stmt_execute($stmt);
            $session_result = mysqli_stmt_get_result($stmt);

            if(mysqli_num_rows($session_result) == 0){
                $error_msg = "Invalid session code.";
            } else {
                $session_data = mysqli_fetch_assoc($session_result);
                $session_id = $session_data["id"];

                // Check if the session is expired (date and time are in the past)
                $now = get_philippines_datetime(); // Current date and time in Philippines timezone
                $session_date = $session_data["session_date"];
                $session_time = $session_data["session_time"];

                // Ensure the session date is in the correct format
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $session_date)) {
                    // Date is in YYYY-MM-DD format, proceed normally

                    // Ensure time is in HH:MM:SS format
                    if(preg_match('/^\d{2}:\d{2}$/', $session_time)) {
                        $session_time = $session_time . ":00";

                        // Update the session time in the database
                        $update_time_sql = "UPDATE attendance_sessions SET session_time = ? WHERE id = ?";
                        $update_time_stmt = mysqli_prepare($conn, $update_time_sql);
                        mysqli_stmt_bind_param($update_time_stmt, "si", $session_time, $session_data["id"]);
                        mysqli_stmt_execute($update_time_stmt);
                        mysqli_stmt_close($update_time_stmt);
                    }

                    // Use the timezone function to check if session is expired
                    $is_expired = is_session_expired($session_date, $session_time);

                    // For debug information
                    $session_datetime = $session_date . " " . $session_time;
                    $session_timestamp = strtotime($session_datetime);
                    $now_timestamp = strtotime($now);
                } else {
                    // Try to convert the date to the correct format
                    $parsed_date = date_parse($session_date);
                    if ($parsed_date['error_count'] == 0 && checkdate($parsed_date['month'], $parsed_date['day'], $parsed_date['year'])) {
                        // Valid date, convert to YYYY-MM-DD format
                        $formatted_date = sprintf("%04d-%02d-%02d", $parsed_date['year'], $parsed_date['month'], $parsed_date['day']);
                        $session_datetime = $formatted_date . " " . $session_time;
                        $session_timestamp = strtotime($session_datetime);
                        $now_timestamp = strtotime($now);

                        // Update the session date in the database
                        $update_sql = "UPDATE attendance_sessions SET session_date = ? WHERE id = ?";
                        $update_stmt = mysqli_prepare($conn, $update_sql);
                        mysqli_stmt_bind_param($update_stmt, "si", $formatted_date, $session_data["id"]);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);

                        // Update the session date for this request
                        $session_date = $formatted_date;
                    } else {
                        // Invalid date, consider it as now to avoid errors
                        $session_timestamp = strtotime($now);
                        $now_timestamp = strtotime($now);
                        $is_expired = false;
                    }
                }

                // Debug information is removed for production

                if($is_expired){
                    $error_msg = "Expired Session. Attendance cannot be marked.";
                    // Stop processing - do not allow attendance marking for expired sessions
                    // Skip to the end of the if-else block
                    $auto_process = false; // Prevent auto-processing as well
                    goto display_page; // Jump to the display section
                } else {
                    // Check if student is enrolled in the class
                    $enrollment_sql = "SELECT * FROM class_students WHERE class_id = ? AND student_id = ?";
                    $enrollment_stmt = mysqli_prepare($conn, $enrollment_sql);
                    mysqli_stmt_bind_param($enrollment_stmt, "ii", $session_data["class_id"], $student_id);
                    mysqli_stmt_execute($enrollment_stmt);
                    mysqli_stmt_store_result($enrollment_stmt);

                    if(mysqli_stmt_num_rows($enrollment_stmt) == 0){
                        $error_msg = "You are not enrolled in this class.";
                    } else {
                        // Check if student is already marked present
                        $check_sql = "SELECT * FROM attendance_records WHERE session_id = ? AND student_id = ?";
                        $check_stmt = mysqli_prepare($conn, $check_sql);
                        mysqli_stmt_bind_param($check_stmt, "ii", $session_id, $student_id);
                        mysqli_stmt_execute($check_stmt);
                        mysqli_stmt_store_result($check_stmt);

                        if(mysqli_stmt_num_rows($check_stmt) > 0){
                            $error_msg = "You have already marked your attendance for this session.";
                        } else {
                            // Mark attendance
                            $insert_sql = "INSERT INTO attendance_records (session_id, student_id) VALUES (?, ?)";
                            $insert_stmt = mysqli_prepare($conn, $insert_sql);
                            mysqli_stmt_bind_param($insert_stmt, "ii", $session_id, $student_id);

                            if(mysqli_stmt_execute($insert_stmt)){
                                $success_msg = "Attendance marked successfully!";
                            } else {
                                $error_msg = "Error marking attendance.";
                            }

                            mysqli_stmt_close($insert_stmt);
                        }

                        mysqli_stmt_close($check_stmt);
                    }

                    mysqli_stmt_close($enrollment_stmt);
                }
            }

            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "Please enter a session code.";
    }
}

// Auto-process the session code if provided via URL
if($auto_process && !empty($session_code)) {
    // Check if session code exists
    $session_sql = "SELECT * FROM attendance_sessions WHERE qr_code_data = ?";
    if($stmt = mysqli_prepare($conn, $session_sql)){
        mysqli_stmt_bind_param($stmt, "s", $session_code);
        mysqli_stmt_execute($stmt);
        $session_result = mysqli_stmt_get_result($stmt);

        if(mysqli_num_rows($session_result) == 0){
            $error_msg = "Invalid session code.";
        } else {
            $session_data = mysqli_fetch_assoc($session_result);
            $session_id = $session_data["id"];

            // Check if the session is expired (date and time are in the past)
            $now = get_philippines_datetime(); // Current date and time in Philippines timezone
            $session_date = $session_data["session_date"];
            $session_time = $session_data["session_time"];

            // Ensure the session date is in the correct format
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $session_date)) {
                // Date is in YYYY-MM-DD format, proceed normally

                // Ensure time is in HH:MM:SS format
                if(preg_match('/^\d{2}:\d{2}$/', $session_time)) {
                    $session_time = $session_time . ":00";

                    // Update the session time in the database
                    $update_time_sql = "UPDATE attendance_sessions SET session_time = ? WHERE id = ?";
                    $update_time_stmt = mysqli_prepare($conn, $update_time_sql);
                    mysqli_stmt_bind_param($update_time_stmt, "si", $session_time, $session_data["id"]);
                    mysqli_stmt_execute($update_time_stmt);
                    mysqli_stmt_close($update_time_stmt);
                }

                // Use the timezone function to check if session is expired
                $is_expired = is_session_expired($session_date, $session_time);

                // For debug information
                $session_datetime = $session_date . " " . $session_time;
                $session_timestamp = strtotime($session_datetime);
                $now_timestamp = strtotime($now);
            } else {
                // Try to convert the date to the correct format
                $parsed_date = date_parse($session_date);
                if ($parsed_date['error_count'] == 0 && checkdate($parsed_date['month'], $parsed_date['day'], $parsed_date['year'])) {
                    // Valid date, convert to YYYY-MM-DD format
                    $formatted_date = sprintf("%04d-%02d-%02d", $parsed_date['year'], $parsed_date['month'], $parsed_date['day']);
                    $session_datetime = $formatted_date . " " . $session_time;
                    $session_timestamp = strtotime($session_datetime);
                    $now_timestamp = strtotime($now);

                    // Update the session date in the database
                    $update_sql = "UPDATE attendance_sessions SET session_date = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "si", $formatted_date, $session_data["id"]);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);

                    // Update the session date for this request
                    $session_date = $formatted_date;
                } else {
                    // Invalid date, consider it as now to avoid errors
                    $session_timestamp = strtotime($now);
                    $now_timestamp = strtotime($now);
                    $is_expired = false;
                }
            }

            // Debug information is removed for production

            if($is_expired){
                $error_msg = "Expired Session. Attendance cannot be marked.";
                // Stop processing - do not allow attendance marking for expired sessions
                // Skip to the end of the if-else block
                goto display_page; // Jump to the display section
            } else {
                // Check if student is enrolled in the class
                $enrollment_sql = "SELECT * FROM class_students WHERE class_id = ? AND student_id = ?";
                $enrollment_stmt = mysqli_prepare($conn, $enrollment_sql);
                mysqli_stmt_bind_param($enrollment_stmt, "ii", $session_data["class_id"], $student_id);
                mysqli_stmt_execute($enrollment_stmt);
                mysqli_stmt_store_result($enrollment_stmt);

                if(mysqli_stmt_num_rows($enrollment_stmt) == 0){
                    $error_msg = "You are not enrolled in this class.";
                } else {
                    // Check if student is already marked present
                    $check_sql = "SELECT * FROM attendance_records WHERE session_id = ? AND student_id = ?";
                    $check_stmt = mysqli_prepare($conn, $check_sql);
                    mysqli_stmt_bind_param($check_stmt, "ii", $session_id, $student_id);
                    mysqli_stmt_execute($check_stmt);
                    mysqli_stmt_store_result($check_stmt);

                    if(mysqli_stmt_num_rows($check_stmt) > 0){
                        $error_msg = "You have already marked your attendance for this session.";
                    } else {
                        // Mark attendance
                        $insert_sql = "INSERT INTO attendance_records (session_id, student_id) VALUES (?, ?)";
                        $insert_stmt = mysqli_prepare($conn, $insert_sql);
                        mysqli_stmt_bind_param($insert_stmt, "ii", $session_id, $student_id);

                        if(mysqli_stmt_execute($insert_stmt)){
                            $success_msg = "Attendance marked successfully!";
                        } else {
                            $error_msg = "Error marking attendance.";
                        }

                        mysqli_stmt_close($insert_stmt);
                    }

                    mysqli_stmt_close($check_stmt);
                }

                mysqli_stmt_close($enrollment_stmt);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Label for goto statements - this is where we jump to when we want to skip processing
display_page:

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Mark Attendance</h2>
        <p>Enter the session code provided by your teacher to mark your attendance.</p>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Enter Session Code</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="session_code" class="form-label">Session Code</label>
                        <input type="text" name="session_code" id="session_code" class="form-control" value="<?php echo htmlspecialchars($session_code); ?>" required>
                        <div class="form-text">Enter the code displayed on the QR code or provided by your teacher.</div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Mark Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12 text-center">
        <a href="student_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
