<?php
// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get admin name from the database based on session user_id
function getAdminName() {
    // Default name if we can't fetch from database
    $defaultName = 'مدير النظام';
    
    // If no user_id in session, return default
    if (!isset($_SESSION['user_id'])) {
        return $defaultName;
    }
    
    $admin_id = $_SESSION['user_id'];
    $conn = connectDB();
    
    // First try to get admin's name from a dedicated admins table if it exists
    $sql = "SELECT name FROM admins WHERE admin_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $row['name'];
    }
    
    // If not found in admins table, try users table
    $sql = "SELECT name FROM users WHERE user_id = ? AND role = 'admin' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $row['name'];
    }
    
    // If no name field, try to get email as identifier
    $sql = "SELECT email FROM users WHERE user_id = ? AND role = 'admin' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $row['email'];
    }
    
    $stmt->close();
    $conn->close();
    return $defaultName;
}

// Get the current language
$current_lang = isset($_SESSION['admin_lang']) ? $_SESSION['admin_lang'] : 'en';

// Get admin name
$adminName = getAdminName();

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
$is_dashboard = ($current_page === 'admin_dashboard.php');

// Common translations for navigation
$nav_translations = [
    'en' => [
        'dashboard' => 'Dashboard',
        'add_users' => 'Add Users',
        'view_users' => 'View Users',
        'course_management' => 'Course Management',
        'notifications' => 'Notifications',
        'logout' => 'Logout',
        'english' => 'English',
        'arabic' => 'العربية'
    ],
    'ar' => [
        'dashboard' => 'لوحة التحكم',
        'add_users' => 'إضافة مستخدمين',
        'view_users' => 'عرض المستخدمين',
        'course_management' => 'إدارة المقررات',
        'notifications' => 'الإشعارات',
        'logout' => 'تسجيل خروج',
        'english' => 'English',
        'arabic' => 'العربية'
    ]
];

// Get unread notifications count (if notifications.php is already included)
$unreadNotificationsCount = 0;
if (function_exists('getUnreadNotificationsCount')) {
    $unreadNotificationsCount = getUnreadNotificationsCount();
}
?>

<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
            <img src="admin.jpg" alt="Admin Photo" id="adminPhoto" width="40" height="40" class="rounded-circle" />
            <span class="ms-2 fw-semibold" id="adminName"><?php echo htmlspecialchars($adminName); ?></span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarItems">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarItems">
            <?php if (!$is_dashboard): ?>
            <!-- Hide these navigation links on the dashboard page -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <?php echo $nav_translations[$current_lang]['dashboard']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'add_users.php' ? 'active' : ''; ?>" href="add_users.php">
                        <i class="fas fa-user-plus"></i>
                        <?php echo $nav_translations[$current_lang]['add_users']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'view_users.php' ? 'active' : ''; ?>" href="view_users.php">
                        <i class="fas fa-users"></i>
                        <?php echo $nav_translations[$current_lang]['view_users']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'course_enrollment_management.php' ? 'active' : ''; ?>" href="course_enrollment_management.php">
                        <i class="fas fa-book"></i>
                        <?php echo $nav_translations[$current_lang]['course_management']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'notifications_management.php' ? 'active' : ''; ?>" href="notifications_management.php">
                        <i class="fas fa-bell"></i>
                        <?php echo $nav_translations[$current_lang]['notifications']; ?>
                        <?php if ($unreadNotificationsCount > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $unreadNotificationsCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            <?php endif; ?>

            <ul class="navbar-nav <?php echo $is_dashboard ? 'me-auto' : 'ms-auto'; ?> align-items-center">
                <li class="nav-item dropdown me-2">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-globe"></i>
                        <?php echo $current_lang == 'ar' ? 'العربية' : 'English'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?lang=en"><?php echo $nav_translations[$current_lang]['english']; ?></a></li>
                        <li><a class="dropdown-item" href="?lang=ar"><?php echo $nav_translations[$current_lang]['arabic']; ?></a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="btn btn-danger" id="logoutBtn">
                        <i class="fas fa-sign-out-alt"></i>
                        <?php echo $nav_translations[$current_lang]['logout']; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
