<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if the user is an admin, if not then redirect to dashboard
if($_SESSION["role"] !== "admin"){
    header("location: dashboard.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";
$success_msg = $error_msg = "";
$change_password = false;

// Check if id parameter is present
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: manage_users.php");
    exit;
}

// Get user ID from URL
$id = trim($_GET["id"]);

// Check if user is trying to edit themselves
if($id == $_SESSION["id"]){
    $error_msg = "You cannot edit your own account from this page.";
}

// Prepare a select statement
$sql = "SELECT * FROM users WHERE id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            // Fetch result row as an associative array
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            
            // Retrieve individual field value
            $username = $row["username"];
            $current_role = $row["role"];
        } else{
            // No valid ID parameter, redirect to manage users page
            header("location: manage_users.php");
            exit;
        }
        
    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_username, $id);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Check if password should be updated
    if(isset($_POST["change_password"]) && $_POST["change_password"] == "1"){
        $change_password = true;
        
        // Validate password
        if(empty(trim($_POST["password"]))){
            $password_err = "Please enter a password.";     
        } elseif(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password must have at least 6 characters.";
        } else{
            $password = trim($_POST["password"]);
        }
    }
    
    // Check input errors before updating in database
    if(empty($username_err) && ($change_password == false || empty($password_err))){
        
        if($change_password){
            // Prepare an update statement with password
            $sql = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "sssi", $param_username, $param_password, $param_role, $param_id);
                
                // Set parameters
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_role = $_POST["role"];
                $param_id = $id;
            }
        } else {
            // Prepare an update statement without password
            $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ssi", $param_username, $param_role, $param_id);
                
                // Set parameters
                $param_username = $username;
                $param_role = $_POST["role"];
                $param_id = $id;
            }
        }
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            $success_msg = "User updated successfully.";
        } else{
            $error_msg = "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
    }
    
    // Close connection
    mysqli_close($conn);
}

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Edit User</h2>
        <p>Update user information.</p>
        
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
                <h5>Edit User Information</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select name="role" id="role" class="form-select">
                            <option value="admin" <?php echo ($current_role == "admin") ? "selected" : ""; ?>>Admin</option>
                            <option value="teacher" <?php echo ($current_role == "teacher") ? "selected" : ""; ?>>Teacher</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="change_password" name="change_password" value="1" onchange="togglePasswordField()">
                        <label class="form-check-label" for="change_password">Change Password</label>
                    </div>
                    <div class="mb-3" id="password_field" style="display: none;">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                        <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                    
                    <script>
                        function togglePasswordField() {
                            var passwordField = document.getElementById('password_field');
                            var checkbox = document.getElementById('change_password');
                            
                            if (checkbox.checked) {
                                passwordField.style.display = 'block';
                            } else {
                                passwordField.style.display = 'none';
                            }
                        }
                    </script>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
