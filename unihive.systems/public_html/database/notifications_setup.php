<?php
require_once '../config.php';

function setupNotificationsTable() {
    $conn = connectDB();
    
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS Notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sender_id INT,
        target_type VARCHAR(50) NOT NULL COMMENT 'all, course, specific',
        target_id INT NULL COMMENT 'course_id or NULL if for all',
        FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE SET NULL
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Notifications table created successfully<br>";
    } else {
        echo "Error creating Notifications table: " . $conn->error . "<br>";
    }
    
    // Create notification_recipients table to track which users received which notifications
    $sql = "CREATE TABLE IF NOT EXISTS NotificationRecipients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notification_id INT NOT NULL,
        user_id INT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        read_at TIMESTAMP NULL,
        FOREIGN KEY (notification_id) REFERENCES Notifications(notification_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "NotificationRecipients table created successfully<br>";
    } else {
        echo "Error creating NotificationRecipients table: " . $conn->error . "<br>";
    }
    
    $conn->close();
    return true;
}
?>
