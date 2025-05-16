<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection and timezone configuration
require_once "../config/database.php";
require_once "../config/timezone.php";

// Define variables and initialize with empty values
$class_id = $session_date = $session_time = "";
$class_id_err = $session_date_err = $session_time_err = "";
$success_msg = $error_msg = "";
$qr_code_data = "";
$session_id = 0;

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate class
    if(empty(trim($_POST["class_id"]))){
        $class_id_err = "Please select a class.";
    } else{
        $class_id = trim($_POST["class_id"]);

        // Check if user has permission for this class
        if($_SESSION["role"] != "admin"){
            $check_sql = "SELECT * FROM classes WHERE id = ? AND teacher_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ii", $class_id, $_SESSION["id"]);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if(mysqli_stmt_num_rows($check_stmt) == 0){
                $class_id_err = "You don't have permission for this class.";
            }
            mysqli_stmt_close($check_stmt);
        }
    }

    // Validate date
    if(empty(trim($_POST["session_date"]))){
        $session_date_err = "Please enter a date.";
    } else{
        $session_date = trim($_POST["session_date"]);
        // Check if date is valid
        if(!preg_match("/^\d{4}-\d{2}-\d{2}$/", $session_date)){
            $session_date_err = "Please enter a valid date in YYYY-MM-DD format.";
        }
    }

    // Validate time
    if(empty(trim($_POST["session_time"]))){
        $session_time_err = "Please enter a time.";
    } else{
        $session_time = trim($_POST["session_time"]);
        // Check if time is valid
        if(!preg_match("/^\d{2}:\d{2}$/", $session_time)){
            $session_time_err = "Please enter a valid time in HH:MM format.";
        }
    }

    // Check input errors before creating session
    if(empty($class_id_err) && empty($session_date_err) && empty($session_time_err)){
        // Generate a unique QR code data using Philippines time
        $philippines_timestamp = time(); // Already in Philippines time because of timezone.php
        $qr_code_data = "QR_ATTENDANCE_" . $philippines_timestamp . "_" . $class_id . "_" . rand(1000, 9999);

        // Prepare an insert statement
        $sql = "INSERT INTO attendance_sessions (class_id, session_date, session_time, qr_code_data) VALUES (?, ?, ?, ?)";

        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "isss", $param_class_id, $param_session_date, $param_session_time, $param_qr_code_data);

            // Set parameters
            $param_class_id = $class_id;
            $param_session_date = $session_date;

            // Ensure time is in HH:MM:SS format
            if(preg_match('/^\d{2}:\d{2}$/', $session_time)) {
                $param_session_time = $session_time . ":00";
            } else {
                $param_session_time = $session_time;
            }

            $param_qr_code_data = $qr_code_data;

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $session_id = mysqli_insert_id($conn);
                $success_msg = "Attendance session created successfully.";
            } else{
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all classes for the dropdown
if($_SESSION["role"] == "admin"){
    $sql = "SELECT * FROM classes ORDER BY name";
} else {
    $sql = "SELECT * FROM classes WHERE teacher_id = " . $_SESSION["id"] . " ORDER BY name";
}
$classes = mysqli_query($conn, $sql);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Take Attendance</h2>
        <p>Generate a QR code for students to scan and mark their attendance.</p>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if(empty($qr_code_data)): ?>
<div class="row mt-4">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Create Attendance Session</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="class_id" class="form-label">Class</label>
                        <select name="class_id" id="class_id" class="form-select <?php echo (!empty($class_id_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Class</option>
                            <?php while($row = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $row["id"]; ?>" <?php echo ($class_id == $row["id"]) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row["name"]); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="invalid-feedback"><?php echo $class_id_err; ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="session_date" class="form-label">Date</label>
                        <input type="date" name="session_date" id="session_date" class="form-control <?php echo (!empty($session_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $session_date; ?>">
                        <div class="invalid-feedback"><?php echo $session_date_err; ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="session_time" class="form-label">Time</label>
                        <input type="time" name="session_time" id="session_time" class="form-control <?php echo (!empty($session_time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $session_time; ?>">
                        <div class="invalid-feedback"><?php echo $session_time_err; ?></div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Generate QR Code</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row mt-4">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>QR Code for Attendance</h5>
            </div>
            <div class="card-body text-center">
                <p>Show this QR code to students to scan for attendance.</p>

                <div class="qr-code-container">
                    <div id="qrcode" class="qr-code"></div>
                    <input type="hidden" id="qr-data" value="<?php echo $qr_code_data; ?>">
                </div>

                <div class="mt-3">
                    <p><strong>Session Details:</strong></p>
                    <?php
                    // Get class name
                    $class_sql = "SELECT name FROM classes WHERE id = " . $class_id;
                    $class_result = mysqli_query($conn, $class_sql);
                    $class_name = mysqli_fetch_assoc($class_result)["name"];
                    ?>
                    <p>Class: <?php echo htmlspecialchars($class_name); ?></p>
                    <p>Date: <?php echo htmlspecialchars($session_date); ?></p>
                    <p>Time: <?php echo htmlspecialchars($session_time); ?> (Philippines Time UTC+8)</p>
                    <p>Current Time: <?php echo get_philippines_time(); ?> (Philippines Time UTC+8)</p>

                    <?php
                    // Check if the session is expired
                    $is_expired = is_session_expired($session_date, $session_time);
                    if($is_expired):
                    ?>
                    <div class="alert alert-warning mt-2">
                        <strong>Expired Session</strong><br>
                        <small>This QR code has expired. Students cannot mark attendance for this session.</small>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success mt-2">
                        <strong>Active Session</strong><br>
                        <small>This QR code is active. Students can mark attendance for this session.</small>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <a href="scan_attendance.php?session_id=<?php echo $session_id; ?>" class="btn btn-success">Go to Scanning Page</a>
                    <a href="view_attendance.php?id=<?php echo $session_id; ?>" class="btn btn-info">View Attendance</a>
                    <a href="attendance.php" class="btn btn-secondary">Create New Session</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Include footer
include_once "../includes/footer.php";
?>
