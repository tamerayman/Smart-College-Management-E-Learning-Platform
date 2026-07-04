<?php
// filepath: d:\wamp64\www\admin\professor_operations.php
// عمليات إدارة الدكاترة

require_once 'config.php';

// إضافة دكتور جديد
function addProfessor($profEmail, $profPassword, $profName, $deptId) {
    $conn = connectDB();
    
    // إدخال المستخدم في جدول Users
    $stmt = $conn->prepare("INSERT INTO Users (email, password, role) VALUES (?, ?, 'professor')");
    $stmt->bind_param("ss", $profEmail, $profPassword);
    $stmt->execute();
    $newProfId = $stmt->insert_id;
    $stmt->close();
    
    // إدخال بيانات الدكتور في جدول Professors
    $stmt = $conn->prepare("INSERT INTO Professors (professor_id, name, department_id) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $newProfId, $profName, $deptId);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    return true;
}

// تحديث بيانات دكتور
function updateProfessor($profId, $profEmail, $profName, $deptId) {
    $conn = connectDB();
    
    // تحديث بيانات المستخدم
    $stmt = $conn->prepare("UPDATE Users SET email=? WHERE user_id=?");
    $stmt->bind_param("si", $profEmail, $profId);
    $stmt->execute();
    $stmt->close();
    
    // تحديث بيانات الدكتور
    $stmt = $conn->prepare("UPDATE Professors SET name=?, department_id=? WHERE professor_id=?");
    $stmt->bind_param("sii", $profName, $deptId, $profId);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    return true;
}

// حذف دكتور
function deleteProfessor($profId) {
    $conn = connectDB();
    
    // حذف بيانات الدكتور
    $stmt = $conn->prepare("DELETE FROM Professors WHERE professor_id=?");
    $stmt->bind_param("i", $profId);
    $stmt->execute();
    $stmt->close();
    
    // حذف بيانات المستخدم
    $stmt = $conn->prepare("DELETE FROM Users WHERE user_id=?");
    $stmt->bind_param("i", $profId);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    return true;
}

// معالجة نماذج الدكاترة
function handleProfessorForms() {
    if (isset($_POST["add_professor"])) {
        addProfessor(
            $_POST["prof_email"],
            $_POST["prof_password"],
            $_POST["prof_name"],
            $_POST["prof_department_id"]
        );
    }
    
    if (isset($_POST["update_professor"])) {
        updateProfessor(
            $_POST["prof_id"],
            $_POST["prof_email"],
            $_POST["prof_name"],
            $_POST["dept_id"]
        );
    }
    
    if (isset($_POST["delete_professor"])) {
        deleteProfessor($_POST["prof_id"]);
    }
}

// REMOVE OR COMMENT OUT this function as it's already defined in db_functions.php
/*
function getAllProfessors() {
    global $conn;
    $sql = "SELECT * FROM professors";
    return $conn->query($sql);
}
*/

?>