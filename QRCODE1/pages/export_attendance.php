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
$session_id = "";

// Check if session_id is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: dashboard.php");
    exit;
}

$session_id = $_GET["id"];

// Verify session exists and user has permission
$session_sql = "SELECT a.*, c.name as class_name, c.teacher_id
                FROM attendance_sessions a
                JOIN classes c ON a.class_id = c.id
                WHERE a.id = ?";
if($stmt = mysqli_prepare($conn, $session_sql)){
    mysqli_stmt_bind_param($stmt, "i", $session_id);
    mysqli_stmt_execute($stmt);
    $session_result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($session_result) == 0){
        // Session not found
        header("location: dashboard.php");
        exit;
    }

    $session_data = mysqli_fetch_assoc($session_result);

    // Check if user has permission
    if($_SESSION["role"] != "admin" && $session_data["teacher_id"] != $_SESSION["id"]){
        // User doesn't have permission
        header("location: dashboard.php");
        exit;
    }

    mysqli_stmt_close($stmt);
}

// Get all students in this class
$students_sql = "SELECT s.* FROM students s
                JOIN class_students cs ON s.id = cs.student_id
                WHERE cs.class_id = ?
                ORDER BY s.student_id";
$students_stmt = mysqli_prepare($conn, $students_sql);
mysqli_stmt_bind_param($students_stmt, "i", $session_data["class_id"]);
mysqli_stmt_execute($students_stmt);
$students_result = mysqli_stmt_get_result($students_stmt);

// Get attendance records for this session
$attendance_sql = "SELECT ar.*, s.student_id as student_code
                  FROM attendance_records ar
                  JOIN students s ON ar.student_id = s.id
                  WHERE ar.session_id = ?
                  ORDER BY ar.timestamp";
$attendance_stmt = mysqli_prepare($conn, $attendance_sql);
mysqli_stmt_bind_param($attendance_stmt, "i", $session_id);
mysqli_stmt_execute($attendance_stmt);
$attendance_result = mysqli_stmt_get_result($attendance_stmt);

// Create an array of student IDs who attended
$attended_students = array();
$attendance_times = array();
while($row = mysqli_fetch_assoc($attendance_result)){
    $attended_students[] = $row["student_id"];
    $attendance_times[$row["student_id"]] = $row["timestamp"];
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_' . $session_data["class_name"] . '_' . $session_data["session_date"] . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, array('Student ID', 'Name', 'Status', 'Time'));

// Output each row of the data
while($student = mysqli_fetch_assoc($students_result)){
    $is_present = in_array($student["id"], $attended_students);
    $attendance_time = $is_present ? date("h:i A", strtotime($attendance_times[$student["id"]])) : "";

    fputcsv($output, array(
        $student["student_id"],
        $student["student_id"], // Use student_id as name since name column was removed
        $is_present ? "Present" : "Absent",
        $attendance_time
    ));
}

// Close the file pointer
fclose($output);
exit;
?>
