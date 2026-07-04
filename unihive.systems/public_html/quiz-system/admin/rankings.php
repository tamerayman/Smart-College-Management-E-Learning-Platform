<?php
// filepath: quiz-system/admin/rankings.php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if the user is logged in and is a professor
if (!isLoggedIn() || $_SESSION["role"] !== "professor") {
    header("Location: ../index.php");
    exit;
}

// Connect to the database
$conn = connectDB();

// Fetch rankings based on quiz scores and completion times
$query = "
    SELECT s.name AS student_name, q.title AS quiz_title, 
           s.score, s.completion_time 
    FROM submissions s
    JOIN quizzes q ON s.quiz_id = q.id
    WHERE q.professor_id = ?
    ORDER BY s.score DESC, s.completion_time ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

$rankings = [];
while ($row = $result->fetch_assoc()) {
    $rankings[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rankings - UniHive</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Student Rankings</h1>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Quiz Title</th>
                    <th>Score</th>
                    <th>Completion Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rankings)): ?>
                    <?php foreach ($rankings as $ranking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ranking['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($ranking['quiz_title']); ?></td>
                            <td><?php echo htmlspecialchars($ranking['score']); ?></td>
                            <td><?php echo htmlspecialchars($ranking['completion_time']); ?> seconds</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No rankings available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>