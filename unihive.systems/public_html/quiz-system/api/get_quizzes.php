<?php
// filepath: quiz-system/api/get_quizzes.php

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized access"]);
    exit;
}

// Connect to the database
$conn = connectDB();

// Get user role
$userId = $_SESSION["user_id"];
$role = $_SESSION["role"];
$quizzes = [];

// Fetch quizzes based on user role
if ($role == "student") {
    $stmt = $conn->prepare("
        SELECT q.quiz_id, q.title, q.duration, q.expiration_time, p.name as professor_name 
        FROM quizzes q 
        JOIN professors p ON q.professor_id = p.professor_id 
        JOIN student_courses sc ON q.subject_id = sc.subject_id 
        WHERE sc.student_id = ?
    ");
    $stmt->bind_param("i", $userId);
} elseif ($role == "professor") {
    $stmt = $conn->prepare("
        SELECT q.quiz_id, q.title, q.duration, q.expiration_time 
        FROM quizzes q 
        WHERE q.professor_id = ?
    ");
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $quizzes[] = $row;
    }
}

// Return quizzes as JSON
header('Content-Type: application/json');
echo json_encode($quizzes);

$stmt->close();
$conn->close();
?>