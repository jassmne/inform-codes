<?php
session_start();

// Disable error display (remove warnings)
error_reporting(0);
ini_set('display_errors', 0);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_enrollment');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: Please make sure XAMPP MySQL is running and the database 'college_enrollment' exists.");
}

// Set charset to utf8
$conn->set_charset("utf8");

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Check if user is staff
function isStaff() {
    return isLoggedIn() && $_SESSION['user_type'] === 'staff';
}

// Check if user is student
function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if not staff
function requireStaff() {
    requireLogin();
    if (!isStaff()) {
        header('Location: student_dashboard.php');
        exit();
    }
}

// Redirect if not student
function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: staff_dashboard.php');
        exit();
    }
}
?>