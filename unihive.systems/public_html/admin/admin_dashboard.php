<?php
// Main Admin Dashboard

require_once 'auth.php';
require_once 'db_functions.php';
require_once 'student_operations.php';
require_once 'professor_operations.php';
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
    
    // Handle student forms
    handleStudentForms();
    
    // Handle professor forms
    handleProfessorForms();
    
    // Redirect to prevent form resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Statistics
$statistics = getStatistics();
$totalStudents = $statistics['students'];
$totalProfessors = $statistics['professors'];

// Prepare translations for the UI
$translations = [
    'en' => [
        'dashboard_title' => 'Admin Dashboard',
        'total_students' => 'Total Students',
        'total_professors' => 'Total Professors',
        'add_student' => 'Add Student',
        'add_professor' => 'Add Professor',
        'view_students' => 'View Students',
        'view_professors' => 'View Professors',
        'view_users' => 'View Users',
        'notifications' => 'Notifications',
        'course_enrollment' => 'Course Enrollment Management',
        'student_email' => 'Student Email',
        'professor_email' => 'Professor Email',
        'password' => 'Password',
        'student_name' => 'Student Name',
        'professor_name' => 'Professor Name',
        'level' => 'Level',
        'department_id' => 'Department ID',
        'add' => 'Add',
        'students_list' => 'Students List',
        'professors_list' => 'Professors List',
        'id' => 'ID',
        'email' => 'Email',
        'name' => 'Name',
        'actions' => 'Actions',
        'update' => 'Update',
        'delete' => 'Delete',
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
        'no_students' => 'No students found',
        'no_professors' => 'No professors found',
        'logout' => 'Logout'
    ],
    'ar' => [
        'dashboard_title' => 'لوحة تحكم المسؤول',
        'total_students' => 'إجمالي الطلاب',
        'total_professors' => 'إجمالي الأساتذة',
        'add_student' => 'إضافة طالب',
        'add_professor' => 'إضافة أستاذ',
        'view_students' => 'عرض الطلاب',
        'view_professors' => 'عرض الأساتذة',
        'view_users' => 'عرض المستخدمين',
        'notifications' => 'الإشعارات',
        'course_enrollment' => 'إدارة تسجيل المقررات',
        'student_email' => 'بريد الطالب الإلكتروني',
        'professor_email' => 'بريد الأستاذ الإلكتروني',
        'password' => 'كلمة المرور',
        'student_name' => 'اسم الطالب',
        'professor_name' => 'اسم الأستاذ',
        'level' => 'المستوى',
        'department_id' => 'رقم القسم',
        'add' => 'إضافة',
        'students_list' => 'قائمة الطلاب',
        'professors_list' => 'قائمة الأساتذة',
        'id' => 'الرقم',
        'email' => 'البريد الإلكتروني',
        'name' => 'الاسم',
        'actions' => 'الإجراءات',
        'update' => 'تحديث',
        'delete' => 'حذف',
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
        'no_students' => 'لا يوجد طلاب',
        'no_professors' => 'لا يوجد أساتذة',
        'logout' => 'تسجيل خروج'
    ]
];

// Get the translations for the current language
$t = $translations[$current_lang];

// Fetch data
$studentsResult = getAllStudents();
$professorsResult = getAllProfessors();
$coursesResult = getAllCourses();
$notificationsResult = getAllNotifications();

// Get latest notifications for preview
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$latestNotifications = getLatestNotifications($user_id, 5);

// تسجيل زيارة للصفحة الحالية
trackPageVisit('admin_dashboard.php', $t['dashboard_title'], 'fas fa-tachometer-alt', 'primary');

// جلب أحدث الصفحات المزارة
$recentPages = getRecentPages(4);

?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['dashboard_title']; ?></title>
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
    <style>
        /* تحسين تناسق التصميم */
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --primary-light: #dbeafe;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #0ea5e9;
            --neutral-50: #f9fafb;
            --neutral-100: #f3f4f6;
            --neutral-200: #e5e7eb;
            --neutral-700: #374151;
            --neutral-800: #1f2937;
            --neutral-900: #111827;
            --card-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            --border-radius: 1rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2.5rem;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--neutral-50);
            color: var(--neutral-700);
            line-height: 1.6;
        }
        
        /* تناسق العناوين */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            color: var(--neutral-800);
        }
        
        /* تناسق العنصر الرئيسي */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: var(--spacing-lg);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-lg);
            text-align: center;
            box-shadow: 0 10px 25px rgba(43, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 80%);
            z-index: 1;
        }
        
        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }
        
        .dashboard-header p {
            font-size: 1.2rem;
            margin: 0.75rem 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        /* تناسق بطاقات الإحصائيات */
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            justify-content: space-between;
            margin-bottom: var(--spacing-lg);
        }
        
        .stats-card {
            flex: 1;
            min-width: 200px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: var(--spacing-md);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            height: 4px;
            top: 0;
            left: 25%;
            right: 25%;
            border-radius: 4px;
        }
        
        .stats-card:nth-child(1)::before {
            background-color: var(--primary-color);
        }
        
        .stats-card:nth-child(2)::before {
            background-color: var(--success-color);
        }
        
        .stats-card:nth-child(3)::before {
            background-color: var(--warning-color);
        }
        
        .stats-card:nth-child(4)::before {
            background-color: var(--danger-color);
        }
        
        .stats-card:hover {
            transform: translateY(-7px);
            box-shadow: var(--hover-shadow);
        }
        
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .stats-card:nth-child(1) i {
            color: var(--primary-color);
        }
        
        .stats-card:nth-child(2) i {
            color: var(--success-color);
        }
        
        .stats-card:nth-child(3) i {
            color: var(--warning-color);
        }
        
        .stats-card:nth-child(4) i {
            color: var(--danger-color);
        }
        
        .stats-card h2 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: var(--neutral-800);
        }
        
        .stats-card p {
            font-size: 1rem;
            color: var(--neutral-700);
            margin: 0.25rem 0 0;
            opacity: 0.8;
        }
        
        /* تناسق البطاقات العامة */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            margin-bottom: var(--spacing-md);
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            background: var(--neutral-100);
            border-bottom: none;
            padding: var(--spacing-sm) var(--spacing-md);
            font-weight: 700;
            color: var(--neutral-800);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-header i {
            color: var(--primary-color);
        }
        
        .card-body {
            padding: var(--spacing-md);
        }
        
        /* تناسق التبويبات */
        .nav-tabs {
            border: none;
            background: var(--neutral-100);
            border-radius: 0.75rem;
            padding: 0.5rem;
            margin-bottom: var(--spacing-md);
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1.25rem;
            color: var(--neutral-700);
            font-weight: 600;
            margin: 0 0.25rem;
            transition: all 0.2s ease;
        }
        
        .nav-tabs .nav-link:hover {
            background: var(--neutral-200);
            color: var(--primary-color);
        }
        
        .nav-tabs .nav-link.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .nav-tabs .nav-link i {
            margin-right: 0.5rem;
            opacity: 0.8;
        }
        
        /* تناسق قائمة الإشعارات */
        .list-group-item {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 1rem;
            transition: background-color 0.2s ease;
        }
        
        .list-group-item:hover {
            background-color: var(--neutral-100);
        }
        
        .list-group-item-primary {
            background-color: var(--primary-light);
        }
        
        .list-group-item h6 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        /* تحسين التناسق للشاشات المختلفة */
        @media (max-width: 991px) {
            .dashboard-header {
                padding: var(--spacing-md);
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .card-body {
                padding: var(--spacing-sm);
            }
            
            .feature-icon {
                font-size: 1.25rem !important;
            }
        }
        
        @media (max-width: 767px) {
            .stats-container {
                flex-direction: column;
            }
            
            .stats-card {
                min-width: 100%;
            }
            
            .dashboard-header h1 {
                font-size: 1.75rem;
            }
            
            .dashboard-header p {
                font-size: 1rem;
            }
        }
        
        /* تنسيق أيقونات الميزات */
        .feature-icon {
            font-size: 1.5rem;
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-light);
            color: var(--primary-color);
            margin-right: 1rem;
        }
        
        /* تنسيق مجموعات العناصر */
        .feature-group {
            margin-bottom: 2rem;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .feature-info h5 {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        
        .feature-info p {
            color: var(--neutral-700);
            margin-bottom: 0;
        }

        /* تحسين قسم الصفحات الأخيرة */
        .recent-pages-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: var(--spacing-md);
        }

        .recent-page-item {
            display: flex;
            align-items: center;
            padding: var(--spacing-sm);
            border-radius: var(--border-radius);
            background-color: var(--neutral-100);
            text-decoration: none;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .recent-page-item:hover {
            background-color: var(--neutral-200);
            box-shadow: var(--hover-shadow);
        }

        .recent-page-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--spacing-sm);
        }

        .recent-page-content {
            flex-grow: 1;
        }

        .recent-page-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .recent-page-time {
            font-size: 0.875rem;
            color: var(--neutral-700);
        }

        .recent-page-arrow {
            font-size: 1.25rem;
            color: var(--neutral-700);
        }
    </style>
</head>
<body class="bg-light">
<?php 
// Make sure the includes directory exists
if (!file_exists('includes')) {
    mkdir('includes', 0755, true);
}
include 'includes/header.php'; 
?>

<div class="container">
    <div class="dashboard-header">
        <h1><?php echo $t['dashboard_title']; ?></h1>
        <p><?php echo $current_lang == 'ar' ? 'مرحباً بك في لوحة التحكم الخاصة بك' : 'Welcome to your admin dashboard'; ?></p>
    </div>

    <div class="stats-container">
        <div class="stats-card">
            <i class="fas fa-user-graduate"></i>
            <h2><?php echo $totalStudents; ?></h2>
            <p><?php echo $t['total_students']; ?></p>
        </div>
        <div class="stats-card">
            <i class="fas fa-chalkboard-teacher"></i>
            <h2><?php echo $totalProfessors; ?></h2>
            <p><?php echo $t['total_professors']; ?></p>
        </div>
        <div class="stats-card">
            <i class="fas fa-bell"></i>
            <h2><?php echo $notificationsResult ? $notificationsResult->num_rows : 0; ?></h2>
            <p><?php echo $t['notifications']; ?></p>
        </div>
        <div class="stats-card">
            <i class="fas fa-book"></i>
            <h2><?php echo $coursesResult ? $coursesResult->num_rows : 0; ?></h2>
            <p><?php echo $t['course_enrollment']; ?></p>
        </div>
    </div>

    <?php if (!empty($notification_message)): ?>
    <div class="alert alert-success mb-4">
        <?php echo $notification_message; ?>
    </div>
    <?php endif; ?>

    <!-- إعادة إضافة أزرار التنقل الرئيسية التي اختفت -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card navigation-card">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="add_users.php" class="btn btn-light px-4 py-2">
                            <i class="fas fa-user-plus text-primary"></i>
                            <span class="ms-2"><?php echo $current_lang == 'ar' ? 'إضافة مستخدمين' : 'Add Users'; ?></span>
                        </a>
                        <a href="view_users.php" class="btn btn-light px-4 py-2">
                            <i class="fas fa-list text-primary"></i>
                            <span class="ms-2"><?php echo $t['view_users']; ?></span>
                        </a>
                        <a href="notifications_management.php" class="btn btn-light px-4 py-2">
                            <i class="fas fa-bell text-primary"></i>
                            <span class="ms-2"><?php echo $current_lang == 'ar' ? 'إدارة الإشعارات' : 'Notifications'; ?></span>
                        </a>
                        <a href="course_enrollment_management.php" class="btn btn-light px-4 py-2">
                            <i class="fas fa-graduation-cap text-primary"></i>
                            <span class="ms-2"><?php echo $t['course_enrollment']; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> <?php echo $current_lang == 'ar' ? 'معلومات لوحة التحكم' : 'Dashboard Information'; ?>
                </div>
                <div class="card-body">
                    <p class="lead mb-4"><?php echo $current_lang == 'ar' ? 'مرحباً بك في لوحة التحكم الخاصة بنظام إدارة التعليم.' : 'Welcome to your Learning Management System dashboard.'; ?></p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="feature-info">
                                    <h5><?php echo $current_lang == 'ar' ? 'إدارة المستخدمين' : 'Manage Users'; ?></h5>
                                    <p><?php echo $current_lang == 'ar' ? 'إضافة، تعديل، وحذف المستخدمين' : 'Add, edit, and delete users'; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="feature-info">
                                    <h5><?php echo $current_lang == 'ar' ? 'إدارة المقررات' : 'Manage Courses'; ?></h5>
                                    <p><?php echo $current_lang == 'ar' ? 'إدارة المقررات والتسجيل فيها' : 'Manage courses and enrollment'; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="feature-info">
                                    <h5><?php echo $current_lang == 'ar' ? 'إرسال الإشعارات' : 'Send Notifications'; ?></h5>
                                    <p><?php echo $current_lang == 'ar' ? 'إرسال الإشعارات للطلاب' : 'Send notifications to students'; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="feature-info">
                                    <h5><?php echo $current_lang == 'ar' ? 'متابعة الإحصائيات' : 'Track Statistics'; ?></h5>
                                    <p><?php echo $current_lang == 'ar' ? 'متابعة إحصائيات النظام' : 'Monitor system statistics'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-history"></i> <?php echo $current_lang == 'ar' ? 'الصفحات الأخيرة' : 'Recent Pages'; ?>
                </div>
                <div class="card-body p-0">
                    <div class="recent-pages-list">
                        <?php foreach($recentPages as $index => $page): ?>
                            <a href="<?php echo $page['path']; ?>" class="recent-page-item">
                                <div class="recent-page-icon" style="background-color: var(--<?php echo $page['color']; ?>-light);">
                                    <i class="<?php echo $page['icon']; ?>" style="color: var(--<?php echo $page['color']; ?>-color);"></i>
                                </div>
                                <div class="recent-page-content">
                                    <h6 class="recent-page-title"><?php echo $current_lang == 'ar' ? ($page['title_ar'] ?? $page['title']) : $page['title']; ?></h6>
                                    <div class="recent-page-time">
                                        <i class="far fa-clock me-1 opacity-50"></i>
                                        <small>
                                            <?php 
                                            $timeAgo = time() - $page['timestamp'];
                                            if ($timeAgo < 60) {
                                                echo $current_lang == 'ar' ? 'الآن' : 'just now';
                                            } elseif ($timeAgo < 3600) {
                                                $mins = floor($timeAgo / 60);
                                                echo $current_lang == 'ar' ? "منذ $mins دقيقة" : "$mins min ago";
                                            } elseif ($timeAgo < 86400) {
                                                $hours = floor($timeAgo / 3600);
                                                echo $current_lang == 'ar' ? "منذ $hours ساعة" : "$hours hr ago";
                                            } else {
                                                $days = floor($timeAgo / 86400);
                                                echo $current_lang == 'ar' ? "منذ $days يوم" : "$days days ago";
                                            }
                                            ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="recent-page-arrow">
                                    <i class="fas fa-chevron-<?php echo $current_lang == 'ar' ? 'left' : 'right'; ?>"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($recentPages) == 0): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-history d-block mb-3" style="font-size: 2.5rem; opacity: 0.2;"></i>
                            <p><?php echo $current_lang == 'ar' ? 'لا توجد صفحات مزارة حتى الآن' : 'No recently visited pages yet'; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-link"></i> <?php echo $current_lang == 'ar' ? 'روابط سريعة' : 'Quick Links'; ?>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="view_users.php?active_tab=students" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-graduate me-2 text-primary"></i>
                            <?php echo $current_lang == 'ar' ? 'عرض الطلاب' : 'View Students'; ?>
                        </a>
                        <a href="view_users.php?active_tab=professors" class="list-group-item list-group-item-action">
                            <i class="fas fa-chalkboard-teacher me-2 text-success"></i>
                            <?php echo $current_lang == 'ar' ? 'عرض الأساتذة' : 'View Professors'; ?>
                        </a>
                        <a href="course_enrollment_management.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book-open me-2 text-info"></i>
                            <?php echo $current_lang == 'ar' ? 'إدارة المقررات' : 'Manage Courses'; ?>
                        </a>
                        <a href="notifications_management.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-bell me-2 text-warning"></i>
                            <?php echo $current_lang == 'ar' ? 'إدارة الإشعارات' : 'Manage Notifications'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="ui-components.js"></script>
<script src="admin_dashboard.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>