<?php
session_start();
require_once '../../config.php';
require_once '../../auth.php';

// Check if user is logged in and is a professor
if (!isLoggedIn() || $_SESSION["role"] != "professor") {
    header("Location: ../../index.php?error=role");
    exit;
}

$userId = $_SESSION["user_id"];
$conn = connectDB();

// Get professor courses
$courses_stmt = $conn->prepare("
    SELECT c.course_id, c.name as course_name
    FROM professor_courses pc
    JOIN courses c ON pc.course_id = c.course_id
    WHERE pc.professor_id = ?
");
$courses_stmt->bind_param("i", $userId);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

$courses = [];
while ($course = $courses_result->fetch_assoc()) {
    $courses[] = $course;
}

// Get professor's quizzes
$quizzes_stmt = $conn->prepare("
    SELECT q.*, c.name as course_name, 
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.quiz_id) as attempt_count
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    WHERE q.professor_id = ?
    ORDER BY q.created_at DESC
");
$quizzes_stmt->bind_param("i", $userId);
$quizzes_stmt->execute();
$quizzes_result = $quizzes_stmt->get_result();

$quizzes = [];
while ($quiz = $quizzes_result->fetch_assoc()) {
    $quizzes[] = $quiz;
}

// Get professor name
$name_stmt = $conn->prepare("SELECT name FROM professors WHERE professor_id = ?");
$name_stmt->bind_param("i", $userId);
$name_stmt->execute();
$name_result = $name_stmt->get_result();
$professor_name = $name_result->fetch_assoc()['name'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHive - Quiz Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
                <h2>UniHive</h2>
            </div>
            <ul class="nav-links">
                <li class="active">
                    <a href="dashboard.php">
                        <i class='bx bxs-dashboard'></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="create_quiz.php">
                        <i class='bx bx-plus-circle'></i>
                        <span>Create Quiz</span>
                    </a>
                </li>
                <li>
                    <a href="manage_quizzes.php">
                        <i class='bx bx-list-ul'></i>
                        <span>Manage Quizzes</span>
                    </a>
                </li>
                <li>
                    <a href="../quiz/index.php">
                        <i class='bx bx-arrow-back'></i>
                        <span>Back to Quizzes</span>
                    </a>
                </li>
                <li>
                    <a href="../../home/home.php">
                        <i class='bx bx-home'></i>
                        <span>Back to Home</span>
                    </a>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <div class="header-content">
                    <h1>Quiz Dashboard</h1>
                    <div class="user-info">
                        <span>Welcome, <?php echo htmlspecialchars($professor_name); ?></span>
                    </div>
                </div>
            </header>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class='bx bx-book-alt'></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo count($courses); ?></h3>
                        <p>Your Courses</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class='bx bx-clipboard'></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo count($quizzes); ?></h3>
                        <p>Total Quizzes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class='bx bx-time'></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo count(array_filter($quizzes, function($q) { return strtotime($q['end_time']) >= time(); })); ?></h3>
                        <p>Active Quizzes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class='bx bx-user-check'></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo array_sum(array_column($quizzes, 'attempt_count')); ?></h3>
                        <p>Total Attempts</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-sections">
                <section class="recent-quizzes">
                    <div class="section-header">
                        <h2>Recent Quizzes</h2>
                        <a href="create_quiz.php" class="btn-primary">Create New Quiz</a>
                    </div>
                    
                    <?php if (empty($quizzes)): ?>
                        <div class="empty-state">
                            <p>You haven't created any quizzes yet.</p>
                            <a href="create_quiz.php" class="btn-primary">Create Your First Quiz</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Course</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                        <th>Attempts</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($quizzes, 0, 5) as $quiz): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                            <td><?php echo htmlspecialchars($quiz['course_name']); ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($quiz['start_time'])); ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($quiz['end_time'])); ?></td>
                                            <td>
                                                <?php 
                                                $now = time();
                                                $start = strtotime($quiz['start_time']);
                                                $end = strtotime($quiz['end_time']);
                                                
                                                if ($now < $start) {
                                                    echo '<span class="status upcoming">Upcoming</span>';
                                                } elseif ($now > $end) {
                                                    echo '<span class="status expired">Expired</span>';
                                                } else {
                                                    echo '<span class="status active">Active</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo $quiz['attempt_count']; ?></td>
                                            <td class="actions">
                                                <a href="../quiz/view.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn-icon" title="View Quiz">
                                                    <i class='bx bx-show'></i>
                                                </a>
                                                <a href="manage_questions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn-icon" title="Manage Questions">
                                                    <i class='bx bx-question-mark'></i>
                                                </a>
                                                <a href="manage_submissions.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn-icon" title="View Submissions">
                                                    <i class='bx bx-file'></i>
                                                </a>
                                                <?php if ($now < $start): ?>
                                                <a href="create_quiz.php?edit=<?php echo $quiz['quiz_id']; ?>" class="btn-icon" title="Edit Quiz">
                                                    <i class='bx bx-edit'></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (count($quizzes) > 5): ?>
                                <div class="view-all">
                                    <a href="manage_quizzes.php">View All Quizzes</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="your-courses">
                    <div class="section-header">
                        <h2>Your Courses</h2>
                    </div>
                    
                    <?php if (empty($courses)): ?>
                        <div class="empty-state">
                            <p>You don't have any assigned courses.</p>
                        </div>
                    <?php else: ?>
                        <div class="courses-list">
                            <?php foreach ($courses as $course): ?>
                                <div class="course-card">
                                    <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                    <?php 
                                    // Count quizzes for this course
                                    $courseQuizzes = array_filter($quizzes, function($q) use ($course) {
                                        return $q['course_id'] == $course['course_id'];
                                    });
                                    
                                    // Count active quizzes
                                    $activeQuizzes = array_filter($courseQuizzes, function($q) {
                                        $now = time();
                                        $start = strtotime($q['start_time']);
                                        $end = strtotime($q['end_time']);
                                        return $now >= $start && $now <= $end;
                                    });
                                    ?>
                                    <div class="course-stats">
                                        <div class="course-stat">
                                            <span class="count"><?php echo count($courseQuizzes); ?></span>
                                            <span class="label">Total Quizzes</span>
                                        </div>
                                        <div class="course-stat">
                                            <span class="count"><?php echo count($activeQuizzes); ?></span>
                                            <span class="label">Active Quizzes</span>
                                        </div>
                                    </div>
                                    <a href="create_quiz.php?course=<?php echo $course['course_id']; ?>" class="btn-secondary">Create Quiz</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>