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
error_log("Get note data: " . print_r($data, true));

if (!isset($data['noteId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing note ID']);
    exit;
}

$noteId = $data['noteId'];
$professorId = $_SESSION['user_id'];

// Connect to database
$conn = connectDB();

// Get note details
$stmt = $conn->prepare("
    SELECT * FROM recording_notes
    WHERE id = ? AND professor_id = ?
");

$stmt->bind_param("ii", $noteId, $professorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $note = $result->fetch_assoc();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'noteContent' => $note['note_content'],
        'timestamp' => $note['timestamp'],
        'isPublic' => (bool)$note['is_public'],
        'noteCategory' => $note['note_category'],
        'noteColor' => $note['note_color']
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Note not found or not authorized']);
}

$conn->close();
exit;
?>
