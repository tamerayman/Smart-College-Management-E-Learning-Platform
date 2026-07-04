<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if notification_id is provided
if (!isset($_POST['notification_id']) && !isset($_POST['mark_all'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$conn = connectDB();

if (isset($_POST['notification_id'])) {
    // Mark specific notification as read
    $notification_id = $_POST['notification_id'];
    
    $stmt = $conn->prepare("
        UPDATE NotificationRecipients 
        SET is_read = 1, read_at = NOW() 
        WHERE notification_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['error' => 'Failed to mark notification as read']);
    }
} else if (isset($_POST['mark_all'])) {
    // Mark all notifications as read
    $stmt = $conn->prepare("
        UPDATE NotificationRecipients 
        SET is_read = 1, read_at = NOW() 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } else {
        echo json_encode(['error' => 'Failed to mark notifications as read']);
    }
}

$conn->close();
?>
