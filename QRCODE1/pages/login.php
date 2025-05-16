<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if($_SESSION["role"] === "student") {
        header("location: student_dashboard.php");
    } else {
        header("location: dashboard.php");
    }
    exit;
}

// Include database connection
require_once "../config/database.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username/student_id is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username or Student ID.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Check if it's the admin account
        if($username === 'admin') {
            // Get the admin account from the database
            $sql = "SELECT * FROM users WHERE username = 'admin'";
            $result = mysqli_query($conn, $sql);

            if(mysqli_num_rows($result) > 0) {
                $admin = mysqli_fetch_assoc($result);

                // Verify password
                if(password_verify($password, $admin['password'])) {
                    // Password is correct, so start a new session
                    session_start();

                    // Store data in session variables
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $admin['id'];
                    $_SESSION["username"] = $admin['username'];
                    $_SESSION["role"] = $admin['role'];

                    // Redirect to dashboard
                    header("location: dashboard.php");
                    exit;
                } else {
                    $login_err = "Invalid password for admin account.";
                }
            } else {
                $login_err = "Admin account not found.";
            }
        } else {
            // Try student login
            $sql = "SELECT id, student_id, password FROM students WHERE student_id = ?";

            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "s", $param_student_id);

                // Set parameters
                $param_student_id = $username; // Using username field for student_id

                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Store result
                    mysqli_stmt_store_result($stmt);

                    // Check if student_id exists
                    if(mysqli_stmt_num_rows($stmt) == 1){
                        // Bind result variables
                        mysqli_stmt_bind_result($stmt, $id, $student_id, $student_password);
                        if(mysqli_stmt_fetch($stmt)){
                            // Check if password matches
                            if(password_verify($password, $student_password)){
                                // Password is correct, so start a new session
                                session_start();

                                // Store data in session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["username"] = $student_id;
                                $_SESSION["role"] = "student";

                                // Redirect student to their attendance page
                                header("location: student_dashboard.php");
                                exit;
                            } else {
                                $login_err = "Invalid password for student account.";
                            }
                        }
                    } else {
                        $login_err = "Student ID not found.";
                    }

                    // Close statement
                    mysqli_stmt_close($stmt);
                }
            }
        }

        // If we get here, login failed
        if(empty($login_err)) {
            $login_err = "Invalid username/student ID or password.";
        }
    }

    // Close connection
    mysqli_close($conn);
}

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card login-form">
            <div class="card-header">
                <h2 class="text-center">Login</h2>
            </div>
            <div class="card-body">
                <?php
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }
                ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username / Student ID</label>
                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" placeholder="Enter username or student ID">
                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </div>
                </form>

                <div class="mt-3 text-center">
                    <p>Don't have a student account? <a href="register_student.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once "../includes/footer.php";
?>
