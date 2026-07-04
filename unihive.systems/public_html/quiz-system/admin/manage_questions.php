<?php
session_start();
require_once '../../config.php';
require_once '../../auth.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المستخدم
if (!isLoggedIn() || $_SESSION["role"] !== "professor") {
    header("Location: ../index.php");
    exit;
}

// الحصول على معرف الاختبار من الرابط
$quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

if ($quizId <= 0) {
    header("Location: manage_quizzes.php?error=invalid_quiz");
    exit;
}

$conn = connectDB();
$professorId = $_SESSION["user_id"];

// التحقق من ملكية الاختبار
$checkQuizQuery = $conn->prepare("SELECT title FROM quizzes WHERE quiz_id = ? AND professor_id = ?");
$checkQuizQuery->bind_param("ii", $quizId, $professorId);
$checkQuizQuery->execute();
$quizResult = $checkQuizQuery->get_result();

if ($quizResult->num_rows === 0) {
    header("Location: manage_quizzes.php?error=unauthorized");
    exit;
}

$quiz = $quizResult->fetch_assoc();

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
    // جلب خيارات كل سؤال
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

// عرض رسالة النجاح إذا وجدت في الجلسة
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // مسح الرسالة بعد عرضها
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة أسئلة الاختبار - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #edf2ff;
            --secondary-color: #3a0ca3;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gray-color: #94a3b8;
            --border-radius: 10px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f1f5f9;
            font-family: 'Cairo', sans-serif;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px 30px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header-content {
            display: flex;
            flex-direction: column;
        }
        
        .page-header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        .page-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .back-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .back-button i {
            margin-left: 5px;
        }
        
        .content-container {
            background: white;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            border: none;
        }
        
        .btn i {
            margin-left: 8px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark-color);
            border: 1px solid #cbd5e1;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-primary:hover, .btn-success:hover, .btn-warning:hover, .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-secondary:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 0;
            color: var(--gray-color);
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .empty-state p {
            margin-bottom: 20px;
            color: var(--gray-color);
        }
        
        .success-alert, .error-alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
        }
        
        .success-alert {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .error-alert {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .success-alert i, .error-alert i {
            margin-left: 10px;
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .question-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 25px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .question-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .question-number {
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .question-number i {
            margin-left: 8px;
        }
        
        .question-type-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .question-type-multiple {
            background: var(--primary-color);
        }
        
        .question-type-tf {
            background: var(--warning-color);
        }
        
        .question-content {
            padding: 20px;
        }
        
        .question-text {
            font-size: 18px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .options-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .option-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: #f8fafc;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .option-item.correct {
            background: rgba(46, 204, 113, 0.1);
            border-right: 3px solid var(--success-color);
        }
        
        .option-prefix {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-left: 10px;
            font-size: 12px;
        }
        
        .option-item.correct .option-prefix {
            background: var(--success-color);
        }
        
        .correct-indicator {
            margin-right: auto;
            color: var(--success-color);
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .correct-indicator i {
            margin-left: 5px;
        }
        
        .question-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .question-actions .btn {
            padding: 8px 12px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 15px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .content-container {
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <h1>إدارة أسئلة الاختبار</h1>
                <div class="page-subtitle"><?php echo htmlspecialchars($quiz['title']); ?></div>
            </div>
            <a href="manage_quizzes.php" class="back-button">
                <i class='bx bx-arrow-back'></i> العودة إلى الاختبارات
            </a>
        </div>
        
        <div class="content-container">
            <?php if (isset($success_message)): ?>
                <div class="success-alert">
                    <i class='bx bx-check-circle'></i> 
                    <div><?php echo $success_message; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-alert">
                    <i class='bx bx-error-circle'></i>
                    <div><?php echo $error_message; ?></div>
                </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <a href="add_question.php?quiz_id=<?php echo $quizId; ?>" class="btn btn-primary">
                    <i class='bx bx-plus'></i> إضافة سؤال جديد
                </a>
            </div>
            
            <?php if (empty($questions)): ?>
                <div class="empty-state">
                    <i class='bx bx-question-mark'></i>
                    <h3>لا توجد أسئلة بعد</h3>
                    <p>قم بإضافة أسئلة لهذا الاختبار</p>
                    <a href="add_question.php?quiz_id=<?php echo $quizId; ?>" class="btn btn-primary">
                        <i class='bx bx-plus'></i> إضافة سؤال جديد
                    </a>
                </div>
            <?php else: ?>
                <div class="questions-list">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card">
                            <div class="question-header">
                                <div class="question-number">
                                    <i class='bx bx-question-mark'></i>
                                    السؤال <?php echo $index + 1; ?>
                                </div>
                                <div class="question-type-badge <?php echo ($question['question_type'] === 'multiple_choice') ? 'question-type-multiple' : 'question-type-tf'; ?>">
                                    <?php echo ($question['question_type'] === 'multiple_choice') ? 'اختيار من متعدد' : 'صح / خطأ'; ?>
                                </div>
                            </div>
                            <div class="question-content">
                                <div class="question-text">
                                    <?php echo htmlspecialchars($question['question_text']); ?>
                                </div>
                                <ul class="options-list">
                                    <?php foreach ($question['options'] as $optIndex => $option): ?>
                                        <li class="option-item <?php echo $option['is_correct'] ? 'correct' : ''; ?>">
                                            <div class="option-prefix"><?php echo chr(65 + $optIndex); ?></div>
                                            <div class="option-text">
                                                <?php echo htmlspecialchars($option['option_text']); ?>
                                            </div>
                                            <?php if ($option['is_correct']): ?>
                                                <div class="correct-indicator">
                                                    <i class='bx bx-check'></i> الإجابة الصحيحة
                                                </div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="question-actions">
                                    <a href="edit_question.php?id=<?php echo $question['question_id']; ?>" class="btn btn-warning">
                                        <i class='bx bx-edit'></i> تعديل
                                    </a>
                                    <a href="delete_question.php?id=<?php echo $question['question_id']; ?>&quiz_id=<?php echo $quizId; ?>" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا السؤال؟');">
                                        <i class='bx bx-trash'></i> حذف
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
