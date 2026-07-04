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

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];
$conn = connectDB();
$response = ['success' => false, 'message' => ''];

// Get delete parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($type) || $id <= 0) {
    $response['message'] = 'Invalid parameters';
    echo json_encode($response);
    exit();
}

// Set table based on type
if ($type == 'book') {
    $table = 'library_books';
} elseif ($type == 'exam') {
    $table = 'library_exams';
} else {
    $response['message'] = 'Invalid file type';
    echo json_encode($response);
    exit();
}

// Check if file belongs to the professor
$check_sql = "SELECT file_path FROM $table WHERE id = $id AND uploaded_by = $user_id";
$check_result = $conn->query($check_sql);

if ($check_result && $check_result->num_rows > 0) {
    $file = $check_result->fetch_assoc();
    $file_path = '../' . $file['file_path'];
    
    // Delete from database
    $delete_sql = "DELETE FROM $table WHERE id = $id AND uploaded_by = $user_id";
    if ($conn->query($delete_sql)) {
        // Delete physical file
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $response['success'] = true;
        $response['message'] = 'File deleted successfully';
    } else {
        $response['message'] = 'Error deleting file from database: ' . $conn->error;
    }
} else {
    $response['message'] = 'File not found or you do not have permission to delete it';
}

echo json_encode($response);
$conn->close();
?>
