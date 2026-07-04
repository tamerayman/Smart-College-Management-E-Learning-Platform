<?php
/**
 * نظام تتبع الصفحات المزارة من قبل المدير
 */

/**
 * تسجيل زيارة صفحة في تاريخ التصفح
 * 
 * @param string $page_path مسار الصفحة
 * @param string $page_title عنوان الصفحة
 * @param string $page_icon أيقونة الصفحة (Font Awesome)
 * @param string $page_color لون الأيقونة (bootstrap color class)
 */
function trackPageVisit($page_path, $page_title, $page_icon, $page_color = 'primary') {
    if (!isset($_SESSION['recent_pages'])) {
        $_SESSION['recent_pages'] = [];
    }
    
    // Remove current page if it exists already in history
    $_SESSION['recent_pages'] = array_filter($_SESSION['recent_pages'], function($page) use ($page_path) {
        return $page['path'] !== $page_path;
    });
    
    // Add current page to the start of the array
    array_unshift($_SESSION['recent_pages'], [
        'path' => $page_path,
        'title' => $page_title,
        'icon' => $page_icon,
        'color' => $page_color,
        'timestamp' => time()
    ]);
    
    // Keep only the most recent 5 pages
    $_SESSION['recent_pages'] = array_slice($_SESSION['recent_pages'], 0, 5);
}

/**
 * الحصول على أحدث الصفحات المزارة
 * 
 * @param int $limit عدد الصفحات المطلوب
 * @return array مصفوفة بالصفحات المزارة
 */
function getRecentPages($limit = 4) {
    if (!isset($_SESSION['recent_pages'])) {
        return getDefaultPages($limit);
    }
    
    $pages = $_SESSION['recent_pages'];
    
    // تجاهل الصفحة الحالية (لوحة التحكم) من قائمة الروابط السريعة
    $pages = array_filter($pages, function($page) {
        return $page['path'] !== 'admin_dashboard.php';
    });
    
    // إذا لم يكن هناك صفحات كافية، أضف الصفحات الافتراضية
    if (count($pages) < $limit) {
        $defaultPages = getDefaultPages(4);
        $existingPaths = array_column($pages, 'path');
        
        foreach ($defaultPages as $page) {
            if (!in_array($page['path'], $existingPaths) && count($pages) < $limit) {
                $pages[] = $page;
            }
        }
    }
    
    return array_slice($pages, 0, $limit);
}

/**
 * الصفحات الافتراضية للروابط السريعة
 */
function getDefaultPages($limit = 4) {
    $defaultPages = [
        [
            'path' => 'view_users.php?active_tab=students',
            'title' => 'View Students',
            'title_ar' => 'عرض الطلاب',
            'icon' => 'fas fa-user-graduate',
            'color' => 'primary',
            'timestamp' => time()
        ],
        [
            'path' => 'view_users.php?active_tab=professors',
            'title' => 'View Professors',
            'title_ar' => 'عرض الأساتذة',
            'icon' => 'fas fa-chalkboard-teacher',
            'color' => 'success',
            'timestamp' => time() - 60
        ],
        [
            'path' => 'course_enrollment_management.php',
            'title' => 'Manage Courses',
            'title_ar' => 'إدارة المقررات',
            'icon' => 'fas fa-book-open', 
            'color' => 'info',
            'timestamp' => time() - 120
        ],
        [
            'path' => 'notifications_management.php',
            'title' => 'Manage Notifications',
            'title_ar' => 'إدارة الإشعارات',
            'icon' => 'fas fa-bell',
            'color' => 'warning',
            'timestamp' => time() - 180
        ]
    ];
    
    return array_slice($defaultPages, 0, $limit);
}
?>
