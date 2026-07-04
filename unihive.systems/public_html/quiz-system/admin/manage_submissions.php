<?php
// filepath: quiz-system/admin/manage_submissions.php
session_start();
#require_once '../includes/config.php';
require_once '../../config.php';
require_once '../../auth.php';
#require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if the user is logged in and is a professor
if (!isLoggedIn() || $_SESSION["role"] !== "professor") {
    header("Location: ../index.php");
    exit;
}

// Connect to the database
$conn = connectDB();

// Fetch submissions for quizzes created by the logged-in professor
$professorId = $_SESSION["user_id"];
$submissions = [];

// Check if filtering by quiz ID
$filterQuizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
$autoShowSubmissions = false;

// If filtering by quiz ID, check if the quiz exists and belongs to the professor
if ($filterQuizId > 0) {
    $checkQuizQuery = $conn->prepare("SELECT quiz_id, title FROM quizzes WHERE quiz_id = ? AND professor_id = ?");
    $checkQuizQuery->bind_param("ii", $filterQuizId, $professorId);
    $checkQuizQuery->execute();
    $quizCheckResult = $checkQuizQuery->get_result();
    
    if ($quizCheckResult->num_rows === 0) {
        // If quiz doesn't exist or doesn't belong to the professor, reset the filter
        $filterQuizId = 0;
    } else {
        $filteredQuiz = $quizCheckResult->fetch_assoc();
        $quizTitle = $filteredQuiz['title'];
        $autoShowSubmissions = true; // Auto-show submissions when filtering by quiz
    }
}

// Base query for quiz data and submissions
$quizQueryBase = "
    FROM quiz_attempts a 
    JOIN quizzes q ON a.quiz_id = q.quiz_id 
    JOIN Users u ON a.student_id = u.user_id 
    JOIN courses c ON q.course_id = c.course_id
    LEFT JOIN students s ON a.student_id = s.student_id
    WHERE q.professor_id = ? AND a.status = 'completed'";

// If filtering by quiz, add the condition
$quizFilter = "";
if ($filterQuizId > 0) {
    $quizFilter = " AND a.quiz_id = ?";
}

// Get all submissions query
$stmt = $conn->prepare("
    SELECT a.attempt_id as submission_id, a.quiz_id, a.student_id, a.score, 
           a.end_time as submission_time, q.title as quiz_title, c.name as course_name,
           u.email as student_email, s.name as student_name
    " . $quizQueryBase . $quizFilter . "
    ORDER BY a.end_time DESC
");

// Bind the parameters based on whether we're filtering
if ($filterQuizId > 0) {
    $stmt->bind_param("ii", $professorId, $filterQuizId);
} else {
    $stmt->bind_param("i", $professorId);
}

$stmt->execute();
$result = $stmt->get_result();

$submissions = [];
// قائمة بكل الاختبارات التي لها محاولات
$quizzes = [];

while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
    
    // تجميع الاختبارات
    $quizId = $row['quiz_id'];
    if (!isset($quizzes[$quizId])) {
        $quizzes[$quizId] = [
            'quiz_id' => $quizId,
            'title' => $row['quiz_title'],
            'course_name' => $row['course_name'],
            'attempts_count' => 0,
            'average_score' => 0,
            'total_score' => 0
        ];
    }
    
    // إحصائيات كل اختبار
    $quizzes[$quizId]['attempts_count']++;
    $quizzes[$quizId]['total_score'] += $row['score'];
}

// حساب متوسط الدرجات
foreach ($quizzes as $quizId => $quiz) {
    if ($quiz['attempts_count'] > 0) {
        $quizzes[$quizId]['average_score'] = round($quiz['total_score'] / $quiz['attempts_count'], 2);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة محاولات الاختبارات</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #e9efff;
            --secondary-color: #3a0ca3;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-color: #f5f7fa;
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Cairo', sans-serif;
            color: var(--dark-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin: 0;
        }
        
        /* Dashboard Sections */
        .dashboard-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .section-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .section-title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .section-body {
            padding: 1.5rem;
        }
        
        /* Filters and Notices */
        .filter-notice {
            margin-bottom: 1.5rem;
            padding: 1rem 1.2rem;
            background-color: var(--primary-light);
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .filter-notice p {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .clear-filter {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .clear-filter:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        /* Search */
        .search-bar {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 0.8rem 1rem;
            padding-right: 3rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.2rem;
        }
        
        /* Quiz Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .quiz-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .quiz-card-header {
            background: var(--primary-color);
            color: white;
            padding: 1.2rem;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .quiz-card-content {
            padding: 1.2rem;
            flex-grow: 1;
        }
        
        .quiz-title-row {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 1rem;
        }
        
        .quiz-stats {
            display: flex;
            justify-content: space-between;
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
            background: var(--primary-light);
            padding: 1rem;
            border-radius: 8px;
        }
        
        .stat-item:first-child {
            margin-left: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.3rem;
        }
        
        .quiz-card-footer {
            padding: 1rem 1.2rem;
            background: #f8fafc;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.7rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
        }
        
        .btn i {
            margin-left: 0.5rem;
            font-size: 1.1rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .view-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.8rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .view-btn i {
            margin-left: 0.4rem;
        }
        
        .view-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        /* Table */
        .submissions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .submissions-table th, .submissions-table td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .submissions-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--dark-color);
            white-space: nowrap;
        }
        
        .submissions-table tr:hover {
            background-color: var(--primary-light);
        }
        
        .score-high {
            color: var(--success-color);
            font-weight: bold;
        }
        
        .score-medium {
            color: var(--warning-color);
            font-weight: bold;
        }
        
        .score-low {
            color: var(--danger-color);
            font-weight: bold;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.2rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #718096;
            margin-bottom: 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 0;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .submissions-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1 class="page-title">إدارة محاولات الاختبارات</h1>
            <a href="manage_quizzes.php" class="btn btn-primary">
                <i class='bx bx-arrow-back'></i> الرجوع للاختبارات
            </a>
        </header>
        
        <?php if ($filterQuizId > 0): ?>
            <div class="filter-notice">
                <p>تم تصفية النتائج لاختبار: <strong><?php echo htmlspecialchars($quizTitle); ?></strong></p>
                <a href="manage_submissions.php" class="clear-filter">
                    <i class='bx bx-x'></i> إزالة التصفية
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Quizzes Section -->
        <?php if (!$filterQuizId && !empty($quizzes)): ?>
        <section class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class='bx bx-pie-chart-alt-2'></i> الاختبارات التي تمت محاولتها
                </h2>
            </div>
            <div class="section-body">
                <div class="dashboard-grid">
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="quiz-card">
                            <div class="quiz-card-header">
                                <?php echo htmlspecialchars($quiz['title']); ?>
                            </div>
                            <div class="quiz-card-content">
                                <div class="quiz-title-row">
                                    <i class='bx bx-book'></i> المادة: <?php echo htmlspecialchars($quiz['course_name']); ?>
                                </div>
                                <div class="quiz-stats">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $quiz['attempts_count']; ?></div>
                                        <div class="stat-label">عدد المحاولات</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $quiz['average_score']; ?>%</div>
                                        <div class="stat-label">متوسط الدرجات</div>
                                    </div>
                                </div>
                            </div>
                            <div class="quiz-card-footer">
                                <a href="manage_submissions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-primary">
                                    <i class='bx bx-bar-chart-alt-2'></i> تفاصيل المحاولات
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Submissions Section -->
        <section class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class='bx bx-list-check'></i>
                    <?php echo $filterQuizId > 0 ? 'محاولات اختبار: ' . htmlspecialchars($quizTitle) : 'جميع المحاولات'; ?>
                </h2>
            </div>
            <div class="section-body">
                <div class="search-bar">
                    <input type="text" id="search-submissions" class="search-input" placeholder="البحث عن طالب أو اختبار...">
                    <i class='bx bx-search search-icon'></i>
                </div>
                
                <?php if (empty($submissions)): ?>
                    <div class="empty-state">
                        <i class='bx bx-clipboard'></i>
                        <h3>لا توجد محاولات بعد</h3>
                        <p>لم يقم أي طالب بحل الاختبارات الخاصة بك حتى الآن.</p>
                        <a href="manage_quizzes.php" class="btn btn-primary">
                            <i class='bx bx-arrow-back'></i> الذهاب إلى الاختبارات
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="submissions-table">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <?php if (!$filterQuizId): ?>
                                    <th>اسم الاختبار</th>
                                    <th>المادة</th>
                                    <?php endif; ?>
                                    <th>الدرجة</th>
                                    <th>تاريخ التقديم</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="submissions-list">
                                <?php foreach ($submissions as $submission): ?>
                                    <tr class="submission-row">
                                        <td data-search="<?php echo htmlspecialchars($submission['student_name'] ?? ''); ?> <?php echo htmlspecialchars($submission['student_email']); ?>">
                                            <div style="font-weight: 600;">
                                                <?php echo htmlspecialchars($submission['student_name'] ?? ''); ?>
                                            </div>
                                            <div style="color: #64748b; font-size: 0.9em;">
                                                <?php echo htmlspecialchars($submission['student_email']); ?>
                                            </div>
                                        </td>
                                        
                                        <?php if (!$filterQuizId): ?>
                                        <td data-search="<?php echo htmlspecialchars($submission['quiz_title']); ?>">
                                            <?php echo htmlspecialchars($submission['quiz_title']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($submission['course_name']); ?></td>
                                        <?php endif; ?>
                                        
                                        <td class="<?php 
                                            if ($submission['score'] >= 80) echo 'score-high';
                                            elseif ($submission['score'] >= 60) echo 'score-medium';
                                            else echo 'score-low';
                                        ?>">
                                            <?php echo $submission['score']; ?>%
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($submission['submission_time'])); ?></td>
                                        <td>
                                            <a href="../quiz/results.php?id=<?php echo $submission['quiz_id']; ?>&attempt=<?php echo $submission['submission_id']; ?>" class="view-btn">
                                                <i class='bx bx-show'></i> عرض
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script>
        // Search functionality
        const searchInput = document.getElementById('search-submissions');
        const submissionRows = document.querySelectorAll('.submission-row');
        
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            
            submissionRows.forEach(row => {
                const searchableFields = row.querySelectorAll('[data-search]');
                let found = false;
                
                searchableFields.forEach(field => {
                    const searchText = field.getAttribute('data-search').toLowerCase();
                    if (searchText.includes(searchTerm)) {
                        found = true;
                    }
                });
                
                row.style.display = found ? '' : 'none';
            });
        });
    </script>
</body>
</html>