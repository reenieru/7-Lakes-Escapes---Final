<?php
// Database configuration (merged from config.php)
$host     = 'localhost';
$dbname   = 'tourism_booking_system';
$db_user  = 'root';
$db_pass  = '';

// Create the MySQLi connection
$conn = mysqli_connect($host, $db_user, $db_pass, $dbname);
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
?>
