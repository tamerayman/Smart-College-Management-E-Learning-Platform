<?php
// Script to mark notification as read

require_once 'auth.php';
require_once 'notifications.php';

// Check user authorization
checkAdminAuth();

// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if notification_id is provided
if (isset($_POST['notification_id']) && is_numeric($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Mark notification as read
    if (markNotificationAsRead($notification_id, $user_id)) {
        // Success
        $message = 'Notification marked as read';
    } else {
        // Error
        $message = 'Failed to mark notification as read';
    }
    
    // Redirect back to the referring page or to notifications management
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'notifications_management.php';
    
    // Pass status message if needed
    if (!empty($message)) {
        $_SESSION['notification_message'] = $message;
    }
    
    header("Location: $redirect_url");
    exit;
} else {
    // Invalid request
    $_SESSION['notification_message'] = 'Invalid notification ID';
    header("Location: notifications_management.php");
    exit;
}
?>
