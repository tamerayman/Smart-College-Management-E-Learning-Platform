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
$conn = connectDB();

// Check if NotificationRecipients table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'NotificationRecipients'");
if ($tableExists->num_rows == 0) {
    // Create the tables if they don't exist
    include_once '../database/notifications_setup.php';
    setupNotificationsTable();
}

// Track last notification fetch time to prevent duplicate fetches on page refresh
$current_time = time();
$fetch_interval = 10; // Minimum seconds between notification fetches
$last_fetch_time = isset($_SESSION['last_notification_fetch']) ? $_SESSION['last_notification_fetch'] : 0;

// Get user's notifications
$stmt = $conn->prepare("
    SELECT n.notification_id, n.title, n.message, n.created_at, nr.is_read 
    FROM Notifications n
    JOIN NotificationRecipients nr ON n.notification_id = nr.notification_id
    WHERE nr.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    // Format date to be more readable
    $date = new DateTime($row['created_at']);
    $row['formatted_date'] = $date->format('M d, Y h:i A');
    
    $notifications[] = $row;
}

// Get count of unread notifications
$stmt = $conn->prepare("
    SELECT COUNT(*) as unread_count 
    FROM NotificationRecipients 
    WHERE user_id = ? AND is_read = 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_result = $stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];

$stmt->close();
$conn->close();

// Update the last fetch time
$_SESSION['last_notification_fetch'] = $current_time;

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => (int)$unread_count
]);
?>
