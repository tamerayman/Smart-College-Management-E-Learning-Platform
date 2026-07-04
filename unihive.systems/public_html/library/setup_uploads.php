<?php
// Script to set up upload directories with proper permissions

// Books directory
$books_dir = '../uploads/books/';
if (!file_exists($books_dir)) {
    if (!mkdir($books_dir, 0777, true)) {
        echo "Failed to create books directory.\n";
    } else {
        chmod($books_dir, 0777);
        echo "Books directory created successfully.\n";
    }
} else {
    chmod($books_dir, 0777);
    echo "Books directory already exists, permissions updated.\n";
}

// Exams directory
$exams_dir = '../uploads/exams/';
if (!file_exists($exams_dir)) {
    if (!mkdir($exams_dir, 0777, true)) {
        echo "Failed to create exams directory.\n";
    } else {
        chmod($exams_dir, 0777);
        echo "Exams directory created successfully.\n";
    }
} else {
    chmod($exams_dir, 0777);
    echo "Exams directory already exists, permissions updated.\n";
}

echo "Setup complete.\n";
?>
