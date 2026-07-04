<?php
// filepath: quiz-system/admin/manage_quizzes.php
session_start();
#require_once '../includes/config.php';
require_once '../../config.php';
require_once '../../auth.php';
#require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if the user is logged in and is a professor
if (!isLoggedIn() || $_SESSION["role"] !== "professor") {
    header("Location: ../index.php");
    exit;
}

// Connect to the database
$conn = connectDB();

// Fetch quizzes created by the logged-in professor with course names
$userId = $_SESSION["user_id"];
$quizzes = [];
$stmt = $conn->prepare("
    SELECT q.quiz_id, q.title, q.time_limit, q.end_time, q.course_id, c.name as course_name,
           (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as question_count
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    WHERE q.professor_id = ?
    ORDER BY q.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($quiz = $result->fetch_assoc()) {
        $quizzes[] = $quiz;
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الاختبارات</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .quiz-card {
            background: #fff;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .quiz-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .quiz-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        
        .quiz-content {
            padding: 20px;
        }
        
        .quiz-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .quiz-detail {
            display: flex;
            align-items: center;
        }
        
        .quiz-detail i {
            font-size: 20px;
            margin-left: 10px;
            color: #3498db;
        }
        
        .quiz-detail-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .quiz-detail-value {
            font-weight: bold;
            margin-top: 3px;
        }
        
        .quiz-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: none;
            transition: all 0.3s;
        }
        
        .btn i {
            margin-left: 5px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #3498db;
            color: #3498db;
        }
        
        .btn-outline:hover {
            background: #3498db;
            color: white;
        }
        
        .question-badge {
            background: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            margin: 0;
            color: #2c3e50;
        }
        
        @media (max-width: 768px) {
            .quiz-details {
                grid-template-columns: 1fr;
            }
            
            .quiz-actions {
                flex-wrap: wrap;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php if (function_exists('session_status') && session_status() == PHP_SESSION_NONE) { session_start(); } ?>
    
    <div class="container">
        <div class="page-header">
            <h1>إدارة الاختبارات</h1>
            <a href="create_quiz.php" class="btn btn-primary">
                <i class='bx bx-plus'></i> إنشاء اختبار جديد
            </a>
        </div>

        <?php if (empty($quizzes)): ?>
            <div class="empty-state">
                <p>لم تقم بإنشاء أي اختبارات بعد.</p>
                <a href="create_quiz.php" class="btn btn-primary">إنشاء أول اختبار</a>
            </div>
        <?php else: ?>
            <div class="quizzes-container">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card">
                        <div class="quiz-header">
                            <h3 class="quiz-title">
                                <?php echo htmlspecialchars($quiz['title']); ?>
                                <span class="question-badge">
                                    <i class='bx bx-question-mark'></i> 
                                    <?php echo $quiz['question_count']; ?> سؤال
                                </span>
                            </h3>
                        </div>
                        <div class="quiz-content">
                            <div class="quiz-details">
                                <div class="quiz-detail">
                                    <i class='bx bx-book'></i>
                                    <div>
                                        <div class="quiz-detail-label">المادة</div>
                                        <div class="quiz-detail-value"><?php echo htmlspecialchars($quiz['course_name']); ?></div>
                                    </div>
                                </div>
                                <div class="quiz-detail">
                                    <i class='bx bx-time'></i>
                                    <div>
                                        <div class="quiz-detail-label">المدة الزمنية</div>
                                        <div class="quiz-detail-value"><?php echo htmlspecialchars($quiz['time_limit']); ?> دقيقة</div>
                                    </div>
                                </div>
                                <div class="quiz-detail">
                                    <i class='bx bx-calendar-exclamation'></i>
                                    <div>
                                        <div class="quiz-detail-label">متاح حتى</div>
                                        <div class="quiz-detail-value"><?php echo date('Y-m-d h:i A', strtotime($quiz['end_time'])); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="quiz-actions">
                                <a href="manage_questions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-primary">
                                    <i class='bx bx-edit'></i> إدارة الأسئلة
                                </a>
                                <a href="../quiz/view.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-secondary">
                                    <i class='bx bx-show'></i> عرض الاختبار
                                </a>
                                <!-- تعديل الرابط لتضمين معرف الاختبار في العنوان -->
                                <a href="manage_submissions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-success">
                                    <i class='bx bx-file'></i> الإجابات المقدمة
                                </a>
                                <a href="edit_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-warning">
                                    <i class='bx bx-pencil'></i> تعديل
                                </a>
                                <a href="delete_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الاختبار؟');">
                                    <i class='bx bx-trash'></i> حذف
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>