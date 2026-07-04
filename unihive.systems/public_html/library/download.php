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
    die('Invalid request');
}

// Get file information
if ($type == 'book') {
    $table = 'library_books';
} elseif ($type == 'exam') {
    $table = 'library_exams';
} else {
    die('Invalid file type');
}

$sql = "SELECT * FROM $table WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die('File not found');
}

$file = $result->fetch_assoc();

// Check if user has access to this file
if ($user_role == 'student') {
    // Check if student is registered in the course
    $access_sql = "SELECT 1 FROM student_courses 
                  WHERE student_id = $user_id 
                  AND course_id = " . $file['course_id'];
    $access_result = $conn->query($access_sql);
    
    if ($access_result->num_rows == 0) {
        die('You do not have access to this file');
    }
}

// Get file path
$file_path = '../' . $file['file_path'];

if (!file_exists($file_path)) {
    die('File does not exist on server');
}

// Set headers and serve file
$file_name = basename($file_path);
$file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

// Set the appropriate content type
switch ($file_extension) {
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'doc':
        $content_type = 'application/msword';
        break;
    case 'docx':
        $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    default:
        $content_type = 'application/octet-stream';
}

header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $file['title'] . '.' . $file_extension . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($file_path);
exit;
?>
