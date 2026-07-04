<?php
// filepath: quiz-system/admin/create_quiz.php
session_start();
#require_once '../includes/config.php';
require_once '../../config.php';
require_once '../../auth.php';
#require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المستخدم
if (!isLoggedIn() || $_SESSION["role"] !== "professor") {
    header("Location: ../index.php");
    exit;
}

// معالجة نموذج إنشاء الاختبار
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = []; // Array to track validation errors
    
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
    } else {
        $expirationDateTime = new DateTime($expiration_time);
        $now = new DateTime();
        if ($expirationDateTime <= $now) {
            $errors[] = "يجب أن يكون موعد انتهاء الاختبار في المستقبل";
        }
    }
    
    if ($courseId <= 0) {
        $errors[] = "يجب اختيار المادة الدراسية";
    }
    
    // التحقق من وجود أسئلة
    if (!isset($_POST['question_text']) || !is_array($_POST['question_text']) || count($_POST['question_text']) == 0) {
        $errors[] = "يجب إضافة سؤال واحد على الأقل";
    } else {
        // التحقق من صحة الأسئلة
        foreach ($_POST['question_text'] as $index => $questionText) {
            if (empty(trim($questionText))) {
                $errors[] = "نص السؤال #" . ($index + 1) . " لا يمكن أن يكون فارغاً";
                continue;
            }
            
            $questionType = $_POST['question_type'][$index] ?? '';
            if (!in_array($questionType, ['multiple_choice', 'true_false'])) {
                $errors[] = "نوع السؤال #" . ($index + 1) . " غير صالح";
                continue;
            }
            
            if ($questionType == 'multiple_choice') {
                // التحقق من الخيارات للسؤال متعدد الاختيارات
                for ($i = 1; $i <= 4; $i++) {
                    $optionKey = "option_{$index}_{$i}";
                    if (!isset($_POST[$optionKey]) || empty(trim($_POST[$optionKey]))) {
                        $errors[] = "الخيار {$i} للسؤال #" . ($index + 1) . " لا يمكن أن يكون فارغاً";
                    }
                }
                
                // التأكد من وجود إجابة صحيحة للسؤال متعدد الخيارات
                if (!isset($_POST['correct_answer_mc'][$index])) {
                    if (isset($_POST['correct_answer'][$index]) && in_array($_POST['correct_answer'][$index], ['1', '2', '3', '4'])) {
                        // استخدام القيمة من الحقل العام إذا كانت صالحة
                        $_POST['correct_answer_mc'][$index] = $_POST['correct_answer'][$index];
                    } else {
                        // استخدام الخيار الأول كإجابة افتراضية
                        $_POST['correct_answer_mc'][$index] = '1';
                    }
                }
            } else if ($questionType == 'true_false') {
                // التأكد من وجود إجابة صحيحة للسؤال صح/خطأ
                if (!isset($_POST['correct_answer_tf'][$index])) {
                    if (isset($_POST['correct_answer'][$index]) && in_array($_POST['correct_answer'][$index], ['true', 'false'])) {
                        // استخدام القيمة من الحقل العام إذا كانت صالحة
                        $_POST['correct_answer_tf'][$index] = $_POST['correct_answer'][$index];
                    } else {
                        // استخدام "صح" كإجابة افتراضية
                        $_POST['correct_answer_tf'][$index] = 'true';
                    }
                }
            }
        }
    }
    
    // إذا لم تكن هناك أخطاء، قم بإنشاء الاختبار
    if (empty($errors)) {
        $conn = connectDB();
        
        try {
            // بدء المعاملة
            $conn->begin_transaction();
            
            // إعداد تاريخ بدء الاختبار (الآن)
            $start_time = date('Y-m-d H:i:s');
            
            // إدخال بيانات الاختبار
            $stmt = $conn->prepare("
                INSERT INTO quizzes (
                    title, 
                    time_limit, 
                    start_time, 
                    end_time, 
                    course_id, 
                    professor_id, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $created_by = $_SESSION["user_id"];
            $stmt->bind_param("ssssii", $title, $duration, $start_time, $expiration_time, $courseId, $created_by);
            $stmt->execute();
            
            $quizId = $conn->insert_id;
            
            // إدخال الأسئلة وخياراتها
            foreach ($_POST['question_text'] as $index => $questionText) {
                if (empty(trim($questionText))) continue;
                
                $questionType = $_POST['question_type'][$index];
                $questionOrder = $index + 1;
                
                // إدخال السؤال
                $q_stmt = $conn->prepare("
                    INSERT INTO quiz_questions (
                        quiz_id, 
                        question_text, 
                        question_type, 
                        question_order
                    ) VALUES (?, ?, ?, ?)
                ");
                $q_stmt->bind_param("issi", $quizId, $questionText, $questionType, $questionOrder);
                $q_stmt->execute();
                
                $questionId = $conn->insert_id;
                
                // إدخال خيارات السؤال
                if ($questionType == 'multiple_choice') {
                    // استخدام الإجابة الصحيحة من الحقل المخصص
                    $correctAnswer = isset($_POST['correct_answer_mc'][$index]) ? $_POST['correct_answer_mc'][$index] : $_POST['correct_answer'][$index];
                    
                    for ($i = 1; $i <= 4; $i++) {
                        $optionText = $_POST["option_{$index}_{$i}"];
                        $isCorrect = ($correctAnswer == $i) ? 1 : 0;
                        $optionOrder = $i;
                        
                        $opt_stmt = $conn->prepare("
                            INSERT INTO question_options (
                                question_id, 
                                option_text, 
                                is_correct, 
                                option_order
                            ) VALUES (?, ?, ?, ?)
                        ");
                        $opt_stmt->bind_param("isii", $questionId, $optionText, $isCorrect, $optionOrder);
                        $opt_stmt->execute();
                    }
                } elseif ($questionType == 'true_false') {
                    // استخدام الإجابة الصحيحة من الحقل المخصص
                    $correctAnswer = isset($_POST['correct_answer_tf'][$index]) ? $_POST['correct_answer_tf'][$index] : $_POST['correct_answer'][$index];
                    
                    // إدخال خيار "صح"
                    $isTrue = ($correctAnswer == 'true') ? 1 : 0;
                    $true_stmt = $conn->prepare("
                        INSERT INTO question_options (
                            question_id, 
                            option_text, 
                            is_correct, 
                            option_order
                        ) VALUES (?, 'صح', ?, 1)
                    ");
                    $true_stmt->bind_param("ii", $questionId, $isTrue);
                    $true_stmt->execute();
                    
                    // إدخال خيار "خطأ"
                    $isFalse = ($correctAnswer == 'false') ? 1 : 0;
                    $false_stmt = $conn->prepare("
                        INSERT INTO question_options (
                            question_id, 
                            option_text, 
                            is_correct, 
                            option_order
                        ) VALUES (?, 'خطأ', ?, 2)
                    ");
                    $false_stmt->bind_param("ii", $questionId, $isFalse);
                    $false_stmt->execute();
                }
            }
            
            // تأكيد المعاملة
            $conn->commit();
            
            // تسجيل رسالة النجاح
            $_SESSION['success_message'] = "تم إنشاء الاختبار بنجاح!";
            
            // إعادة التوجيه لمنع إعادة إرسال النموذج
            header("Location: manage_quizzes.php");
            exit;
            
        } catch (Exception $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $conn->rollback();
            $error_message = "حدث خطأ أثناء إنشاء الاختبار: " . $e->getMessage();
        }
        
        $conn->close();
    } else {
        // تجميع الأخطاء لعرضها
        $error_message = "يرجى تصحيح الأخطاء التالية:<br>" . implode("<br>", $errors);
    }
}

// جلب المواد الدراسية المتاحة للأستاذ
$conn = connectDB();
$subjects = [];

try {
    // الاستعلام عن المواد الدراسية التي يدرسها الأستاذ
    $subject_stmt = $conn->prepare("
        SELECT c.course_id, c.name 
        FROM courses c
        JOIN professor_courses pc ON c.course_id = pc.course_id
        WHERE pc.professor_id = ?
        ORDER BY c.name ASC
    ");
    
    if (!$subject_stmt) {
        throw new Exception("خطأ في إعداد استعلام المواد الدراسية: " . $conn->error);
    }
    
    $professor_id = $_SESSION["user_id"];
    $subject_stmt->bind_param("i", $professor_id);
    $subject_stmt->execute();
    $result = $subject_stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    } else {
        throw new Exception("فشل في استرجاع المواد الدراسية");
    }
    
    $subject_stmt->close();
} catch (Exception $e) {
    // تسجيل الخطأ
    error_log("خطأ في استرجاع المواد الدراسية: " . $e->getMessage());
    // إعداد رسالة خطأ للمستخدم
    $subject_error = "حدث خطأ في استرجاع المواد الدراسية. يرجى التحقق من اتصالك بقاعدة البيانات.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء اختبار جديد</title>
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
        
        .form-stepper {
            display: flex;
            margin-bottom: 30px;
            position: relative;
            justify-content: space-between;
        }
        
        .form-stepper::before {
            content: "";
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-circle {
            width: 32px;
            height: 32px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--gray-color);
            transition: var(--transition);
        }
        
        .step-title {
            font-size: 14px;
            color: var(--gray-color);
            transition: var(--transition);
        }
        
        .step.active .step-circle {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .step.active .step-title {
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .step.completed .step-circle {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
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
        
        .quiz-preview {
            background: var(--primary-light);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-top: 25px;
            border-right: 4px solid var(--primary-color);
        }
        
        .quiz-preview-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 15px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .quiz-preview-title i {
            margin-left: 8px;
            font-size: 18px;
        }
        
        .quiz-preview-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .quiz-preview-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }
        
        .quiz-preview-item i {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            color: white;
            font-size: 16px;
        }
        
        .quiz-preview-item:nth-child(1) i {
            background: #4361ee;
        }
        
        .quiz-preview-item:nth-child(2) i {
            background: #f72585;
        }
        
        .quiz-preview-item:nth-child(3) i {
            background: #7209b7;
        }
        
        .quiz-preview-item:nth-child(4) i {
            background: #3a0ca3;
        }
        
        .quiz-preview-item div {
            flex: 1;
        }
        
        .quiz-preview-label {
            font-size: 12px;
            color: #64748b;
            display: block;
        }
        
        .quiz-preview-value {
            font-weight: 600;
            color: var(--dark-color);
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
        
        .question-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-right: 4px solid var(--primary-color);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        
        .question-container:hover {
            box-shadow: var(--shadow-md);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .question-number {
            display: flex;
            align-items: center;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 16px;
        }
        
        .question-number::before {
            content: "";
            display: block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            margin-left: 8px;
        }
        
        .question-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            transition: var(--transition);
        }
        
        .remove-question {
            background-color: var(--danger-color);
        }
        
        .duplicate-question {
            background-color: var(--primary-color);
        }
        
        .move-question {
            background-color: var(--gray-color);
            cursor: move;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
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
        
        .option-item.correct-option .option-prefix {
            background: var(--success-color);
        }
        
        .option-item .make-correct {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #cbd5e1;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .option-item.correct-option .make-correct {
            border-color: var(--success-color);
            background: var(--success-color);
            color: white;
        }
        
        .add-question-btn {
            background: var(--primary-light);
            color: var(--primary-color);
            border: 2px dashed var(--primary-color);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            width: 100%;
            max-width: 400px;
        }
        
        .add-question-btn i {
            margin-left: 8px;
            font-size: 20px;
        }
        
        .add-question-btn:hover {
            background: var(--primary-color);
            color: white;
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
        
        .question-counter {
            background: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 13px;
            margin-right: 10px;
            font-weight: 500;
        }
        
        .dragging {
            opacity: 0.5;
            border: 2px dashed var(--primary-color);
        }
        
        .drag-over {
            border-top: 3px solid var(--primary-color);
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
            
            .form-stepper {
                overflow-x: auto;
                padding-bottom: 10px;
            }
            
            .step {
                margin: 0 15px;
                min-width: 100px;
            }
            
            .quiz-preview-info {
                grid-template-columns: 1fr;
            }
            
            .btn {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .quiz-form-footer {
                flex-direction: column;
                gap: 15px;
            }
            
            .quiz-form-footer .btn {
                width: 100%;
            }
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>إنشاء اختبار جديد</h1>
            <a href="manage_quizzes.php" class="back-button">
                <i class='bx bx-arrow-back'></i> العودة إلى الاختبارات
            </a>
        </div>
        
        <div class="form-container">
            <div class="form-stepper">
                <div class="step active">
                    <div class="step-circle">1</div>
                    <div class="step-title">معلومات الاختبار</div>
                </div>
                <div class="step" id="step2">
                    <div class="step-circle">2</div>
                    <div class="step-title">إضافة الأسئلة</div>
                </div>
                <div class="step" id="step3">
                    <div class="step-circle">3</div>
                    <div class="step-title">مراجعة ونشر</div>
                </div>
            </div>
        
            <?php if (!empty($success_message)): ?>
                <div class="success-alert">
                    <i class='bx bx-check-circle'></i> 
                    <div><?php echo $success_message; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
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
            
            <form action="create_quiz.php" method="post" id="quizForm">
                <div class="form-section fade-in">
                    <h2 class="form-section-title">
                        <i class='bx bx-info-circle'></i> معلومات الاختبار الأساسية
                    </h2>
                    
                    <div class="quiz-settings">
                        <div class="form-group">
                            <label for="title">عنوان الاختبار</label>
                            <input type="text" name="title" id="title" class="form-control" required 
                                placeholder="أدخل عنوان الاختبار هنا..." value="<?php echo $_POST['title'] ?? ''; ?>">
                            <p class="form-help-text">اختر عنوانًا واضحًا وموجزًا يصف محتوى الاختبار</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">مدة الاختبار (بالدقائق)</label>
                            <input type="number" name="duration" id="duration" class="form-control" required min="1" 
                                placeholder="مثال: 60" value="<?php echo $_POST['duration'] ?? '30'; ?>">
                            <p class="form-help-text">المدة التي سيُتاح للطالب حل الاختبار خلالها</p>
                        </div>
                    </div>
                    
                    <div class="quiz-settings">
                        <div class="form-group">
                            <label for="expiration_time">موعد انتهاء الاختبار</label>
                            <input type="datetime-local" name="expiration_time" id="expiration_time" class="form-control" required
                                value="<?php echo $_POST['expiration_time'] ?? ''; ?>">
                            <p class="form-help-text">آخر موعد يمكن للطلاب بدء محاولة الاختبار فيه</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject_id">المادة الدراسية</label>
                            <select name="subject_id" id="subject_id" class="form-control" required>
                                <option value="">-- اختر المادة --</option>
                                <?php if (!empty($subjects)): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['course_id']; ?>" <?php echo (isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['course_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>لا توجد مواد دراسية متاحة</option>
                                <?php endif; ?>
                            </select>
                            <?php if (isset($subject_error)): ?>
                                <p class="error-message"><?php echo $subject_error; ?></p>
                            <?php else: ?>
                                <p class="form-help-text">المادة التي سيظهر فيها هذا الاختبار للطلاب</p>
                                <?php if (empty($subjects)): ?>
                                    <p class="form-help-text" style="color: #e74c3c;">
                                        لم يتم العثور على أي مواد دراسية. يرجى التأكد من تعيينك كأستاذ لمادة واحدة على الأقل.
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="quiz-preview" id="quizPreview">
                        <div class="quiz-preview-title">
                            <i class='bx bx-info-circle'></i> معاينة معلومات الاختبار
                        </div>
                        <div class="quiz-preview-info">
                            <div class="quiz-preview-item">
                                <i class='bx bx-book-open'></i>
                                <div>
                                    <span class="quiz-preview-label">عنوان الاختبار</span>
                                    <span class="quiz-preview-value" id="previewTitle">-</span>
                                </div>
                            </div>
                            <div class="quiz-preview-item">
                                <i class='bx bx-time'></i>
                                <div>
                                    <span class="quiz-preview-label">مدة الاختبار</span>
                                    <span class="quiz-preview-value">
                                        <span id="previewDuration">-</span> دقيقة
                                    </span>
                                </div>
                            </div>
                            <div class="quiz-preview-item">
                                <i class='bx bx-calendar-exclamation'></i>
                                <div>
                                    <span class="quiz-preview-label">متاح حتى</span>
                                    <span class="quiz-preview-value" id="previewExpiration">-</span>
                                </div>
                            </div>
                            <div class="quiz-preview-item">
                                <i class='bx bx-book'></i>
                                <div>
                                    <span class="quiz-preview-label">المادة الدراسية</span>
                                    <span class="quiz-preview-value" id="previewCourse">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section fade-in">
                    <h2 class="form-section-title">
                        <i class='bx bx-question-mark'></i> أسئلة الاختبار
                        <span class="question-counter" id="questionCounter">0 أسئلة</span>
                    </h2>
                    
                    <div id="questions-container">
                        <!-- Questions will be added here dynamically -->
                    </div>
                    
                    <button type="button" class="add-question-btn" onclick="addQuestion()" id="addQuestionBtn">
                        <i class='bx bx-plus-circle'></i> إضافة سؤال جديد
                    </button>
                </div>
                
                <div class="quiz-form-footer">
                    <button type="button" class="btn btn-secondary" id="saveAsDraftBtn">
                        <i class='bx bx-save'></i> حفظ كمسودة
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitButton">
                        <i class='bx bx-check-circle'></i> نشر الاختبار
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Question template -->
    <template id="question-template">
        <div class="question-container fade-in" draggable="true">
            <div class="question-header">
                <div class="question-number">السؤال <span class="question-index">1</span></div>
                <div class="question-actions">
                    <button type="button" class="action-btn duplicate-question" title="نسخ السؤال" onclick="duplicateQuestion(this)">
                        <i class='bx bx-duplicate'></i>
                    </button>
                    <button type="button" class="action-btn remove-question" title="حذف السؤال" onclick="removeQuestion(this)">
                        <i class='bx bx-trash'></i>
                    </button>
                    <div class="action-btn move-question" title="سحب لإعادة الترتيب">
                        <i class='bx bx-move'></i>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="question_text_IDX">نص السؤال</label>
                <textarea name="question_text[IDX]" id="question_text_IDX" class="form-control" required 
                    placeholder="اكتب نص السؤال هنا..."></textarea>
            </div>
            
            <div class="question-type-selector">
                <div class="type-option" onclick="selectQuestionType(IDX, 'multiple_choice', this)" data-type="multiple_choice">
                    <i class='bx bx-list-check'></i> اختيار من متعدد
                </div>
                <div class="type-option" onclick="selectQuestionType(IDX, 'true_false', this)" data-type="true_false">
                    <i class='bx bx-check-circle'></i> صح / خطأ
                </div>
            </div>
            
            <input type="hidden" name="question_type[IDX]" id="question_type_IDX" required value="">
            
            <div id="multipleChoiceOptions_IDX" class="option-fields" style="display: none;">
                <div class="option-item" id="option_item_IDX_1">
                    <div class="option-prefix">أ</div>
                    <input type="text" name="option_IDX_1" id="option_IDX_1" class="form-control" 
                        placeholder="الخيار الأول">
                    <div class="make-correct" onclick="setCorrectOption(IDX, 1, this)"></div>
                </div>
                <div class="option-item" id="option_item_IDX_2">
                    <div class="option-prefix">ب</div>
                    <input type="text" name="option_IDX_2" id="option_IDX_2" class="form-control" 
                        placeholder="الخيار الثاني">
                    <div class="make-correct" onclick="setCorrectOption(IDX, 2, this)"></div>
                </div>
                <div class="option-item" id="option_item_IDX_3">
                    <div class="option-prefix">ج</div>
                    <input type="text" name="option_IDX_3" id="option_IDX_3" class="form-control" 
                        placeholder="الخيار الثالث">
                    <div class="make-correct" onclick="setCorrectOption(IDX, 3, this)"></div>
                </div>
                <div class="option-item" id="option_item_IDX_4">
                    <div class="option-prefix">د</div>
                    <input type="text" name="option_IDX_4" id="option_IDX_4" class="form-control" 
                        placeholder="الخيار الرابع">
                    <div class="make-correct" onclick="setCorrectOption(IDX, 4, this)"></div>
                </div>
                <input type="hidden" name="correct_answer[IDX]" id="correct_answer_mc_IDX" value="1">
            </div>
            
            <div id="trueFalseOptions_IDX" class="option-fields" style="display: none;">
                <div class="option-item correct-option" id="tf_option_item_IDX_true">
                    <div class="option-prefix">أ</div>
                    <input type="text" value="صح" disabled class="form-control">
                    <div class="make-correct" onclick="setTrueFalseOption(IDX, 'true', this)"><i class='bx bx-check'></i></div>
                </div>
                <div class="option-item" id="tf_option_item_IDX_false">
                    <div class="option-prefix">ب</div>
                    <input type="text" value="خطأ" disabled class="form-control">
                    <div class="make-correct" onclick="setTrueFalseOption(IDX, 'false', this)"></div>
                </div>
                <input type="hidden" name="correct_answer[IDX]" id="correct_answer_tf_IDX" value="true">
            </div>
        </div>
    </template>

    <script>
        let questionCount = 0;
        let questionOrder = [];
        
        // Form validation and preview
        document.addEventListener('DOMContentLoaded', function() {
            // Add first question when page loads
            addQuestion();
            
            // Set up drag and drop for questions
            setupDragDrop();
            
            // Update step indicators based on form progress
            const formInputs = document.querySelectorAll('.form-section:first-child input, .form-section:first-child select');
            formInputs.forEach(input => {
                input.addEventListener('change', checkStepProgress);
            });
            
            // Form preview functionality
            const title = document.getElementById('title');
            const duration = document.getElementById('duration');
            const expirationTime = document.getElementById('expiration_time');
            const subjectId = document.getElementById('subject_id');
            
            // Update preview when inputs change
            title.addEventListener('input', updatePreview);
            duration.addEventListener('input', updatePreview);
            expirationTime.addEventListener('input', updatePreview);
            subjectId.addEventListener('change', updatePreview);
            
            // Initial preview update
            updatePreview();
            
            // Save as draft button
            document.getElementById('saveAsDraftBtn').addEventListener('click', function() {
                // You could implement draft saving functionality here
                alert('تم حفظ الاختبار كمسودة');
            });
            
            // Form validation before submit
            document.getElementById('quizForm').addEventListener('submit', function(e) {
                if (questionCount === 0) {
                    e.preventDefault();
                    alert('يجب إضافة سؤال واحد على الأقل للاختبار');
                    return false;
                }
                
                // Check if all required fields are filled
                let emptyFields = [];
                const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
                requiredInputs.forEach(input => {
                    if (!input.value.trim()) {
                        emptyFields.push(input.getAttribute('placeholder') || input.getAttribute('name'));
                        input.classList.add('error');
                    }
                });
                
                if (emptyFields.length > 0) {
                    e.preventDefault();
                    alert('يرجى ملء جميع الحقول المطلوبة: ' + emptyFields.join(', '));
                    return false;
                }
                
                // Show loading state
                const submitBtn = document.getElementById('submitButton');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading-spinner"></span> جاري النشر...';
                
                // Mark step 3 as active before submission
                document.getElementById('step3').classList.add('active');
            });
            
            // Check initial step progress
            checkStepProgress();
        });
        
        function checkStepProgress() {
            // Check if all required fields in step 1 are filled
            const step1Inputs = document.querySelectorAll('.form-section:first-child input[required], .form-section:first-child select[required]');
            let step1Complete = true;
            
            step1Inputs.forEach(input => {
                if (!input.value.trim()) {
                    step1Complete = false;
                }
            });
            
            // Update step 2 status
            if (step1Complete) {
                document.getElementById('step2').classList.add('active');
            } else {
                document.getElementById('step2').classList.remove('active');
            }
            
            // Update step 3 status based on having questions
            if (questionCount > 0 && step1Complete) {
                document.getElementById('step3').classList.add('active');
            } else {
                document.getElementById('step3').classList.remove('active');
            }
        }
        
        function setupDragDrop() {
            const container = document.getElementById('questions-container');
            
            container.addEventListener('dragstart', function(e) {
                if (e.target.classList.contains('question-container')) {
                    e.target.classList.add('dragging');
                    e.dataTransfer.setData('text/plain', Array.from(container.children).indexOf(e.target));
                }
            });
            
            container.addEventListener('dragend', function(e) {
                if (e.target.classList.contains('question-container')) {
                    e.target.classList.remove('dragging');
                }
            });
            
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                const dragging = document.querySelector('.dragging');
                if (!dragging) return;
                
                const afterElement = getDragAfterElement(container, e.clientY);
                if (afterElement == null) {
                    container.appendChild(dragging);
                } else {
                    container.insertBefore(dragging, afterElement);
                }
                
                // Remove any existing drag-over indicators
                document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                
                // Add indicator to the element we're hovering over
                if (afterElement) {
                    afterElement.classList.add('drag-over');
                }
            });
            
            container.addEventListener('dragleave', function(e) {
                if (e.target.classList.contains('question-container')) {
                    e.target.classList.remove('drag-over');
                }
            });
            
            container.addEventListener('drop', function(e) {
                e.preventDefault();
                document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                updateQuestionNumbers();
            });
        }
        
        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.question-container:not(.dragging)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
        
        function updatePreview() {
            const title = document.getElementById('title').value || '-';
            const duration = document.getElementById('duration').value || '-';
            const expirationTime = document.getElementById('expiration_time').value;
            const subjectId = document.getElementById('subject_id');
            
            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewDuration').textContent = duration;
            
            if (expirationTime) {
                const formattedDate = new Date(expirationTime).toLocaleString('ar-EG');
                document.getElementById('previewExpiration').textContent = formattedDate;
            } else {
                document.getElementById('previewExpiration').textContent = '-';
            }
            
            if (subjectId.selectedIndex > 0) {
                document.getElementById('previewCourse').textContent = subjectId.options[subjectId.selectedIndex].text;
            } else {
                document.getElementById('previewCourse').textContent = '-';
            }
            
            // Update step status
            checkStepProgress();
        }
        
        function addQuestion() {
            try {
                const container = document.getElementById('questions-container');
                const template = document.getElementById('question-template');
                
                if (!template) {
                    console.error('Template element not found');
                    return;
                }
                
                // Clone the template content
                const clone = template.content.cloneNode(true);
                
                // Replace all IDX with current question count
                const elements = clone.querySelectorAll('[id*="IDX"], [name*="IDX"], [onclick*="IDX"]');
                elements.forEach(element => {
                    if (element.id) {
                        element.id = element.id.replace(/IDX/g, questionCount);
                    }
                    if (element.name) {
                        element.name = element.name.replace(/IDX/g, questionCount);
                    }
                    if (element.getAttribute('onclick')) {
                        const onclickValue = element.getAttribute('onclick').replace(/IDX/g, questionCount);
                        element.setAttribute('onclick', onclickValue);
                    }
                });
                
                // Update the question number
                const questionIndex = clone.querySelector('.question-index');
                if (questionIndex) {
                    questionIndex.textContent = questionCount + 1;
                }
                
                // Append the clone to the container
                container.appendChild(clone);
                
                // Set a default question type
                const hiddenInput = document.getElementById(`question_type_${questionCount}`);
                if (hiddenInput) {
                    hiddenInput.value = 'multiple_choice';
                    
                    // Set the first option as active
                    const firstOption = document.querySelector(`.question-container:nth-child(${questionCount + 1}) .type-option[data-type="multiple_choice"]`);
                    if (firstOption) {
                        firstOption.classList.add('active');
                        
                        // Show multiple choice options
                        const mcOptions = document.getElementById(`multipleChoiceOptions_${questionCount}`);
                        if (mcOptions) {
                            mcOptions.style.display = 'block';
                        }
                    }
                }
                
                // Set the first option as correct by default for MC
                setCorrectOption(questionCount, 1, document.querySelector(`#option_item_${questionCount}_1 .make-correct`));
                
                // Increment question count
                questionCount++;
                updateQuestionCounter();
                
                // Update step progress
                checkStepProgress();
                
                // Scroll to the new question
                const newQuestion = document.querySelector('.question-container:last-child');
                newQuestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                console.log(`Question added. Total: ${questionCount}`);
            } catch (error) {
                console.error('Error adding question:', error);
            }
        }
        
        function duplicateQuestion(button) {
            const questionContainer = button.closest('.question-container');
            const questionIndex = parseInt(questionContainer.querySelector('.question-index').textContent) - 1;
            
            // Get all the data from the current question
            const questionText = document.getElementById(`question_text_${questionIndex}`).value;
            const questionType = document.getElementById(`question_type_${questionIndex}`).value;
            const correctAnswer = questionType === 'multiple_choice' ? 
                document.getElementById(`correct_answer_mc_${questionIndex}`).value : 
                document.getElementById(`correct_answer_tf_${questionIndex}`).value;
            
            // Add a new question
            addQuestion();
            
            // Set the values for the new question (which will be at questionCount-1)
            const newIndex = questionCount - 1;
            document.getElementById(`question_text_${newIndex}`).value = questionText;
            
            // Set question type
            selectQuestionType(newIndex, questionType, document.querySelector(`#question_type_${newIndex}`).parentElement.querySelector(`.type-option[data-type="${questionType}"]`));
            
            // Set options and correct answer for multiple choice
            if (questionType === 'multiple_choice') {
                for (let i = 1; i <= 4; i++) {
                    document.getElementById(`option_${newIndex}_${i}`).value = document.getElementById(`option_${questionIndex}_${i}`).value;
                }
                
                // Set correct answer
                setCorrectOption(newIndex, correctAnswer, document.querySelector(`#option_item_${newIndex}_${correctAnswer} .make-correct`));
            } else {
                // Set correct answer for true/false
                setTrueFalseOption(newIndex, correctAnswer, document.querySelector(`#tf_option_item_${newIndex}_${correctAnswer} .make-correct`));
            }
            
            // Show success message
            const notification = document.createElement('div');
            notification.classList.add('success-alert', 'fade-in');
            notification.innerHTML = '<i class="bx bx-check-circle"></i><div>تم نسخ السؤال بنجاح</div>';
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.left = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '1000';
            document.body.appendChild(notification);
            
            // Remove the notification after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        function removeQuestion(button) {
            const questionContainer = button.closest('.question-container');
            
            // Add fade-out animation
            questionContainer.style.opacity = '0';
            questionContainer.style.transform = 'scale(0.9)';
            
            // Remove after animation completes
            setTimeout(() => {
                questionContainer.remove();
                updateQuestionNumbers();
                
                // Update question count and counter
                questionCount--;
                updateQuestionCounter();
                
                // Update step progress
                checkStepProgress();
            }, 300);
        }
        
        function updateQuestionNumbers() {
            const questions = document.querySelectorAll('.question-container');
            questions.forEach((question, index) => {
                const questionNumberSpan = question.querySelector('.question-index');
                if (questionNumberSpan) {
                    questionNumberSpan.textContent = index + 1;
                }
            });
        }
        
        function updateQuestionCounter() {
            const counter = document.getElementById('questionCounter');
            if (counter) {
                const text = questionCount === 1 ? 'سؤال واحد' : 
                             questionCount === 2 ? 'سؤالان' : 
                             questionCount + ' أسئلة';
                counter.textContent = text;
            }
            
            // Add visual cue if there are no questions
            const addQuestionBtn = document.getElementById('addQuestionBtn');
            if (questionCount === 0) {
                addQuestionBtn.classList.add('pulse');
            } else {
                addQuestionBtn.classList.remove('pulse');
            }
        }
        
        function selectQuestionType(idx, type, clickedOption) {
            // Update hidden input with the selected type
            document.getElementById(`question_type_${idx}`).value = type;
            
            // Toggle option display
            const mcOptions = document.getElementById(`multipleChoiceOptions_${idx}`);
            const tfOptions = document.getElementById(`trueFalseOptions_${idx}`);
            
            // Update active class on type options
            const typeOptions = clickedOption.parentElement.querySelectorAll('.type-option');
            typeOptions.forEach(option => {
                option.classList.remove('active');
            });
            clickedOption.classList.add('active');
            
            if (type === 'multiple_choice') {
                mcOptions.style.display = 'block';
                tfOptions.style.display = 'none';
                
                // Make multiple choice fields required
                document.getElementById(`option_${idx}_1`).required = true;
                document.getElementById(`option_${idx}_2`).required = true;
                document.getElementById(`option_${idx}_3`).required = true;
                document.getElementById(`option_${idx}_4`).required = true;
                
                // Set the hidden input to use MC answer
                document.getElementById(`correct_answer_mc_${idx}`).disabled = false;
                document.getElementById(`correct_answer_tf_${idx}`).disabled = true;
            } else if (type === 'true_false') {
                mcOptions.style.display = 'none';
                tfOptions.style.display = 'block';
                
                // Remove required from multiple choice fields
                document.getElementById(`option_${idx}_1`).required = false;
                document.getElementById(`option_${idx}_2`).required = false;
                document.getElementById(`option_${idx}_3`).required = false;
                document.getElementById(`option_${idx}_4`).required = false;
                
                // Set the hidden input to use TF answer
                document.getElementById(`correct_answer_mc_${idx}`).disabled = true;
                document.getElementById(`correct_answer_tf_${idx}`).disabled = false;
            }
        }
        
        function setCorrectOption(idx, optionNum, element) {
            // Clear all correct options first
            const allOptions = document.querySelectorAll(`[id^="option_item_${idx}_"]`);
            allOptions.forEach(opt => opt.classList.remove('correct-option'));
            
            // Set the clicked option as correct
            const optionItem = document.getElementById(`option_item_${idx}_${optionNum}`);
            optionItem.classList.add('correct-option');
            
            // Update the hidden correct answer field
            document.getElementById(`correct_answer_mc_${idx}`).value = optionNum;
            
            // Add a checkmark to the selected option
            const allCheckmarks = document.querySelectorAll(`[id^="option_item_${idx}_"] .make-correct i`);
            allCheckmarks.forEach(check => check.remove());
            
            const checkmark = document.createElement('i');
            checkmark.className = 'bx bx-check';
            element.appendChild(checkmark);
        }
        
        function setTrueFalseOption(idx, value, element) {
            // Clear all correct options first
            document.getElementById(`tf_option_item_${idx}_true`).classList.remove('correct-option');
            document.getElementById(`tf_option_item_${idx}_false`).classList.remove('correct-option');
            
            // Set the clicked option as correct
            document.getElementById(`tf_option_item_${idx}_${value}`).classList.add('correct-option');
            
            // Update the hidden correct answer field
            document.getElementById(`correct_answer_tf_${idx}`).value = value;
            
            // Add a checkmark to the selected option
            const allCheckmarks = document.querySelectorAll(`[id^="tf_option_item_${idx}_"] .make-correct i`);
            allCheckmarks.forEach(check => check.remove());
            
            const checkmark = document.createElement('i');
            checkmark.className = 'bx bx-check';
            element.appendChild(checkmark);
        }
    </script>
</body>
</html>