<?php
require_once 'bbb_api.php';

// Share recording with all students
function shareRecordingWithStudents($meetingId, $courseId) {
    global $conn;
    
    // First check if the recording exists
    $recordings = getMeetingRecordings($meetingId);
    
    if (empty($recordings)) {
        return ["success" => false, "message" => "لا توجد تسجيلات متاحة لهذا الاجتماع"];
    }
    
    // Get all students in this course
    $stmt = $conn->prepare("
        SELECT student_id 
        FROM student_courses 
        WHERE course_id = ?
    ");
    
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentIds = [];
    
    while ($row = $result->fetch_assoc()) {
        $studentIds[] = $row['student_id'];
    }
    $stmt->close();
    
    if (empty($studentIds)) {
        return ["success" => false, "message" => "لا يوجد طلاب مسجلين في هذه المادة"];
    }
    
    // Add a record in the shared_recordings table
    $stmt = $conn->prepare("
        INSERT INTO shared_recordings (meeting_id, course_id, shared_at) 
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE shared_at = NOW()
    ");
    
    $stmt->bind_param("si", $meetingId, $courseId);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        return ["success" => true, "message" => "تم مشاركة التسجيل مع " . count($studentIds) . " طالب بنجاح"];
    } else {
        return ["success" => false, "message" => "حدث خطأ أثناء مشاركة التسجيل"];
    }
}
?>
