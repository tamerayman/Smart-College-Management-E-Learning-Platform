<?php
// filepath: quiz-system/api/submit_quiz.php

session_start();
require_once '../../config.php';
require_once '../../auth.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn() || $_SESSION["role"] !== "student") {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// التحقق من البيانات المرسلة
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quiz_id']) || !isset($_POST['attempt_id'])) {
    header("Location: ../dashboard.php?error=invalid_submission");
    exit;
}

$quizId = intval($_POST['quiz_id']);
$attemptId = intval($_POST['attempt_id']);
$studentId = $_SESSION["user_id"];
$answers = isset($_POST['answers']) ? $_POST['answers'] : [];

$conn = connectDB();

// التحقق من أن المحاولة تخص الطالب الحالي
$verifyAttempt = $conn->prepare("
    SELECT status FROM quiz_attempts 
    WHERE attempt_id = ? AND quiz_id = ? AND student_id = ?
");
$verifyAttempt->bind_param("iii", $attemptId, $quizId, $studentId);
$verifyAttempt->execute();
$verifyResult = $verifyAttempt->get_result();

if ($verifyResult->num_rows === 0) {
    header("Location: ../dashboard.php?error=invalid_attempt");
    exit;
}

$attempt = $verifyResult->fetch_assoc();

// التحقق من أن المحاولة لم تكتمل بعد
if ($attempt['status'] === 'completed') {
    header("Location: ../quiz/results.php?id={$quizId}&attempt={$attemptId}");
    exit;
}

// الحصول على جميع أسئلة الاختبار
$questionsQuery = $conn->prepare("SELECT question_id FROM quiz_questions WHERE quiz_id = ?");
$questionsQuery->bind_param("i", $quizId);
$questionsQuery->execute();
$questionsResult = $questionsQuery->get_result();

$questions = [];
while ($question = $questionsResult->fetch_assoc()) {
    $questions[] = $question['question_id'];
}

// Check if student_answers table exists, if not create it
$tableCheckQuery = "SHOW TABLES LIKE 'student_answers'";
$tableCheckResult = $conn->query($tableCheckQuery);

if ($tableCheckResult->num_rows == 0) {
    // Table doesn't exist, create it
    $createTableQuery = "
        CREATE TABLE student_answers (
            answer_id INT AUTO_INCREMENT PRIMARY KEY,
            attempt_id INT NOT NULL,
            question_id INT NOT NULL,
            selected_option_id INT NULL,
            is_correct TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (attempt_id),
            INDEX (question_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createTableQuery);
}

// Check if column structure is correct
$columnCheckQuery = "SHOW COLUMNS FROM student_answers LIKE 'option_id'";
$columnCheckResult = $conn->query($columnCheckQuery);

// If option_id doesn't exist but selected_option_id does, use that instead
if ($columnCheckResult->num_rows == 0) {
    $optionColumnName = "selected_option_id";
} else {
    $optionColumnName = "option_id";
}

// حساب النتيجة
$totalQuestions = count($questions);
$correctAnswers = 0;

// معالجة الإجابات وحفظها
foreach ($questions as $questionId) {
    $selectedOptionId = isset($answers[$questionId]) ? intval($answers[$questionId]) : 0;
    $isCorrect = 0;
    
    // التحقق مما إذا كانت الإجابة صحيحة
    if ($selectedOptionId > 0) {
        $checkAnswerQuery = $conn->prepare("
            SELECT is_correct FROM question_options 
            WHERE option_id = ? AND question_id = ?
        ");
        $checkAnswerQuery->bind_param("ii", $selectedOptionId, $questionId);
        $checkAnswerQuery->execute();
        $answerResult = $checkAnswerQuery->get_result();
        
        if ($answerResult->num_rows > 0) {
            $answerData = $answerResult->fetch_assoc();
            $isCorrect = $answerData['is_correct'];
            
            if ($isCorrect) {
                $correctAnswers++;
            }
        }
    }
    
    // حفظ إجابة الطالب using the correct column name
    $saveAnswerQuery = $conn->prepare("
        INSERT INTO student_answers (attempt_id, question_id, {$optionColumnName}, is_correct) 
        VALUES (?, ?, ?, ?)
    ");
    $saveAnswerQuery->bind_param("iiis", $attemptId, $questionId, $selectedOptionId, $isCorrect);
    $saveAnswerQuery->execute();
}

// حساب النتيجة النهائية
$score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;

// تحديث حالة المحاولة ونتيجتها
$updateAttemptQuery = $conn->prepare("
    UPDATE quiz_attempts 
    SET status = 'completed', end_time = NOW(), score = ? 
    WHERE attempt_id = ?
");
$updateAttemptQuery->bind_param("di", $score, $attemptId);
$updateAttemptQuery->execute();

$conn->close();

// إعادة التوجيه إلى صفحة النتائج
header("Location: ../quiz/results.php?id={$quizId}&attempt={$attemptId}");
exit;
?>