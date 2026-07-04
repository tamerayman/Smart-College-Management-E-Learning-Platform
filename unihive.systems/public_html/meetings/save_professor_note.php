<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// Check login status
if (!isLoggedIn() || $_SESSION['role'] !== 'professor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Debug incoming data
error_log("Save note data: " . print_r($data, true));

if (!isset($data['recordingId']) || !isset($data['noteContent']) || !isset($data['timestamp'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$recordingId = $data['recordingId'];
$noteContent = $data['noteContent'];
$timestamp = convertTimestampToSeconds($data['timestamp']);
$isPublic = isset($data['isPublic']) && $data['isPublic'] ? 1 : 0;
$noteCategory = isset($data['noteCategory']) ? $data['noteCategory'] : 'general';
$noteColor = isset($data['noteColor']) ? $data['noteColor'] : '#f8f9fa';
$professorId = $_SESSION['user_id'];

// Validate note category
$validCategories = ['important', 'explanation', 'question', 'reminder', 'general'];
if (!in_array($noteCategory, $validCategories)) {
    $noteCategory = 'general';
}

// Connect to database
$conn = connectDB();

// Insert new note
$stmt = $conn->prepare("
    INSERT INTO recording_notes (recording_id, professor_id, note_content, note_category, note_color, timestamp, is_public) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("sisssdi", $recordingId, $professorId, $noteContent, $noteCategory, $noteColor, $timestamp, $isPublic);
$success = $stmt->execute();

// If there was an error, log it
if (!$success) {
    error_log("MySQL Error: " . $stmt->error);
}

$conn->close();

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
exit;

// Helper function to convert timestamp format (MM:SS) to seconds
function convertTimestampToSeconds($timestamp) {
    $parts = explode(':', $timestamp);
    if (count($parts) === 2) {
        $minutes = intval($parts[0]);
        $seconds = intval($parts[1]);
        return ($minutes * 60) + $seconds;
    }
    return 0;
}
?>
