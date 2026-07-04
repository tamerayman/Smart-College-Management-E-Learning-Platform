<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$conn = connectDB();

// Get file type and ID
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($type) || $id <= 0) {
    header('Location: index.php');
    exit();
}

// Get file information
if ($type == 'book') {
    $table = 'library_books';
    $itemType = 'كتاب';
} elseif ($type == 'exam') {
    $table = 'library_exams';
    $itemType = 'امتحان';
} else {
    header('Location: index.php');
    exit();
}

$sql = "SELECT * FROM $table WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: index.php');
    exit();
}

$file = $result->fetch_assoc();

// Check if user has access to this file
if ($user_role == 'student') {
    $access_sql = "SELECT 1 FROM student_courses 
                  WHERE student_id = $user_id 
                  AND course_id = " . $file['course_id'];
    $access_result = $conn->query($access_sql);
    
    if ($access_result->num_rows == 0) {
        header('Location: index.php');
        exit();
    }
}

// Get course name
$course_sql = "SELECT name as course_name FROM courses WHERE course_id = " . $file['course_id'];
$course_result = $conn->query($course_sql);
$course = $course_result->fetch_assoc();

// Include header
if (file_exists('../includes/header.php')) {
    include_once '../includes/header.php';
} else {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>عرض ' . htmlspecialchars($file['title']) . '</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
        <style>
            .pdf-container {
                width: 100%;
                height: 80vh;
                overflow: hidden;
                margin-bottom: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .pdf-iframe {
                width: 100%;
                height: 100%;
                border: none;
            }
            .file-info {
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
    <div class="container mt-4">';
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4><?php echo htmlspecialchars($file['title']); ?></h4>
                </div>
                <div class="card-body">
                    <div class="file-info">
                        <p><strong>النوع:</strong> <?php echo $itemType; ?></p>
                        <p><strong>المادة:</strong> <?php echo htmlspecialchars($course['course_name']); ?></p>
                        <?php if ($type == 'exam' && !empty($file['exam_type'])): ?>
                            <p><strong>نوع الامتحان:</strong> <?php echo htmlspecialchars($file['exam_type']); ?> (<?php echo $file['exam_year']; ?>)</p>
                        <?php endif; ?>
                        <p><strong>تاريخ الرفع:</strong> <?php echo date('Y-m-d', strtotime($file['upload_date'])); ?></p>
                        <?php if (!empty($file['description'])): ?>
                            <p><strong>الوصف:</strong> <?php echo nl2br(htmlspecialchars($file['description'])); ?></p>
                        <?php endif; ?>
                        <a href="download.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i> تحميل الملف
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            العودة للمكتبة
                        </a>
                    </div>
                    
                    <div class="pdf-container">
                        <iframe src="view.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>" class="pdf-iframe" title="PDF Viewer"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
if (file_exists('../includes/footer.php')) {
    include_once '../includes/footer.php';
} else {
    echo '</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
}
?>
