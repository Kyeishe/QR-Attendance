<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "student"){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Define variables
$error_msg = $success_msg = "";
$student_id = $_SESSION["id"];
$search_query = "";

// Process search form
if(isset($_GET["search"])) {
    $search_query = trim($_GET["search"]);
}

// Process enrollment form
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["enroll"]) && !empty($_POST["class_id"])){
        $class_id = $_POST["class_id"];

        // Check if student is already enrolled in this class
        $check_sql = "SELECT * FROM class_students WHERE class_id = ? AND student_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $class_id, $student_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if(mysqli_stmt_num_rows($check_stmt) > 0){
            $error_msg = "You are already enrolled in this class.";
        } else {
            // Enroll student in the class
            $enroll_sql = "INSERT INTO class_students (class_id, student_id) VALUES (?, ?)";
            $enroll_stmt = mysqli_prepare($conn, $enroll_sql);
            mysqli_stmt_bind_param($enroll_stmt, "ii", $class_id, $student_id);

            if(mysqli_stmt_execute($enroll_stmt)){
                // Get class name for success message
                $class_name_sql = "SELECT name FROM classes WHERE id = ?";
                $class_name_stmt = mysqli_prepare($conn, $class_name_sql);
                mysqli_stmt_bind_param($class_name_stmt, "i", $class_id);
                mysqli_stmt_execute($class_name_stmt);
                $class_name_result = mysqli_stmt_get_result($class_name_stmt);
                $class_name = mysqli_fetch_assoc($class_name_result)["name"];

                $success_msg = "Successfully enrolled in " . htmlspecialchars($class_name) . ".";
                mysqli_stmt_close($class_name_stmt);
            } else {
                $error_msg = "Error enrolling in class: " . mysqli_error($conn);
            }

            mysqli_stmt_close($enroll_stmt);
        }

        mysqli_stmt_close($check_stmt);
    }
}

// Get all available classes
$classes_sql = "SELECT c.*, u.username as teacher_name
                FROM classes c
                JOIN users u ON c.teacher_id = u.id
                WHERE c.id NOT IN (
                    SELECT class_id FROM class_students WHERE student_id = ?
                )";

// Add search condition if search query is provided
if(!empty($search_query)) {
    $classes_sql .= " AND (c.name LIKE ? OR u.username LIKE ?)";
}

$classes_sql .= " ORDER BY c.name";
$classes_stmt = mysqli_prepare($conn, $classes_sql);

// Bind parameters
if(!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    mysqli_stmt_bind_param($classes_stmt, "iss", $student_id, $search_param, $search_param);
} else {
    mysqli_stmt_bind_param($classes_stmt, "i", $student_id);
}
mysqli_stmt_execute($classes_stmt);
$classes_result = mysqli_stmt_get_result($classes_stmt);

// Get classes the student is already enrolled in
$enrolled_sql = "SELECT c.*, u.username as teacher_name
                FROM classes c
                JOIN users u ON c.teacher_id = u.id
                JOIN class_students cs ON c.id = cs.class_id
                WHERE cs.student_id = ?
                ORDER BY c.name";
$enrolled_stmt = mysqli_prepare($conn, $enrolled_sql);
mysqli_stmt_bind_param($enrolled_stmt, "i", $student_id);
mysqli_stmt_execute($enrolled_stmt);
$enrolled_result = mysqli_stmt_get_result($enrolled_stmt);

// Include header
include_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-12">
        <h2>Enroll in Classes</h2>
        <p>Browse available classes and enroll in them.</p>

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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Available Classes</h5>
                <span class="badge bg-primary"><?php echo mysqli_num_rows($classes_result); ?> classes</span>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by class or teacher name" value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if(!empty($search_query)): ?>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if(mysqli_num_rows($classes_result) > 0): ?>
                <div class="list-group">
                    <?php while($class = mysqli_fetch_assoc($classes_result)): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($class["name"]); ?></h6>
                            <small>Teacher: <?php echo htmlspecialchars($class["teacher_name"]); ?></small>
                        </div>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="class_id" value="<?php echo $class["id"]; ?>">
                            <button type="submit" name="enroll" class="btn btn-sm btn-primary">Enroll</button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                    <?php if(!empty($search_query)): ?>
                    <div class="alert alert-info">
                        <p>No classes found matching "<strong><?php echo htmlspecialchars($search_query); ?></strong>".</p>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-sm btn-secondary mt-2">Show All Classes</a>
                    </div>
                    <?php else: ?>
                    <p>No available classes found. You are enrolled in all existing classes.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Your Enrolled Classes</h5>
                <span class="badge bg-success"><?php echo mysqli_num_rows($enrolled_result); ?> classes</span>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($enrolled_result) > 0): ?>
                <div class="list-group">
                    <?php while($class = mysqli_fetch_assoc($enrolled_result)): ?>
                    <div class="list-group-item">
                        <h6 class="mb-1"><?php echo htmlspecialchars($class["name"]); ?></h6>
                        <small>Teacher: <?php echo htmlspecialchars($class["teacher_name"]); ?></small>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p>You are not enrolled in any classes yet.</p>
                <?php endif; ?>
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

<script>
// Auto-focus the search input when the page loads
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput && searchInput.value) {
        // If there's a search query, highlight it
        searchInput.select();
    }

    // Add event listener for Enter key in search input
    searchInput.addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchInput.closest('form').submit();
        }
    });
});
</script>
