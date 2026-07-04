<?php
// filepath: quiz-system/includes/functions.php

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('redirectUserByRole')) {
    function redirectUserByRole($role) {
        if ($role == 'student') {
            header("Location: ../quiz/index.php");
        } elseif ($role == 'professor') {
            header("Location: ../admin/dashboard.php");
        }
        exit;
    }
}

if (!function_exists('getQuizzesBySubject')) {
    function getQuizzesBySubject($courseId) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM quizzes WHERE course_id = ? AND end_time > NOW()");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('createQuiz')) {
    function createQuiz($title, $duration, $expirationTime, $courseId, $professorId) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO quizzes (title, time_limit, end_time, course_id, professor_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $title, $duration, $expirationTime, $courseId, $professorId);
        return $stmt->execute();
    }
}

if (!function_exists('getQuizResults')) {
    function getQuizResults($quizId) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM quiz_results WHERE quiz_id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('getStudentRankings')) {
    function getStudentRankings($quizId) {
        global $conn;
        $stmt = $conn->prepare("SELECT student_id, score, completion_time FROM quiz_results WHERE quiz_id = ? ORDER BY score DESC, completion_time ASC");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>