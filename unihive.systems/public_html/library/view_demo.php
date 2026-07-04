<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Get parameters
$course = isset($_GET['course']) ? $_GET['course'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'read';
$embed = isset($_GET['embed']) ? true : false;

// If reading in embedded mode, show the PDF directly
if ($type == 'read' && !$embed) {
    header('Location: view_demo_page.php?course=' . urlencode($course));
    exit();
}

// Map of courses to demo PDF files
$demo_files = [
    'accounting' => '../demo/accounting.pdf',
    'database' => '../demo/database.pdf',
    'data_mining' => '../demo/data_mining.pdf',
    'data_structure' => '../demo/data_structure.pdf',
    'economy' => '../demo/economy.pdf',
    // Add more courses as needed
];

// Default demo file
$file_path = isset($demo_files[$course]) ? $demo_files[$course] : '../demo/accounting.pdf';

// Create demo directory if it doesn't exist
if (!file_exists('../demo')) {
    mkdir('../demo', 0777, true);
}

// If the file doesn't exist, create an empty file as placeholder
if (!file_exists($file_path)) {
    // First check if we can find any PDF files in the uploads directory
    $uploads_dir = '../uploads/books/';
    $found_file = false;
    
    if (file_exists($uploads_dir)) {
        $files = scandir($uploads_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
                $file_path = $uploads_dir . $file;
                $found_file = true;
                break;
            }
        }
    }
    
    // If still no file, create placeholder notice
    if (!$found_file) {
        $default_message = "No file available for {$course}. Please upload content first.";
        file_put_contents('../demo/default.txt', $default_message);
        $file_path = '../demo/default.txt';
    }
}

// Handle the request based on type
if ($type == 'download') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    // Display PDF in browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
}
?>
