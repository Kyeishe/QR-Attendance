<?php
// Initialize the session
session_start();

// Include header
include_once "includes/header.php";
?>

<div class="row">
    <div class="col-md-12 text-center">
        <h1 class="display-4 mt-5">QR Code Attendance System</h1>
        <p class="lead">A simple and efficient way to track student attendance using QR codes</p>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <i class="fas fa-qrcode dashboard-icon text-primary"></i>
                <h5 class="card-title">Quick Attendance</h5>
                <p class="card-text">Generate QR codes for classes and let students scan to mark their attendance.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <i class="fas fa-chart-bar dashboard-icon text-success"></i>
                <h5 class="card-title">Detailed Reports</h5>
                <p class="card-text">Access comprehensive attendance reports and analytics for your classes.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <i class="fas fa-users dashboard-icon text-info"></i>
                <h5 class="card-title">Student Management</h5>
                <p class="card-text">Easily manage student information and class enrollments.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12 text-center">
        <?php if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
            <a href="pages/login.php" class="btn btn-primary">Login</a>
        <?php else: ?>
            <?php if($_SESSION["role"] === "student"): ?>
                <a href="pages/student_dashboard.php" class="btn btn-primary">Go to Student Dashboard</a>
            <?php else: ?>
                <a href="pages/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once "includes/footer.php";
?>
