<?php
require_once 'config.php';

// Function to send a notification to all students
function sendNotificationToAll($title, $message, $sender_id) {
    $conn = connectDB();
    
    // Check if a similar notification was sent in the last hour to prevent duplicates
    $stmt = $conn->prepare("
        SELECT notification_id 
        FROM Notifications 
        WHERE title = ? 
        AND message = ? 
        AND sender_id = ? 
        AND target_type = 'all'
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->bind_param("ssi", $title, $message, $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Notification already sent recently - prevent duplicate
        $stmt->close();
        $conn->close();
        return true;
    }
    
    // Insert notification
    $stmt = $conn->prepare("INSERT INTO Notifications (title, message, sender_id, target_type) VALUES (?, ?, ?, 'all')");
    $stmt->bind_param("ssi", $title, $message, $sender_id);
    $stmt->execute();
    $notification_id = $stmt->insert_id;
    $stmt->close();
    
    // Get all students
    $students = $conn->query("SELECT user_id FROM Users WHERE role = 'student'");
    
    // Add each student as recipient
    $stmt = $conn->prepare("INSERT INTO NotificationRecipients (notification_id, user_id) VALUES (?, ?)");
    while ($student = $students->fetch_assoc()) {
        $stmt->bind_param("ii", $notification_id, $student['user_id']);
        $stmt->execute();
    }
    $stmt->close();
    
    $conn->close();
    return true;
}

// Function to send notification to students in a specific course
function sendNotificationToCourse($title, $message, $sender_id, $course_id) {
    $conn = connectDB();
    
    // Enable transaction support
    $conn->begin_transaction();
    
    try {
        // Validate course_id
        if (empty($course_id) || !is_numeric($course_id)) {
            throw new Exception("Invalid course ID");
        }
        
        // Determine course name column
        $courseNameColumn = getCourseNameColumn($conn);
        if (!$courseNameColumn) {
            $courseNameColumn = "course_id"; // Fallback to ID if no name column found
        }
        
        // Check if course exists
        $stmt = $conn->prepare("SELECT course_id, $courseNameColumn as course_name FROM Courses WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("Course not found");
        }
        $courseData = $result->fetch_assoc();
        $stmt->close();
        
        // Check for duplicate notifications
        $stmt = $conn->prepare("
            SELECT notification_id 
            FROM Notifications 
            WHERE title = ? AND message = ? AND sender_id = ? 
            AND target_type = 'course' AND target_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->bind_param("ssii", $title, $message, $sender_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            throw new Exception("Similar notification already sent recently");
        }
        $stmt->close();
        
        // Get students enrolled in this course
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM CourseEnrollments e
            JOIN Users u ON e.user_id = u.user_id
            WHERE e.course_id = ? AND u.role = 'student'
        ");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $studentCount = $result->fetch_assoc()['count'];
        $stmt->close();
        
        if ($studentCount == 0) {
            throw new Exception("No students enrolled in course: " . $courseData['course_name']);
        }
        
        // Insert the notification
        $stmt = $conn->prepare("
            INSERT INTO Notifications (title, message, sender_id, target_type, target_id) 
            VALUES (?, ?, ?, 'course', ?)
        ");
        $stmt->bind_param("ssii", $title, $message, $sender_id, $course_id);
        $stmt->execute();
        $notification_id = $stmt->insert_id;
        $stmt->close();
        
        // Get students enrolled in the course - using proper JOIN
        $stmt = $conn->prepare("
            SELECT u.user_id 
            FROM Users u
            INNER JOIN CourseEnrollments e ON u.user_id = e.user_id
            WHERE e.course_id = ? AND u.role = 'student'
        ");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $students = $stmt->get_result();
        $stmt->close();
        
        // Add recipients
        $recipientCount = 0;
        $stmt = $conn->prepare("INSERT INTO NotificationRecipients (notification_id, user_id) VALUES (?, ?)");
        
        while ($student = $students->fetch_assoc()) {
            $stmt->bind_param("ii", $notification_id, $student['user_id']);
            $stmt->execute();
            $recipientCount++;
        }
        $stmt->close();
        
        // Check if any recipients were added
        if ($recipientCount == 0) {
            throw new Exception("Failed to add notification recipients");
        }
        
        // All operations successful
        $conn->commit();
        
        return [
            'success' => true,
            'course_name' => $courseData['course_name'],
            'recipient_count' => $recipientCount
        ];
        
    } catch (Exception $e) {
        // Any error occurred, rollback
        $conn->rollback();
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    } finally {
        $conn->close();
    }
}

// Helper function to get course name column
function getCourseNameColumn($conn) {
    $courseColumns = $conn->query("SHOW COLUMNS FROM Courses");
    $columns = [];
    while ($column = $courseColumns->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    if (in_array('course_name', $columns)) {
        return 'course_name';
    } else if (in_array('name', $columns)) {
        return 'name';
    } else if (in_array('title', $columns)) {
        return 'title';
    }
    
    return null;
}

// Function to handle notification forms
function handleNotificationForms() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Skip processing if we just redirected after a successful notification
    if (isset($_SESSION['last_notification_processed']) && $_SESSION['last_notification_processed'] === $_SERVER['REQUEST_TIME']) {
        return "";
    }
    
    if (isset($_POST['send_notification_all'])) {
        $title = $_POST['notification_title'];
        $message = $_POST['notification_message'];
        $sender_id = $_SESSION['user_id'];
        
        if (sendNotificationToAll($title, $message, $sender_id)) {
            // Mark that we've processed this notification
            $_SESSION['last_notification_processed'] = $_SERVER['REQUEST_TIME'];
            return "Notification sent to all students successfully!";
        }
    }
    
    if (isset($_POST['send_notification_course'])) {
        $title = $_POST['notification_title'];
        $message = $_POST['notification_message'];
        $sender_id = $_SESSION['user_id'];
        
        // Validate course_id
        if (!isset($_POST['course_id']) || empty($_POST['course_id'])) {
            return "Error: Please select a course to send notifications to!";
        }
        
        $course_id = $_POST['course_id'];
        $result = sendNotificationToCourse($title, $message, $sender_id, $course_id);
        
        if (is_array($result)) {
            if ($result['success']) {
                // Mark that we've processed this notification
                $_SESSION['last_notification_processed'] = $_SERVER['REQUEST_TIME'];
                return "Success: Notification sent to {$result['recipient_count']} students in course '{$result['course_name']}'";
            } else {
                return "Error: {$result['error']}";
            }
        } else if ($result === true) {
            // Legacy return value for backward compatibility
            $_SESSION['last_notification_processed'] = $_SERVER['REQUEST_TIME'];
            return "Notification sent to students in selected course successfully!";
        } else {
            return "Error: Failed to send notification. Please try again.";
        }
    }
    
    return "";
}

// Function to get all notifications for admin view
function getAllNotifications() {
    $conn = connectDB();
    
    // First check if Notifications table exists
    $notificationsTableExists = $conn->query("SHOW TABLES LIKE 'Notifications'");
    if ($notificationsTableExists->num_rows == 0) {
        // Create the table if it doesn't exist
        include_once '../database/notifications_setup.php';
        setupNotificationsTable();
        return new CustomArrayIterator([]);
    }
    
    // Get the course name column for proper display
    $nameColumn = getCourseNameColumn($conn);
    $courseColumn = $nameColumn ? "c.$nameColumn" : "CONCAT('Course #', n.target_id)";
    
    // Query notifications with recipient counts and read status
    $query = "
        SELECT 
            n.*, 
            COUNT(nr.id) as recipient_count, 
            SUM(CASE WHEN nr.is_read = 1 THEN 1 ELSE 0 END) as read_count,
            CASE 
                WHEN n.target_type = 'course' THEN 
                    (SELECT $courseColumn FROM Courses c WHERE c.course_id = n.target_id LIMIT 1)
                ELSE 'All Students' 
            END as target_name
        FROM Notifications n
        LEFT JOIN NotificationRecipients nr ON n.notification_id = nr.notification_id
        GROUP BY n.notification_id
        ORDER BY n.created_at DESC
    ";
    
    try {
        $result = $conn->query($query);
        if ($result === false) {
            // If query fails, try a simpler version
            $simpleQuery = "SELECT * FROM Notifications ORDER BY created_at DESC";
            $result = $conn->query($simpleQuery);
            
            if ($result && $result->num_rows > 0) {
                $notifications = [];
                while ($row = $result->fetch_assoc()) {
                    $row['recipient_count'] = 0;
                    $row['read_count'] = 0;
                    $row['target_name'] = ($row['target_type'] == 'course') ? 'Course #' . $row['target_id'] : 'All Students';
                    $notifications[] = $row;
                }
                $result = new CustomArrayIterator($notifications);
            } else {
                $result = new CustomArrayIterator([]);
            }
        }
    } catch (Exception $e) {
        // If any error occurs, return empty result
        $result = new CustomArrayIterator([]);
    }
    
    $conn->close();
    return $result;
}

// Function to get unread notifications count
function getUnreadNotificationsCount() {
    $conn = connectDB();
    $count = 0;
    
    // Check if the NotificationRecipients table exists
    $tableExistsResult = $conn->query("SHOW TABLES LIKE 'NotificationRecipients'");
    if ($tableExistsResult->num_rows == 0) {
        return $count;
    }
    
    // Get current user ID (admin in this case)
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Get count of unread notifications for this user
    $query = "SELECT COUNT(*) as count FROM NotificationRecipients WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $count = $row['count'];
    }
    
    $stmt->close();
    $conn->close();
    return $count;
}

// Function to mark notification as read
function markNotificationAsRead($notification_id, $user_id) {
    $conn = connectDB();
    
    // Update the notification status to read
    $query = "UPDATE NotificationRecipients SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    return $success;
}

// Function to get latest notifications for a user
function getLatestNotifications($user_id, $limit = 5) {
    $conn = connectDB();
    $notifications = [];
    
    // Check if tables exist
    $tableExistsResult = $conn->query("SHOW TABLES LIKE 'NotificationRecipients'");
    if ($tableExistsResult->num_rows == 0) {
        return $notifications;
    }
    
    // Get latest notifications for this user
    $query = "SELECT n.notification_id, n.title, n.message, n.created_at, nr.is_read 
              FROM Notifications n
              JOIN NotificationRecipients nr ON n.notification_id = nr.notification_id
              WHERE nr.user_id = ?
              ORDER BY n.created_at DESC
              LIMIT ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    return $notifications;
}

// Helper class to make array behave like mysqli_result for compatibility
// Renamed from ArrayIterator to CustomArrayIterator to avoid conflicts
class CustomArrayIterator implements Iterator {
    private $array;
    private $position = 0;
    public $num_rows = 0;
    
    public function __construct(array $array) {
        $this->array = $array;
        $this->num_rows = count($array);
    }
    
    #[\ReturnTypeWillChange]
    public function rewind() {
        $this->position = 0;
    }
    
    #[\ReturnTypeWillChange]
    public function current() {
        return $this->array[$this->position];
    }
    
    #[\ReturnTypeWillChange]
    public function key() {
        return $this->position;
    }
    
    #[\ReturnTypeWillChange]
    public function next() {
        ++$this->position;
    }
    
    #[\ReturnTypeWillChange]
    public function valid() {
        return isset($this->array[$this->position]);
    }
    
    public function fetch_assoc() {
        if (!$this->valid()) {
            return false;
        }
        $result = $this->current();
        $this->next();
        return $result;
    }
}
?>
