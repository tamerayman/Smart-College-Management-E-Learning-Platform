<?php
// filepath: quiz-system/quiz-system/quiz/view.php
session_start();
#require_once '../includes/config.php';
#require_once '../includes/db_connect.php';
require_once '../../config.php';
require_once '../../auth.php';
require_once '../includes/functions.php';

// Check if the user is logged in and is a professor
if (!isLoggedIn() || $_SESSION["role"] !== "professor") {
    header("Location: ../index.php");
    exit;
}

// Get quiz ID from the request
$quizId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch quiz details
$conn = connectDB();
$quiz_stmt = $conn->prepare("SELECT title, time_limit, end_time, professor_id FROM quizzes WHERE quiz_id = ?");
$quiz_stmt->bind_param("i", $quizId);
$quiz_stmt->execute();
$quiz_result = $quiz_stmt->get_result();

if ($quiz_result->num_rows === 0) {
    echo "Quiz not found.";
    exit;
}

$quiz = $quiz_result->fetch_assoc();
$quizTitle = htmlspecialchars($quiz['title']);
$duration = $quiz['time_limit']; // This maps 'time_limit' from DB to 'duration' variable
$expirationTime = $quiz['end_time'];
$professorId = $quiz['professor_id'];

// Fetch submissions for the quiz - Changed table from quiz_submissions to quiz_attempts
$submissions_stmt = $conn->prepare("
    SELECT a.student_id, a.end_time as submission_time, a.score 
    FROM quiz_attempts a 
    WHERE a.quiz_id = ? AND a.status = 'completed'
");
$submissions_stmt->bind_param("i", $quizId);
$submissions_stmt->execute();
$submissions_result = $submissions_stmt->get_result();

$submissions = [];
while ($submission = $submissions_result->fetch_assoc()) {
    $submissions[] = $submission;
}

// Get question count
$questionsStmt = $conn->prepare("SELECT COUNT(*) as total FROM quiz_questions WHERE quiz_id = ?");
$questionsStmt->bind_param("i", $quizId);
$questionsStmt->execute();
$questionsResult = $questionsStmt->get_result();
$questionCount = $questionsResult->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Quiz - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/quiz.css">
    <style>
        .quiz-details {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .quiz-details p {
            margin: 10px 0;
            font-size: 16px;
        }
        
        .quiz-details strong {
            font-weight: bold;
            color: #333;
        }
        
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $quizTitle; ?></h1>
        
        <div class="quiz-details">
            <p><strong>مدة الاختبار:</strong> <?php echo $duration; ?> دقيقة</p>
            <p><strong>متاح حتى:</strong> <?php echo date('Y-m-d h:i A', strtotime($expirationTime)); ?></p>
            <p><strong>عدد الأسئلة:</strong> <?php echo $questionCount; ?> سؤال</p>
        </div>
        
        <p>عند بدء الاختبار، سيكون لديك <?php echo $duration; ?> دقيقة لإكماله. تأكد من أن لديك اتصال إنترنت مستقر قبل البدء.</p>
        
        <a href="attempt.php?id=<?php echo $quizId; ?>" class="btn">بدء الاختبار</a>
    </div>
</body>
</html>