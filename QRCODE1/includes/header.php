<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <?php
    // Determine if we're in the pages directory or root for paths
    $isInPagesDir = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
    $rootPath = $isInPagesDir ? '../' : '';
    $pagesPath = $isInPagesDir ? '' : 'pages/';
    ?>
    <link rel="stylesheet" href="<?php echo $rootPath; ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $rootPath; ?>index.php">QR Attendance</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <?php if($_SESSION["role"] === "student"): ?>
                            <!-- Student Navigation -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>student_dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>student_mark_attendance.php">Mark Attendance</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>student_qr_codes.php">QR Codes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>logout.php">Logout</a>
                            </li>
                        <?php else: ?>
                            <!-- Admin/Teacher Navigation -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>dashboard.php">Dashboard</a>
                            </li>
                            <?php if($_SESSION["role"] === "admin"): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo $pagesPath; ?>manage_users.php">Manage Users</a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>manage_students.php">Manage Students</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>manage_classes.php">Manage Classes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>attendance.php">Attendance</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>qr_code_list.php">QR Codes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>reports.php">Reports</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $pagesPath; ?>logout.php">Logout</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $pagesPath; ?>login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $pagesPath; ?>register_student.php">Student Registration</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
