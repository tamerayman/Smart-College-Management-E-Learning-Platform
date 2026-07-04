<?php
// Diagnostic tool for course enrollments
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Course Enrollment Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #103054; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .action-btn { padding: 5px 10px; background: #103054; color: white; border: none; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Course Enrollment Diagnostic Tool</h1>
    <p>This tool helps diagnose issues with course enrollments and notifications.</p>

<?php
function diagnoseEnrollments() {
    $conn = connectDB();
    
    echo "<h2>Database Tables Check</h2>";
    
    // Check Users table
    $usersTable = $conn->query("SHOW TABLES LIKE 'Users'");
    if ($usersTable->num_rows == 0) {
        echo "<p class='error'>Users table does not exist!</p>";
    } else {
        $usersCount = $conn->query("SELECT COUNT(*) as count FROM Users WHERE role = 'student'");
        $studentCount = $usersCount->fetch_assoc()['count'];
        echo "<p class='success'>Users table exists with $studentCount students</p>";
    }
    
    // Check Courses table and get column names
    $coursesTable = $conn->query("SHOW TABLES LIKE 'Courses'");
    if ($coursesTable->num_rows == 0) {
        echo "<p class='error'>Courses table does not exist!</p>";
    } else {
        // Get column names from Courses table
        $courseColumns = $conn->query("SHOW COLUMNS FROM Courses");
        $columns = [];
        while ($column = $courseColumns->fetch_assoc()) {
            $columns[] = $column['Field'];
        }
        
        // Determine course name column - could be course_name, name, title, etc.
        $nameColumn = in_array('course_name', $columns) ? 'course_name' : 
                     (in_array('name', $columns) ? 'name' : 
                     (in_array('title', $columns) ? 'title' : 'course_id'));
        
        $coursesCount = $conn->query("SELECT COUNT(*) as count FROM Courses");
        $courseCount = $coursesCount->fetch_assoc()['count'];
        echo "<p class='success'>Courses table exists with $courseCount courses</p>";
        echo "<p>Found course name column: <strong>$nameColumn</strong></p>";
        
        // List some courses with their columns
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";
        
        $courses = $conn->query("SELECT * FROM Courses LIMIT 10");
        while ($course = $courses->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $course['course_id'] . "</td>";
            echo "<td>" . ($course[$nameColumn] ?? 'N/A') . "</td>";
            echo "<td>" . (isset($course['description']) ? $course['description'] : 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check CourseEnrollments table
    $enrollmentsTable = $conn->query("SHOW TABLES LIKE 'CourseEnrollments'");
    if ($enrollmentsTable->num_rows == 0) {
        echo "<p class='error'>CourseEnrollments table does not exist!</p>";
        echo "<form method='post'><button type='submit' name='create_enrollments_table' class='action-btn'>Create CourseEnrollments Table</button></form>";
    } else {
        $enrollmentsCount = $conn->query("SELECT COUNT(*) as count FROM CourseEnrollments");
        $enrollmentCount = $enrollmentsCount->fetch_assoc()['count'];
        echo "<p class='success'>CourseEnrollments table exists with $enrollmentCount enrollments</p>";
        
        // Show sample enrollments - carefully construct query based on available columns
        if ($enrollmentCount > 0) {
            echo "<h3>Sample Enrollments</h3>";
            $nameColumn = getCourseNameColumn($conn);
            
            if ($nameColumn) {
                $query = "
                    SELECT e.enrollment_id, e.user_id, u.email, e.course_id, c.$nameColumn as course_name
                    FROM CourseEnrollments e
                    JOIN Users u ON e.user_id = u.user_id
                    JOIN Courses c ON e.course_id = c.course_id
                    LIMIT 10
                ";
            } else {
                $query = "
                    SELECT e.enrollment_id, e.user_id, u.email, e.course_id
                    FROM CourseEnrollments e
                    JOIN Users u ON e.user_id = u.user_id
                    LIMIT 10
                ";
            }
            
            try {
                $enrollments = $conn->query($query);
                
                if ($enrollments) {
                    echo "<table>";
                    if ($nameColumn) {
                        echo "<tr><th>Enrollment ID</th><th>User ID</th><th>User Email</th><th>Course ID</th><th>Course Name</th></tr>";
                    } else {
                        echo "<tr><th>Enrollment ID</th><th>User ID</th><th>User Email</th><th>Course ID</th></tr>";
                    }
                    
                    while ($enrollment = $enrollments->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $enrollment['enrollment_id'] . "</td>";
                        echo "<td>" . $enrollment['user_id'] . "</td>";
                        echo "<td>" . $enrollment['email'] . "</td>";
                        echo "<td>" . $enrollment['course_id'] . "</td>";
                        if ($nameColumn) {
                            echo "<td>" . $enrollment['course_name'] . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='error'>Error executing enrollment query: " . $conn->error . "</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>Error retrieving enrollments: " . $e->getMessage() . "</p>";
                echo "<p>Simplified query:</p>";
                
                // Fallback to a simpler query
                $simpleQuery = "SELECT * FROM CourseEnrollments LIMIT 10";
                $simpleEnrollments = $conn->query($simpleQuery);
                
                if ($simpleEnrollments) {
                    echo "<table>";
                    echo "<tr><th>Enrollment ID</th><th>User ID</th><th>Course ID</th></tr>";
                    
                    while ($enrollment = $simpleEnrollments->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $enrollment['enrollment_id'] . "</td>";
                        echo "<td>" . $enrollment['user_id'] . "</td>";
                        echo "<td>" . $enrollment['course_id'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
        }
    }
    
    // Course-specific enrollment counts
    if ($enrollmentsTable->num_rows > 0 && $enrollmentCount > 0) {
        echo "<h2>Enrollment Counts by Course</h2>";
        
        $nameColumn = getCourseNameColumn($conn);
        
        $courseCounts = null;
        if ($nameColumn) {
            $courseCounts = $conn->query("
                SELECT c.course_id, c.$nameColumn as course_name, COUNT(e.user_id) as enrolled_count
                FROM Courses c
                LEFT JOIN CourseEnrollments e ON c.course_id = e.course_id
                GROUP BY c.course_id
                ORDER BY c.course_id
            ");
        } else {
            $courseCounts = $conn->query("
                SELECT c.course_id, COUNT(e.user_id) as enrolled_count
                FROM Courses c
                LEFT JOIN CourseEnrollments e ON c.course_id = e.course_id
                GROUP BY c.course_id
                ORDER BY c.course_id
            ");
        }
        
        if ($courseCounts) {
            echo "<table>";
            if ($nameColumn) {
                echo "<tr><th>Course ID</th><th>Course Name</th><th>Enrolled Students</th><th>Actions</th></tr>";
            } else {
                echo "<tr><th>Course ID</th><th>Enrolled Students</th><th>Actions</th></tr>";
            }
            
            while ($course = $courseCounts->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$course['course_id']}</td>";
                if ($nameColumn) {
                    echo "<td>{$course['course_name']}</td>";
                }
                echo "<td>" . ($course['enrolled_count'] > 0 ? 
                    "<span class='success'>{$course['enrolled_count']}</span>" : 
                    "<span class='error'>0</span>") . "</td>";
                echo "<td>";
                echo "<form method='post' style='display:inline'>
                    <input type='hidden' name='course_id' value='{$course['course_id']}'>
                    <button type='submit' name='view_students_in_course' class='action-btn'>View Students</button>
                    <button type='submit' name='add_test_enrollments' class='action-btn'>Add Test Enrollments</button>
                </form>";
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p class='error'>Error retrieving course enrollment counts: " . $conn->error . "</p>";
        }
    }
    
    // Process actions for specific course if requested
    if (isset($_POST['view_students_in_course']) && isset($_POST['course_id'])) {
        $courseId = $_POST['course_id'];
        echo "<h2>Students Enrolled in Course ID: $courseId</h2>";
        
        $nameColumn = getCourseNameColumn($conn);
        $courseStudents = null;
        
        if ($nameColumn) {
            $courseStudents = $conn->query("
                SELECT c.$nameColumn as course_name, u.user_id, u.email, u.role
                FROM CourseEnrollments e
                JOIN Courses c ON e.course_id = c.course_id
                JOIN Users u ON e.user_id = u.user_id
                WHERE e.course_id = $courseId
            ");
        } else {
            $courseStudents = $conn->query("
                SELECT u.user_id, u.email, u.role
                FROM CourseEnrollments e
                JOIN Users u ON e.user_id = u.user_id
                WHERE e.course_id = $courseId
            ");
        }
        
        if ($courseStudents && $courseStudents->num_rows > 0) {
            $course = $courseStudents->fetch_assoc();
            $courseName = isset($course['course_name']) ? $course['course_name'] : "Course #$courseId";
            $courseStudents->data_seek(0); // Reset pointer
            
            echo "<p>Showing students enrolled in: <strong>$courseName</strong></p>";
            echo "<table><tr><th>User ID</th><th>Email</th><th>Role</th></tr>";
            
            while ($student = $courseStudents->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$student['user_id']}</td>";
                echo "<td>{$student['email']}</td>";
                echo "<td>{$student['role']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>No students found enrolled in this course.</p>";
        }
    }
    
    // Handle adding enrollments to specific course
    if (isset($_POST['add_test_enrollments']) && isset($_POST['course_id'])) {
        $courseId = $_POST['course_id'];
        
        // Get course info
        $nameColumn = getCourseNameColumn($conn);
        $courseInfo = null;
        
        if ($nameColumn) {
            $courseInfo = $conn->query("SELECT $nameColumn as course_name FROM Courses WHERE course_id = $courseId");
            $courseName = $courseInfo->fetch_assoc()['course_name'];
        } else {
            $courseName = "Course #$courseId";
        }
        
        echo "<h2>Adding Test Enrollments to: $courseName (ID: $courseId)</h2>";
        
        // Get students not enrolled in this course
        $availableStudents = $conn->query("
            SELECT u.user_id, u.email 
            FROM Users u
            WHERE u.role = 'student'
            AND u.user_id NOT IN (
                SELECT e.user_id FROM CourseEnrollments e WHERE e.course_id = $courseId
            )
            LIMIT 10
        ");
        
        if ($availableStudents && $availableStudents->num_rows > 0) {
            // Add up to 3 students automatically
            $count = 0;
            $stmt = $conn->prepare("INSERT INTO CourseEnrollments (user_id, course_id) VALUES (?, ?)");
            
            while ($student = $availableStudents->fetch_assoc()) {
                $stmt->bind_param("ii", $student['user_id'], $courseId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    echo "<p class='success'>Enrolled student ID {$student['user_id']} ({$student['email']}) in course $courseName</p>";
                    $count++;
                }
                if ($count >= 3) break; // Limit to 3 automatic enrollments
            }
            
            if ($count == 0) {
                echo "<p class='warning'>No new students were enrolled.</p>";
            } else {
                echo "<p class='success'>Successfully enrolled $count students in the course.</p>";
            }
            $stmt->close();
        } else {
            echo "<p class='error'>No available students to enroll in this course.</p>";
        }
    }
    
    // Create CourseEnrollments table
    if (isset($_POST['create_enrollments_table'])) {
        $sql = "CREATE TABLE IF NOT EXISTS CourseEnrollments (
            enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE,
            UNIQUE KEY unique_enrollment (user_id, course_id)
        )";
        
        if ($conn->query($sql)) {
            echo "<p class='success'>CourseEnrollments table created successfully.</p>";
        } else {
            echo "<p class='error'>Error creating CourseEnrollments table: " . $conn->error . "</p>";
        }
    }
    
    $conn->close();
}

// Helper function to get the course name column
function getCourseNameColumn($conn) {
    $courseColumns = $conn->query("SHOW COLUMNS FROM Courses");
    $columns = [];
    while ($column = $courseColumns->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    if (in_array('course_name', $columns)) {
        return 'course_name';
    } else if (in_array('name', $columns)) {
        return 'name';
    } else if (in_array('title', $columns)) {
        return 'title';
    }
    
    return null;
}

// Run the diagnostic
diagnoseEnrollments();
?>

<p><a href="admin_dashboard.php">Return to Admin Dashboard</a></p>
</body>
</html>
