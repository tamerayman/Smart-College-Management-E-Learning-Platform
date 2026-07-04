<?php
// filepath: d:\wamp64\www\auth.php
// ملف وظائف المصادقة وإدارة الجلسات

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// دالة التحقق من بيانات المستخدم وتسجيل الدخول
function loginUser($email, $password) {
    $conn = connectDB();
    
    // تسجيل محاولة تسجيل الدخول للتصحيح
    error_log("Login attempt for email: $email");
    
    $stmt = $conn->prepare("SELECT password, role, user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        error_log("User found with role: " . $row["role"]);
        
        // خاص بالمسؤول: التحقق من كلمة المرور بطريقة خاصة إذا كان المسؤول لديه كلمة مرور غير مشفرة
        if ($row["role"] === "admin") {
            // تحقق فقط بـ password_verify
            if (password_verify($password, $row["password"])) {
                // تخزين بيانات المستخدم بالجلسة
                $_SESSION["logged_in"] = true;
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["role"] = $row["role"];
                
                $stmt->close();
                $conn->close();
                
                // توجيه المسؤول
                redirectUserByRole($row["role"]);
                return true;
            }
        } else {
            // للمستخدمين العاديين، استخدم فقط password_verify
            if (password_verify($password, $row["password"])) {
                $_SESSION["logged_in"] = true;
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["role"] = $row["role"];
                
                $stmt->close();
                $conn->close();
                
                // توجيه المستخدم حسب نوع الحساب
                redirectUserByRole($row["role"]);
                return true;
            }
        }
    }
    
    error_log("Login failed for email: $email");
    $stmt->close();
    $conn->close();
    return false;
}

// دالة لتحديث كلمة مرور المسؤول بتشفير صحيح
function updateAdminPasswordHash($email, $password) {
    $conn = connectDB();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("ss", $hashedPassword, $email);
    $stmt->execute();
    
    error_log("Updated admin password hash for: $email");
    
    $stmt->close();
    $conn->close();
}

// دالة توجيه المستخدم بناءً على دوره
function redirectUserByRole($role) {
    switch ($role) {
        case "admin":
            header("Location: admin/admin_dashboard.php");
            exit;
        case "student":
        case "professor":
            header("Location: home/home.php"); // تم تعديل المسار من home.html إلى home.php
            exit;
        default:
            // إذا لم يكن دور معروف
            header("Location: index.php?error=role");
            exit;
    }
}

// دالة التحقق إذا كان المستخدم مسجل الدخول
function isLoggedIn() {
    return isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true;
}

// دالة تسجيل الخروج
function logoutUser() {
    // مسح جميع متغيرات الجلسة
    $_SESSION = array();
    
    // إذا كان هناك كوكيز للجلسة، قم بحذفه
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // تدمير الجلسة
    session_destroy();
    
    // إعادة توجيه المستخدم إلى صفحة تسجيل الدخول
    header("Location: index.php");
    exit;
}
?>