<?php
session_start();
require_once '../config.php';
require_once '../auth.php';
require_once 'meeting_functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

// التأكد من توفر معرف الاجتماع وتاريخه
if (!isset($_GET['meeting_id']) || !isset($_GET['date'])) {
    header("Location: meetings.php");
    exit;
}

$meetingId = $_GET['meeting_id'];
$meetingDate = $_GET['date'];
$format = isset($_GET['format']) ? $_GET['format'] : 'ics';
$duration = isset($_GET['duration']) ? (int)$_GET['duration'] : 60;

// استعلام عن بيانات الاجتماع
$conn = connectDB();
$stmt = $conn->prepare("SELECT * FROM scheduled_meetings WHERE meeting_id = ?");
$stmt->bind_param("s", $meetingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $meeting = $result->fetch_assoc();
    
    if ($format === 'google') {
        // توجيه إلى Google Calendar
        $gcalUrl = generateGoogleCalendarUrl($meeting, $meetingDate, $duration);
        header("Location: " . $gcalUrl);
        exit;
    } else {
        // تصدير ملف ICS
        $icsData = generateIcsData($meeting, $meetingDate, $duration);
        
        // إرسال رأس HTTP المناسب
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="meeting_' . $meetingId . '.ics"');
        
        // إرسال محتوى الملف
        echo $icsData;
        exit;
    }
} else {
    $_SESSION['message'] = "Meeting not found.";
    $_SESSION['messageType'] = "danger";
    header("Location: meetings.php");
    exit;
}

$stmt->close();
$conn->close();
