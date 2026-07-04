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
$response = ['success' => false, 'message' => '', 'items' => []];

// Get upload type
$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($type == 'books') {
    // Get professor's uploaded books
    $books_sql = "SELECT lb.*, c.name as course_name, DATE_FORMAT(lb.upload_date, '%Y-%m-%d') as upload_date
                  FROM library_books lb
                  INNER JOIN courses c ON lb.course_id = c.course_id
                  WHERE lb.uploaded_by = $user_id
                  ORDER BY lb.upload_date DESC";
    $books_result = $conn->query($books_sql);
    
    if ($books_result) {
        $books = [];
        while ($book = $books_result->fetch_assoc()) {
            $books[] = [
                'id' => $book['id'],
                'title' => htmlspecialchars($book['title']),
                'course_name' => htmlspecialchars($book['course_name']),
                'upload_date' => $book['upload_date']
            ];
        }
        
        $response['success'] = true;
        $response['items'] = $books;
    } else {
        $response['message'] = 'Error fetching books: ' . $conn->error;
    }
} elseif ($type == 'exams') {
    // Get professor's uploaded exams
    $exams_sql = "SELECT le.*, c.name as course_name, DATE_FORMAT(le.upload_date, '%Y-%m-%d') as upload_date
                  FROM library_exams le
                  INNER JOIN courses c ON le.course_id = c.course_id
                  WHERE le.uploaded_by = $user_id
                  ORDER BY le.upload_date DESC";
    $exams_result = $conn->query($exams_sql);
    
    if ($exams_result) {
        $exams = [];
        while ($exam = $exams_result->fetch_assoc()) {
            $exams[] = [
                'id' => $exam['id'],
                'title' => htmlspecialchars($exam['title']),
                'course_name' => htmlspecialchars($exam['course_name']),
                'exam_type' => htmlspecialchars($exam['exam_type']),
                'exam_year' => $exam['exam_year'],
                'upload_date' => $exam['upload_date']
            ];
        }
        
        $response['success'] = true;
        $response['items'] = $exams;
    } else {
        $response['message'] = 'Error fetching exams: ' . $conn->error;
    }
} else {
    $response['message'] = 'Invalid request type';
}

echo json_encode($response);
$conn->close();
?>
