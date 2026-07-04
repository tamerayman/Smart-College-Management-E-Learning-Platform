<?php
session_start();

// Include database configuration
require_once 'db_config.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Modify the existing getCurrentUser function to include profile_image
function getCurrentUser() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    switch ($role) {
        case 'student':
            // Try with student_id which is likely the primary key column
            $sql = "SELECT s.*, d.name as department_name FROM students s 
                    LEFT JOIN departments d ON s.department_id = d.department_id 
                    WHERE s.student_id = ?";
            break;
        case 'professor':
            // Try with professor_id which is likely the primary key column
            $sql = "SELECT p.*, d.name as department_name FROM professors p 
                    LEFT JOIN departments d ON p.department_id = d.department_id 
                    WHERE p.professor_id = ?";
            break;
        case 'admin':
            // Try with admin_id which is likely the primary key column
            $sql = "SELECT a.* FROM admins a 
                    WHERE a.admin_id = ?";
            break;
        default:
            return false;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Function to handle logout
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}
?>
