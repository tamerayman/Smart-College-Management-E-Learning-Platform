<?php
session_start();
require_once '../../config.php';
require_once '../../auth.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// الحصول على معرف الاختبار والمحاولة من الرابط
$quizId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$attemptId = isset($_GET['attempt']) ? intval($_GET['attempt']) : 0;

if ($quizId <= 0 || $attemptId <= 0) {
    header("Location: ../dashboard.php?error=invalid_params");
    exit;
}

$conn = connectDB();
$studentId = $_SESSION["user_id"];

// التحقق من أن المحاولة تخص الطالب الحالي
$verifyAttempt = $conn->prepare("
    SELECT a.*, q.title, q.time_limit 
    FROM quiz_attempts a
    JOIN quizzes q ON a.quiz_id = q.quiz_id
    WHERE a.attempt_id = ? AND a.quiz_id = ? 
    AND (a.student_id = ? OR q.professor_id = ?)
");
$verifyAttempt->bind_param("iiii", $attemptId, $quizId, $studentId, $studentId);
$verifyAttempt->execute();
$verifyResult = $verifyAttempt->get_result();

if ($verifyResult->num_rows === 0) {
    header("Location: ../dashboard.php?error=unauthorized_access");
    exit;
}

$attempt = $verifyResult->fetch_assoc();
$isProfessor = ($_SESSION["role"] === "professor");

// Check if student_answers table has option_id or selected_option_id column
$columnCheckQuery = "SHOW COLUMNS FROM student_answers LIKE 'option_id'";
$columnCheckResult = $conn->query($columnCheckQuery);

// Determine the correct column name to use
if ($columnCheckResult->num_rows == 0) {
    $optionColumnName = "selected_option_id";
} else {
    $optionColumnName = "option_id";
}

// جلب أسئلة الاختبار مع إجابات الطالب
$questions = [];
$questionsQuery = $conn->prepare("
    SELECT q.question_id, q.question_text, q.question_type, q.question_order,
           sa.{$optionColumnName} as student_option_id, sa.is_correct as correct_answer
    FROM quiz_questions q
    LEFT JOIN student_answers sa ON q.question_id = sa.question_id AND sa.attempt_id = ?
    WHERE q.quiz_id = ?
    ORDER BY q.question_order, q.question_id
");
$questionsQuery->bind_param("ii", $attemptId, $quizId);
$questionsQuery->execute();
$questionsResult = $questionsQuery->get_result();

while ($question = $questionsResult->fetch_assoc()) {
    // جلب جميع خيارات الإجابة لكل سؤال
    $optionsQuery = $conn->prepare("
        SELECT option_id, option_text, is_correct 
        FROM question_options 
        WHERE question_id = ? 
        ORDER BY option_order, option_id
    ");
    $optionsQuery->bind_param("i", $question['question_id']);
    $optionsQuery->execute();
    $optionsResult = $optionsQuery->get_result();
    
    $options = [];
    $correctOptionId = null;
    
    while ($option = $optionsResult->fetch_assoc()) {
        $options[] = $option;
        if ($option['is_correct']) {
            $correctOptionId = $option['option_id'];
        }
    }
    
    $question['options'] = $options;
    $question['correct_option_id'] = $correctOptionId;
    $questions[] = $question;
}

$conn->close();

// حساب إحصائيات
$totalQuestions = count($questions);
$answeredQuestions = 0;
$correctAnswers = 0;

foreach ($questions as $question) {
    if (!empty($question['student_option_id'])) {
        $answeredQuestions++;
        if ($question['correct_answer']) {
            $correctAnswers++;
        }
    }
}

$score = $attempt['score'];
$scorePercentage = ($totalQuestions > 0) ? round(($correctAnswers / $totalQuestions) * 100) : 0;
$passingScore = 60; // عتبة النجاح
$passed = ($scorePercentage >= $passingScore);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج الاختبار - <?php echo htmlspecialchars($attempt['title']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --dark-color: #2c3e50;
            --light-color: #f5f7fa;
        }
        
        body {
            background-color: var(--light-color);
            font-family: 'Cairo', sans-serif;
            color: var(--dark-color);
        }
        
        .results-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .results-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .results-title {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .score-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #e0e3e8 100%);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .score-percentage {
            font-size: 2rem;
            font-weight: bold;
            line-height: 1;
            color: var(--primary-color);
        }
        
        .score-label {
            font-size: 0.9rem;
            color: #888;
        }
        
        .pass-badge {
            background: var(--success-color);
            color: white;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .fail-badge {
            background: var(--danger-color);
            color: white;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .score-details {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            text-align: center;
        }
        
        .score-item {
            flex: 1;
            padding: 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.7);
        }
        
        .score-item-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .score-item-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .questions-review {
            margin-top: 2rem;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .question-item {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-right: 4px solid var(--primary-color);
        }
        
        .question-text {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1.2rem;
        }
        
        .option-item {
            position: relative;
            padding: 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
        }
        
        .option-prefix {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-left: 10px;
            font-weight: bold;
        }
        
        .option-correct {
            background-color: rgba(46, 204, 113, 0.1);
            border-color: var(--success-color);
        }
        
        .option-incorrect {
            background-color: rgba(231, 76, 60, 0.1);
            border-color: var(--danger-color);
        }
        
        .option-correct .option-prefix {
            background-color: var(--success-color);
        }
        
        .option-incorrect .option-prefix {
            background-color: var(--danger-color);
        }
        
        .option-status {
            position: absolute;
            left: 1rem;
            font-size: 1.2rem;
        }
        
        .correct-icon {
            color: var(--success-color);
        }
        
        .incorrect-icon {
            color: var(--danger-color);
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #3051d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            margin-top: 1.5rem;
        }
        
        .back-btn i {
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="results-container">
            <div class="results-header">
                <h1 class="results-title">نتائج الاختبار</h1>
                <h2><?php echo htmlspecialchars($attempt['title']); ?></h2>
            </div>
            
            <div class="score-card">
                <div class="score-circle">
                    <div class="score-percentage"><?php echo $scorePercentage; ?>%</div>
                    <div class="score-label">الدرجة</div>
                </div>
                
                <div>
                    <?php if ($passed): ?>
                        <div class="pass-badge">
                            <i class='bx bx-check'></i> اجتياز
                        </div>
                    <?php else: ?>
                        <div class="fail-badge">
                            <i class='bx bx-x'></i> عدم اجتياز
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="score-details">
                    <div class="score-item">
                        <div class="score-item-value"><?php echo $totalQuestions; ?></div>
                        <div class="score-item-label">إجمالي الأسئلة</div>
                    </div>
                    <div class="score-item">
                        <div class="score-item-value"><?php echo $correctAnswers; ?></div>
                        <div class="score-item-label">الإجابات الصحيحة</div>
                    </div>
                    <div class="score-item">
                        <div class="score-item-value"><?php echo $totalQuestions - $correctAnswers; ?></div>
                        <div class="score-item-label">الإجابات الخاطئة</div>
                    </div>
                </div>
            </div>
            
            <div class="questions-review">
                <div class="review-header">
                    <h3>مراجعة الأسئلة والإجابات</h3>
                </div>
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-item">
                        <div class="question-text">
                            <?php echo ($index + 1) . '. ' . htmlspecialchars($question['question_text']); ?>
                        </div>
                        
                        <div class="options-container">
                            <?php foreach ($question['options'] as $optionIndex => $option): ?>
                                <?php 
                                    $optionClass = '';
                                    $statusIcon = '';
                                    
                                    // تحديد حالة الخيار (صحيح/خاطئ/محدد)
                                    if ($option['option_id'] == $question['student_option_id']) {
                                        if ($option['is_correct']) {
                                            $optionClass = 'option-correct';
                                            $statusIcon = '<i class="bx bx-check-circle correct-icon"></i>';
                                        } else {
                                            $optionClass = 'option-incorrect';
                                            $statusIcon = '<i class="bx bx-x-circle incorrect-icon"></i>';
                                        }
                                    } elseif ($option['is_correct']) {
                                        $optionClass = 'option-correct';
                                    }
                                ?>
                                <div class="option-item <?php echo $optionClass; ?>">
                                    <span class="option-prefix"><?php echo chr(65 + $optionIndex); ?></span>
                                    <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                    <?php if (!empty($statusIcon)): ?>
                                        <span class="option-status"><?php echo $statusIcon; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="../dashboard.php" class="btn btn-primary back-btn">
                <i class='bx bx-arrow-back'></i> العودة إلى لوحة التحكم
            </a>
        </div>
    </div>
</body>
</html>