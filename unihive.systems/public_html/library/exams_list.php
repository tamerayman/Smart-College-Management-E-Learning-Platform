<?php
if (!defined('BASEPATH')) define('BASEPATH', true);

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get exams
if ($user_role == 'student') {
    // For students, show only exams from registered courses
    $sql = "SELECT le.* FROM library_exams le 
            INNER JOIN student_courses sc ON le.course_id = sc.course_id 
            WHERE sc.student_id = $user_id
            ORDER BY le.exam_year DESC, le.upload_date DESC";
} else {
    // For professors, show all exams or filter by their courses
    $sql = "SELECT * FROM library_exams 
            ORDER BY exam_year DESC, upload_date DESC";
}

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo '<div class="list-group">';
    while ($row = $result->fetch_assoc()) {
        echo '<div class="list-group-item">';
        echo '<h5 class="mb-1">' . htmlspecialchars($row['title']) . '</h5>';
        
        // Get course name
        $course_sql = "SELECT name as course_name FROM courses WHERE course_id = " . $row['course_id'];
        $course_result = $conn->query($course_sql);
        $course = $course_result->fetch_assoc();
        
        echo '<p class="mb-1">Course: ' . htmlspecialchars($course['course_name']) . '</p>';
        echo '<p class="mb-1">Exam Type: ' . htmlspecialchars($row['exam_type']) . ' - ' . $row['exam_year'] . '</p>';
        echo '<small>Upload Date: ' . date('Y-m-d', strtotime($row['upload_date'])) . '</small><br>';
        echo '<div class="mt-2">';
        echo '<a href="view_page.php?type=exam&id=' . $row['id'] . '" target="_blank" class="btn btn-sm btn-info me-1">Read</a>';
        echo '<a href="download.php?type=exam&id=' . $row['id'] . '" class="btn btn-sm btn-primary">Download</a>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<p class="text-center">No exams available at this time</p>';
}
?>
