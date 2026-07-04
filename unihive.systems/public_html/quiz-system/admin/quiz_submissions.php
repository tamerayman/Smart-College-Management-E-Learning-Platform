<?php
session_start();
require_once '../../config.php';
require_once '../../auth.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المستخدم
if (!isLoggedIn() || $_SESSION["role"] !== "professor") {
    header("Location: ../index.php");
    exit;
}

// الحصول على معرف الاختبار من الرابط
$quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

if ($quizId <= 0) {
    header("Location: manage_submissions.php?error=invalid_quiz");
    exit;
}

$conn = connectDB();
$professorId = $_SESSION["user_id"];

// التحقق من ملكية الاختبار
$quizQuery = $conn->prepare("
    SELECT q.*, c.name as course_name 
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    WHERE q.quiz_id = ? AND q.professor_id = ?
");
$quizQuery->bind_param("ii", $quizId, $professorId);
$quizQuery->execute();
$quizResult = $quizQuery->get_result();

if ($quizResult->num_rows === 0) {
    header("Location: manage_submissions.php?error=unauthorized");
    exit;
}

$quiz = $quizResult->fetch_assoc();

// استرداد محاولات هذا الاختبار
$attemptsQuery = $conn->prepare("
    SELECT a.*, u.email, s.name as student_name,
           TIMESTAMPDIFF(MINUTE, a.start_time, a.end_time) as completion_time
    FROM quiz_attempts a
    JOIN Users u ON a.student_id = u.user_id
    LEFT JOIN students s ON a.student_id = s.student_id
    WHERE a.quiz_id = ? AND a.status = 'completed'
    ORDER BY a.score DESC, completion_time ASC
");
$attemptsQuery->bind_param("i", $quizId);
$attemptsQuery->execute();
$attemptsResult = $attemptsQuery->get_result();

$attempts = [];
while ($attempt = $attemptsResult->fetch_assoc()) {
    $attempts[] = $attempt;
}

// حساب الإحصائيات
$totalAttempts = count($attempts);
$totalScore = 0;
$highestScore = 0;
$lowestScore = 100;
$fastestTime = PHP_INT_MAX;
$slowestTime = 0;

foreach ($attempts as $attempt) {
    $totalScore += $attempt['score'];
    $highestScore = max($highestScore, $attempt['score']);
    $lowestScore = min($lowestScore, $attempt['score']);
    
    if ($attempt['completion_time'] > 0) {
        $fastestTime = min($fastestTime, $attempt['completion_time']);
        $slowestTime = max($slowestTime, $attempt['completion_time']);
    }
}

$averageScore = $totalAttempts > 0 ? round($totalScore / $totalAttempts, 2) : 0;
$fastestTime = $fastestTime < PHP_INT_MAX ? $fastestTime : 0;

// عدد الطلاب الذين اجتازوا الاختبار (>= 60%)
$passCount = 0;
foreach ($attempts as $attempt) {
    if ($attempt['score'] >= 60) {
        $passCount++;
    }
}

$passRate = $totalAttempts > 0 ? round(($passCount / $totalAttempts) * 100, 2) : 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>محاولات الاختبار - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #edf2ff;
            --secondary-color: #3a0ca3;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gray-color: #94a3b8;
            --border-radius: 10px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        body {
            background-color: #f1f5f9;
            font-family: 'Cairo', sans-serif;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px 30px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header-content h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        .page-subtitle {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .back-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .back-button i {
            margin-left: 5px;
        }
        
        .content-container {
            background: white;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--light-color);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--gray-color);
            font-size: 14px;
        }
        
        .stat-card.success .stat-value {
            color: var(--success-color);
        }
        
        .stat-card.warning .stat-value {
            color: var(--warning-color);
        }
        
        .stat-card.danger .stat-value {
            color: var(--danger-color);
        }
        
        .stat-card.info .stat-value {
            color: var(--info-color);
        }
        
        .leaderboard {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
        }
        
        .leaderboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .leaderboard-header h2 {
            margin: 0;
            font-size: 20px;
            color: var(--dark-color);
        }
        
        .leaderboard-content {
            overflow-x: auto;
        }
        
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .leaderboard-table th, .leaderboard-table td {
            padding: 12px 15px;
            text-align: right;
        }
        
        .leaderboard-table th {
            background: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
            position: sticky;
            top: 0;
        }
        
        .leaderboard-table tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .leaderboard-table tr:hover {
            background: var(--primary-light);
        }
        
        .rank {
            font-weight: bold;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .rank-1 {
            background: #ffd700;
            color: #7e6b00;
        }
        
        .rank-2 {
            background: #c0c0c0;
            color: #494949;
        }
        
        .rank-3 {
            background: #cd7f32;
            color: white;
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
        
        .view-btn {
            display: inline-block;
            padding: 8px 12px;
            background-color: var(--info-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .view-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .search-bar {
            margin-bottom: 20px;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 20px;
            padding-right: 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 0;
            color: var(--gray-color);
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .empty-state p {
            margin-bottom: 20px;
            color: var(--gray-color);
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 15px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .content-container {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <h1>محاولات الاختبار</h1>
                <div class="page-subtitle">
                    <?php echo htmlspecialchars($quiz['title']); ?> | <?php echo htmlspecialchars($quiz['course_name']); ?>
                </div>
            </div>
            <a href="manage_submissions.php" class="back-button">
                <i class='bx bx-arrow-back'></i> العودة إلى المحاولات
            </a>
        </div>
        
        <div class="content-container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalAttempts; ?></div>
                    <div class="stat-label">إجمالي المحاولات</div>
                </div>
                <div class="stat-card <?php echo ($averageScore >= 80) ? 'success' : (($averageScore >= 60) ? 'warning' : 'danger'); ?>">
                    <div class="stat-value"><?php echo $averageScore; ?>%</div>
                    <div class="stat-label">متوسط الدرجات</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $highestScore; ?>%</div>
                    <div class="stat-label">أعلى درجة</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-value"><?php echo $lowestScore; ?>%</div>
                    <div class="stat-label">أدنى درجة</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-value"><?php echo $passRate; ?>%</div>
                    <div class="stat-label">نسبة الاجتياز</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $fastestTime; ?></div>
                    <div class="stat-label">أسرع وقت (دقيقة)</div>
                </div>
            </div>
            
            <div class="leaderboard">
                <div class="leaderboard-header">
                    <h2>ترتيب الطلاب حسب الدرجات</h2>
                </div>
                
                <div class="search-bar">
                    <input type="text" id="search-attempts" class="search-input" placeholder="البحث عن طالب...">
                    <i class='bx bx-search search-icon'></i>
                </div>
                
                <?php if (empty($attempts)): ?>
                    <div class="empty-state">
                        <i class='bx bx-clipboard'></i>
                        <h3>لا توجد محاولات بعد</h3>
                        <p>لم يقم أي طالب بحل هذا الاختبار حتى الآن.</p>
                    </div>
                <?php else: ?>
                    <div class="leaderboard-content">
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th style="text-align: center;">الترتيب</th>
                                    <th>الطالب</th>
                                    <th>الدرجة</th>
                                    <th>وقت الإكمال</th>
                                    <th>تاريخ المحاولة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="attempts-list">
                                <?php foreach ($attempts as $index => $attempt): ?>
                                    <tr class="attempt-row">
                                        <td style="text-align: center;">
                                            <div class="rank <?php echo ($index < 3) ? "rank-" . ($index + 1) : ""; ?>">
                                                <?php echo $index + 1; ?>
                                            </div>
                                        </td>
                                        <td data-search="<?php echo htmlspecialchars($attempt['student_name']); ?> <?php echo htmlspecialchars($attempt['email']); ?>">
                                            <?php echo htmlspecialchars($attempt['student_name'] ?? $attempt['email']); ?>
                                        </td>
                                        <td class="<?php 
                                            if ($attempt['score'] >= 80) echo 'score-high';
                                            elseif ($attempt['score'] >= 60) echo 'score-medium';
                                            else echo 'score-low';
                                        ?>">
                                            <?php echo $attempt['score']; ?>%
                                        </td>
                                        <td>
                                            <?php 
                                                if ($attempt['completion_time'] > 0) {
                                                    $hours = floor($attempt['completion_time'] / 60);
                                                    $minutes = $attempt['completion_time'] % 60;
                                                    
                                                    if ($hours > 0) {
                                                        echo $hours . ' ساعة و ' . $minutes . ' دقيقة';
                                                    } else {
                                                        echo $minutes . ' دقيقة';
                                                    }
                                                } else {
                                                    echo '-';
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($attempt['end_time'])); ?></td>
                                        <td>
                                            <a href="../quiz/results.php?id=<?php echo $quizId; ?>&attempt=<?php echo $attempt['attempt_id']; ?>" class="view-btn">عرض التفاصيل</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        const searchInput = document.getElementById('search-attempts');
        const attemptRows = document.querySelectorAll('.attempt-row');
        
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            
            attemptRows.forEach(row => {
                const searchableField = row.querySelector('[data-search]');
                let found = false;
                
                if (searchableField) {
                    const searchText = searchableField.getAttribute('data-search').toLowerCase();
                    if (searchText.includes(searchTerm)) {
                        found = true;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            });
        });
    </script>
</body>
</html>
