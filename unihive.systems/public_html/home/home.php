<?php
// filepath: d:\wamp64\www\home\home.php
// استدعاء ملف المصادقة للتحقق من تسجيل الدخول وجلب بيانات المستخدم
session_start();
require_once '../config.php';
require_once '../auth.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

// الحصول على بيانات المستخدم الحالي
$userId = $_SESSION["user_id"];
$role = $_SESSION["role"];
$userData = [];

// جلب بيانات المستخدم من قاعدة البيانات
$conn = connectDB();
if ($role == "student") {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.email, s.name, s.level, d.name as department_name 
        FROM users u 
        JOIN students s ON u.user_id = s.student_id 
        JOIN departments d ON s.department_id = d.department_id 
        WHERE u.user_id = ?
    ");
} elseif ($role == "professor") {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.email, p.name, d.name as department_name 
        FROM users u 
        JOIN professors p ON u.user_id = p.professor_id 
        JOIN departments d ON p.department_id = d.department_id 
        WHERE u.user_id = ?
    ");
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $userData = $result->fetch_assoc();
}

// جلب صورة الملف الشخصي للمستخدم
$profileImage = "profile-1.jpg"; // الصورة الافتراضية
$img_sql = "SELECT profile_image FROM user_profiles WHERE user_id = ?";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param("i", $userId);
$img_stmt->execute();
$img_result = $img_stmt->get_result();

if ($img_result && $img_result->num_rows > 0) {
    $profile_data = $img_result->fetch_assoc();
    if (!empty($profile_data['profile_image'])) {
        $profileImage = "../R2/unihive profile/" . $profile_data['profile_image'];
    }
}

// جلب المواد الدراسية للبروفيسور
$professorCourses = [];
if ($role == "professor") {
    $courses_stmt = $conn->prepare("
        SELECT c.course_id, c.name as course_name, c.department_id, d.name as department_name
        FROM professor_courses pc
        JOIN courses c ON pc.course_id = c.course_id
        JOIN departments d ON c.department_id = d.department_id
        WHERE pc.professor_id = ?
    ");
    $courses_stmt->bind_param("i", $userId);
    $courses_stmt->execute();
    $courses_result = $courses_stmt->get_result();
    while ($course = $courses_result->fetch_assoc()) {
        $professorCourses[] = $course;
    }
    $courses_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHive - Home</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <!-- particles.js container -->
    <div id="particles-js"></div>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="https://threejs.org/examples/js/libs/stats.min.js"></script>

    <!-- ************************************* -->
    <div class="content">
        <h3>UniHive</h3>
        <div class="photo" style="z-index: 1;">
            <div class="notification-bell">
                <i class='bx bx-bell' id="bellIcon"></i>
                <span class="notification-count" id="notificationCount"></span>
                
                <!-- Notification Dropdown -->
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button id="markAllRead">Mark all as read</button>
                    </div>
                    <div class="notification-body" id="notificationList">
                        <!-- Notifications will be loaded here -->
                        <div class="notification-empty">No notifications</div>
                    </div>
                </div>
            </div>
            
            <a href="../R2/unihive profile/profile.php">
                <img src="<?php echo $profileImage; ?>">
            </a>
        </div>
    </div>
    <div class="mean">
        <div class="word" style="z-index: 1;">
            <h2>UniHive</h2>
            <span>Welcome to unihive learn,connect,<br>andinnovate all in one place </span>
        </div>
        <div class="photo2">
            <img src="logo_white.png">
        </div>
    </div>

    <div class="gg">
        <h2>Learn with UniHive platform</h2>
        


        <div class="cards">
            <a href="../library/library.php">
                <div class="card">
                    <img src="../photo/Library/accounting card.png" class="card-img-top">
                    <p class="text">library</p>
                </div>
            </a>

            <a href="../R2/unihive course/course.html">
                <div class="card">
                    <img src="../photo/css card.png" class="card-img-top">
                    <p class="text">courses</p>
                </div>
            </a>
            
            <a href="../quiz-system/quiz/index.php">
                <div class="card">
                    <img src="../photo/cc.png" class="card-img-top">
                    <p class="text">quizzes</p>
                </div>
            </a>
            
            <a href="../meetings/meetings.php">
                <div class="card">
                    <img src="../photo/css card.png" class="card-img-top">
                    <p class="text">Meeting</p>
                </div>
            </a>
        </div>
    </div>

    <script src="home.js"></script>
</body>
</html>
