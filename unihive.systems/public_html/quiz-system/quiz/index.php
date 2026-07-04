<?php
session_start();
require_once '../../config.php';
require_once '../../auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit;
}

$userId = $_SESSION["user_id"];
$role = $_SESSION["role"];
$conn = connectDB();
$quizzes = [];

// Get relevant quizzes based on user role
if ($role == "student") {
    // Get courses the student is enrolled in
    $stmt = $conn->prepare("
        SELECT c.course_id, c.name as course_name
        FROM student_courses sc
        JOIN courses c ON sc.course_id = c.course_id
        WHERE sc.student_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $courses_result = $stmt->get_result();
    
    $courses = [];
    while ($course = $courses_result->fetch_assoc()) {
        $courses[] = $course['course_id'];
    }
    
    if (!empty($courses)) {
        $course_ids = implode(',', $courses);
        
        // Get quizzes for these courses without joining professors
        $quiz_sql = "
            SELECT q.*, c.name as course_name
            FROM quizzes q
            JOIN courses c ON q.course_id = c.course_id
            WHERE q.course_id IN ($course_ids)
            ORDER BY q.end_time DESC
        ";
        
        $quiz_result = $conn->query($quiz_sql);
        if ($quiz_result) {
            while ($quiz = $quiz_result->fetch_assoc()) {
                // Retrieve professor information separately
                if (isset($quiz['professor_id'])) {
                    $prof_stmt = $conn->prepare("
                        SELECT name as professor_name
                        FROM professors
                        WHERE professor_id = ?
                    ");
                    $prof_stmt->bind_param("i", $quiz['professor_id']);
                    $prof_stmt->execute();
                    $prof_result = $prof_stmt->get_result();
                    if ($prof_result && $prof_data = $prof_result->fetch_assoc()) {
                        $quiz['professor_name'] = $prof_data['professor_name'];
                    } else {
                        $quiz['professor_name'] = 'Unknown';
                    }
                } else {
                    $quiz['professor_name'] = 'Unknown';
                }
                
                // Check if student has already attempted this quiz
                $attempt_stmt = $conn->prepare("
                    SELECT * FROM quiz_attempts 
                    WHERE quiz_id = ? AND student_id = ?
                ");
                $attempt_stmt->bind_param("ii", $quiz['quiz_id'], $userId);
                $attempt_stmt->execute();
                $attempt_result = $attempt_stmt->get_result();
                
                $quiz['attempted'] = $attempt_result->num_rows > 0;
                if ($quiz['attempted']) {
                    $attempt = $attempt_result->fetch_assoc();
                    $quiz['attempt_status'] = $attempt['status'];
                    $quiz['score'] = $attempt['score'];
                }
                
                $quizzes[] = $quiz;
            }
        }
    }
} elseif ($role == "professor") {
    // Get quizzes created by this professor
    $stmt = $conn->prepare("
        SELECT q.*, c.name as course_name
        FROM quizzes q
        JOIN courses c ON q.course_id = c.course_id
        WHERE q.professor_id = ?
        ORDER BY q.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($quiz = $result->fetch_assoc()) {
        // Get number of student attempts for each quiz
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM quiz_attempts 
            WHERE quiz_id = ?
        ");
        $count_stmt->bind_param("i", $quiz['quiz_id']);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        
        $quiz['attempt_count'] = $count_data['attempt_count'];
        $quizzes[] = $quiz;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHive - Quizzes</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/quiz.css">
</head>
<body>
    <header>
        <div class="header-container">
            <h1>UniHive Quiz System</h1>
            <div class="user-actions">
                <?php if ($role == "professor"): ?>
                <a href="../admin/dashboard.php" class="btn-primary">Admin Dashboard</a>
                <?php endif; ?>
                <a href="../../home/home.php" class="btn-secondary">Back to Home</a>
            </div>
        </div>
    </header>

    <main>
        <section class="quiz-container">
            <h2><?php echo ($role == "student") ? "Available Quizzes" : "Your Quizzes"; ?></h2>
            
            <?php if (empty($quizzes)): ?>
                <div class="empty-state">
                    <?php if ($role == "student"): ?>
                        <p>No quizzes available for your courses at the moment.</p>
                    <?php else: ?>
                        <p>You haven't created any quizzes yet.</p>
                        <a href="../admin/create_quiz.php" class="btn-primary">Create Your First Quiz</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="quiz-list">
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="quiz-card <?php echo (strtotime($quiz['end_time']) < time()) ? 'expired' : ''; ?>">
                            <div class="quiz-header">
                                <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                <span class="course-badge"><?php echo htmlspecialchars($quiz['course_name']); ?></span>
                            </div>
                            
                            <div class="quiz-details">
                                <?php if ($role == "student"): ?>
                                    <p>Created by: <span><?php echo htmlspecialchars($quiz['professor_name']); ?></span></p>
                                <?php endif; ?>
                                <p>Time Limit: <span><?php echo htmlspecialchars($quiz['time_limit']); ?> minutes</span></p>
                                <p>Available from: <span><?php echo date('M d, Y h:i A', strtotime($quiz['start_time'])); ?></span></p>
                                <p>Available until: <span><?php echo date('M d, Y h:i A', strtotime($quiz['end_time'])); ?></span></p>
                                
                                <?php if ($role == "student"): ?>
                                    <?php if (isset($quiz['attempted']) && $quiz['attempted']): ?>
                                        <?php if ($quiz['attempt_status'] == 'completed'): ?>
                                            <div class="quiz-status completed">
                                                <i class='bx bx-check-circle'></i>
                                                <span>Completed: Score <?php echo $quiz['score']; ?>%</span>
                                            </div>
                                            <a href="results.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn-secondary">View Results</a>
                                        <?php else: ?>
                                            <div class="quiz-status in-progress">
                                                <i class='bx bx-time'></i>
                                                <span>Attempt in progress</span>
                                            </div>
                                            <a href="attempt.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn-primary">Continue Quiz</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (strtotime($quiz['start_time']) <= time() && strtotime($quiz['end_time']) >= time()): ?>
                                            <a href="attempt.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn-primary">Start Quiz</a>
                                        <?php elseif (strtotime($quiz['start_time']) > time()): ?>
                                            <div class="quiz-status upcoming">
                                                <i class='bx bx-calendar'></i>
                                                <span>Not yet available</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="quiz-status expired">
                                                <i class='bx bx-x-circle'></i>
                                                <span>Expired</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>Attempts: <span><?php echo $quiz['attempt_count']; ?></span></p>
                                    <div class="professor-actions">
                                        <a href="view.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn-secondary">View Quiz</a>
                                        <a href="../admin/manage_submissions.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn-primary">View Submissions</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> UniHive Quiz System</p>
    </footer>

    <script src="../assets/js/quiz.js"></script>
</body>
</html>