<?php
// ملف إعدادات الاتصال بقاعدة البيانات

// بيانات الاتصال بقاعدة البيانات
$host = "localhost";
$dbname = "project";
$username_db = "root";
$password_db = "";

// دالة للتصال بقعدة البينات
function connectDB() {
    global $host, $dbname, $username_db, $password_db;
    
    $conn = new mysqli($host, $username_db, $password_db, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}
?>
