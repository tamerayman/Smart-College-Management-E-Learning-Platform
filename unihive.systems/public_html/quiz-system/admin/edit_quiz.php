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
$quizId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($quizId <= 0) {
    header("Location: manage_quizzes.php?error=invalid_quiz");
    exit;
}

$conn = connectDB();
$professorId = $_SESSION["user_id"];

// التحقق من ملكية الاختبار
$checkQuizQuery = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id = ? AND professor_id = ?");
$checkQuizQuery->bind_param("ii", $quizId, $professorId);
$checkQuizQuery->execute();
$quizResult = $checkQuizQuery->get_result();

if ($quizResult->num_rows === 0) {
    header("Location: manage_quizzes.php?error=unauthorized");
    exit;
}

$quiz = $quizResult->fetch_assoc();

// معالجة نموذج تعديل الاختبار
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = []; // مصفوفة لتتبع أخطاء التحقق

    // تحقق من البيانات المدخلة
    $title = trim($_POST['title']);
    $duration = intval($_POST['duration']);
    $expiration_time = $_POST['expiration_time'];
    $courseId = intval($_POST['subject_id']);
    
    // التحقق من صحة البيانات
    if (empty($title)) {
        $errors[] = "عنوان الاختبار مطلوب";
    }
    
    if ($duration <= 0) {
        $errors[] = "مدة الاختبار يجب أن تكون أكبر من 0";
    }
    
    if (empty($expiration_time)) {
        $errors[] = "يجب تحديد موعد انتهاء الاختبار";
    }
    
    if ($courseId <= 0) {
        $errors[] = "يجب اختيار المادة الدراسية";
    }
    
    // إذا لم تكن هناك أخطاء، قم بتحديث الاختبار
    if (empty($errors)) {
        $updateQuizQuery = $conn->prepare("
            UPDATE quizzes 
            SET title = ?, time_limit = ?, end_time = ?, course_id = ?
            WHERE quiz_id = ? AND professor_id = ?
        ");
        $updateQuizQuery->bind_param("sssiii", $title, $duration, $expiration_time, $courseId, $quizId, $professorId);
        
        if ($updateQuizQuery->execute()) {
            $_SESSION['success_message'] = "تم تحديث الاختبار بنجاح!";
            header("Location: manage_quizzes.php");
            exit;
        } else {
            $error_message = "حدث خطأ أثناء تحديث الاختبار: " . $conn->error;
        }
    } else {
        // تجميع الأخطاء لعرضها
        $error_message = "يرجى تصحيح الأخطاء التالية:<br>" . implode("<br>", $errors);
    }
} else {
    // تعبئة بيانات النموذج بالقيم الحالية للاختبار
    $title = $quiz['title'];
    $duration = $quiz['time_limit'];
    $expiration_time = $quiz['end_time'];
    $courseId = $quiz['course_id'];
}

// جلب المواد الدراسية المتاحة للأستاذ
$subjects = [];
$subject_stmt = $conn->prepare("
    SELECT c.course_id, c.name 
    FROM courses c
    JOIN professor_courses pc ON c.course_id = pc.course_id
    WHERE pc.professor_id = ?
    ORDER BY c.name ASC
");
$subject_stmt->bind_param("i", $professorId);
$subject_stmt->execute();
$result = $subject_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الاختبار</title>
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
        
        .page-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
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
        
        .form-container {
            background: white;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .form-section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .form-section:hover {
            box-shadow: var(--shadow-md);
        }
        
        .form-section-title {
            color: var(--primary-color);
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-section-title i {
            font-size: 22px;
            margin-left: 8px;
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background-color: #f8fafc;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
            background-color: white;
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }
        
        select.form-control {
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="%2394a3b8" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: left 10px center;
            background-size: 20px;
            padding-left: 35px;
        }
        
        .form-help-text {
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
        }
        
        .quiz-settings {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
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
        
        .error-list {
            list-style-type: disc;
            padding-right: 20px;
            margin-top: 10px;
        }
        
        .error-list li {
            margin-bottom: 6px;
        }
        
        .quiz-form-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-secondary:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 15px;
                padding: 0;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .quiz-form-footer {
                flex-direction: column;
                gap: 15px;
            }
            
            .quiz-form-footer .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>تعديل الاختبار</h1>
            <a href="manage_quizzes.php" class="back-button">
                <i class='bx bx-arrow-back'></i> العودة إلى الاختبارات
            </a>
        </div>
        
        <div class="form-container">
            <?php if (isset($success_message)): ?>
                <div class="success-alert">
                    <i class='bx bx-check-circle'></i> 
                    <div><?php echo $success_message; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-alert">
                    <i class='bx bx-error-circle'></i>
                    <div>
                        <?php echo $error_message; ?>
                        <?php if (!empty($errors)): ?>
                            <ul class="error-list">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form action="edit_quiz.php?id=<?php echo $quizId; ?>" method="post" id="quizForm">
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class='bx bx-info-circle'></i> معلومات الاختبار الأساسية
                    </h2>
                    
                    <div class="quiz-settings">
                        <div class="form-group">
                            <label for="title">عنوان الاختبار</label>
                            <input type="text" name="title" id="title" class="form-control" required 
                                   placeholder="أدخل عنوان الاختبار هنا..." value="<?php echo htmlspecialchars($title); ?>">
                            <p class="form-help-text">اختر عنوانًا واضحًا وموجزًا يصف محتوى الاختبار</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">مدة الاختبار (بالدقائق)</label>
                            <input type="number" name="duration" id="duration" class="form-control" required min="1" 
                                   placeholder="مثال: 60" value="<?php echo htmlspecialchars($duration); ?>">
                            <p class="form-help-text">المدة التي سيُتاح للطالب حل الاختبار خلالها</p>
                        </div>
                    </div>
                    
                    <div class="quiz-settings">
                        <div class="form-group">
                            <label for="expiration_time">موعد انتهاء الاختبار</label>
                            <input type="datetime-local" name="expiration_time" id="expiration_time" class="form-control" required
                                   value="<?php echo date('Y-m-d\TH:i', strtotime($expiration_time)); ?>">
                            <p class="form-help-text">آخر موعد يمكن للطلاب بدء محاولة الاختبار فيه</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject_id">المادة الدراسية</label>
                            <select name="subject_id" id="subject_id" class="form-control" required>
                                <option value="">-- اختر المادة --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['course_id']; ?>" <?php echo ($courseId == $subject['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-help-text">المادة التي سيظهر فيها هذا الاختبار للطلاب</p>
                        </div>
                    </div>
                </div>
                
                <div class="quiz-form-footer">
                    <a href="manage_quizzes.php" class="btn btn-secondary">
                        <i class='bx bx-x'></i> إلغاء
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitButton">
                        <i class='bx bx-check'></i> حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تعطيل زر الحفظ عند النقر لمنع الإرسال المتكرر
            document.getElementById('quizForm').addEventListener('submit', function() {
                document.getElementById('submitButton').disabled = true;
                document.getElementById('submitButton').innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> جاري الحفظ...';
            });
        });
    </script>
</body>
</html>
