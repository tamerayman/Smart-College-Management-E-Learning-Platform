<?php
// filepath: d:\wamp64\www\login_process.php
// ملف معالجة نموذج تسجيل الدخول

require_once 'auth.php';

// معالجة نموذج تسجيل الدخول
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // تنظيف وتأمين المدخلات
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"]; // كلمة المرور الأصلية بدون معالجة لأن دالة التحقق ستقارنها بالهاش
    
    $loginSuccess = loginUser($email, $password);
    
    if (!$loginSuccess) {
        // إذا فشل تسجيل الدخول، إعادة التوجيه مع رسالة خطأ
        header("Location: index.php?error=invalid");
        exit;
    }
}
else {
    // إذا تم الوصول مباشرة للصفحة، إعادة التوجيه للصفحة الرئيسية
    header("Location: index.php");
    exit;
}
?>