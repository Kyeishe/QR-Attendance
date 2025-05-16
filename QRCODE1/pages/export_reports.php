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
$class_id = $start_date = $end_date = "";

// Get filter parameters
if(isset($_GET["class_id"]) && !empty($_GET["class_id"])){
    $class_id = $_GET["class_id"];
}

if(isset($_GET["start_date"]) && !empty($_GET["start_date"])){
    $start_date = $_GET["start_date"];
}

if(isset($_GET["end_date"]) && !empty($_GET["end_date"])){
    $end_date = $_GET["end_date"];
}

// Build the attendance sessions query based on filters
$sessions_sql = "SELECT a.*, c.name as class_name, 
                (SELECT COUNT(*) FROM attendance_records WHERE session_id = a.id) as attendance_count,
                (SELECT COUNT(*) FROM class_students WHERE class_id = a.class_id) as total_students
                FROM attendance_sessions a
                JOIN classes c ON a.class_id = c.id
                WHERE 1=1";

// Add filter conditions
if($_SESSION["role"] != "admin"){
    $sessions_sql .= " AND c.teacher_id = " . $_SESSION["id"];
}

if(!empty($class_id)){
    $sessions_sql .= " AND a.class_id = " . $class_id;
}

if(!empty($start_date)){
    $sessions_sql .= " AND a.session_date >= '" . $start_date . "'";
}

if(!empty($end_date)){
    $sessions_sql .= " AND a.session_date <= '" . $end_date . "'";
}

$sessions_sql .= " ORDER BY a.session_date DESC, a.session_time DESC";
$sessions_result = mysqli_query($conn, $sessions_sql);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, array('Date', 'Time', 'Class', 'Present', 'Total', 'Percentage'));

// Output each row of the data
while($session = mysqli_fetch_assoc($sessions_result)){
    $attendance_percentage = ($session["total_students"] > 0) ? 
        round(($session["attendance_count"] / $session["total_students"]) * 100) : 0;
    
    fputcsv($output, array(
        $session["session_date"],
        $session["session_time"],
        $session["class_name"],
        $session["attendance_count"],
        $session["total_students"],
        $attendance_percentage . '%'
    ));
}

// Close the file pointer
fclose($output);
exit;
?>
