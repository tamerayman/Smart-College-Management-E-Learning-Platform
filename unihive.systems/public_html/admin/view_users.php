<?php
// View and manage students and professors page

require_once 'auth.php';
require_once 'db_functions.php';
require_once 'student_operations.php';
require_once 'professor_operations.php';
require_once 'notifications.php'; // Include notifications functionality
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

// Get search parameters
$studentSearch = isset($_GET['student_search']) ? $_GET['student_search'] : '';
$professorSearch = isset($_GET['professor_search']) ? $_GET['professor_search'] : '';

// Determine active tab
$activeTab = 'students'; // Default tab
if (isset($_GET['active_tab'])) {
    $activeTab = $_GET['active_tab'];
} elseif (isset($_GET['professor_search']) && !empty($_GET['professor_search'])) {
    // If professor search is present, set professors as active tab
    $activeTab = 'professors';
}

// تسجيل زيارة للصفحة المناسبة بناءً على علامة التبويب النشطة
if ($activeTab == 'students') {
    trackPageVisit('view_users.php?active_tab=students', $current_lang == 'ar' ? 'عرض الطلاب' : 'View Students', 'fas fa-user-graduate', 'primary');
} else {
    trackPageVisit('view_users.php?active_tab=professors', $current_lang == 'ar' ? 'عرض الأساتذة' : 'View Professors', 'fas fa-chalkboard-teacher', 'success');
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which tab to return to after form submission
    $redirectTab = 'students'; // Default tab
    
    // If professor form was submitted, set professors as active tab
    if (isset($_POST['update_professor']) || isset($_POST['delete_professor']) || isset($_POST['prof_id'])) {
        $redirectTab = 'professors';
    }
    
    // Handle student forms
    handleStudentForms();
    
    // Handle professor forms
    handleProfessorForms();
    
    // Redirect to prevent form resubmission on refresh, preserving active tab
    $redirectUrl = $_SERVER['PHP_SELF'] . '?active_tab=' . $redirectTab;
    
    // Add language parameter if needed
    if ($current_lang == 'ar') {
        $redirectUrl .= '&lang=ar';
    }
    
    header("Location: " . $redirectUrl);
    exit;
}

// Prepare translations for the UI
$translations = [
    'en' => [
        'page_title' => 'View Users',
        'students_list' => 'Students List',
        'professors_list' => 'Professors List',
        'id' => 'ID',
        'email' => 'Email',
        'name' => 'Name',
        'level' => 'Level',
        'department_id' => 'Department ID',
        'actions' => 'Actions',
        'update' => 'Update',
        'delete' => 'Delete',
        'back_to_dashboard' => 'Back to Dashboard',
        'no_students' => 'No students found',
        'no_professors' => 'No professors found',
        'logout' => 'Logout',
        'search' => 'Search',
        'search_placeholder' => 'Search by ID, name or email',
        'clear' => 'Clear'
    ],
    'ar' => [
        'page_title' => 'عرض المستخدمين',
        'students_list' => 'قائمة الطلاب',
        'professors_list' => 'قائمة الأساتذة',
        'id' => 'الرقم',
        'email' => 'البريد الإلكتروني',
        'name' => 'الاسم',
        'level' => 'المستوى',
        'department_id' => 'رقم القسم',
        'actions' => 'الإجراءات',
        'update' => 'تحديث',
        'delete' => 'حذف',
        'back_to_dashboard' => 'العودة إلى لوحة التحكم',
        'no_students' => 'لا يوجد طلاب',
        'no_professors' => 'لا يوجد أساتذة',
        'logout' => 'تسجيل خروج',
        'search' => 'بحث',
        'search_placeholder' => 'البحث بالرقم أو الاسم أو البريد الإلكتروني',
        'clear' => 'مسح'
    ]
];

// Get the translations for the current language
$t = $translations[$current_lang];

// Fetch data with search parameters
$studentsResult = getAllStudents($studentSearch);
$professorsResult = getAllProfessors($professorSearch);

// Get all departments for dropdown
$departmentsResult = getAllDepartments();
$departments = [];
if ($departmentsResult && $departmentsResult->num_rows > 0) {
    while ($row = $departmentsResult->fetch_assoc()) {
        $departments[$row['department_id']] = $row['name'];
    }
}
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
<?php include 'includes/header.php'; ?>

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
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="usersTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link <?php echo ($activeTab == 'students') ? 'active' : ''; ?>" 
                    id="students-tab" data-bs-toggle="tab" data-bs-target="#students" 
                    type="button" role="tab"><?php echo $t['students_list']; ?></button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?php echo ($activeTab == 'professors') ? 'active' : ''; ?>" 
                    id="professors-tab" data-bs-toggle="tab" data-bs-target="#professors" 
                    type="button" role="tab"><?php echo $t['professors_list']; ?></button>
        </li>
    </ul>

    <div class="tab-content" id="usersTabsContent">
        <!-- Students Tab -->
        <div class="tab-pane fade <?php echo ($activeTab == 'students') ? 'show active' : ''; ?>" 
             id="students" role="tabpanel">
            <h5><?php echo $t['students_list']; ?></h5>
            
            <!-- Add search form for students -->
            <form class="row g-3 mb-3" method="get">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="student_search" 
                               placeholder="<?php echo $t['search_placeholder']; ?>" 
                               value="<?php echo htmlspecialchars($studentSearch); ?>">
                        <input type="hidden" name="active_tab" value="students">
                        <?php if ($current_lang == 'ar'): ?>
                            <input type="hidden" name="lang" value="ar">
                        <?php endif; ?>
                        <button class="btn btn-primary" type="submit"><?php echo $t['search']; ?></button>
                        <a href="<?php echo $_SERVER['PHP_SELF'] . ($current_lang == 'ar' ? '?lang=ar&active_tab=students' : '?active_tab=students'); ?>" 
                           class="btn btn-secondary"><?php echo $t['clear']; ?></a>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive mt-3">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo $t['id']; ?></th>
                            <th><?php echo $t['email']; ?></th>
                            <th><?php echo $t['name']; ?></th>
                            <th><?php echo $t['level']; ?></th>
                            <th><?php echo $t['department_id']; ?></th>
                            <th><?php echo $t['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
                            <?php while ($row = $studentsResult->fetch_assoc()): ?>
                                <tr>
                                    <form method="post">
                                        <td>
                                            <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                            <?php echo $row['student_id']; ?>
                                        </td>
                                        <td>
                                            <input type="text" name="student_email" class="form-control" value="<?php echo isset($row['email']) ? htmlspecialchars($row['email']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="student_name" class="form-control" value="<?php echo isset($row['name']) ? htmlspecialchars($row['name']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="number" name="student_level" class="form-control" value="<?php echo isset($row['level']) ? htmlspecialchars($row['level']) : ''; ?>">
                                        </td>
                                        <td>
                                            <select name="dept_id" class="form-control">
                                                <?php foreach ($departments as $deptId => $deptName): ?>
                                                    <option value="<?php echo $deptId; ?>" <?php echo (isset($row['department_id']) && $row['department_id'] == $deptId) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($deptName); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="submit" name="update_student" class="btn btn-success btn-sm">
                                                <i class="fas fa-save"></i> <?php echo $t['update']; ?>
                                            </button>
                                            <button type="submit" name="delete_student" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> <?php echo $t['delete']; ?>
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center"><?php echo $t['no_students']; ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Professors Tab -->
        <div class="tab-pane fade <?php echo ($activeTab == 'professors') ? 'show active' : ''; ?>" 
             id="professors" role="tabpanel">
            <h5><?php echo $t['professors_list']; ?></h5>
            
            <!-- Add search form for professors -->
            <form class="row g-3 mb-3" method="get">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="professor_search" 
                               placeholder="<?php echo $t['search_placeholder']; ?>" 
                               value="<?php echo htmlspecialchars($professorSearch); ?>">
                        <input type="hidden" name="active_tab" value="professors">
                        <?php if ($current_lang == 'ar'): ?>
                            <input type="hidden" name="lang" value="ar">
                        <?php endif; ?>
                        <button class="btn btn-primary" type="submit"><?php echo $t['search']; ?></button>
                        <a href="<?php echo $_SERVER['PHP_SELF'] . ($current_lang == 'ar' ? '?lang=ar&active_tab=professors' : '?active_tab=professors'); ?>" 
                           class="btn btn-secondary"><?php echo $t['clear']; ?></a>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive mt-3">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo $t['id']; ?></th>
                            <th><?php echo $t['email']; ?></th>
                            <th><?php echo $t['name']; ?></th>
                            <th><?php echo $t['department_id']; ?></th>
                            <th><?php echo $t['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($professorsResult && $professorsResult->num_rows > 0): ?>
                            <?php while ($row = $professorsResult->fetch_assoc()): ?>
                                <tr>
                                    <form method="post">
                                        <td>
                                            <input type="hidden" name="prof_id" value="<?php echo $row['professor_id']; ?>">
                                            <?php echo $row['professor_id']; ?>
                                        </td>
                                        <td>
                                            <input type="text" name="prof_email" class="form-control" value="<?php echo isset($row['email']) ? htmlspecialchars($row['email']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="prof_name" class="form-control" value="<?php echo isset($row['name']) ? htmlspecialchars($row['name']) : ''; ?>">
                                        </td>
                                        <td>
                                            <select name="dept_id" class="form-control">
                                                <?php foreach ($departments as $deptId => $deptName): ?>
                                                    <option value="<?php echo $deptId; ?>" <?php echo (isset($row['department_id']) && $row['department_id'] == $deptId) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($deptName); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="submit" name="update_professor" class="btn btn-success btn-sm">
                                                <i class="fas fa-save"></i> <?php echo $t['update']; ?>
                                            </button>
                                            <button type="submit" name="delete_professor" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> <?php echo $t['delete']; ?>
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center"><?php echo $t['no_professors']; ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="ui-components.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmation for delete actions
    const deleteButtons = document.querySelectorAll('button[name="delete_student"], button[name="delete_professor"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this user?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
</body>
</html>
