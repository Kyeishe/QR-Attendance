<?php
// Include database connection
require_once "../config/database.php";

// Define variables
$session_id = $student_id = "";
$success_msg = $error_msg = "";

// Check if session_id is provided
if(!isset($_GET["session_id"]) || empty($_GET["session_id"])){
    header("location: ../index.php");
    exit;
}

$session_id = $_GET["session_id"];

// Verify session exists
$session_sql = "SELECT a.*, c.name as class_name
                FROM attendance_sessions a
                JOIN classes c ON a.class_id = c.id
                WHERE a.id = ?";
if($stmt = mysqli_prepare($conn, $session_sql)){
    mysqli_stmt_bind_param($stmt, "i", $session_id);
    mysqli_stmt_execute($stmt);
    $session_result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($session_result) == 0){
        // Session not found
        header("location: ../index.php");
        exit;
    }

    $session_data = mysqli_fetch_assoc($session_result);
    mysqli_stmt_close($stmt);
}

// Process form submission
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
                    $error_msg = "You have already marked your attendance for this session.";
                } else {
                    // Mark attendance
                    $insert_sql = "INSERT INTO attendance_records (session_id, student_id) VALUES (?, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($insert_stmt, "ii", $session_id, $student_db_id);

                    if(mysqli_stmt_execute($insert_stmt)){
                        $success_msg = "Attendance marked successfully. Thank you, Student ID: " . $student_id_val . "!";
                    } else {
                        $error_msg = "Error marking attendance.";
                    }

                    mysqli_stmt_close($insert_stmt);
                }

                mysqli_stmt_close($check_stmt);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Simple header for student page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">QR Attendance System</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Mark Your Attendance</h5>
                    </div>
                    <div class="card-body">
                        <h6>Class: <?php echo htmlspecialchars($session_data["class_name"]); ?></h6>
                        <p>Date: <?php echo htmlspecialchars($session_data["session_date"]); ?></p>
                        <p>Time: <?php echo htmlspecialchars($session_data["session_time"]); ?></p>

                        <?php if(!empty($success_msg)): ?>
                            <div class="alert alert-success"><?php echo $success_msg; ?></div>
                        <?php endif; ?>

                        <?php if(!empty($error_msg)): ?>
                            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?session_id=" . $session_id); ?>" method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Enter Your Student ID</label>
                                <input type="text" name="student_id" id="student_id" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Mark Attendance</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
