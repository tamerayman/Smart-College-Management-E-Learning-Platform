<?php
// Notifications Management Page

require_once 'auth.php';
require_once 'db_functions.php';
require_once 'notifications.php';
require_once 'includes/page_tracker.php'; // استيراد ملف تتبع الصفحات

// Check user authorization
checkAdminAuth();

// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle language setting from URL or session
if (isset($_GET['lang'])) {
    // If language is specified in URL, update session
    $_SESSION['admin_lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['admin_lang'])) {
    // Set default language if not set
    $_SESSION['admin_lang'] = 'en';
}

// Use session language as current language
$current_lang = $_SESSION['admin_lang'];

// تسجيل زيارة للصفحة الحالية
trackPageVisit('notifications_management.php', $current_lang == 'ar' ? 'إدارة الإشعارات' : 'Notifications Management', 'fas fa-bell', 'warning');

// Get notification message from session and clear it
$notification_message = "";
if (isset($_SESSION['notification_message'])) {
    $notification_message = $_SESSION['notification_message'];
    unset($_SESSION['notification_message']); // Clear the message after displaying it
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle notification forms
    $result = handleNotificationForms();
    
    if (!empty($result)) {
        // Store message in session and redirect to prevent form resubmission
        $_SESSION['notification_message'] = $result;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Redirect to prevent form resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Prepare translations for the UI
$translations = [
    'en' => [
        'page_title' => 'Notifications Management',
        'send_notification' => 'Send Notification',
        'notification_title' => 'Notification Title',
        'notification_message' => 'Notification Message',
        'send_to' => 'Send to:',
        'all_students' => 'All Students',
        'course_students' => 'Course Students',
        'select_course' => 'Select Course',
        'send_to_all' => 'Send to All Students',
        'send_to_course' => 'Send to Course Students',
        'sent_notifications' => 'Sent Notifications',
        'title' => 'Title',
        'target' => 'Target',
        'read' => 'Read',
        'date' => 'Date',
        'no_notifications' => 'No notifications sent yet',
        'dashboard' => 'Dashboard',
        'add_users' => 'Add Users',
        'view_users' => 'View Users',
        'course_management' => 'Course Management',
        'notifications' => 'Notifications',
        'logout' => 'Logout'
    ],
    'ar' => [
        'page_title' => 'إدارة الإشعارات',
        'send_notification' => 'إرسال إشعار',
        'notification_title' => 'عنوان الإشعار',
        'notification_message' => 'رسالة الإشعار',
        'send_to' => 'إرسال إلى:',
        'all_students' => 'جميع الطلاب',
        'course_students' => 'طلاب المقرر',
        'select_course' => 'اختر المقرر',
        'send_to_all' => 'إرسال لجميع الطلاب',
        'send_to_course' => 'إرسال لطلاب المقرر',
        'sent_notifications' => 'الإشعارات المرسلة',
        'title' => 'العنوان',
        'target' => 'الهدف',
        'read' => 'مقروء',
        'date' => 'التاريخ',
        'no_notifications' => 'لا توجد إشعارات مرسلة حتى الآن',
        'dashboard' => 'لوحة التحكم',
        'add_users' => 'إضافة مستخدمين',
        'view_users' => 'عرض المستخدمين',
        'course_management' => 'إدارة المقررات',
        'notifications' => 'الإشعارات',
        'logout' => 'تسجيل خروج'
    ]
];

// Get the translations for the current language
$t = $translations[$current_lang];

// Fetch data
$coursesResult = getAllCourses();
$notificationsResult = getAllNotifications();

// Get statistics for notifications
function getNotificationsStatistics() {
    $conn = connectDB();
    
    $stats = [
        'total' => 0,
        'read' => 0,
        'unread' => 0,
        'all_target' => 0,
        'course_target' => 0
    ];
    
    // Check if the Notifications table exists
    $tableExistsResult = $conn->query("SHOW TABLES LIKE 'Notifications'");
    if ($tableExistsResult->num_rows == 0) {
        return $stats;
    }
    
    // Get total notifications
    $totalQuery = "SELECT COUNT(*) as count FROM Notifications";
    $result = $conn->query($totalQuery);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total'] = $row['count'];
    }
    
    // Get notifications by target type
    $targetTypeQuery = "SELECT target_type, COUNT(*) as count FROM Notifications GROUP BY target_type";
    $result = $conn->query($targetTypeQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['target_type'] == 'all') {
                $stats['all_target'] = $row['count'];
            } elseif ($row['target_type'] == 'course') {
                $stats['course_target'] = $row['count'];
            }
        }
    }
    
    // Get read/unread statistics
    $readStatsQuery = "SELECT SUM(is_read) as read_count, COUNT(*) as total FROM NotificationRecipients";
    $result = $conn->query($readStatsQuery);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['read'] = $row['read_count'] ?: 0;
        $stats['unread'] = $row['total'] - $stats['read'];
    }
    
    $conn->close();
    return $stats;
}

// Fetch notification statistics
$notificationStats = getNotificationsStatistics();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['page_title']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <?php if($current_lang == 'ar'): ?>
    <style>
        body {
            direction: rtl;
            text-align: right;
        }
    </style>
    <?php endif; ?>
</head>
<body class="bg-light" <?php echo $current_lang == 'ar' ? 'dir="rtl"' : ''; ?>>
<?php 
// Include header file if it exists
if (file_exists('includes/header.php')) {
    include 'includes/header.php';
} else {
?>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
            <img src="admin.jpg" alt="Admin Photo" id="adminPhoto" width="40" height="40" class="rounded-circle" />
            <span class="ms-2 fw-semibold" id="adminName">Admin Name</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarItems">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarItems">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <?php echo $t['dashboard']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_users.php">
                        <i class="fas fa-user-plus"></i>
                        <?php echo $t['add_users']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_users.php">
                        <i class="fas fa-users"></i>
                        <?php echo $t['view_users']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="course_enrollment_management.php">
                        <i class="fas fa-book"></i>
                        <?php echo $t['course_management']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="notifications_management.php">
                        <i class="fas fa-bell"></i>
                        <?php echo $t['notifications']; ?>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown me-2">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-globe"></i>
                        <?php echo $current_lang == 'ar' ? 'العربية' : 'English'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?lang=en">English</a></li>
                        <li><a class="dropdown-item" href="?lang=ar">العربية</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="btn btn-danger" id="logoutBtn">
                        <i class="fas fa-sign-out-alt"></i>
                        <?php echo $t['logout']; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php } ?>

<?php if (!empty($notification_message)): ?>
<div class="container">
    <div class="alert alert-success"><?php echo $notification_message; ?></div>
</div>
<?php endif; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $t['page_title']; ?></h2>
        <a href="admin_dashboard.php<?php echo $current_lang == 'ar' ? '?lang=ar' : ''; ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i> <?php echo $current_lang == 'ar' ? 'العودة إلى لوحة التحكم' : 'Back to Dashboard'; ?>
        </a>
    </div>
    
    <!-- Notification Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body card-stats card-stats-primary">
                    <div class="card-stats-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="card-stats-content">
                        <h4><?php echo $current_lang == 'ar' ? 'إجمالي الإشعارات' : 'Total Notifications'; ?></h4>
                        <h2><?php echo $notificationStats['total']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body card-stats card-stats-success">
                    <div class="card-stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-stats-content">
                        <h4><?php echo $current_lang == 'ar' ? 'الإشعارات المقروءة' : 'Read Notifications'; ?></h4>
                        <h2><?php echo $notificationStats['read']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body card-stats card-stats-warning">
                    <div class="card-stats-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="card-stats-content">
                        <h4><?php echo $current_lang == 'ar' ? 'إشعارات لجميع الطلاب' : 'All Students Notifications'; ?></h4>
                        <h2><?php echo $notificationStats['all_target']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body card-stats card-stats-info">
                    <div class="card-stats-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="card-stats-content">
                        <h4><?php echo $current_lang == 'ar' ? 'إشعارات للمقررات' : 'Course Notifications'; ?></h4>
                        <h2><?php echo $notificationStats['course_target']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-paper-plane me-2"></i><?php echo $t['send_notification']; ?>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="notification_title" class="form-label"><?php echo $t['notification_title']; ?></label>
                            <input type="text" class="form-control" id="notification_title" name="notification_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="notification_message" class="form-label"><?php echo $t['notification_message']; ?></label>
                            <textarea class="form-control" id="notification_message" name="notification_message" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-block"><?php echo $t['send_to']; ?></label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target_type" id="target_all" value="all" checked>
                                <label class="form-check-label" for="target_all"><?php echo $t['all_students']; ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target_type" id="target_course" value="course">
                                <label class="form-check-label" for="target_course"><?php echo $t['course_students']; ?></label>
                            </div>
                        </div>
                        
                        <div class="mb-3 course-select" style="display: none;">
                            <label for="course_id" class="form-label"><?php echo $t['select_course']; ?></label>
                            <select class="form-select" id="course_id" name="course_id">
                                <option value=""><?php echo $t['select_course']; ?></option>
                                <?php if ($coursesResult && $coursesResult->num_rows > 0): ?>
                                    <?php while ($course = $coursesResult->fetch_assoc()): ?>
                                        <option value="<?php echo $course['course_id']; ?>"><?php echo isset($course['course_name']) ? $course['course_name'] : $course['name']; ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="send_notification_all" class="btn btn-primary send-all">
                                <i class="fas fa-paper-plane me-2"></i><?php echo $t['send_to_all']; ?>
                            </button>
                            <button type="submit" name="send_notification_course" class="btn btn-primary send-course" style="display: none;">
                                <i class="fas fa-paper-plane me-2"></i><?php echo $t['send_to_course']; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history me-2"></i><?php echo $t['sent_notifications']; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th><?php echo $t['title']; ?></th>
                                    <th><?php echo $t['target']; ?></th>
                                    <th><?php echo $t['read']; ?></th>
                                    <th><?php echo $t['date']; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($notificationsResult && $notificationsResult->num_rows > 0): ?>
                                    <?php while ($notification = $notificationsResult->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo $notification['title']; ?></td>
                                            <td>
                                                <?php if ($notification['target_type'] == 'all'): ?>
                                                    <span class="badge bg-info"><?php echo $notification['target_name']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary"><?php echo $notification['target_name']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2"><?php echo $notification['read_count']; ?>/<?php echo $notification['recipient_count']; ?></div>
                                                    <?php 
                                                    $percentage = ($notification['recipient_count'] > 0) ? ($notification['read_count'] / $notification['recipient_count']) * 100 : 0;
                                                    ?>
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                                             aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($notification['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="fas fa-bell-slash mb-3 d-block" style="font-size: 2rem;"></i>
                                            <?php echo $t['no_notifications']; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Notification target type handling
    const targetTypeRadios = document.querySelectorAll('input[name="target_type"]');
    const courseSelect = document.querySelector('.course-select');
    const sendAllBtn = document.querySelector('.send-all');
    const sendCourseBtn = document.querySelector('.send-course');
    
    targetTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'course') {
                courseSelect.style.display = 'block';
                sendAllBtn.style.display = 'none';
                sendCourseBtn.style.display = 'block';
            } else {
                courseSelect.style.display = 'none';
                sendAllBtn.style.display = 'block';
                sendCourseBtn.style.display = 'none';
            }
        });
    });
    
    // Validate course selection before submitting
    const notificationForm = document.querySelector('form');
    if (notificationForm) {
        notificationForm.addEventListener('submit', function(e) {
            const selectedTargetType = document.querySelector('input[name="target_type"]:checked');
            if (selectedTargetType && selectedTargetType.value === 'course') {
                const courseId = document.querySelector('select[name="course_id"]').value;
                if (!courseId) {
                    e.preventDefault();
                    alert('<?php echo $current_lang == "ar" ? "الرجاء اختيار مقرر قبل إرسال الإشعار" : "Please select a course before sending notification"; ?>');
                }
            }
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
