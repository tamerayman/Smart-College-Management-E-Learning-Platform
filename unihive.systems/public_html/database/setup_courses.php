<?php
require_once '../config.php';

function setupCoursesTable() {
    $conn = connectDB();
    
    // Create Courses table
    $sql = "CREATE TABLE IF NOT EXISTS Courses (
        course_id INT AUTO_INCREMENT PRIMARY KEY,
        course_name VARCHAR(255) NOT NULL,
        description TEXT,
        department_id INT,
        professor_id INT,
        FOREIGN KEY (department_id) REFERENCES Departments(department_id) ON DELETE SET NULL,
        FOREIGN KEY (professor_id) REFERENCES Professors(professor_id) ON DELETE SET NULL
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Courses table created successfully<br>";
    } else {
        echo "Error creating Courses table: " . $conn->error . "<br>";
    }
    
    // Create CourseEnrollments table
    $sql = "CREATE TABLE IF NOT EXISTS CourseEnrollments (
        enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "CourseEnrollments table created successfully<br>";
    } else {
        echo "Error creating CourseEnrollments table: " . $conn->error . "<br>";
    }
    
    // Insert sample courses
    $sampleCourses = [
        ['Mathematics 101', 'Introduction to Mathematics', 1, 1],
        ['Computer Science Basics', 'Fundamentals of Computer Science', 2, 2],
        ['Physics for Beginners', 'Basic concepts of Physics', 3, 3]
    ];
    
    $stmt = $conn->prepare("INSERT INTO Courses (course_name, description, department_id, professor_id) VALUES (?, ?, ?, ?)");
    
    foreach ($sampleCourses as $course) {
        try {
            $stmt->bind_param("ssii", $course[0], $course[1], $course[2], $course[3]);
            $stmt->execute();
            echo "Added course: " . $course[0] . "<br>";
        } catch (Exception $e) {
            echo "Could not add course " . $course[0] . ": " . $e->getMessage() . "<br>";
        }
    }
    
    $stmt->close();
    $conn->close();
}

// Run the setup function
setupCoursesTable();
echo "Courses setup completed!";
?>
