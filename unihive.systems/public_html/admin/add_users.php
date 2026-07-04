<?php
// Add Users Page - for adding students and professors

require_once 'auth.php';
require_once 'db_functions.php';
require_once 'student_operations.php';
require_once 'professor_operations.php';
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

// Track page visit
trackPageVisit('add_users.php', $current_lang == 'ar' ? 'إضافة مستخدمين' : 'Add Users', 'fas fa-user-plus', 'primary');

// Get notification message from session and clear it
$notification_message = "";
if (isset($_SESSION['notification_message'])) {
    $notification_message = $_SESSION['notification_message'];
    unset($_SESSION['notification_message']); // Clear the message after displaying it
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine active tab for redirecting after form submission
    $activeTab = 'students'; // Default tab
    
    // Add student
    if (isset($_POST['add_student'])) {
        $activeTab = 'students';
        $result = addStudent(
            $_POST['student_email'],
            password_hash($_POST['student_password'], PASSWORD_DEFAULT), // Hash password
            $_POST['student_name'], // Asegurarse de incluir el nombre
            $_POST['student_level'],
            $_POST['department_id']
        );
        
        if ($result) {
            $_SESSION['notification_message'] = $current_lang == 'ar' ? 'تمت إضافة الطالب بنجاح' : 'Student added successfully';
        } else {
            $_SESSION['notification_message'] = $current_lang == 'ar' ? 'حدث خطأ أثناء إضافة الطالب' : 'Error adding student';
        }
    }
    
    // Add professor
    if (isset($_POST['add_professor'])) {
        $activeTab = 'professors';
        $result = addProfessor(
            $_POST['professor_email'],
            password_hash($_POST['professor_password'], PASSWORD_DEFAULT), // Hash password
            $_POST['professor_name'], // Asegurarse de incluir el nombre
            $_POST['department_id']
        );
        
        if ($result) {
            $_SESSION['notification_message'] = $current_lang == 'ar' ? 'تمت إضافة الأستاذ بنجاح' : 'Professor added successfully';
        } else {
            $_SESSION['notification_message'] = $current_lang == 'ar' ? 'حدث خطأ أثناء إضافة الأستاذ' : 'Error adding professor';
        }
    }
    
    // Redirect to maintain active tab
    header("Location: " . $_SERVER['PHP_SELF'] . "?active_tab=" . $activeTab . ($current_lang == 'ar' ? '&lang=ar' : ''));
    exit;
}

// Determine active tab (students is default)
$activeTab = isset($_GET['active_tab']) ? $_GET['active_tab'] : 'students';

// Get all departments for department dropdown
$departmentsResult = getAllDepartments();

// Prepare translations
$translations = [
    'en' => [
        'add_users' => 'Add Users',
        'add_student' => 'Add New Student',
        'add_professor' => 'Add New Professor',
        'student_email' => 'Student Email',
        'professor_email' => 'Professor Email',
        'password' => 'Password',
        'student_name' => 'Student Name',
        'professor_name' => 'Professor Name',
        'level' => 'Student Level',
        'department' => 'Department',
        'select_department' => 'Select Department',
        'add' => 'Add',
        'cancel' => 'Cancel',
        'back_to_dashboard' => 'Back to Dashboard',
        'copy_success' => 'Password copied to clipboard!',
        'show_password' => 'Show password',
        'hide_password' => 'Hide password',
        'copy_password' => 'Copy password',
    ],
    'ar' => [
        'add_users' => 'إضافة مستخدمين',
        'add_student' => 'إضافة طالب جديد',
        'add_professor' => 'إضافة أستاذ جديد',
        'student_email' => 'البريد الإلكتروني للطالب',
        'professor_email' => 'البريد الإلكتروني للأستاذ',
        'password' => 'كلمة المرور',
        'student_name' => 'اسم الطالب',
        'professor_name' => 'اسم الأستاذ',
        'level' => 'مستوى الطالب',
        'department' => 'القسم',
        'select_department' => 'اختر القسم',
        'add' => 'إضافة',
        'cancel' => 'إلغاء',
        'back_to_dashboard' => 'العودة إلى لوحة التحكم',
        'copy_success' => 'تم نسخ كلمة المرور إلى الحافظة!',
        'show_password' => 'إظهار كلمة المرور',
        'hide_password' => 'إخفاء كلمة المرور',
        'copy_password' => 'نسخ كلمة المرور',
    ]
];

// Get the translations for the current language
$t = $translations[$current_lang];
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['add_users']; ?></title>
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
    <!-- إضافة أنماط للـ toast -->
    <style>
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .rtl .toast-container {
            left: 20px;
            right: auto;
        }
    </style>
</head>

<body class="bg-light <?php echo $current_lang == 'ar' ? 'rtl' : ''; ?>">
<?php include 'includes/header.php'; ?>

<!-- إضافة حاوية Toast -->
<div class="toast-container">
    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" id="copyToast">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i> <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $t['add_users']; ?></h2>
        <a href="admin_dashboard.php<?php echo $current_lang == 'ar' ? '?lang=ar' : ''; ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i> <?php echo $t['back_to_dashboard']; ?>
        </a>
    </div>
    
    <?php if (!empty($notification_message)): ?>
    <div class="alert alert-success mb-4">
        <?php echo $notification_message; ?>
    </div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="usersTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link <?php echo ($activeTab == 'students') ? 'active' : ''; ?>" 
                    id="students-tab" data-bs-toggle="tab" data-bs-target="#students" 
                    type="button" role="tab"><?php echo $t['add_student']; ?></button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?php echo ($activeTab == 'professors') ? 'active' : ''; ?>" 
                    id="professors-tab" data-bs-toggle="tab" data-bs-target="#professors" 
                    type="button" role="tab"><?php echo $t['add_professor']; ?></button>
        </li>
    </ul>
    
    <div class="tab-content" id="addUsersTabContent">
        <!-- Add Student Tab -->
        <div class="tab-pane fade <?php echo ($activeTab == 'students') ? 'show active' : ''; ?>" 
             id="students" role="tabpanel">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?php echo $t['add_student']; ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="addStudentForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="student_email" class="form-label"><?php echo $t['student_email']; ?> <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="student_email" name="student_email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="student_password" class="form-label"><?php echo $t['password']; ?> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="student_password" name="student_password" required>
                                    <button class="btn btn-outline-secondary password-toggle" type="button" data-target="student_password" title="<?php echo $t['show_password']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="generateStudentPassword" title="<?php echo $current_lang == 'ar' ? 'توليد كلمة مرور قوية' : 'Generate strong password'; ?>">
                                        <i class="fas fa-random"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary copy-password" type="button" data-target="student_password" title="<?php echo $t['copy_password']; ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="student_name" class="form-label"><?php echo $t['student_name']; ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="student_name" name="student_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="student_level" class="form-label"><?php echo $t['level']; ?> <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="student_level" name="student_level" min="1" max="12" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="department_id" class="form-label"><?php echo $t['department']; ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value=""><?php echo $t['select_department']; ?></option>
                                <?php if ($departmentsResult && $departmentsResult->num_rows > 0): ?>
                                    <?php while ($department = $departmentsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $department['department_id']; ?>">
                                            <?php echo $department['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-outline-secondary me-2"><?php echo $t['cancel']; ?></button>
                            <button type="submit" name="add_student" class="btn btn-primary"><?php echo $t['add']; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Add Professor Tab -->
        <div class="tab-pane fade <?php echo ($activeTab == 'professors') ? 'show active' : ''; ?>" 
             id="professors" role="tabpanel">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?php echo $t['add_professor']; ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="addProfessorForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="professor_email" class="form-label"><?php echo $t['professor_email']; ?> <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="professor_email" name="professor_email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="professor_password" class="form-label"><?php echo $t['password']; ?> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="professor_password" name="professor_password" required>
                                    <button class="btn btn-outline-secondary password-toggle" type="button" data-target="professor_password" title="<?php echo $t['show_password']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="generateProfessorPassword" title="<?php echo $current_lang == 'ar' ? 'توليد كلمة مرور قوية' : 'Generate strong password'; ?>">
                                        <i class="fas fa-random"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary copy-password" type="button" data-target="professor_password" title="<?php echo $t['copy_password']; ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="professor_name" class="form-label"><?php echo $t['professor_name']; ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="professor_name" name="professor_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="department_id" class="form-label"><?php echo $t['department']; ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    <option value=""><?php echo $t['select_department']; ?></option>
                                    <?php 
                                    // Reset departments result pointer
                                    if ($departmentsResult) {
                                        $departmentsResult->data_seek(0);
                                        while ($department = $departmentsResult->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $department['department_id']; ?>">
                                            <?php echo $department['name']; ?>
                                        </option>
                                    <?php 
                                        endwhile;
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-outline-secondary me-2"><?php echo $t['cancel']; ?></button>
                            <button type="submit" name="add_professor" class="btn btn-primary"><?php echo $t['add']; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Maintain selected tab after page reload
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('active_tab');
    
    if (activeTab) {
        const tab = document.querySelector('#' + activeTab + '-tab');
        if (tab) {
            const bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }
    
    // Password generator function - تعديل الطول إلى 15 حرف
    function generateStrongPassword(length = 15) {
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+";
        let password = "";
        for (let i = 0, n = charset.length; i < length; ++i) {
            password += charset.charAt(Math.floor(Math.random() * n));
        }
        return password;
    }

    // Student password generator button
    const genStudentBtn = document.getElementById('generateStudentPassword');
    if (genStudentBtn) {
        genStudentBtn.addEventListener('click', function() {
            const pwd = generateStrongPassword();
            document.getElementById('student_password').value = pwd;
        });
    }

    // Professor password generator button
    const genProfBtn = document.getElementById('generateProfessorPassword');
    if (genProfBtn) {
        genProfBtn.addEventListener('click', function() {
            const pwd = generateStrongPassword();
            document.getElementById('professor_password').value = pwd;
        });
    }
    
    // Password visibility toggle functionality
    const toggleButtons = document.querySelectorAll('.password-toggle');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                this.title = '<?php echo $t['hide_password']; ?>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
                this.title = '<?php echo $t['show_password']; ?>';
            }
        });
    });
    
    // تهيئة Toast
    const copyToast = document.getElementById('copyToast');
    const toast = new bootstrap.Toast(copyToast, {
        delay: 3000,
        animation: true
    });
    const toastMessage = document.getElementById('toastMessage');
    
    // Copy password functionality - تحديث لاستخدام Toast بدلاً من Alert
    const copyButtons = document.querySelectorAll('.copy-password');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            // Save the original type to restore it later
            const originalType = passwordInput.type;
            
            // Temporarily set the type to text so we can select the value
            passwordInput.type = 'text';
            
            // Select the text field
            passwordInput.select();
            passwordInput.setSelectionRange(0, 99999); // For mobile devices
            
            // Copy the text inside the text field
            document.execCommand("copy");
            
            // Restore the original type
            passwordInput.type = originalType;
            
            // Deselect the input
            passwordInput.blur();
            
            // Show feedback with toast
            toastMessage.textContent = '<?php echo $t['copy_success']; ?>';
            toast.show();
        });
    });
    
    // Form validation
    const addStudentForm = document.getElementById('addStudentForm');
    const addProfessorForm = document.getElementById('addProfessorForm');
    
    if (addStudentForm) {
        addStudentForm.addEventListener('submit', function(e) {
            const password = document.getElementById('student_password').value;
            if (password.length < 6) {
                alert('<?php echo $current_lang == 'ar' ? 'يجب أن تتكون كلمة المرور من 6 أحرف على الأقل' : 'Password must be at least 6 characters long'; ?>');
                e.preventDefault();
            }
        });
    }
    
    if (addProfessorForm) {
        addProfessorForm.addEventListener('submit', function(e) {
            const password = document.getElementById('professor_password').value;
            if (password.length < 6) {
                alert('<?php echo $current_lang == 'ar' ? 'يجب أن تتكون كلمة المرور من 6 أحرف على الأقل' : 'Password must be at least 6 characters long'; ?>');
                e.preventDefault();
            }
        });
    }
});
</script>

</body>
</html>
