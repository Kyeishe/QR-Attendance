# QR Attendance System - Code Structure

## Database Schema

```
+----------------+       +----------------+       +----------------+
|     users      |       |    students    |       |    classes     |
+----------------+       +----------------+       +----------------+
| id (PK)        |       | id (PK)        |       | id (PK)        |
| username       |       | student_id     |       | name           |
| password       |       | password       |       | teacher_id (FK)|
| role           |       +----------------+       +----------------+
+----------------+               |                       |
        |                        |                       |
        |                        |                       |
        v                        v                       v
+----------------+       +----------------+       +----------------+
|  class_students |       | attendance_    |       | attendance_   |
+----------------+       | sessions       |       | records       |
| id (PK)        |       +----------------+       +----------------+
| class_id (FK)  |<------| id (PK)        |       | id (PK)        |
| student_id (FK)|       | class_id (FK)  |------>| session_id (FK)|
+----------------+       | session_date   |       | student_id (FK)|
                         | session_time   |       | timestamp      |
                         | qr_code_data   |       +----------------+
                         +----------------+
```

## Application Flow

```
                  +----------------+
                  |    index.php   |
                  +----------------+
                          |
                          v
                  +----------------+
                  |    login.php   |
                  +----------------+
                          |
                +---------+---------+
                |                   |
                v                   v
        +----------------+  +----------------+
        | dashboard.php  |  | student_       |
        | (Admin/Teacher)|  | dashboard.php  |
        +----------------+  +----------------+
                |                   |
    +-----------+------------+     |
    |           |            |     |
    v           v            v     v
+--------+ +--------+ +--------+ +--------+
| Manage | | Manage | |Attendance| | Mark  |
| Users  | | Classes| |  QR     | |Attendance|
+--------+ +--------+ +--------+ +--------+
    |           |            |        |
    v           v            v        v
+--------+ +--------+ +--------+ +--------+
| Users  | | Class   | | QR Code | |Attendance|
| CRUD   | | CRUD    | | Generation| | Records |
+--------+ +--------+ +--------+ +--------+
                          |
                          v
                  +----------------+
                  |    Reports     |
                  +----------------+
```

## Key Code Components

### 1. Database Connection (config/database.php)
```php
<?php
// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'qr_attendance');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>
```

### 2. Authentication (pages/login.php)
```php
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
            }
        }
    } else {
        // Try student login
        $sql = "SELECT id, student_id, password FROM students WHERE student_id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    mysqli_stmt_bind_result($stmt, $id, $student_id, $student_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $student_password)){
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $student_id;
                            $_SESSION["role"] = "student";                            
                            
                            // Redirect student to their dashboard
                            header("location: student_dashboard.php");
                            exit;
                        }
                    }
                }
            }
        }
    }
}
```

### 3. QR Code Generation (pages/attendance.php)
```php
// Generate a unique QR code data
$qr_code_data = "QR_ATTENDANCE_" . time() . "_" . $class_id . "_" . rand(1000, 9999);

// Prepare an insert statement
$sql = "INSERT INTO attendance_sessions (class_id, session_date, session_time, qr_code_data) VALUES (?, ?, ?, ?)";
 
if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "isss", $param_class_id, $param_session_date, $param_session_time, $param_qr_code_data);
    
    // Set parameters
    $param_class_id = $class_id;
    $param_session_date = $session_date;
    $param_session_time = $session_time;
    $param_qr_code_data = $qr_code_data;
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        $session_id = mysqli_insert_id($conn);
        $success_msg = "Attendance session created successfully.";
    }
}
```

### 4. Student Marking Attendance (pages/student_mark_attendance.php)
```php
// Check if session code exists
$session_sql = "SELECT * FROM attendance_sessions WHERE qr_code_data = ?";
if($stmt = mysqli_prepare($conn, $session_sql)){
    mysqli_stmt_bind_param($stmt, "s", $session_code);
    mysqli_stmt_execute($stmt);
    $session_result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($session_result) > 0){
        $session_data = mysqli_fetch_assoc($session_result);
        $session_id = $session_data["id"];
        
        // Check if student is enrolled in the class
        $enrollment_sql = "SELECT * FROM class_students WHERE class_id = ? AND student_id = ?";
        $enrollment_stmt = mysqli_prepare($conn, $enrollment_sql);
        mysqli_stmt_bind_param($enrollment_stmt, "ii", $session_data["class_id"], $student_id);
        mysqli_stmt_execute($enrollment_stmt);
        
        if(mysqli_stmt_num_rows($enrollment_stmt) > 0){
            // Check if student is already marked present
            $check_sql = "SELECT * FROM attendance_records WHERE session_id = ? AND student_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ii", $session_id, $student_id);
            mysqli_stmt_execute($check_stmt);
            
            if(mysqli_stmt_num_rows($check_stmt) == 0){
                // Mark attendance
                $insert_sql = "INSERT INTO attendance_records (session_id, student_id) VALUES (?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "ii", $session_id, $student_id);
                
                if(mysqli_stmt_execute($insert_stmt)){
                    $success_msg = "Attendance marked successfully!";
                }
            }
        }
    }
}
```

### 5. QR Code Management (pages/qr_code_list.php)
```php
// Delete expired QR codes
if(isset($_POST["action"]) && $_POST["action"] == "delete_expired"){
    $today = date("Y-m-d");
    
    // First, get all expired session IDs
    $get_expired_sql = "SELECT id FROM attendance_sessions WHERE session_date < ?";
    $get_expired_stmt = mysqli_prepare($conn, $get_expired_sql);
    mysqli_stmt_bind_param($get_expired_stmt, "s", $today);
    mysqli_stmt_execute($get_expired_stmt);
    $expired_result = mysqli_stmt_get_result($get_expired_stmt);
    
    $expired_ids = [];
    while($row = mysqli_fetch_assoc($expired_result)) {
        $expired_ids[] = $row['id'];
    }
    
    if(!empty($expired_ids)) {
        // Delete associated attendance records first
        $ids_string = implode(",", $expired_ids);
        $delete_records_sql = "DELETE FROM attendance_records WHERE session_id IN ($ids_string)";
        
        if(mysqli_query($conn, $delete_records_sql)) {
            // Now delete the sessions
            $delete_sessions_sql = "DELETE FROM attendance_sessions WHERE id IN ($ids_string)";
            
            if(mysqli_query($conn, $delete_sessions_sql)) {
                $affected_rows = mysqli_affected_rows($conn);
                $success_msg = "Successfully deleted $affected_rows expired QR code(s).";
            }
        }
    }
}
```

### 6. Reports Generation (pages/reports.php)
```php
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
```
