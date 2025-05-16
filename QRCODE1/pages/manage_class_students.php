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
$class_id = "";
$success_msg = $error_msg = "";

// Check if class_id is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: manage_classes.php");
    exit;
}

$class_id = $_GET["id"];

// Verify class exists and user has permission
$class_sql = "SELECT * FROM classes WHERE id = ?";
if($stmt = mysqli_prepare($conn, $class_sql)){
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    $class_result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($class_result) == 0){
        // Class not found
        header("location: manage_classes.php");
        exit;
    }
    
    $class_data = mysqli_fetch_assoc($class_result);
    
    // Check if user has permission
    if($_SESSION["role"] != "admin" && $class_data["teacher_id"] != $_SESSION["id"]){
        // User doesn't have permission
        header("location: manage_classes.php");
        exit;
    }
    
    mysqli_stmt_close($stmt);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"])){
        if($_POST["action"] == "add" && isset($_POST["student_id"])){
            // Add student to class
            $student_id = $_POST["student_id"];
            
            // Check if student is already in the class
            $check_sql = "SELECT * FROM class_students WHERE class_id = ? AND student_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ii", $class_id, $student_id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if(mysqli_stmt_num_rows($check_stmt) > 0){
                $error_msg = "Student is already in this class.";
            } else {
                // Add student to class
                $insert_sql = "INSERT INTO class_students (class_id, student_id) VALUES (?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "ii", $class_id, $student_id);
                
                if(mysqli_stmt_execute($insert_stmt)){
                    $success_msg = "Student added to class successfully.";
                } else {
                    $error_msg = "Error adding student to class.";
                }
                
                mysqli_stmt_close($insert_stmt);
            }
            
            mysqli_stmt_close($check_stmt);
        } elseif($_POST["action"] == "remove" && isset($_POST["student_id"])){
            // Remove student from class
            $student_id = $_POST["student_id"];
            
            // Remove student from class
            $delete_sql = "DELETE FROM class_students WHERE class_id = ? AND student_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "ii", $class_id, $student_id);
            
            if(mysqli_stmt_execute($delete_stmt)){
                $success_msg = "Student removed from class successfully.";
            } else {
                $error_msg = "Error removing student from class.";
            }
            
            mysqli_stmt_close($delete_stmt);
        }
    }
}

// Get all students
$all_students_sql = "SELECT * FROM students ORDER BY name";
$all_students_result = mysqli_query($conn, $all_students_sql);

// Get students in this class
$class_students_sql = "SELECT s.* FROM students s 
                      JOIN class_students cs ON s.id = cs.student_id 
                      WHERE cs.class_id = ? 
                      ORDER BY s.name";
$class_students_stmt = mysqli_prepare($conn, $class_students_sql);
mysqli_stmt_bind_param($class_students_stmt, "i", $class_id);
mysqli_stmt_execute($class_students_stmt);
$class_students_result = mysqli_stmt_get_result($class_students_stmt);

// Create an array of student IDs in the class
$class_student_ids = array();
while($student = mysqli_fetch_assoc($class_students_result)){
    $class_student_ids[] = $student["id"];
}
// Reset the result pointer
mysqli_data_seek($class_students_result, 0);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Manage Students in <?php echo htmlspecialchars($class_data["name"]); ?></h2>
        <p>Add or remove students from this class.</p>
        
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
                <h5>Add Students to Class</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $class_id); ?>" method="post" class="mb-3">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Select Student</label>
                        <select name="student_id" id="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php 
                            // Reset the all students result pointer
                            mysqli_data_seek($all_students_result, 0);
                            
                            while($student = mysqli_fetch_assoc($all_students_result)): 
                                // Skip students already in the class
                                if(in_array($student["id"], $class_student_ids)) continue;
                            ?>
                            <option value="<?php echo $student["id"]; ?>">
                                <?php echo htmlspecialchars($student["name"] . " (" . $student["student_id"] . ")"); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Add to Class</button>
                    </div>
                </form>
                
                <div class="mt-4">
                    <a href="manage_students.php" class="btn btn-outline-primary">Manage All Students</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Students in Class</h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($class_students_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($student = mysqli_fetch_assoc($class_students_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student["student_id"]); ?></td>
                                <td><?php echo htmlspecialchars($student["name"]); ?></td>
                                <td>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $class_id); ?>" method="post" onsubmit="return confirm('Are you sure you want to remove this student from the class?');">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="student_id" value="<?php echo $student["id"]; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>No students in this class yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <a href="manage_classes.php" class="btn btn-secondary">Back to Classes</a>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
