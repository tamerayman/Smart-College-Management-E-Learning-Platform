<?php
require_once '../../config.php';

// Create database connection
$conn = connectDB();

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Array to store results
$results = [];

try {
    // 1. Create student_answers table if it doesn't exist
    $createStudentAnswersTable = "
        CREATE TABLE IF NOT EXISTS student_answers (
            answer_id INT AUTO_INCREMENT PRIMARY KEY,
            attempt_id INT NOT NULL,
            question_id INT NOT NULL,
            selected_option_id INT NULL,
            is_correct TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (attempt_id),
            INDEX (question_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createStudentAnswersTable);
    $results[] = "Student answers table checked/created successfully";

    // 2. Create quiz_attempts table if it doesn't exist
    $createAttemptsTable = "
        CREATE TABLE IF NOT EXISTS quiz_attempts (
            attempt_id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_id INT NOT NULL,
            student_id INT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NULL,
            status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
            score DECIMAL(5,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (quiz_id),
            INDEX (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createAttemptsTable);
    $results[] = "Quiz attempts table checked/created successfully";

    // 3. Alter student_answers table if option_id column exists but selected_option_id doesn't
    $checkOptionIdColumn = $conn->query("SHOW COLUMNS FROM student_answers LIKE 'option_id'");
    $checkSelectedOptionIdColumn = $conn->query("SHOW COLUMNS FROM student_answers LIKE 'selected_option_id'");
    
    if ($checkOptionIdColumn->num_rows > 0 && $checkSelectedOptionIdColumn->num_rows == 0) {
        // Rename option_id to selected_option_id
        $conn->query("ALTER TABLE student_answers CHANGE option_id selected_option_id INT NULL");
        $results[] = "Renamed 'option_id' column to 'selected_option_id'";
    } elseif ($checkOptionIdColumn->num_rows == 0 && $checkSelectedOptionIdColumn->num_rows == 0) {
        // Add selected_option_id column if neither exists
        $conn->query("ALTER TABLE student_answers ADD COLUMN selected_option_id INT NULL AFTER question_id");
        $results[] = "Added 'selected_option_id' column to student_answers table";
    }

    echo "<h1>Database Setup Results</h1>";
    echo "<ul>";
    foreach ($results as $result) {
        echo "<li>{$result}</li>";
    }
    echo "</ul>";
    echo "<p>Database tables are now set up correctly.</p>";
    echo "<p><a href='../index.php'>Return to homepage</a></p>";

} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>An error occurred: " . $e->getMessage() . "</p>";
}

$conn->close();
