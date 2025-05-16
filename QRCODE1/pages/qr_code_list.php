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

// Define variables
$class_id = $date = "";
$error_msg = $success_msg = "";
$show_expired = false;

// Process filter form
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"]) && $_POST["action"] == "filter"){
        if(isset($_POST["class_id"])){
            $class_id = $_POST["class_id"];
        }

        if(isset($_POST["date"]) && !empty($_POST["date"])){
            $date = $_POST["date"];
        }

        if(isset($_POST["show_expired"])){
            $show_expired = true;
        }
    } elseif(isset($_POST["action"]) && $_POST["action"] == "delete_expired"){
        // Delete expired QR codes
        $today = date("Y-m-d");

        // First, get all expired session IDs
        $get_expired_sql = "SELECT id FROM attendance_sessions WHERE session_date < ?";
        $get_expired_stmt = mysqli_prepare($conn, $get_expired_sql);
        mysqli_stmt_bind_param($get_expired_stmt, "s", $today);
        mysqli_stmt_execute($get_expired_stmt);
        $expired_result = mysqli_stmt_get_result($get_expired_stmt);

        $expired_ids = [];
        while($row = mysqli_fetch_assoc($expired_result)) {
            $expired_ids[] = $row['id'];
        }
        mysqli_stmt_close($get_expired_stmt);

        if(!empty($expired_ids)) {
            // Delete associated attendance records first
            $ids_string = implode(",", $expired_ids);
            $delete_records_sql = "DELETE FROM attendance_records WHERE session_id IN ($ids_string)";

            if(mysqli_query($conn, $delete_records_sql)) {
                // Now delete the sessions
                $delete_sessions_sql = "DELETE FROM attendance_sessions WHERE id IN ($ids_string)";

                if(mysqli_query($conn, $delete_sessions_sql)) {
                    $affected_rows = mysqli_affected_rows($conn);
                    $success_msg = "Successfully deleted $affected_rows expired QR code(s).";
                } else {
                    $error_msg = "Error deleting expired QR codes: " . mysqli_error($conn);
                }
            } else {
                $error_msg = "Error deleting attendance records: " . mysqli_error($conn);
            }
        } else {
            $success_msg = "No expired QR codes found to delete.";
        }
    } elseif(isset($_POST["action"]) && $_POST["action"] == "delete_selected" && isset($_POST["session_ids"]) && !empty($_POST["session_ids"])){
        // Delete selected QR codes
        $session_ids = $_POST["session_ids"];
        $ids_string = implode(",", array_map('intval', $session_ids));

        // First delete associated attendance records
        $delete_records_sql = "DELETE FROM attendance_records WHERE session_id IN ($ids_string)";

        if(mysqli_query($conn, $delete_records_sql)){
            // Now delete the sessions
            $delete_sessions_sql = "DELETE FROM attendance_sessions WHERE id IN ($ids_string)";

            if(mysqli_query($conn, $delete_sessions_sql)){
                $affected_rows = mysqli_affected_rows($conn);
                $success_msg = "Successfully deleted $affected_rows selected QR code(s).";
            } else {
                $error_msg = "Error deleting selected QR codes: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Error deleting attendance records: " . mysqli_error($conn);
        }
    } elseif(isset($_POST["action"]) && $_POST["action"] == "delete_single" && isset($_POST["session_id"]) && !empty($_POST["session_id"])){
        // Delete a single attendance session
        $session_id = intval($_POST["session_id"]);

        // Get session details for the success message
        $session_sql = "SELECT c.name as class_name, a.session_date
                        FROM attendance_sessions a
                        JOIN classes c ON a.class_id = c.id
                        WHERE a.id = ?";
        $session_stmt = mysqli_prepare($conn, $session_sql);
        mysqli_stmt_bind_param($session_stmt, "i", $session_id);
        mysqli_stmt_execute($session_stmt);
        $session_result = mysqli_stmt_get_result($session_stmt);
        $session_details = mysqli_fetch_assoc($session_result);
        mysqli_stmt_close($session_stmt);

        // First delete associated attendance records
        $delete_records_sql = "DELETE FROM attendance_records WHERE session_id = ?";
        $delete_records_stmt = mysqli_prepare($conn, $delete_records_sql);
        mysqli_stmt_bind_param($delete_records_stmt, "i", $session_id);

        if(mysqli_stmt_execute($delete_records_stmt)){
            mysqli_stmt_close($delete_records_stmt);

            // Now delete the session
            $delete_session_sql = "DELETE FROM attendance_sessions WHERE id = ?";
            $delete_session_stmt = mysqli_prepare($conn, $delete_session_sql);
            mysqli_stmt_bind_param($delete_session_stmt, "i", $session_id);

            if(mysqli_stmt_execute($delete_session_stmt)){
                if($session_details) {
                    $success_msg = "Successfully deleted attendance session for " . htmlspecialchars($session_details["class_name"]) . " on " . htmlspecialchars($session_details["session_date"]) . ".";
                } else {
                    $success_msg = "Successfully deleted attendance session.";
                }
            } else {
                $error_msg = "Error deleting attendance session: " . mysqli_error($conn);
            }

            mysqli_stmt_close($delete_session_stmt);
        } else {
            $error_msg = "Error deleting attendance records: " . mysqli_error($conn);
            mysqli_stmt_close($delete_records_stmt);
        }
    }
}

// Get all classes for the dropdown
if($_SESSION["role"] == "admin"){
    $classes_sql = "SELECT * FROM classes ORDER BY name";
} else {
    $classes_sql = "SELECT * FROM classes WHERE teacher_id = " . $_SESSION["id"] . " ORDER BY name";
}
$classes_result = mysqli_query($conn, $classes_sql);

// Get today's date
$today = date("Y-m-d");

// Build the attendance sessions query based on filters
$sessions_sql = "SELECT a.*, c.name as class_name,
                CASE WHEN a.session_date < '$today' THEN 1 ELSE 0 END as is_expired
                FROM attendance_sessions a
                JOIN classes c ON a.class_id = c.id
                WHERE 1=1";

// Add filter conditions
if($_SESSION["role"] != "admin"){
    $sessions_sql .= " AND c.teacher_id = " . $_SESSION["id"];
}

if(!empty($class_id)){
    $sessions_sql .= " AND a.class_id = " . $class_id;
}

if(!empty($date)){
    $sessions_sql .= " AND a.session_date = '" . $date . "'";
}

// Show expired QR codes only if requested
if(!$show_expired){
    $sessions_sql .= " AND a.session_date >= '" . $today . "'";
}

$sessions_sql .= " ORDER BY a.session_date DESC, a.session_time DESC";
$sessions_result = mysqli_query($conn, $sessions_sql);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>QR Code List</h2>
        <p>View all QR codes for attendance sessions. Students can use these to mark their attendance.</p>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Filter QR Codes</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="row g-3">
                    <input type="hidden" name="action" value="filter">
                    <div class="col-md-4">
                        <label for="class_id" class="form-label">Class</label>
                        <select name="class_id" id="class_id" class="form-select">
                            <option value="">All Classes</option>
                            <?php
                            // Reset the pointer to the beginning of the result set
                            mysqli_data_seek($classes_result, 0);
                            while($class = mysqli_fetch_assoc($classes_result)):
                            ?>
                            <option value="<?php echo $class["id"]; ?>" <?php echo ($class_id == $class["id"]) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class["name"]); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" value="<?php echo $date; ?>">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="show_expired" id="show_expired" <?php echo $show_expired ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="show_expired">
                                Show Expired QR Codes
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>

                <div class="mt-3 d-flex gap-2">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return confirm('Are you sure you want to delete all expired QR codes? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete_expired">
                        <button type="submit" class="btn btn-danger">Delete All Expired QR Codes</button>
                    </form>

                    <a href="attendance.php" class="btn btn-primary">Create New Attendance Session</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>QR Codes for Attendance</h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($sessions_result) > 0): ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="qrCodesForm" onsubmit="return confirmDelete();">
                    <input type="hidden" name="action" value="delete_selected">

                    <div class="mb-3">
                        <button type="submit" class="btn btn-danger">Delete Selected QR Codes</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleCheckboxes()">Select/Deselect All</button>
                    </div>

                    <div class="row">
                        <?php while($session = mysqli_fetch_assoc($sessions_result)):
                            $is_expired = $session["is_expired"] == 1;
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card <?php echo $is_expired ? 'border-danger' : ''; ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6><?php echo htmlspecialchars($session["class_name"]); ?></h6>
                                    <div class="form-check">
                                        <input class="form-check-input qr-checkbox" type="checkbox" name="session_ids[]" value="<?php echo $session["id"]; ?>" id="check-<?php echo $session["id"]; ?>">
                                    </div>
                                </div>
                                <div class="card-body text-center">
                                    <p>Date: <?php echo htmlspecialchars($session["session_date"]); ?></p>
                                    <p>Time: <?php echo htmlspecialchars($session["session_time"]); ?></p>

                                    <?php
                                    // Check if the session is expired based on both date and time
                                    $is_time_expired = is_session_expired($session["session_date"], $session["session_time"]);

                                    if($is_expired || $is_time_expired):
                                    ?>
                                    <div class="alert alert-danger">
                                        <strong>Expired Session</strong><br>
                                        <small>Attendance cannot be marked</small>
                                    </div>
                                    <?php else:
                                        $days_until_expiry = (strtotime($session["session_date"]) - strtotime($today)) / (60 * 60 * 24);
                                        if($days_until_expiry <= 1) {
                                            $expiry_class = "text-danger";
                                        } elseif($days_until_expiry <= 3) {
                                            $expiry_class = "text-warning";
                                        } else {
                                            $expiry_class = "text-success";
                                        }
                                    ?>
                                    <p class="<?php echo $expiry_class; ?>">
                                        <small>
                                            <?php if($days_until_expiry == 0): ?>
                                                Expires today
                                            <?php elseif($days_until_expiry == 1): ?>
                                                Expires tomorrow
                                            <?php else: ?>
                                                Expires in <?php echo ceil($days_until_expiry); ?> days
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                    <?php endif; ?>

                                    <div class="qr-code-container">
                                        <div id="qrcode-<?php echo $session["id"]; ?>" class="qr-code"></div>
                                        <input type="hidden" id="qr-data-<?php echo $session["id"]; ?>" value="<?php echo $session["qr_code_data"]; ?>">
                                    </div>

                                    <div class="mt-2">
                                        <p><small>Session Code: <strong><?php echo $session["qr_code_data"]; ?></strong></small></p>
                                    </div>

                                    <div class="mt-3">
                                        <a href="scan_attendance.php?session_id=<?php echo $session["id"]; ?>" class="btn btn-sm btn-success" <?php echo ($is_expired || $is_time_expired) ? 'disabled' : ''; ?>>Scan</a>
                                        <a href="view_attendance.php?id=<?php echo $session["id"]; ?>" class="btn btn-sm btn-info">View</a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteSession(<?php echo $session['id']; ?>, '<?php echo htmlspecialchars($session['class_name']); ?>', '<?php echo htmlspecialchars($session['session_date']); ?>')">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </form>
                <?php else: ?>
                <p>No attendance sessions found matching the selected criteria.</p>
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
    <?php endwhile; ?>
});

// Function to toggle all checkboxes
function toggleCheckboxes() {
    const checkboxes = document.querySelectorAll('.qr-checkbox');
    const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);

    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

// Function to confirm deletion of selected QR codes
function confirmDelete() {
    const checkboxes = document.querySelectorAll('.qr-checkbox:checked');

    if (checkboxes.length === 0) {
        alert('Please select at least one QR code to delete.');
        return false;
    }

    return confirm(`Are you sure you want to delete ${checkboxes.length} selected QR code(s)? This will also delete all attendance records for these sessions. This action cannot be undone.`);
}

// Function to delete a single session
function deleteSession(sessionId, className, sessionDate) {
    if (confirm(`Are you sure you want to delete the attendance session for ${className} on ${sessionDate}? This will also delete all attendance records for this session. This action cannot be undone.`)) {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;

        // Create hidden input for action
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_single';
        form.appendChild(actionInput);

        // Create hidden input for session ID
        const sessionIdInput = document.createElement('input');
        sessionIdInput.type = 'hidden';
        sessionIdInput.name = 'session_id';
        sessionIdInput.value = sessionId;
        form.appendChild(sessionIdInput);

        // Append form to body and submit
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Include footer
include_once "../includes/footer.php";
?>
