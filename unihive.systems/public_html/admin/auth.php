<?php
// filepath: d:\wamp64\www\admin\auth.php
// ملف التحقق من صلاحيات المستخدم

session_start();

// التحقق من صلاحيات الأدمن
function checkAdminAuth() {
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "admin") {
        header("Location: ../index.php");
        exit;
    }
}

// تسجيل الخروج
function logoutUser() {
    session_destroy();
    header("Location: ../index.php");
    exit;
}
?>