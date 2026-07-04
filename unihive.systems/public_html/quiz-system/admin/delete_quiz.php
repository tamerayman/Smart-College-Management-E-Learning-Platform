<?php
session_start();
require_once '../../config.php';
require_once '../../auth.php';

// التحقق من تسجيل الدخول وصلاحيات المستخدم
if (!isLoggedIn() || $_SESSION["role"] !== "professor") {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// التحقق من وجود معرف الاختبار في الطلب
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_quizzes.php?error=invalid_quiz");
    exit;
}

$quizId = intval($_GET['id']);
$userId = $_SESSION["user_id"];

$conn = connectDB();

// التحقق من أن الاختبار يخص الأستاذ الحالي
$check_stmt = $conn->prepare("
    SELECT quiz_id 
    FROM quizzes 
    WHERE quiz_id = ? AND professor_id = ?
");
$check_stmt->bind_param("ii", $quizId, $userId);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    $conn->close();
    header("Location: manage_quizzes.php?error=unauthorized");
    exit;
}

$check_stmt->close();

// بدء عملية الحذف
$conn->begin_transaction();

try {
    // حذف الخيارات المرتبطة بالأسئلة
    $conn->query("DELETE qo FROM question_options qo 
                  JOIN quiz_questions qq ON qo.question_id = qq.question_id 
                  WHERE qq.quiz_id = $quizId");

    // حذف الأسئلة المرتبطة بالاختبار
    $conn->query("DELETE FROM quiz_questions WHERE quiz_id = $quizId");

    // حذف محاولات الاختبار (إن وجدت)
    $conn->query("DELETE FROM quiz_attempts WHERE quiz_id = $quizId");

    // حذف الاختبار نفسه
    $conn->query("DELETE FROM quizzes WHERE quiz_id = $quizId");

    // تأكيد الحذف
    $conn->commit();
    $conn->close();

    header("Location: manage_quizzes.php?success=quiz_deleted");
    exit;
} catch (Exception $e) {
    // التراجع في حالة حدوث خطأ
    $conn->rollback();
    $conn->close();
    header("Location: manage_quizzes.php?error=delete_failed");
    exit;
}
?>
