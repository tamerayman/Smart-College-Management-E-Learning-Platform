<?php
// Student and professor course enrollment management file

require_once 'auth.php';
require_once 'db_functions.php';
require_once 'notifications.php'; // Add notifications support
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
trackPageVisit('course_enrollment_management.php', $current_lang == 'ar' ? 'إدارة تسجيل المقررات' : 'Course Enrollment Management', 'fas fa-book-open', 'info');

// Get notification message from session and clear it
$notification_message = "";
if (isset($_SESSION['notification_message'])) {
    $notification_message = $_SESSION['notification_message'];
    unset($_SESSION['notification_message']); // Clear the message after displaying it
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add student to course
    if (isset($_POST['add_student_to_course'])) {
        $student_id = $_POST['student_id'];
        $course_id = $_POST['course_id'];
        
        if (addStudentToCourse($student_id, $course_id)) {
            $_SESSION['notification_message'] = $current_lang == 'ar' ? "تمت إضافة الطالب إلى المقرر بنجاح" : "Student added to course successfully";
        } else {
            $_SESSION['notification_message'] = $current_lang == 'ar' ? "الطالب مسجل بالفعل في هذا المقرر" : "Student is already enrolled in this course";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Remove student from course
    if (isset($_POST['remove_student_from_course'])) {
        $student_id = $_POST['old_student_id'];
        $course_id = $_POST['old_course_id'];
        
        if (removeStudentFromCourse($student_id, $course_id)) {
            $_SESSION['notification_message'] = "Student removed from course successfully";
        } else {
            $_SESSION['notification_message'] = "Error removing student from course";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Add professor to course
    if (isset($_POST['add_professor_to_course'])) {
        $professor_id = $_POST['professor_id'];
        $course_id = $_POST['course_id'];
        
        if (addProfessorToCourse($professor_id, $course_id)) {
            $_SESSION['notification_message'] = "Professor added to course successfully";
        } else {
            $_SESSION['notification_message'] = "Professor is already assigned to this course";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Remove professor from course
    if (isset($_POST['remove_professor_from_course'])) {
        $professor_id = $_POST['old_professor_id'];
        $course_id = $_POST['old_course_id'];
        
        if (removeProfessorFromCourse($professor_id, $course_id)) {
            $_SESSION['notification_message'] = "Professor removed from course successfully";
        } else {
            $_SESSION['notification_message'] = "Error removing professor from course";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Edit student enrollment
    if (isset($_POST['edit_student_enrollment'])) {
        $old_student_id = $_POST['old_student_id'];
        $old_course_id = $_POST['old_course_id'];
        $new_student_id = $_POST['new_student_id'];
        $new_course_id = $_POST['new_course_id'];
        
        if (editStudentEnrollment($old_student_id, $old_course_id, $new_student_id, $new_course_id)) {
            $_SESSION['notification_message'] = "Student enrollment updated successfully";
        } else {
            $_SESSION['notification_message'] = "Student is already enrolled in this course";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Edit professor enrollment
    if (isset($_POST['edit_professor_enrollment'])) {
        $old_professor_id = $_POST['old_professor_id'];
        $old_course_id = $_POST['old_course_id'];
        $new_professor_id = $_POST['new_professor_id'];
        $new_course_id = $_POST['new_course_id'];
        
        if (editProfessorEnrollment($old_professor_id, $old_course_id, $new_professor_id, $new_course_id)) {
            $_SESSION['notification_message'] = "Professor enrollment updated successfully";
        } else {
            $_SESSION['notification_message'] = "Professor is already assigned to this course";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Add new course
    if (isset($_POST['add_new_course'])) {
        $course_name = $_POST['course_name'];
        $department_id = $_POST['department_id'];
        
        if (addNewCourse($course_name, $department_id)) {
            $_SESSION['notification_message'] = $current_lang == 'ar' ? "تمت إضافة المقرر بنجاح" : "Course added successfully";
        } else {
            $_SESSION['notification_message'] = $current_lang == 'ar' ? "خطأ في إضافة المقرر" : "Error adding course";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch data
$studentsResult = getAllStudents();
$professorsResult = getAllProfessors();
$departmentsResult = getAllDepartments();
$coursesWithDeptResult = getAllCoursesWithDepartments();
$coursesResult = getAllCourses();

// Store courses in an array for use in both lists, ensure no duplicates
$courses = [];
$courseIds = []; // Track already processed course IDs
if ($coursesResult && $coursesResult->num_rows > 0) {
    while ($course = $coursesResult->fetch_assoc()) {
        // Only add if not already in the array
        if (!in_array($course['course_id'], $courseIds)) {
            $courses[] = $course;
            $courseIds[] = $course['course_id'];
        }
    }
}

// Sort courses alphabetically for better usability
usort($courses, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Debug information
error_log("Number of unique courses loaded into array: " . count($courses));

$studentEnrollmentsResult = getStudentCourseEnrollments();
$professorEnrollmentsResult = getProfessorCourseEnrollments();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_lang == 'ar' ? 'إدارة تسجيل المقررات' : 'Course Enrollment Management'; ?></title>
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
        <h2 class="mb-0" id="pageTitle">Course Enrollment Management</h2>
        <a href="admin_dashboard.php<?php echo $current_lang == 'ar' ? '?lang=ar' : ''; ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i> <?php echo $current_lang == 'ar' ? 'العودة إلى لوحة التحكم' : 'Back to Dashboard'; ?>
        </a>
    </div>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="enrollmentTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="student-tab" data-bs-toggle="tab" data-bs-target="#student" type="button" role="tab">
                <i class="fas fa-user-graduate"></i> <?php echo $current_lang == 'ar' ? 'إدارة تسجيل الطلاب' : 'Student Enrollment Management'; ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="professor-tab" data-bs-toggle="tab" data-bs-target="#professor" type="button" role="tab">
                <i class="fas fa-chalkboard-teacher"></i> <?php echo $current_lang == 'ar' ? 'إدارة تسجيل الأساتذة' : 'Professor Enrollment Management'; ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="course-tab" data-bs-toggle="tab" data-bs-target="#course" type="button" role="tab">
                <i class="fas fa-book-open"></i> <?php echo $current_lang == 'ar' ? 'إدارة المقررات' : 'Course Management'; ?>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="enrollmentTabsContent">
        <!-- Student Enrollment Tab -->
        <div class="tab-pane fade show active" id="student" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-user-plus me-1"></i>
                            <?php echo $current_lang == 'ar' ? 'إضافة طالب إلى مقرر' : 'Add Student to Course'; ?>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Select Student</label>
                                    <select class="form-select" id="student_id" name="student_id" required>
                                        <option value="">Choose a student</option>
                                        <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
                                            <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                                <option value="<?php echo $student['student_id']; ?>"><?php echo $student['name']; ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="course_id" class="form-label">Select Course</label>
                                    <select class="form-select" id="course_id" name="course_id" required>
                                        <option value="">Choose a course</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course['course_id']; ?>"><?php echo $course['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_student_to_course" class="btn btn-primary">Add Student to Course</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Student Course Enrollments</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($studentEnrollmentsResult && $studentEnrollmentsResult->num_rows > 0): ?>
                                            <?php while ($enrollment = $studentEnrollmentsResult->fetch_assoc()): ?>
                                                <tr>
                                                    <form method="post" class="edit-form">
                                                        <input type="hidden" name="old_student_id" value="<?php echo $enrollment['student_id']; ?>">
                                                        <input type="hidden" name="old_course_id" value="<?php echo $enrollment['course_id']; ?>">
                                                        <td>
                                                            <select class="form-select form-select-sm" name="new_student_id" required>
                                                                <?php 
                                                                $studentsResult->data_seek(0);
                                                                while ($student = $studentsResult->fetch_assoc()): 
                                                                ?>
                                                                    <option value="<?php echo $student['student_id']; ?>" 
                                                                            <?php echo ($student['student_id'] == $enrollment['student_id']) ? 'selected' : ''; ?>>
                                                                        <?php echo $student['name']; ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select class="form-select form-select-sm" name="new_course_id" required>
                                                                <?php foreach ($courses as $course): ?>
                                                                    <option value="<?php echo $course['course_id']; ?>"
                                                                            <?php echo ($course['course_id'] == $enrollment['course_id']) ? 'selected' : ''; ?>>
                                                                        <?php echo $course['name']; ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <button type="submit" name="edit_student_enrollment" class="btn btn-success btn-sm me-2">
                                                                <i class="fas fa-save"></i> <?php echo $current_lang == 'ar' ? 'حفظ' : 'Save'; ?>
                                                            </button>
                                                            <button type="submit" name="remove_student_from_course" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('<?php echo $current_lang == 'ar' ? 'هل أنت متأكد من حذف هذا التسجيل؟' : 'Are you sure you want to delete this enrollment?'; ?>')">
                                                                <i class="fas fa-trash"></i> <?php echo $current_lang == 'ar' ? 'حذف' : 'Delete'; ?>
                                                            </button>
                                                        </td>
                                                    </form>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No student enrollments found</td>
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
        
        <!-- Professor Enrollment Tab -->
        <div class="tab-pane fade" id="professor" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">Add Professor to Course</div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="professor_id" class="form-label">Select Professor</label>
                                    <select class="form-select" id="professor_id" name="professor_id" required>
                                        <option value="">Choose a professor</option>
                                        <?php if ($professorsResult && $professorsResult->num_rows > 0): ?>
                                            <?php while ($professor = $professorsResult->fetch_assoc()): ?>
                                                <option value="<?php echo $professor['professor_id']; ?>"><?php echo $professor['name']; ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="prof_course_id" class="form-label">Select Course</label>
                                    <select class="form-select" id="prof_course_id" name="course_id" required>
                                        <option value="">Choose a course</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course['course_id']; ?>"><?php echo $course['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_professor_to_course" class="btn btn-primary">Add Professor to Course</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Professor Course Assignments</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Professor</th>
                                            <th>Course</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($professorEnrollmentsResult && $professorEnrollmentsResult->num_rows > 0): ?>
                                            <?php while ($enrollment = $professorEnrollmentsResult->fetch_assoc()): ?>
                                                <tr>
                                                    <form method="post" class="edit-form">
                                                        <input type="hidden" name="old_professor_id" value="<?php echo $enrollment['professor_id']; ?>">
                                                        <input type="hidden" name="old_course_id" value="<?php echo $enrollment['course_id']; ?>">
                                                        <td>
                                                            <select class="form-select form-select-sm" name="new_professor_id" required>
                                                                <?php 
                                                                $professorsResult->data_seek(0);
                                                                while ($professor = $professorsResult->fetch_assoc()):
                                                                ?>
                                                                    <option value="<?php echo $professor['professor_id']; ?>"
                                                                            <?php echo ($professor['professor_id'] == $enrollment['professor_id']) ? 'selected' : ''; ?>>
                                                                        <?php echo $professor['name']; ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select class="form-select form-select-sm" name="new_course_id" required>
                                                                <?php foreach ($courses as $course): ?>
                                                                    <option value="<?php echo $course['course_id']; ?>"
                                                                            <?php echo ($course['course_id'] == $enrollment['course_id']) ? 'selected' : ''; ?>>
                                                                        <?php echo $course['name']; ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <button type="submit" name="edit_professor_enrollment" class="btn btn-success btn-sm me-2">
                                                                <i class="fas fa-save"></i> <?php echo $current_lang == 'ar' ? 'حفظ' : 'Save'; ?>
                                                            </button>
                                                            <button type="submit" name="remove_professor_from_course" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('<?php echo $current_lang == 'ar' ? 'هل أنت متأكد من حذف هذا التسجيل؟' : 'Are you sure you want to delete this assignment?'; ?>')">
                                                                <i class="fas fa-trash"></i> <?php echo $current_lang == 'ar' ? 'حذف' : 'Delete'; ?>
                                                            </button>
                                                        </td>
                                                    </form>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No professor assignments found</td>
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

        <!-- Course Management Tab -->
        <div class="tab-pane fade" id="course" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">Add New Course</div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="course_name" class="form-label">Course Name</label>
                                    <input type="text" class="form-control" id="course_name" name="course_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="department_id" class="form-label">Department</label>
                                    <select class="form-select" id="department_id" name="department_id" required>
                                        <option value="">Choose a department</option>
                                        <?php if ($departmentsResult && $departmentsResult->num_rows > 0): ?>
                                            <?php while ($department = $departmentsResult->fetch_assoc()): ?>
                                                <option value="<?php echo $department['department_id']; ?>"><?php echo $department['name']; ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_new_course" class="btn btn-primary">Add Course</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Existing Courses</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Course Name</th>
                                            <th>Department</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($coursesWithDeptResult && $coursesWithDeptResult->num_rows > 0): ?>
                                            <?php while ($course = $coursesWithDeptResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $course['name']; ?></td>
                                                    <td><?php echo $course['department_name']; ?></td>
                                                    <td>
                                                        <button class="btn btn-info btn-sm view-enrollments me-2"  
                                                                data-course-id="<?php echo $course['course_id']; ?>"
                                                                data-course-name="<?php echo $course['name']; ?>">
                                                            <i class="fas fa-users"></i> <?php echo $current_lang == 'ar' ? 'عرض الطلاب' : 'View Students'; ?>
                                                        </button>
                                                        <button class="btn btn-secondary btn-sm view-professors" 
                                                                data-course-id="<?php echo $course['course_id']; ?>"
                                                                data-course-name="<?php echo $course['name']; ?>">
                                                            <i class="fas fa-chalkboard-teacher"></i> <?php echo $current_lang == 'ar' ? 'عرض الأساتذة' : 'View Professors'; ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No courses found</td>
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
    </div>
</div>

<!-- Course Enrollments Modal -->
<div class="modal fade" id="courseEnrollmentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Students Enrolled in <span id="courseName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="enrolledStudentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Course Professors Modal -->
<div class="modal fade" id="courseProfessorsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Professors Assigned to <span id="courseNameProf"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="assignedProfessorsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Professor Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="ui-components.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Current language from PHP
    const lang = '<?php echo $current_lang; ?>';
    
    if (lang === 'ar') {
        // Apply Arabic translations
        document.title = 'إدارة تسجيل المقررات';
        document.getElementById('pageTitle').textContent = 'إدارة تسجيل المقررات';
        document.getElementById('dashboardBtn').textContent = 'لوحة التحكم';
        document.getElementById('student-tab').textContent = 'إدارة تسجيل الطلاب';
        document.getElementById('professor-tab').textContent = 'إدارة تسجيل الأساتذة';
        document.getElementById('course-tab').textContent = 'إدارة المقررات';
        
        // Translate card headers and form elements
        const headers = document.querySelectorAll('.card-header');
        headers.forEach(header => {
            if (header.textContent === 'Add Student to Course') header.textContent = 'إضافة طالب إلى مقرر';
            if (header.textContent === 'Student Course Enrollments') header.textContent = 'تسجيلات الطلاب في المقررات';
            if (header.textContent === 'Add Professor to Course') header.textContent = 'إضافة أستاذ إلى مقرر';
            if (header.textContent === 'Professor Course Assignments') header.textContent = 'تكليفات الأساتذة بالمقررات';
            if (header.textContent === 'Add New Course') header.textContent = 'إضافة مقرر جديد';
            if (header.textContent === 'Existing Courses') header.textContent = 'المقررات الموجودة';
        });

        // Translate form labels
        const labels = document.querySelectorAll('.form-label');
        labels.forEach(label => {
            if (label.textContent === 'Select Student') label.textContent = 'اختر طالب';
            if (label.textContent === 'Select Course') label.textContent = 'اختر مقرر';
            if (label.textContent === 'Select Professor') label.textContent = 'اختر أستاذ';
            if (label.textContent === 'Course Name') label.textContent = 'اسم المقرر';
            if (label.textContent === 'Department') label.textContent = 'القسم';
        });

        // Translate buttons
        const buttons = document.querySelectorAll('button[type="submit"]');
        buttons.forEach(button => {
            if (button.textContent.includes('Add Student to Course')) button.textContent = 'إضافة طالب للمقرر';
            if (button.textContent.includes('Add Professor to Course')) button.textContent = 'إضافة أستاذ للمقرر';
            if (button.textContent.includes('Add Course')) button.textContent = 'إضافة مقرر';
            if (button.textContent.includes('Save')) button.textContent = 'حفظ';
            if (button.textContent.includes('Delete')) button.textContent = 'حذف';
        });

        // Translate table headers
        const tableHeaders = document.querySelectorAll('th');
        tableHeaders.forEach(th => {
            if (th.textContent === 'Student') th.textContent = 'الطالب';
            if (th.textContent === 'Course') th.textContent = 'المقرر';
            if (th.textContent === 'Professor') th.textContent = 'الأستاذ';
            if (th.textContent === 'Actions') th.textContent = 'الإجراءات';
            if (th.textContent === 'Department') th.textContent = 'القسم';
        });

        // Translate placeholders
        const options = document.querySelectorAll('option[value=""]');
        options.forEach(option => {
            if (option.textContent === 'Choose a student') option.textContent = 'اختر طالب';
            if (option.textContent === 'Choose a course') option.textContent = 'اختر مقرر';
            if (option.textContent === 'Choose a professor') option.textContent = 'اختر أستاذ';
            if (option.textContent === 'Choose a department') option.textContent = 'اختر قسم';
        });

        // Translate modal text
        document.querySelector('.modal-title').textContent = 'الطلاب المسجلين في ';
        document.querySelector('.modal-footer .btn').textContent = 'إغلاق';
        
        // Translate the new professor modal
        document.querySelector('#courseProfessorsModal .modal-title').textContent = 'الأساتذة المخصصون لـ ';
        document.querySelectorAll('#courseProfessorsModal .modal-footer .btn').forEach(btn => {
            btn.textContent = 'إغلاق';
        });
        
        // Translate new table headers
        document.querySelectorAll('#assignedProfessorsTable th').forEach(th => {
            if (th.textContent === 'ID') th.textContent = 'الرقم';
            if (th.textContent === 'Professor Name') th.textContent = 'اسم الأستاذ';
            if (th.textContent === 'Email') th.textContent = 'البريد الإلكتروني';
        });
    }
    
    // Add event listeners for viewing enrollments
    document.querySelectorAll('.view-enrollments').forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.getAttribute('data-course-id');
            const courseName = this.getAttribute('data-course-name');
            
            // Set the course name in the modal
            document.getElementById('courseName').textContent = courseName;
            
            // Fetch enrolled students via AJAX
            fetch(`get_enrolled_students.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.querySelector('#enrolledStudentsTable tbody');
                    tableBody.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(student => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${student.student_id}</td>
                                <td>${student.name}</td>
                                <td>${student.email || 'N/A'}</td>
                            `;
                            tableBody.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="3" class="text-center">${lang === 'ar' ? 'لا يوجد طلاب مسجلين في هذا المقرر' : 'No students enrolled in this course'}</td>`;
                        tableBody.appendChild(row);
                    }
                    
                    // Show the modal
                    const modal = new bootstrap.Modal(document.getElementById('courseEnrollmentsModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching student data:', error);
                });
        });
    });
    
    // Add event listeners for viewing professors
    document.querySelectorAll('.view-professors').forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.getAttribute('data-course-id');
            const courseName = this.getAttribute('data-course-name');
            
            // Set the course name in the modal
            document.getElementById('courseNameProf').textContent = courseName;
            
            // Fetch assigned professors via AJAX
            fetch(`get_assigned_professors.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.querySelector('#assignedProfessorsTable tbody');
                    tableBody.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(professor => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${professor.professor_id}</td>
                                <td>${professor.name}</td>
                                <td>${professor.email || 'N/A'}</td>
                            `;
                            tableBody.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="3" class="text-center">${lang === 'ar' ? 'لا يوجد أساتذة مخصصون لهذا المقرر' : 'No professors assigned to this course'}</td>`;
                        tableBody.appendChild(row);
                    }
                    
                    // Show the modal
                    const modal = new bootstrap.Modal(document.getElementById('courseProfessorsModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching professor data:', error);
                });
        });
    });
});
</script>
</body>
</html>