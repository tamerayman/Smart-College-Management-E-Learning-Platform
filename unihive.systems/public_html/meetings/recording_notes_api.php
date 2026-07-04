<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}

$role = $_SESSION["role"];
$userId = $_SESSION["user_id"];
$action = $_GET['action'] ?? 'get';
$conn = connectDB();

// جلب ملاحظات الدكتور على تسجيل معين
if ($action === 'get' && isset($_GET['recording_id'])) {
    $recordingId = $_GET['recording_id'];
    
    $stmt = $conn->prepare("
        SELECT rn.*, p.name as professor_name 
        FROM recording_notes rn
        JOIN professors p ON rn.professor_id = p.professor_id
        WHERE rn.recording_id = ? AND rn.is_public = 1
    ");
    
    $stmt->bind_param("s", $recordingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'notes' => $notes]);
    exit;
}

// حفظ ملاحظات الدكتور (للأساتذة فقط)
if ($action === 'save' && $role === 'professor' && isset($_POST['recording_id'])) {
    $recordingId = $_POST['recording_id'];
    $noteContent = $_POST['note_content'] ?? '';
    $timestamp = $_POST['timestamp'] ?? 0;
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    
    // تحقق مما إذا كانت الملاحظة موجودة بالفعل
    $stmt = $conn->prepare("SELECT id FROM recording_notes WHERE recording_id = ? AND professor_id = ?");
    $stmt->bind_param("si", $recordingId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // تحديث الملاحظة الموجودة
        $row = $result->fetch_assoc();
        $noteId = $row['id'];
        
        $updateStmt = $conn->prepare("
            UPDATE recording_notes 
            SET note_content = ?, timestamp = ?, is_public = ? 
            WHERE id = ?
        ");
        
        $updateStmt->bind_param("sdii", $noteContent, $timestamp, $isPublic, $noteId);
        $success = $updateStmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'updated' => true]);
    } else {
        // إضافة ملاحظة جديدة
        $insertStmt = $conn->prepare("
            INSERT INTO recording_notes (recording_id, professor_id, note_content, timestamp, is_public) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $insertStmt->bind_param("sisdi", $recordingId, $userId, $noteContent, $timestamp, $isPublic);
        $success = $insertStmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'inserted' => true]);
    }
    
    exit;
}

// حذف ملاحظة (للأساتذة فقط)
if ($action === 'delete' && $role === 'professor' && isset($_POST['recording_id'])) {
    $recordingId = $_POST['recording_id'];
    
    $stmt = $conn->prepare("DELETE FROM recording_notes WHERE recording_id = ? AND professor_id = ?");
    $stmt->bind_param("si", $recordingId, $userId);
    $success = $stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// في حالة عدم توفر الإجراء المناسب
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
?>
