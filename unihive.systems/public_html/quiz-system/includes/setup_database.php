<?php
require_once '../../config.php';

// Create connection
$conn = connectDB();

// Create tables if they don't exist
$tables = [
    // Quiz table
    "CREATE TABLE IF NOT EXISTS quizzes (
        quiz_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        course_id INT NOT NULL,
        professor_id INT NOT NULL,
        time_limit INT NOT NULL, -- in minutes
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES Courses(course_id),
        FOREIGN KEY (professor_id) REFERENCES Users(user_id)
    )",

    // Questions table
    "CREATE TABLE IF NOT EXISTS quiz_questions (
        question_id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'short_answer') NOT NULL,
        points INT NOT NULL DEFAULT 1,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
    )",

    // Answers table for multiple choice and true/false questions
    "CREATE TABLE IF NOT EXISTS quiz_answers (
        answer_id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        answer_text TEXT NOT NULL,
        is_correct BOOLEAN NOT NULL DEFAULT 0,
        FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id) ON DELETE CASCADE
    )",

    // Student quiz attempts
    "CREATE TABLE IF NOT EXISTS quiz_attempts (
        attempt_id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        student_id INT NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME,
        score DECIMAL(5,2) DEFAULT NULL,
        status ENUM('in_progress', 'completed', 'timed_out') NOT NULL DEFAULT 'in_progress',
        FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES Users(user_id)
    )",

    // Student answers to questions
    "CREATE TABLE IF NOT EXISTS student_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        attempt_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT,
        selected_answer_id INT,
        is_correct BOOLEAN,
        points_earned DECIMAL(5,2) DEFAULT 0,
        FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(attempt_id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id),
        FOREIGN KEY (selected_answer_id) REFERENCES quiz_answers(answer_id)
    )"
];

// Execute queries
$success = true;
foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        echo "Error creating table: " . $conn->error . "<br>";
        $success = false;
    }
}

if ($success) {
    echo "Quiz system database tables created successfully!";
} else {
    echo "There were errors creating the quiz system tables.";
}

$conn->close();
?>