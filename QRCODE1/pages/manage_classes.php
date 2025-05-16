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

// Define variables and initialize with empty values
$name = "";
$name_err = "";
$success_msg = $error_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"]) && $_POST["action"] == "add"){
        // Validate class name
        if(empty(trim($_POST["name"]))){
            $name_err = "Please enter a class name.";
        } else{
            $name = trim($_POST["name"]);
        }

        // Check input errors before inserting in database
        if(empty($name_err)){
            // Prepare an insert statement
            $sql = "INSERT INTO classes (name, teacher_id) VALUES (?, ?)";

            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "si", $param_name, $param_teacher_id);

                // Set parameters
                $param_name = $name;
                $param_teacher_id = $_SESSION["id"];

                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Class added successfully.";
                    // Clear form fields
                    $name = "";
                } else{
                    $error_msg = "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
    } elseif(isset($_POST["action"]) && $_POST["action"] == "delete" && isset($_POST["id"])){
        // Delete class
        $id = $_POST["id"];

        // First check if user has permission to delete this class
        if($_SESSION["role"] != "admin"){
            $check_sql = "SELECT * FROM classes WHERE id = ? AND teacher_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ii", $id, $_SESSION["id"]);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if(mysqli_stmt_num_rows($check_stmt) == 0){
                $error_msg = "You don't have permission to delete this class.";
                mysqli_stmt_close($check_stmt);
                // Include header and show error
                include_once "../includes/header.php";
                echo '<div class="alert alert-danger">' . $error_msg . '</div>';
                include_once "../includes/footer.php";
                exit;
            }
            mysqli_stmt_close($check_stmt);
        }

        // First delete from class_students table
        $sql = "DELETE FROM class_students WHERE class_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Then delete from attendance_records related to this class's sessions
        $sql = "DELETE ar FROM attendance_records ar
                JOIN attendance_sessions a ON ar.session_id = a.id
                WHERE a.class_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Delete from attendance_sessions table
        $sql = "DELETE FROM attendance_sessions WHERE class_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Finally delete from classes table
        $sql = "DELETE FROM classes WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $id);
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Class deleted successfully.";
            } else {
                $error_msg = "Error deleting class.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all classes
if($_SESSION["role"] == "admin"){
    $sql = "SELECT c.*, u.username as teacher_name FROM classes c
            JOIN users u ON c.teacher_id = u.id
            ORDER BY c.name";
} else {
    $sql = "SELECT c.*, u.username as teacher_name FROM classes c
            JOIN users u ON c.teacher_id = u.id
            WHERE c.teacher_id = " . $_SESSION["id"] . "
            ORDER BY c.name";
}
$classes = mysqli_query($conn, $sql);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Manage Classes</h2>
        <p>Add, edit, or remove classes from the system.</p>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Add New Class</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Class Name</label>
                        <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                        <div class="invalid-feedback"><?php echo $name_err; ?></div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Add Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Class List</h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($classes) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <?php if($_SESSION["role"] == "admin"): ?>
                                <th>Teacher</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($classes)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["name"]); ?></td>
                                <?php if($_SESSION["role"] == "admin"): ?>
                                <td><?php echo htmlspecialchars($row["teacher_name"]); ?></td>
                                <?php endif; ?>
                                <td>
                                    <a href="manage_class_students.php?id=<?php echo $row["id"]; ?>" class="btn btn-sm btn-info">Students</a>
                                    <a href="edit_class.php?id=<?php echo $row["id"]; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this class?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row["id"]; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>No classes found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
