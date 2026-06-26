<?php
// Debug options
// $prDebug: set to 1 for local testing, 0 for production
$prDebug = 0;
// for testing when debug=1
$prsch_name = '';
$pruid = '';
$prem1 = '';
$prem2 = '';

// Admin Usernames (CAS uid)
$prAdmin1 = '';
$prAdmin2 = '';

// DB credentials
$prDbhost = 'localhost';
$prDbname = 'school-programs';
$prDbusername = 'root';
$prDbpassword = '';

$prTable = 'progs';
$schTable = 'progs_schools';

function db_connect() {
    global $prDbhost, $prDbusername, $prDbpassword, $prDbname;
    // Temporarily turn off exception reporting for connection fallback
    $previous_reporting = mysqli_report(MYSQLI_REPORT_OFF);
    
    $conn = @new mysqli($prDbhost, $prDbusername, $prDbpassword, $prDbname);
    if ($conn && !$conn->connect_error) {
        $conn->set_charset("utf8");
        mysqli_report($previous_reporting);
        return $conn;
    }
    
    $conn = @new mysqli($prDbhost, $prDbusername, '', $prDbname);
    if ($conn && !$conn->connect_error) {
        $conn->set_charset("utf8");
        mysqli_report($previous_reporting);
        return $conn;
    }
    
    mysqli_report($previous_reporting);
    die("Database connection failed: Access denied or database does not exist.");
}
?>