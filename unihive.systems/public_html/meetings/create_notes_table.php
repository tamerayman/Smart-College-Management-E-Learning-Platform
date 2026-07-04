<?php
require_once '../config.php';

// Connect to the database
$conn = connectDB();

// SQL to create the recording_notes table
$sql = "
CREATE TABLE IF NOT EXISTS recording_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recording_id VARCHAR(255) NOT NULL,
    professor_id INT NOT NULL,
    note_content TEXT NOT NULL,
    note_category ENUM('important', 'explanation', 'question', 'reminder', 'general') DEFAULT 'general',
    note_color VARCHAR(20) DEFAULT '#f8f9fa',
    timestamp FLOAT DEFAULT 0,
    is_public TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (recording_id),
    INDEX (professor_id),
    INDEX (note_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute the SQL query
if ($conn->query($sql) === TRUE) {
    echo "Table 'recording_notes' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// Add categories if not already existing
$checkSql = "SHOW COLUMNS FROM recording_notes LIKE 'note_category'";
$result = $conn->query($checkSql);

if ($result->num_rows == 0) {
    $alterSql = "ALTER TABLE recording_notes 
                 ADD COLUMN note_category ENUM('important', 'explanation', 'question', 'reminder', 'general') DEFAULT 'general',
                 ADD COLUMN note_color VARCHAR(20) DEFAULT '#f8f9fa'";
    
    if ($conn->query($alterSql) === TRUE) {
        echo "<br>Table structure updated with category and color fields!";
    } else {
        echo "<br>Error updating table structure: " . $conn->error;
    }
}

// Close connection
$conn->close();
?>
