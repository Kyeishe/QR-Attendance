<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'qr_attendance');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    // Select the database
    mysqli_select_db($conn, DB_NAME);

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'teacher') NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);

    // Create students table
    $sql = "CREATE TABLE IF NOT EXISTS students (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        student_id VARCHAR(20) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);

    // Create classes table
    $sql = "CREATE TABLE IF NOT EXISTS classes (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        teacher_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(id)
    )";
    mysqli_query($conn, $sql);

    // Create class_students table (for many-to-many relationship)
    $sql = "CREATE TABLE IF NOT EXISTS class_students (
        class_id INT NOT NULL,
        student_id INT NOT NULL,
        PRIMARY KEY (class_id, student_id),
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (student_id) REFERENCES students(id)
    )";
    mysqli_query($conn, $sql);

    // Create attendance_sessions table
    $sql = "CREATE TABLE IF NOT EXISTS attendance_sessions (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        class_id INT NOT NULL,
        session_date DATE NOT NULL,
        session_time TIME NOT NULL,
        qr_code_data VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id)
    )";
    mysqli_query($conn, $sql);

    // Create attendance_records table
    $sql = "CREATE TABLE IF NOT EXISTS attendance_records (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        session_id INT NOT NULL,
        student_id INT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (session_id) REFERENCES attendance_sessions(id),
        FOREIGN KEY (student_id) REFERENCES students(id)
    )";
    mysqli_query($conn, $sql);

    // Check if admin account exists, if not create it
    $check_admin_sql = "SELECT * FROM users WHERE username = 'admin'";
    $admin_result = mysqli_query($conn, $check_admin_sql);

    if (mysqli_num_rows($admin_result) == 0) {
        // Admin account doesn't exist, create it
        $admin_password = password_hash('joshua123', PASSWORD_DEFAULT);
        $create_admin_sql = "INSERT INTO users (username, password, role) VALUES ('admin', '$admin_password', 'admin')";
        mysqli_query($conn, $create_admin_sql);
    }

} else {
    echo "Error creating database: " . mysqli_error($conn);
}
?>
