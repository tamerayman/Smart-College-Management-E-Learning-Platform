<?php
require_once 'auth.php';
require_once 'db_functions.php';

// Check user authorization
checkAdminAuth();

// Get course ID from query parameter
if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];
    
    // Get enrolled students
    $students = getEnrolledStudents($course_id);
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($students);
} else {
    // Return empty array if no course ID provided
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
