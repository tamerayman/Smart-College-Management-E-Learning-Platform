<?php
// filepath: quiz-system/quiz-system/quiz/attempt.php
session_start();
require_once '../../config.php';
require_once '../../auth.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn() || $_SESSION["role"] !== "student") {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// الحصول على معرف الاختبار من الرابط
$quizId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($quizId <= 0) {
    header("Location: ../dashboard.php?error=invalid_quiz");
    exit;
}

$conn = connectDB();
$studentId = $_SESSION["user_id"];

// التحقق مما إذا كان الطالب قد قام بمحاولة سابقة للاختبار
$attemptCheck = $conn->prepare("
    SELECT attempt_id, status, start_time, end_time, score 
    FROM quiz_attempts 
    WHERE quiz_id = ? AND student_id = ? 
    ORDER BY attempt_id DESC LIMIT 1
");
$attemptCheck->bind_param("ii", $quizId, $studentId);
$attemptCheck->execute();
$attemptResult = $attemptCheck->get_result();

$existingAttempt = false;
$attemptCompleted = false;
$attemptId = null;
$studentScore = 0;

if ($attemptResult->num_rows > 0) {
    $attempt = $attemptResult->fetch_assoc();
    $existingAttempt = true;
    $attemptId = $attempt['attempt_id'];
    
    if ($attempt['status'] === 'completed') {
        $attemptCompleted = true;
        $studentScore = $attempt['score'];
        
        // إعادة توجيه الطالب إلى صفحة النتائج إذا كان قد أكمل الاختبار بالفعل
        header("Location: results.php?id={$quizId}&attempt={$attemptId}");
        exit;
    }
}

// جلب تفاصيل الاختبار
$quizQuery = $conn->prepare("SELECT title, time_limit, start_time, end_time FROM quizzes WHERE quiz_id = ?");
$quizQuery->bind_param("i", $quizId);
$quizQuery->execute();
$quizResult = $quizQuery->get_result();

if ($quizResult->num_rows === 0) {
    header("Location: ../dashboard.php?error=quiz_not_found");
    exit;
}

$quiz = $quizResult->fetch_assoc();

// التحقق من صلاحية الاختبار (الوقت)
$now = new DateTime();
$endTime = new DateTime($quiz['end_time']);

if ($now > $endTime) {
    header("Location: ../dashboard.php?error=quiz_expired");
    exit;
}

// إنشاء محاولة جديدة إذا لم تكن هناك محاولة سابقة
if (!$existingAttempt) {
    $createAttempt = $conn->prepare("
        INSERT INTO quiz_attempts (quiz_id, student_id, start_time, status) 
        VALUES (?, ?, NOW(), 'in_progress')
    ");
    $createAttempt->bind_param("ii", $quizId, $studentId);
    $createAttempt->execute();
    $attemptId = $conn->insert_id;
}

// جلب أسئلة الاختبار
$questionsQuery = $conn->prepare("
    SELECT q.question_id, q.question_text, q.question_type, q.question_order
    FROM quiz_questions q 
    WHERE q.quiz_id = ? 
    ORDER BY q.question_order, q.question_id
");
$questionsQuery->bind_param("i", $quizId);
$questionsQuery->execute();
$questionsResult = $questionsQuery->get_result();

$questions = [];
while ($question = $questionsResult->fetch_assoc()) {
    // جلب خيارات الإجابة لكل سؤال
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
    while ($option = $optionsResult->fetch_assoc()) {
        $options[] = $option;
    }
    
    $question['options'] = $options;
    $questions[] = $question;
}

$conn->close();

// حساب الوقت المتبقي للاختبار (بالثواني)
$timeLimit = intval($quiz['time_limit']) * 60; // تحويل من دقائق إلى ثواني
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - اختبار</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #f5f7fa;
        }
        
        body {
            background-color: var(--light-color);
            font-family: 'Cairo', sans-serif;
            color: var(--dark-color);
        }
        
        .quiz-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .quiz-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .quiz-title {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .timer-container {
            position: fixed;
            top: 20px;
            left: 20px;
            background: white;
            padding: 10px 15px;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            z-index: 1000;
        }
        
        .timer-icon {
            color: var(--warning-color);
            font-size: 1.5rem;
            margin-left: 10px;
        }
        
        .timer {
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .timer.warning {
            color: var(--warning-color);
        }
        
        .timer.danger {
            color: var(--danger-color);
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .progress-container {
            margin-bottom: 2rem;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .question-container {
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
        
        .options-container {
            display: grid;
            gap: 0.8rem;
        }
        
        .option-item {
            position: relative;
        }
        
        .option-input {
            display: none;
        }
        
        .option-label {
            display: block;
            padding: 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: normal;
        }
        
        .option-label:hover {
            background: #f0f4ff;
            border-color: #4361ee;
        }
        
        .option-input:checked + .option-label {
            background: #e6efff;
            border-color: #4361ee;
            font-weight: bold;
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
        
        .option-input:checked + .option-label .option-prefix {
            background: #2d3fe0;
        }
        
        .quiz-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            align-items: center;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background: white;
            border: 1px solid #ddd;
        }
        
        .btn-primary:hover {
            background: #3051d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }
        
        .btn-secondary:hover {
            background: #f5f5f5;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="timer-container">
        <i class='bx bx-time timer-icon'></i>
        <div id="timer" class="timer">--:--</div>
    </div>
    
    <div class="container">
        <div class="quiz-container">
            <div class="quiz-header">
                <h1 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h1>
                <p>أجب على جميع الأسئلة في الوقت المحدد</p>
            </div>
            
            <div class="progress-container">
                <div class="progress-text">
                    <span>تقدمك</span>
                    <span id="progress-indicator">0 / <?php echo count($questions); ?></span>
                </div>
                <div class="progress">
                    <div id="progress-bar" class="progress-bar bg-primary" style="width: 0%"></div>
                </div>
            </div>
            
            <form id="quiz-form" method="post" action="../api/submit_quiz.php">
                <input type="hidden" name="quiz_id" value="<?php echo $quizId; ?>">
                <input type="hidden" name="attempt_id" value="<?php echo $attemptId; ?>">
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-container" id="question-<?php echo $index + 1; ?>">
                        <div class="question-text">
                            <?php echo ($index + 1) . '. ' . htmlspecialchars($question['question_text']); ?>
                        </div>
                        
                        <div class="options-container">
                            <?php foreach ($question['options'] as $optionIndex => $option): ?>
                                <div class="option-item">
                                    <input type="radio" 
                                           id="q<?php echo $question['question_id']; ?>-option<?php echo $option['option_id']; ?>" 
                                           name="answers[<?php echo $question['question_id']; ?>]" 
                                           value="<?php echo $option['option_id']; ?>" 
                                           class="option-input" 
                                           data-correct="<?php echo $option['is_correct']; ?>">
                                    <label for="q<?php echo $question['question_id']; ?>-option<?php echo $option['option_id']; ?>" class="option-label">
                                        <span class="option-prefix"><?php echo chr(65 + $optionIndex); ?></span>
                                        <?php echo htmlspecialchars($option['option_text']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="quiz-footer">
                    <span id="answered-questions">0 من <?php echo count($questions); ?> أسئلة تمت الإجابة عليها</span>
                    <button type="submit" class="btn btn-primary">إنهاء الاختبار وإرسال الإجابات</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // تحديد وقت انتهاء الاختبار
        const quizDuration = <?php echo $timeLimit; ?>; // بالثواني
        let timeRemaining = quizDuration;
        
        // معرفة عندما بدأ الاختبار
        const startTime = new Date();
        
        // تحديث المؤقت كل ثانية
        const timerInterval = setInterval(updateTimer, 1000);
        
        function updateTimer() {
            timeRemaining--;
            
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                submitQuiz();
                return;
            }
            
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            
            const timerElement = document.getElementById('timer');
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // تغيير لون المؤقت حسب الوقت المتبقي
            if (timeRemaining < 60) { // أقل من دقيقة
                timerElement.classList.add('danger');
            } else if (timeRemaining < 300) { // أقل من 5 دقائق
                timerElement.classList.add('warning');
            }
        }
        
        // تحديث التقدم عند اختيار إجابة
        const radioButtons = document.querySelectorAll('.option-input');
        const progressBar = document.getElementById('progress-bar');
        const progressIndicator = document.getElementById('progress-indicator');
        const answeredQuestionsElement = document.getElementById('answered-questions');
        
        let answeredQuestions = new Set();
        const totalQuestions = <?php echo count($questions); ?>;
        
        radioButtons.forEach(radio => {
            radio.addEventListener('change', () => {
                const questionId = radio.name.match(/\d+/)[0];
                answeredQuestions.add(questionId);
                
                // تحديث شريط التقدم
                const progress = Math.round((answeredQuestions.size / totalQuestions) * 100);
                progressBar.style.width = `${progress}%`;
                progressIndicator.textContent = `${answeredQuestions.size} / ${totalQuestions}`;
                answeredQuestionsElement.textContent = `${answeredQuestions.size} من ${totalQuestions} أسئلة تمت الإجابة عليها`;
                
                // تخزين الإجابة في localStorage
                localStorage.setItem(`quiz_${<?php echo $quizId; ?>}_question_${questionId}`, radio.value);
            });
        });
        
        // استعادة الإجابات المحفوظة إذا وجدت
        function restoreSavedAnswers() {
            radioButtons.forEach(radio => {
                const questionId = radio.name.match(/\d+/)[0];
                const savedAnswer = localStorage.getItem(`quiz_${<?php echo $quizId; ?>}_question_${questionId}`);
                
                if (savedAnswer === radio.value) {
                    radio.checked = true;
                    answeredQuestions.add(questionId);
                }
            });
            
            // تحديث شريط التقدم
            const progress = Math.round((answeredQuestions.size / totalQuestions) * 100);
            progressBar.style.width = `${progress}%`;
            progressIndicator.textContent = `${answeredQuestions.size} / ${totalQuestions}`;
            answeredQuestionsElement.textContent = `${answeredQuestions.size} من ${totalQuestions} أسئلة تمت الإجابة عليها`;
        }
        
        // استعادة الإجابات عند تحميل الصفحة
        window.addEventListener('DOMContentLoaded', restoreSavedAnswers);
        
        // تقديم الاختبار
        function submitQuiz() {
            document.getElementById('quiz-form').submit();
        }
        
        // تقديم الاختبار تلقائيًا عند إغلاق الصفحة أو تغييرها
        window.addEventListener('beforeunload', function(e) {
            localStorage.removeItem(`quiz_${<?php echo $quizId; ?>}_in_progress`);
            
            // عدم تقديم الاختبار إذا لم يتم الإجابة على أي سؤال
            if (answeredQuestions.size > 0) {
                submitQuiz();
            }
        });
        
        // تخزين حالة الاختبار
        localStorage.setItem(`quiz_${<?php echo $quizId; ?>}_in_progress`, 'true');
        
        // تحديث المؤقت مباشرة
        updateTimer();
    </script>
</body>
</html>