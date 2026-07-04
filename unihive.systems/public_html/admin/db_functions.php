<?php
// Include the main configuration file
require_once '../config.php'; // This path points to the config.php file in the www directory

// Create a database connection using the function from config.php
$conn = connectDB();

// Set charset to utf8
$conn->set_charset("utf8");

// إحصائيات الطلاب والدكاترة
function getStatistics() {
    $conn = connectDB();
    
    $totalStudentsResult = $conn->query("SELECT COUNT(*) AS total_stu FROM students");
    $totalStudents = $totalStudentsResult->fetch_assoc()["total_stu"];
    
    $totalProfResult = $conn->query("SELECT COUNT(*) AS total_prof FROM professors");
    $totalProfessors = $totalProfResult->fetch_assoc()["total_prof"];
    
    $conn->close();
    
    return [
        'students' => $totalStudents,
        'professors' => $totalProfessors
    ];
}

// جلب بيانات الطلاب
function getAllStudents($search = '') {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in getAllStudents()");
        return false;
    }
    
    // Join with users table to get email information and departments to get department names
    $sql = "SELECT s.*, u.email, d.name as department_name 
            FROM students s
            LEFT JOIN users u ON s.student_id = u.user_id
            LEFT JOIN departments d ON s.department_id = d.department_id";
    
    // Add search condition if search parameter is provided
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " WHERE s.student_id LIKE '%$search%' OR s.name LIKE '%$search%' OR u.email LIKE '%$search%'";
    }
    
    $sql .= " ORDER BY s.student_id";
    
    $result = $conn->query($sql);
    $conn->close();
    
    return $result;
}

// جلب بيانات الدكاترة
function getAllProfessors($search = '') {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in getAllProfessors()");
        return false;
    }
    
    // Join with users table to get email information and departments to get department names
    $sql = "SELECT p.*, u.email, d.name as department_name 
            FROM professors p
            LEFT JOIN users u ON p.professor_id = u.user_id
            LEFT JOIN departments d ON p.department_id = d.department_id";
    
    // Add search condition if search parameter is provided
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " WHERE p.professor_id LIKE '%$search%' OR p.name LIKE '%$search%' OR u.email LIKE '%$search%'";
    }
    
    $sql .= " ORDER BY p.professor_id";
    
    $result = $conn->query($sql);
    $conn->close();
    
    return $result;
}

/**
 * Get all departments from the database
 */
function getAllDepartments() {
    global $conn;
    
    // Check if connection is established
    if (!$conn) {
        error_log("Database connection not established in getAllDepartments()");
        return false;
    }
    
    $sql = "SELECT * FROM departments ORDER BY name";
    return $conn->query($sql);
}

/**
 * Get all courses from the database
 */
function getAllCourses() {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in getAllCourses()");
        return false;
    }
    
    // Use DISTINCT to ensure unique courses
    $sql = "SELECT DISTINCT course_id, name, department_id FROM courses ORDER BY name";
    
    $result = $conn->query($sql);
    $conn->close();
    
    return $result;
}

/**
 * Get all courses with their department information
 */
function getAllCoursesWithDepartments() {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in getAllCoursesWithDepartments()");
        return false;
    }
    
    // Use DISTINCT to ensure unique courses
    $sql = "SELECT DISTINCT c.course_id, c.name, c.department_id, d.name as department_name 
            FROM courses c 
            LEFT JOIN departments d ON c.department_id = d.department_id 
            ORDER BY c.name";
    
    $result = $conn->query($sql);
    $conn->close();
    
    return $result;
}

/**
 * Add a new course to the database
 */
function addNewCourse($name, $department_id) {
    global $conn;
    
    // Check if connection is established
    if (!$conn) {
        error_log("Database connection not established in addNewCourse()");
        return false;
    }
    
    // Check if course name already exists in the same department
    $check_sql = "SELECT * FROM courses WHERE name = ? AND department_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $name, $department_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        return false; // Course with this name already exists in this department
    }
    
    // Insert new course
    $sql = "INSERT INTO courses (name, department_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $name, $department_id);
    return $stmt->execute();
}

/**
 * Get students enrolled in a specific course
 */
function getEnrolledStudents($course_id) {
    global $conn;
    
    // Check if connection is established
    if (!$conn) {
        error_log("Database connection not established in getEnrolledStudents()");
        return [];
    }
    
    // Join with users table to get email information
    $sql = "SELECT s.*, u.email FROM students s 
            JOIN student_courses AS sc ON s.student_id = sc.student_id 
            LEFT JOIN users u ON s.student_id = u.user_id
            WHERE sc.course_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    return $students;
}

/**
 * Get professors assigned to a specific course
 */
function getAssignedProfessors($course_id) {
    global $conn;
    
    // Check if connection is established
    if (!$conn) {
        error_log("Database connection not established in getAssignedProfessors()");
        return [];
    }
    
    // Join with users table to get email information
    $sql = "SELECT p.*, u.email FROM professors p 
            JOIN professor_courses AS pc ON p.professor_id = pc.professor_id 
            LEFT JOIN users u ON p.professor_id = u.user_id
            WHERE pc.course_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $professors = [];
    while ($row = $result->fetch_assoc()) {
        $professors[] = $row;
    }
    
    return $professors;
}

/**
 * Get all student course enrollments
 */
function getStudentCourseEnrollments() {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in getStudentCourseEnrollments()");
        return false;
    }
    
    $sql = "SELECT sc.*, s.name as student_name, c.name as course_name 
            FROM student_courses sc
            JOIN students s ON sc.student_id = s.student_id
            JOIN courses c ON sc.course_id = c.course_id
            ORDER BY s.name, c.name";
    
    $result = $conn->query($sql);
    $conn->close();
    
    return $result;
}

/**
 * Get all professor course enrollments
 */
function getProfessorCourseEnrollments() {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in getProfessorCourseEnrollments()");
        return false;
    }
    
    $sql = "SELECT pc.*, p.name as professor_name, c.name as course_name 
            FROM professor_courses pc
            JOIN professors p ON pc.professor_id = p.professor_id
            JOIN courses c ON pc.course_id = c.course_id
            ORDER BY p.name, c.name";
    
    $result = $conn->query($sql);
    $conn->close();
    
    return $result;
}

/**
 * Add a student to a course
 */
function addStudentToCourse($student_id, $course_id) {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in addStudentToCourse()");
        return false;
    }
    
    // Check if student is already enrolled in the course
    $check_sql = "SELECT * FROM student_courses WHERE student_id = ? AND course_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $course_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conn->close();
        return false; // Student already enrolled
    }
    
    // Add student to course
    $sql = "INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $course_id);
    $success = $stmt->execute();
    $conn->close();
    
    return $success;
}

/**
 * Remove a student from a course
 */
function removeStudentFromCourse($student_id, $course_id) {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in removeStudentFromCourse()");
        return false;
    }
    
    $sql = "DELETE FROM student_courses WHERE student_id = ? AND course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $course_id);
    $success = $stmt->execute();
    $conn->close();
    
    return $success;
}

/**
 * Add a professor to a course
 */
function addProfessorToCourse($professor_id, $course_id) {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in addProfessorToCourse()");
        return false;
    }
    
    // Check if professor is already assigned to the course
    $check_sql = "SELECT * FROM professor_courses WHERE professor_id = ? AND course_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $professor_id, $course_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conn->close();
        return false; // Professor already assigned
    }
    
    // Add professor to course
    $sql = "INSERT INTO professor_courses (professor_id, course_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $professor_id, $course_id);
    $success = $stmt->execute();
    $conn->close();
    
    return $success;
}

/**
 * Remove a professor from a course
 */
function removeProfessorFromCourse($professor_id, $course_id) {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in removeProfessorFromCourse()");
        return false;
    }
    
    $sql = "DELETE FROM professor_courses WHERE professor_id = ? AND course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $professor_id, $course_id);
    $success = $stmt->execute();
    $conn->close();
    
    return $success;
}

/**
 * Edit student enrollment
 */
function editStudentEnrollment($old_student_id, $old_course_id, $new_student_id, $new_course_id) {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in editStudentEnrollment()");
        return false;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // If the new enrollment is different from the old one
        if ($old_student_id != $new_student_id || $old_course_id != $new_course_id) {
            // Check if new enrollment already exists
            $check_sql = "SELECT * FROM student_courses WHERE student_id = ? AND course_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $new_student_id, $new_course_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // New enrollment already exists
                throw new Exception("Student is already enrolled in this course");
            }
            
            // Delete old enrollment
            $delete_sql = "DELETE FROM student_courses WHERE student_id = ? AND course_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $old_student_id, $old_course_id);
            $delete_stmt->execute();
            
            // Add new enrollment
            $insert_sql = "INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $new_student_id, $new_course_id);
            $insert_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        $conn->close();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        error_log("Error in editStudentEnrollment: " . $e->getMessage());
        return false;
    }
}

/**
 * Edit professor enrollment
 */
function editProfessorEnrollment($old_professor_id, $old_course_id, $new_professor_id, $new_course_id) {
    $conn = connectDB();
    
    if (!$conn) {
        error_log("Database connection failed in editProfessorEnrollment()");
        return false;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // If the new assignment is different from the old one
        if ($old_professor_id != $new_professor_id || $old_course_id != $new_course_id) {
            // Check if new assignment already exists
            $check_sql = "SELECT * FROM professor_courses WHERE professor_id = ? AND course_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $new_professor_id, $new_course_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // New assignment already exists
                throw new Exception("Professor is already assigned to this course");
            }
            
            // Delete old assignment
            $delete_sql = "DELETE FROM professor_courses WHERE professor_id = ? AND course_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $old_professor_id, $old_course_id);
            $delete_stmt->execute();
            
            // Add new assignment
            $insert_sql = "INSERT INTO professor_courses (professor_id, course_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $new_professor_id, $new_course_id);
            $insert_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        $conn->close();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        error_log("Error in editProfessorEnrollment: " . $e->getMessage());
        return false;
    }
}
?>
