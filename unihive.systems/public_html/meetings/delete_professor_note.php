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
error_log("Delete note data: " . print_r($data, true));

if (!isset($data['noteId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing note ID']);
    exit;
}

$noteId = $data['noteId'];
$professorId = $_SESSION['user_id'];

// Connect to database
$conn = connectDB();

// Delete note
$stmt = $conn->prepare("
    DELETE FROM recording_notes 
    WHERE id = ? AND professor_id = ?
");

$stmt->bind_param("ii", $noteId, $professorId);
$success = $stmt->execute();

// If there was an error, log it
if (!$success) {
    error_log("MySQL Error: " . $stmt->error);
}

$conn->close();

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
exit;
?>
