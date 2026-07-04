<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$conn = connectDB();

// Get file type and ID
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($type) || $id <= 0) {
    header('Location: index.php');
    exit();
}

// Get file information
if ($type == 'book') {
    $table = 'library_books';
} elseif ($type == 'exam') {
    $table = 'library_exams';
} else {
    header('Location: index.php');
    exit();
}

$sql = "SELECT * FROM $table WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: index.php');
    exit();
}

$file = $result->fetch_assoc();

// Check if user has access to this file (registered for the course)
if ($user_role == 'student') {
    $access_sql = "SELECT 1 FROM student_courses 
                  WHERE student_id = $user_id 
                  AND course_id = " . $file['course_id'];
} else {
    $access_sql = "SELECT 1 FROM professor_courses 
                  WHERE professor_id = $user_id 
                  AND course_id = " . $file['course_id'];
}

$access_result = $conn->query($access_sql);

if ($access_result->num_rows == 0) {
    header('Location: index.php');
    exit();
}

// Get file path
$file_path = '../' . $file['file_path'];

if (!file_exists($file_path)) {
    die('File does not exist on server');
}

// Get file extension to check if it's a PDF
$file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

// If PDF, display in iframe, otherwise prompt download
if (strtolower($file_extension) == 'pdf') {
    // Display PDF in browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $file['title'] . '.pdf"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    // Redirect to download for non-PDF files
    header('Location: download.php?type=' . $type . '&id=' . $id);
    exit;
}
?>
