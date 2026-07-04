<?php
// filepath: quiz-system/includes/header.php

// Only start session if one isn't already active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Get user role
$userRole = $_SESSION['role'];

// Include CSS files
echo '<link rel="stylesheet" href="../assets/css/style.css">';
echo '<link rel="stylesheet" href="../assets/css/admin.css">';
echo '<link rel="stylesheet" href="../assets/css/quiz.css">';

// Include JavaScript files
echo '<script src="../assets/js/admin.js"></script>';
echo '<script src="../assets/js/quiz.js"></script>';
echo '<script src="../assets/js/timer.js"></script>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz System</title>
</head>
<body>
    <header>
        <h1>Welcome to the Quiz System</h1>
        <nav>
            <ul>
                <li><a href="../home/home.php">Home</a></li>
                <?php if ($userRole == 'professor'): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                <?php endif; ?>
                <li><a href="../quiz/index.php">Quizzes</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>