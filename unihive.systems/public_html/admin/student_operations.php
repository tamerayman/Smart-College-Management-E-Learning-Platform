<?php
// filepath: d:\wamp64\www\admin\student_operations.php
// عمليات إدارة الطلاب

require_once 'config.php';

// إضافة طالب جديد
function addStudent($studentEmail, $studentPassword, $studentName, $studentLevel, $deptId) {
    $conn = connectDB();
    
    // إدخال المستخدم في جدول Users
    $stmt = $conn->prepare("INSERT INTO Users (email, password, role) VALUES (?, ?, 'student')");
    $stmt->bind_param("ss", $studentEmail, $studentPassword);
    $stmt->execute();
    $newUserId = $stmt->insert_id;
    $stmt->close();
    
    // إدخال بيانات الطالب في جدول Students
    $stmt = $conn->prepare("INSERT INTO Students (student_id, name, level, department_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isii", $newUserId, $studentName, $studentLevel, $deptId);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    return true;
}

// تحديث بيانات طالب
function updateStudent($studentId, $studentEmail, $studentName, $studentLevel, $deptId) {
    $conn = connectDB();
    
    // تحديث بيانات المستخدم
    $stmt = $conn->prepare("UPDATE Users SET email=? WHERE user_id=?");
    $stmt->bind_param("si", $studentEmail, $studentId);
    $stmt->execute();
    $stmt->close();
    
    // تحديث بيانات الطالب
    $stmt = $conn->prepare("UPDATE Students SET name=?, level=?, department_id=? WHERE student_id=?");
    $stmt->bind_param("siii", $studentName, $studentLevel, $deptId, $studentId);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    return true;
}

// حذف طالب
function deleteStudent($studentId) {
    $conn = connectDB();
    
    // حذف بيانات الطالب
    $stmt = $conn->prepare("DELETE FROM Students WHERE student_id=?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $stmt->close();
    
    // حذف بيانات المستخدم
    $stmt = $conn->prepare("DELETE FROM Users WHERE user_id=?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    return true;
}

// REMOVE OR COMMENT OUT this function as it's already defined in db_functions.php
/*
function getAllStudents() {
    global $conn;
    $sql = "SELECT * FROM students";
    return $conn->query($sql);
}
*/

// معالجة نماذج الطلاب
function handleStudentForms() {
    if (isset($_POST["add_student"])) {
        addStudent(
            $_POST["student_email"],
            $_POST["student_password"],
            $_POST["student_name"],
            $_POST["student_level"],
            $_POST["department_id"]
        );
    }
    
    if (isset($_POST["update_student"])) {
        updateStudent(
            $_POST["student_id"],
            $_POST["student_email"],
            $_POST["student_name"],
            $_POST["student_level"],
            $_POST["dept_id"]
        );
    }
    
    if (isset($_POST["delete_student"])) {
        deleteStudent($_POST["student_id"]);
    }
}
?>