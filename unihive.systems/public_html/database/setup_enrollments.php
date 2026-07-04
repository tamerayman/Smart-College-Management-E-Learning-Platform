<?php
require_once '../config.php';

function setupCourseEnrollmentsTable() {
    $conn = connectDB();
    
    // Create CourseEnrollments table
    $sql = "CREATE TABLE IF NOT EXISTS CourseEnrollments (
        enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_enrollment (user_id, course_id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "CourseEnrollments table created successfully<br>";
    } else {
        echo "Error creating CourseEnrollments table: " . $conn->error . "<br>";
    }
    
    // Check if Courses table exists and has data
    $courseCheck = $conn->query("SELECT COUNT(*) as count FROM Courses");
    if (!$courseCheck || $courseCheck->fetch_assoc()['count'] == 0) {
        echo "Warning: No courses found. Creating sample courses...<br>";
        createSampleCourses($conn);
    }
    
    // Check if Users table has student records
    $studentCheck = $conn->query("SELECT COUNT(*) as count FROM Users WHERE role = 'student'");
    if (!$studentCheck || $studentCheck->fetch_assoc()['count'] == 0) {
        echo "Warning: No student users found. Please add students before enrolling.<br>";
        $conn->close();
        return;
    }
    
    // Check if the CourseEnrollments table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM CourseEnrollments");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "No enrollments found. Creating sample enrollments...<br>";
        
        // Get all students
        $students = $conn->query("SELECT user_id FROM Users WHERE role = 'student'");
        $studentIds = [];
        while ($student = $students->fetch_assoc()) {
            $studentIds[] = $student['user_id'];
        }
        
        // Get all courses
        $courses = $conn->query("SELECT course_id FROM Courses");
        $courseIds = [];
        while ($course = $courses->fetch_assoc()) {
            $courseIds[] = $course['course_id'];
        }
        
        // If we have students and courses, create enrollments
        if (!empty($studentIds) && !empty($courseIds)) {
            $stmt = $conn->prepare("INSERT INTO CourseEnrollments (user_id, course_id) VALUES (?, ?)");
            $enrollmentCount = 0;
            
            foreach ($studentIds as $student_id) {
                // Enroll each student in 1-3 random courses
                $numCourses = min(count($courseIds), rand(1, 3));
                $selectedCourses = [];
                
                // Select random courses for this student
                $courseCopy = $courseIds;
                for ($i = 0; $i < $numCourses; $i++) {
                    if (empty($courseCopy)) break;
                    $randomKey = array_rand($courseCopy);
                    $selectedCourses[] = $courseCopy[$randomKey];
                    unset($courseCopy[$randomKey]);
                }
                
                foreach ($selectedCourses as $course_id) {
                    try {
                        $stmt->bind_param("ii", $student_id, $course_id);
                        $stmt->execute();
                        if ($stmt->affected_rows > 0) {
                            $enrollmentCount++;
                            echo "Added enrollment: User $student_id to Course $course_id<br>";
                        }
                    } catch (Exception $e) {
                        echo "Could not add enrollment: " . $e->getMessage() . "<br>";
                    }
                }
            }
            
            echo "Successfully created $enrollmentCount enrollments<br>";
            $stmt->close();
        } else {
            echo "No students or courses found to create enrollments.<br>";
        }
    } else {
        echo "CourseEnrollments table already contains " . $row['count'] . " records<br>";
        
        // Show sample of existing enrollments
        $sampleQuery = $conn->query("
            SELECT e.user_id, u.email, e.course_id, c.course_name 
            FROM CourseEnrollments e
            JOIN Users u ON e.user_id = u.user_id
            JOIN Courses c ON e.course_id = c.course_id
            LIMIT 5
        ");
        
        if ($sampleQuery && $sampleQuery->num_rows > 0) {
            echo "<br>Sample of existing enrollments:<br>";
            echo "<table border='1'><tr><th>User ID</th><th>Email</th><th>Course ID</th><th>Course Name</th></tr>";
            
            while ($row = $sampleQuery->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['user_id'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $row['course_id'] . "</td>";
                echo "<td>" . $row['course_name'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table><br>";
        }
    }
    
    $conn->close();
}

// Helper function to create sample courses if none exist
function createSampleCourses($conn) {
    $sampleCourses = [
        ["Mathematics 101", "Introduction to basic mathematics"],
        ["Computer Science Basics", "Fundamentals of programming"],
        ["Physics 101", "Introduction to physics concepts"]
    ];
    
    $stmt = $conn->prepare("INSERT INTO Courses (course_name, description) VALUES (?, ?)");
    
    foreach ($sampleCourses as $course) {
        try {
            $stmt->bind_param("ss", $course[0], $course[1]);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                echo "Added course: " . $course[0] . "<br>";
            }
        } catch (Exception $e) {
            echo "Could not add course " . $course[0] . ": " . $e->getMessage() . "<br>";
        }
    }
    
    $stmt->close();
}

// Run the setup function
setupCourseEnrollmentsTable();
echo "Course enrollments setup completed!";
?>
