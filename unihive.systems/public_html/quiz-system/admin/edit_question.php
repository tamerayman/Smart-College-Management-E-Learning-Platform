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

// الحصول على معرف السؤال من الرابط
$questionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($questionId <= 0) {
    header("Location: manage_quizzes.php?error=invalid_question");
    exit;
}

$conn = connectDB();
$professorId = $_SESSION["user_id"];

// التحقق من ملكية السؤال
$checkQuestionQuery = $conn->prepare("
    SELECT q.*, qz.quiz_id, q.question_order, q.question_type, qz.title as quiz_title
    FROM quiz_questions q
    JOIN quizzes qz ON q.quiz_id = qz.quiz_id
    WHERE q.question_id = ? AND qz.professor_id = ?
");
$checkQuestionQuery->bind_param("ii", $questionId, $professorId);
$checkQuestionQuery->execute();
$questionResult = $checkQuestionQuery->get_result();

if ($questionResult->num_rows === 0) {
    header("Location: manage_quizzes.php?error=unauthorized");
    exit;
}

$question = $questionResult->fetch_assoc();
$quizId = $question['quiz_id'];

// جلب خيارات السؤال
$optionsQuery = $conn->prepare("
    SELECT * FROM question_options 
    WHERE question_id = ? 
    ORDER BY option_order, option_id
");
$optionsQuery->bind_param("i", $questionId);
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

// معالجة نموذج تعديل السؤال
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = []; // مصفوفة لتتبع أخطاء التحقق

    // تحقق من البيانات المدخلة
    $questionText = trim($_POST['question_text']);
    $questionType = $_POST['question_type'];
    
    // التحقق من صحة البيانات
    if (empty($questionText)) {
        $errors[] = "نص السؤال مطلوب";
    }
    
    if (!in_array($questionType, ['multiple_choice', 'true_false'])) {
        $errors[] = "نوع السؤال غير صالح";
    }
    
    // التحقق من خيارات السؤال
    if ($questionType == 'multiple_choice') {
        $optionTexts = $_POST['option_text'];
        $correctOption = isset($_POST['correct_option']) ? $_POST['correct_option'] : null;
        
        if (!$correctOption || !isset($optionTexts[$correctOption])) {
            $errors[] = "يجب تحديد خيار صحيح للسؤال";
        }
        
        foreach ($optionTexts as $index => $text) {
            if (empty(trim($text))) {
                $errors[] = "الخيار " . ($index + 1) . " لا يمكن أن يكون فارغاً";
            }
        }
    } elseif ($questionType == 'true_false') {
        $correctOption = isset($_POST['tf_correct']) ? $_POST['tf_correct'] : null;
        
        if ($correctOption === null) {
            $errors[] = "يجب تحديد إجابة صحيحة (صح أو خطأ)";
        }
    }
    
    // إذا لم تكن هناك أخطاء، قم بتحديث السؤال
    if (empty($errors)) {
        // بدء المعاملة
        $conn->begin_transaction();
        
        try {
            // تحديث بيانات السؤال
            $updateQuestionQuery = $conn->prepare("
                UPDATE quiz_questions 
                SET question_text = ?, question_type = ?
                WHERE question_id = ?
            ");
            $updateQuestionQuery->bind_param("ssi", $questionText, $questionType, $questionId);
            $updateQuestionQuery->execute();
            
            // حذف الخيارات الحالية
            $deleteOptionsQuery = $conn->prepare("DELETE FROM question_options WHERE question_id = ?");
            $deleteOptionsQuery->bind_param("i", $questionId);
            $deleteOptionsQuery->execute();
            
            // إضافة الخيارات الجديدة
            if ($questionType == 'multiple_choice') {
                foreach ($optionTexts as $index => $text) {
                    $isCorrect = ($index == $correctOption - 1) ? 1 : 0;
                    $optionOrder = $index + 1;
                    
                    $insertOptionQuery = $conn->prepare("
                        INSERT INTO question_options (
                            question_id, option_text, is_correct, option_order
                        ) VALUES (?, ?, ?, ?)
                    ");
                    $insertOptionQuery->bind_param("isii", $questionId, $text, $isCorrect, $optionOrder);
                    $insertOptionQuery->execute();
                }
            } elseif ($questionType == 'true_false') {
                // إضافة خيار "صح"
                $isTrueCorrect = ($correctOption === 'true') ? 1 : 0;
                $insertTrueQuery = $conn->prepare("
                    INSERT INTO question_options (
                        question_id, option_text, is_correct, option_order
                    ) VALUES (?, 'صح', ?, 1)
                ");
                $insertTrueQuery->bind_param("ii", $questionId, $isTrueCorrect);
                $insertTrueQuery->execute();
                
                // إضافة خيار "خطأ"
                $isFalseCorrect = ($correctOption === 'false') ? 1 : 0;
                $insertFalseQuery = $conn->prepare("
                    INSERT INTO question_options (
                        question_id, option_text, is_correct, option_order
                    ) VALUES (?, 'خطأ', ?, 2)
                ");
                $insertFalseQuery->bind_param("ii", $questionId, $isFalseCorrect);
                $insertFalseQuery->execute();
            }
            
            // تأكيد المعاملة
            $conn->commit();
            
            $_SESSION['success_message'] = "تم تحديث السؤال بنجاح!";
            header("Location: manage_questions.php?quiz_id=" . $quizId);
            exit;
        } catch (Exception $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $conn->rollback();
            $error_message = "حدث خطأ أثناء تحديث السؤال: " . $e->getMessage();
        }
    } else {
        // تجميع الأخطاء لعرضها
        $error_message = "يرجى تصحيح الأخطاء التالية:<br>" . implode("<br>", $errors);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل السؤال</title>
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
            max-width: 800px;
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
            margin-bottom: 30px;
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
        
        .question-type-selector {
            display: flex;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        
        .type-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            background: white;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }
        
        .type-option:first-child {
            border-left: none;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        
        .type-option:last-child {
            border-right: none;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        .type-option i {
            margin-left: 8px;
            font-size: 18px;
        }
        
        .type-option.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            font-weight: 600;
        }
        
        .option-fields {
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-top: 20px;
        }
        
        .option-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            position: relative;
        }
        
        .option-prefix {
            width: 28px;
            height: 28px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-left: 12px;
            flex-shrink: 0;
        }
        
        .option-item.is-correct .option-prefix {
            background: var(--success-color);
        }
        
        .option-item input[type="text"] {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
            transition: var(--transition);
        }
        
        .option-item input[type="text"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            outline: none;
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
        
        .correct-option-radio {
            width: 18px;
            height: 18px;
            margin-left: 10px;
            cursor: pointer;
        }
        
        .tf-option {
            display: flex;
            align-items: center;
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            margin-bottom: 10px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .tf-option:hover {
            background: #f8fafc;
            border-color: #a0aec0;
        }
        
        .tf-option.active {
            background: var(--primary-light);
            border-color: var(--primary-color);
        }
        
        .tf-option input {
            margin-left: 10px;
        }
        
        .form-footer {
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
            
            .form-footer {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-footer .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>تعديل السؤال</h1>
            <a href="manage_questions.php?quiz_id=<?php echo $quizId; ?>" class="back-button">
                <i class='bx bx-arrow-back'></i> العودة إلى الأسئلة
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
            
            <div class="quiz-info">
                <p><strong>الاختبار:</strong> <?php echo htmlspecialchars($question['quiz_title']); ?></p>
            </div>
            
            <form action="edit_question.php?id=<?php echo $questionId; ?>" method="post">
                <div class="form-section">
                    <div class="form-group">
                        <label for="question_text">نص السؤال</label>
                        <textarea name="question_text" id="question_text" class="form-control" required rows="3"><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                    </div>
                    
                    <div class="question-type-selector">
                        <div class="type-option <?php echo ($question['question_type'] === 'multiple_choice') ? 'active' : ''; ?>" data-type="multiple_choice" onclick="selectQuestionType('multiple_choice', this)">
                            <i class='bx bx-list-check'></i> اختيار من متعدد
                        </div>
                        <div class="type-option <?php echo ($question['question_type'] === 'true_false') ? 'active' : ''; ?>" data-type="true_false" onclick="selectQuestionType('true_false', this)">
                            <i class='bx bx-check-circle'></i> صح / خطأ
                        </div>
                    </div>
                    
                    <input type="hidden" name="question_type" id="question_type" value="<?php echo $question['question_type']; ?>">
                    
                    <!-- خيارات السؤال متعدد الاختيارات -->
                    <div id="multiple_choice_options" class="option-fields" style="<?php echo ($question['question_type'] !== 'multiple_choice') ? 'display: none;' : ''; ?>">
                        <h3>خيارات الإجابة</h3>
                        
                        <?php 
                        $mcOptions = array_filter($options, function($opt) {
                            return $opt['option_text'] !== 'صح' && $opt['option_text'] !== 'خطأ';
                        });
                        
                        for ($i = 0; $i < 4; $i++): 
                            $optionValue = isset($mcOptions[$i]) ? $mcOptions[$i]['option_text'] : '';
                            $isCorrect = isset($mcOptions[$i]) && $mcOptions[$i]['is_correct'] ? true : false;
                        ?>
                            <div class="option-item <?php echo $isCorrect ? 'is-correct' : ''; ?>">
                                <input type="radio" name="correct_option" value="<?php echo $i + 1; ?>" class="correct-option-radio" <?php echo $isCorrect ? 'checked' : ''; ?>>
                                <div class="option-prefix"><?php echo chr(65 + $i); ?></div>
                                <input type="text" name="option_text[<?php echo $i; ?>]" class="form-control" placeholder="الخيار #<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($optionValue); ?>" required>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- خيارات السؤال صح/خطأ -->
                    <div id="true_false_options" class="option-fields" style="<?php echo ($question['question_type'] !== 'true_false') ? 'display: none;' : ''; ?>">
                        <h3>الإجابة الصحيحة</h3>
                        
                        <?php
                        $tfOptions = array_filter($options, function($opt) {
                            return $opt['option_text'] === 'صح' || $opt['option_text'] === 'خطأ';
                        });
                        
                        $trueIsCorrect = false;
                        $falseIsCorrect = false;
                        
                        foreach ($tfOptions as $opt) {
                            if ($opt['option_text'] === 'صح' && $opt['is_correct']) {
                                $trueIsCorrect = true;
                            } elseif ($opt['option_text'] === 'خطأ' && $opt['is_correct']) {
                                $falseIsCorrect = true;
                            }
                        }
                        ?>
                        
                        <div class="tf-option <?php echo $trueIsCorrect ? 'active' : ''; ?>">
                            <input type="radio" name="tf_correct" value="true" <?php echo $trueIsCorrect ? 'checked' : ''; ?> onclick="this.closest('.tf-option').classList.add('active'); this.closest('.tf-option').nextElementSibling.classList.remove('active');">
                            <span>صح</span>
                        </div>
                        
                        <div class="tf-option <?php echo $falseIsCorrect ? 'active' : ''; ?>">
                            <input type="radio" name="tf_correct" value="false" <?php echo $falseIsCorrect ? 'checked' : ''; ?> onclick="this.closest('.tf-option').classList.add('active'); this.closest('.tf-option').previousElementSibling.classList.remove('active');">
                            <span>خطأ</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-footer">
                    <a href="manage_questions.php?quiz_id=<?php echo $quizId; ?>" class="btn btn-secondary">
                        <i class='bx bx-x'></i> إلغاء
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-save'></i> حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectQuestionType(type, element) {
            // تحديث النوع المختار
            document.getElementById('question_type').value = type;
            
            // تحديث الفئة النشطة
            const typeOptions = document.querySelectorAll('.type-option');
            typeOptions.forEach(option => option.classList.remove('active'));
            element.classList.add('active');
            
            // إظهار/إخفاء خيارات الإجابة حسب النوع
            if (type === 'multiple_choice') {
                document.getElementById('multiple_choice_options').style.display = 'block';
                document.getElementById('true_false_options').style.display = 'none';
            } else if (type === 'true_false') {
                document.getElementById('multiple_choice_options').style.display = 'none';
                document.getElementById('true_false_options').style.display = 'block';
            }
        }
        
        // تحديث حالة الخيارات عند تغيير الإجابة الصحيحة
        document.querySelectorAll('.correct-option-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                // إزالة فئة "is-correct" من جميع الخيارات
                document.querySelectorAll('.option-item').forEach(item => {
                    item.classList.remove('is-correct');
                });
                
                // إضافة فئة "is-correct" للخيار المحدد
                this.closest('.option-item').classList.add('is-correct');
            });
        });
        
        // Ensure TF options have active class when clicking the radio button
        document.querySelectorAll('input[name="tf_correct"]').forEach(radio => {
            radio.addEventListener('click', function() {
                const tfOptions = document.querySelectorAll('.tf-option');
                tfOptions.forEach(opt => opt.classList.remove('active'));
                this.closest('.tf-option').classList.add('active');
            });
        });
    </script>
</body>
</html>
