<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// Check if user is logged in and is a professor
if (!isLoggedIn() || $_SESSION['role'] != 'professor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = connectDB();
$response = ['success' => false, 'message' => ''];

// Check upload type
$upload_type = $_POST['upload_type'] ?? '';

if ($upload_type == 'book') {
    processBookUpload($conn, $user_id, $response);
} elseif ($upload_type == 'exam') {
    processExamUpload($conn, $user_id, $response);
} else {
    $response['message'] = 'Invalid upload type';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();

// Function to process book upload
function processBookUpload($conn, $user_id, &$response) {
    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $course_id = (int)($_POST['course_id'] ?? 0);
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    
    // Validate inputs
    if (empty($title) || $course_id <= 0) {
        $response['message'] = 'Incomplete data provided';
        return;
    }
    
    // Validate course belongs to professor
    $check_sql = "SELECT 1 FROM professor_courses WHERE professor_id = $user_id AND course_id = $course_id";
    $check_result = $conn->query($check_sql);
    if ($check_result->num_rows == 0) {
        $response['message'] = 'You do not have permission to upload files for this course';
        return;
    }
    
    // Validate file was uploaded
    if (!isset($_FILES['book_file']) || $_FILES['book_file']['error'] != 0) {
        $response['message'] = 'File upload error occurred';
        return;
    }
    
    // Process file upload
    $upload_dir = '../uploads/books/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $response['message'] = 'Failed to create upload directory';
            return;
        }
        chmod($upload_dir, 0777);
    }
    
    // Validate file type
    $file_extension = pathinfo($_FILES['book_file']['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['pdf', 'doc', 'docx'];
    
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        $response['message'] = 'Unsupported file type. Allowed types: PDF, DOC, DOCX';
        return;
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['book_file']['tmp_name'], $file_path)) {
        // Save to database
        $relative_path = 'uploads/books/' . $new_filename;
        $sql = "INSERT INTO library_books (title, course_id, uploaded_by, file_path, description) 
                VALUES ('$title', $course_id, $user_id, '$relative_path', '$description')";
        
        if ($conn->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Book uploaded successfully';
        } else {
            $response['message'] = 'Error saving file data: ' . $conn->error;
            unlink($file_path); // Delete the uploaded file
        }
    } else {
        $response['message'] = 'Failed to move uploaded file';
    }
}

// Function to process exam upload
function processExamUpload($conn, $user_id, &$response) {
    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $course_id = (int)($_POST['course_id'] ?? 0);
    $exam_type = $conn->real_escape_string($_POST['exam_type'] ?? '');
    $exam_year = (int)($_POST['exam_year'] ?? 0);
    
    // Validate inputs
    if (empty($title) || $course_id <= 0 || empty($exam_type) || $exam_year <= 0) {
        $response['message'] = 'Incomplete data provided';
        return;
    }
    
    // Validate course belongs to professor
    $check_sql = "SELECT 1 FROM professor_courses WHERE professor_id = $user_id AND course_id = $course_id";
    $check_result = $conn->query($check_sql);
    if ($check_result->num_rows == 0) {
        $response['message'] = 'You do not have permission to upload files for this course';
        return;
    }
    
    // Validate file was uploaded
    if (!isset($_FILES['exam_file']) || $_FILES['exam_file']['error'] != 0) {
        $response['message'] = 'File upload error occurred';
        return;
    }
    
    // Process file upload
    $upload_dir = '../uploads/exams/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $response['message'] = 'Failed to create upload directory';
            return;
        }
        chmod($upload_dir, 0777);
    }
    
    // Validate file type
    $file_extension = pathinfo($_FILES['exam_file']['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['pdf', 'doc', 'docx'];
    
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        $response['message'] = 'Unsupported file type. Allowed types: PDF, DOC, DOCX';
        return;
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['exam_file']['tmp_name'], $file_path)) {
        // Save to database
        $relative_path = 'uploads/exams/' . $new_filename;
        $sql = "INSERT INTO library_exams (title, course_id, exam_type, exam_year, uploaded_by, file_path) 
                VALUES ('$title', $course_id, '$exam_type', $exam_year, $user_id, '$relative_path')";
        
        if ($conn->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Exam uploaded successfully';
        } else {
            $response['message'] = 'Error saving file data: ' . $conn->error;
            unlink($file_path); // Delete the uploaded file
        }
    } else {
        $response['message'] = 'Failed to move uploaded file';
    }
}
