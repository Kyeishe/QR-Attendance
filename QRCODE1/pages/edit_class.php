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

// Check if id parameter is present
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: manage_classes.php");
    exit;
}

// Get class ID from URL
$id = trim($_GET["id"]);

// Verify class exists and user has permission
$class_sql = "SELECT * FROM classes WHERE id = ?";
if($stmt = mysqli_prepare($conn, $class_sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
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
    
    // Set class name
    $name = $class_data["name"];
    
    mysqli_stmt_close($stmt);
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate class name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter a class name.";
    } else{
        $name = trim($_POST["name"]);
    }
    
    // Check input errors before updating in database
    if(empty($name_err)){
        
        // Prepare an update statement
        $sql = "UPDATE classes SET name = ? WHERE id = ?";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_name, $param_id);
            
            // Set parameters
            $param_name = $name;
            $param_id = $id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Class updated successfully.";
            } else{
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Edit Class</h2>
        <p>Update class information.</p>
        
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
                <h5>Edit Class Information</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Class Name</label>
                        <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                        <div class="invalid-feedback"><?php echo $name_err; ?></div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Update Class</button>
                        <a href="manage_classes.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
