<?php
require_once '../config.php';

$conn = connectDB();

// Create books table
$sql_books = "CREATE TABLE IF NOT EXISTS library_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    course_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    description TEXT,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (uploaded_by) REFERENCES Users(user_id)
)";

// Create exams table
$sql_exams = "CREATE TABLE IF NOT EXISTS library_exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    course_id INT NOT NULL,
    exam_type VARCHAR(50) NOT NULL,
    exam_year INT NOT NULL,
    uploaded_by INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (uploaded_by) REFERENCES Users(user_id)
)";

$conn->query($sql_books);
$conn->query($sql_exams);

echo "Library database tables created successfully";
$conn->close();
?>
