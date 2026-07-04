<?php
// Notifications dropdown component to be included in various pages
if (!function_exists('getLatestNotifications')) {
    require_once 'notifications.php';
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$notifications = getLatestNotifications($user_id, 5);
$unreadCount = getUnreadNotificationsCount();

$dropdown_translations = [
    'en' => [
        'notifications' => 'Notifications',
        'no_notifications' => 'No notifications',
        'view_all' => 'View all notifications',
        'mark_read' => 'Mark as read'
    ],
    'ar' => [
        'notifications' => 'الإشعارات',
        'no_notifications' => 'لا توجد إشعارات',
        'view_all' => 'عرض جميع الإشعارات',
        'mark_read' => 'تحديد كمقروء'
    ]
];

$t = $dropdown_translations[$current_lang ?? 'en'];
?>

<div class="dropdown">
    <a class="btn btn-light position-relative" href="#" role="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo $unreadCount; ?>
                <span class="visually-hidden">unread notifications</span>
            </span>
        <?php endif; ?>
    </a>
    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 320px; max-height: 450px; overflow-y: auto;">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-bell me-1"></i> <?php echo $t['notifications']; ?>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($notifications)): ?>
                    <div class="list-group-item text-center py-3"><?php echo $t['no_notifications']; ?></div>
                <?php else: ?>
                    <?php foreach($notifications as $notification): ?>
                        <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?> p-3">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo $notification['title']; ?></h6>
                                <small class="text-muted"><?php echo date('M d', strtotime($notification['created_at'])); ?></small>
                            </div>
                            <p class="mb-1 small"><?php echo substr($notification['message'], 0, 80) . (strlen($notification['message']) > 80 ? '...' : ''); ?></p>
                            <?php if (!$notification['is_read']): ?>
                                <form method="post" action="mark_notification_read.php" class="mt-2">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo $t['mark_read']; ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <a href="notifications_management.php" class="btn btn-sm btn-primary"><?php echo $t['view_all']; ?></a>
            </div>
        </div>
    </div>
</div>
