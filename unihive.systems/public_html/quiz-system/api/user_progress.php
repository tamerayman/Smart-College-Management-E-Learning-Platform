<?php
// filepath: quiz-system/api/user_progress.php

session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized access."]);
    exit;
}

// الحصول على معرف المستخدم
$userId = $_SESSION["user_id"];

// الاتصال بقاعدة البيانات
$conn = connectDB();

// جلب تقدم الطالب في الاختبارات
$query = "
    SELECT q.quiz_id, q.title, 
           CASE 
               WHEN s.submission_id IS NOT NULL THEN 'Completed' 
               ELSE 'Not Completed' 
           END AS progress,
           q.duration, q.expiration_time
    FROM quizzes q
    LEFT JOIN submissions s ON q.quiz_id = s.quiz_id AND s.student_id = ?
    WHERE q.subject_id IN (SELECT subject_id FROM student_subjects WHERE student_id = ?)
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$progressData = [];
while ($row = $result->fetch_assoc()) {
    $progressData[] = $row;
}

$stmt->close();
$conn->close();

// إرجاع البيانات بتنسيق JSON
header('Content-Type: application/json');
echo json_encode($progressData);
?>