<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Get course parameter
$course = isset($_GET['course']) ? $_GET['course'] : '';

// Course names mapping
$course_names = [
    'accounting' => 'محاسبة',
    'database' => 'قواعد البيانات',
    'data_mining' => 'تنقيب البيانات',
    'data_structure' => 'هياكل البيانات',
    'economy' => 'اقتصاد',
    // Add more courses as needed
];

$course_name = isset($course_names[$course]) ? $course_names[$course] : ucfirst($course);

// Include header
if (file_exists('../includes/header.php')) {
    include_once '../includes/header.php';
} else {
    echo '<!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>عرض ' . htmlspecialchars($course_name) . '</title>
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
                    <h4><?php echo htmlspecialchars($course_name); ?></h4>
                </div>
                <div class="card-body">
                    <div class="file-info">
                        <p><strong>النوع:</strong> كتاب</p>
                        <p><strong>المادة:</strong> <?php echo htmlspecialchars($course_name); ?></p>
                        
                        <a href="view_demo.php?course=<?php echo urlencode($course); ?>&type=download" class="btn btn-primary">
                            <i class="fas fa-download"></i> تحميل الملف
                        </a>
                        <a href="../N2/library.html" class="btn btn-secondary">
                            العودة للمكتبة
                        </a>
                    </div>
                    
                    <div class="pdf-container">
                        <iframe src="view_demo.php?course=<?php echo urlencode($course); ?>&type=read&embed=1" class="pdf-iframe" title="PDF Viewer"></iframe>
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
