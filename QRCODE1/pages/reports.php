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
$class_id = $start_date = $end_date = "";
$error_msg = "";

// Process filter form
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["class_id"])){
        $class_id = $_POST["class_id"];
    }
    
    if(isset($_POST["start_date"]) && !empty($_POST["start_date"])){
        $start_date = $_POST["start_date"];
    }
    
    if(isset($_POST["end_date"]) && !empty($_POST["end_date"])){
        $end_date = $_POST["end_date"];
    }
}

// Get all classes for the dropdown
if($_SESSION["role"] == "admin"){
    $classes_sql = "SELECT * FROM classes ORDER BY name";
} else {
    $classes_sql = "SELECT * FROM classes WHERE teacher_id = " . $_SESSION["id"] . " ORDER BY name";
}
$classes_result = mysqli_query($conn, $classes_sql);

// Build the attendance sessions query based on filters
$sessions_sql = "SELECT a.*, c.name as class_name, 
                (SELECT COUNT(*) FROM attendance_records WHERE session_id = a.id) as attendance_count,
                (SELECT COUNT(*) FROM class_students WHERE class_id = a.class_id) as total_students
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

if(!empty($start_date)){
    $sessions_sql .= " AND a.session_date >= '" . $start_date . "'";
}

if(!empty($end_date)){
    $sessions_sql .= " AND a.session_date <= '" . $end_date . "'";
}

$sessions_sql .= " ORDER BY a.session_date DESC, a.session_time DESC";
$sessions_result = mysqli_query($conn, $sessions_sql);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Attendance Reports</h2>
        <p>View and analyze attendance data across different classes and time periods.</p>
        
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Filter Reports</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="row g-3">
                    <div class="col-md-4">
                        <label for="class_id" class="form-label">Class</label>
                        <select name="class_id" id="class_id" class="form-select">
                            <option value="">All Classes</option>
                            <?php while($class = mysqli_fetch_assoc($classes_result)): ?>
                            <option value="<?php echo $class["id"]; ?>" <?php echo ($class_id == $class["id"]) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class["name"]); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Attendance Sessions</h5>
                <?php if(mysqli_num_rows($sessions_result) > 0): ?>
                <a href="export_reports.php<?php echo (!empty($class_id) || !empty($start_date) || !empty($end_date)) ? '?' . http_build_query($_POST) : ''; ?>" class="btn btn-sm btn-success">Export to CSV</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($sessions_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Class</th>
                                <th>Attendance</th>
                                <th>Percentage</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($session = mysqli_fetch_assoc($sessions_result)): 
                                $attendance_percentage = ($session["total_students"] > 0) ? 
                                    round(($session["attendance_count"] / $session["total_students"]) * 100) : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($session["session_date"]); ?></td>
                                <td><?php echo htmlspecialchars($session["session_time"]); ?></td>
                                <td><?php echo htmlspecialchars($session["class_name"]); ?></td>
                                <td><?php echo $session["attendance_count"] . " / " . $session["total_students"]; ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?php echo ($attendance_percentage < 50) ? 'bg-danger' : (($attendance_percentage < 75) ? 'bg-warning' : 'bg-success'); ?>" 
                                             role="progressbar" style="width: <?php echo $attendance_percentage; ?>%"
                                             aria-valuenow="<?php echo $attendance_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $attendance_percentage; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="view_attendance.php?id=<?php echo $session["id"]; ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>No attendance sessions found matching the selected criteria.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
