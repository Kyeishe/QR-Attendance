<?php
// Set the default timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

// Function to get the current datetime in Philippines timezone
function get_philippines_datetime() {
    return date('Y-m-d H:i:s');
}

// Function to get the current date in Philippines timezone
function get_philippines_date() {
    return date('Y-m-d');
}

// Function to get the current time in Philippines timezone
function get_philippines_time() {
    return date('H:i:s');
}

// Function to check if a session is expired based on Philippines timezone
function is_session_expired($session_date, $session_time) {
    // Ensure time is in HH:MM:SS format
    if(preg_match('/^\d{2}:\d{2}$/', $session_time)) {
        $session_time = $session_time . ":00";
    }
    
    $session_datetime = $session_date . " " . $session_time;
    $session_timestamp = strtotime($session_datetime);
    $current_timestamp = time();
    
    return $session_timestamp < $current_timestamp;
}
?>
