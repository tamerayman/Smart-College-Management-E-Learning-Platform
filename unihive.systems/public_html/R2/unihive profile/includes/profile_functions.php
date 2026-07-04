<?php
require_once 'db_config.php';

// Function to update user profile based on role
function updateProfile($user_id, $role, $data) {
    global $conn;
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Common update for all roles - currently just the password
        if (isset($data['password']) && !empty($data['password'])) {
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE Users SET password = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $password_hash, $user_id);
            
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error updating user information");
            }
        }
        
        // Role-specific updates
        switch($role) {
            case 'student':
                $name = $data['name'];
                $level = $data['level'];
                $department_id = $data['department_id'];
                
                $sql = "UPDATE Students SET name = ?, level = ?, department_id = ? WHERE student_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "siii", $name, $level, $department_id, $user_id);
                
                if(!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error updating student information");
                }
                break;
                
            case 'professor':
                $name = $data['name'];
                $department_id = $data['department_id'];
                
                $sql = "UPDATE Professors SET name = ?, department_id = ? WHERE professor_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sii", $name, $department_id, $user_id);
                
                if(!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error updating professor information");
                }
                break;
                
            case 'admin':
                $name = $data['name'];
                
                $sql = "UPDATE Admins SET name = ? WHERE admin_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $name, $user_id);
                
                if(!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error updating admin information");
                }
                break;
        }
        
        // Commit transaction
        mysqli_commit($conn);
        return true;
    }
    catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        return false;
    }
}

// Function to get departments (for dropdowns)
function getDepartments() {
    global $conn;
    
    $sql = "SELECT * FROM Departments ORDER BY name";
    $result = mysqli_query($conn, $sql);
    
    $departments = [];
    while($row = mysqli_fetch_assoc($result)) {
        $departments[] = $row;
    }
    
    return $departments;
}

// Function to get courses for a student
function getStudentCourses($student_id) {
    global $conn;
    
    $sql = "SELECT c.* FROM Courses c 
            JOIN Student_Courses sc ON c.course_id = sc.course_id 
            WHERE sc.student_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $courses = [];
    while($row = mysqli_fetch_assoc($result)) {
        $courses[] = $row;
    }
    
    return $courses;
}

// Function to get courses for a professor
function getProfessorCourses($professor_id) {
    global $conn;
    
    $sql = "SELECT c.* FROM Courses c 
            JOIN Professor_Courses pc ON c.course_id = pc.course_id 
            WHERE pc.professor_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $professor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $courses = [];
    while($row = mysqli_fetch_assoc($result)) {
        $courses[] = $row;
    }
    
    return $courses;
}

// Make sure we have a saveProfileImage function but don't redeclare getCurrentUser
function saveProfileImage($user_id, $file) {
    global $conn;
    
    // Validate file type, size, etc.
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return "Error: Only JPG, PNG and GIF images are allowed.";
    }
    
    if ($file['size'] > $max_size) {
        return "Error: File size must be less than 5MB.";
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = "uploads/profile_images/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = $user_id . '_' . time() . '_' . basename($file['name']);
    $target_file = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Store the image path in user_profiles table instead of users table
        // First check if a profile already exists
        $check_sql = "SELECT * FROM user_profiles WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing profile
            $sql = "UPDATE user_profiles SET profile_image = ? WHERE user_id = ?";
        } else {
            // Create new profile
            $sql = "INSERT INTO user_profiles (user_id, profile_image) VALUES (?, ?)";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $target_file, $user_id);
        
        if ($stmt->execute()) {
            return $target_file;
        } else {
            return "Error: Failed to update database.";
        }
    } else {
        return "Error: Failed to upload file.";
    }
}
?>
