<?php
session_start();
require_once '../config.php';
require_once '../auth.php';
require_once 'bbb_api.php';
require_once 'meeting_functions.php';

// إضافة سجلات تشخيصية
error_log("AJAX Request received in meeting_ajax.php");
file_put_contents(__DIR__ . "/ajax_debug.log", date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$userId = $_SESSION["user_id"];
$role = $_SESSION["role"];
$userName = isset($userData['user_name']) ? $userData['user_name'] : "User";

// تعيين نوع المحتوى كـ JSON
header('Content-Type: application/json');

// سجل طلب AJAX للتشخيص
file_put_contents(__DIR__ . "/ajax_debug.log", date('Y-m-d H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// معالجة الطلبات
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'join' && isset($_POST['meeting_id'])) {
        $meetingId = $_POST['meeting_id'];
        file_put_contents(__DIR__ . "/ajax_debug.log", date('Y-m-d H:i:s') . " - Join request for meeting: $meetingId\n", FILE_APPEND);
        
        try {
            $result = joinMeeting($meetingId, $userName, $role);
            file_put_contents(__DIR__ . "/ajax_debug.log", date('Y-m-d H:i:s') . " - Join result: " . print_r($result, true) . "\n", FILE_APPEND);
            
            echo json_encode($result);
        } catch (Exception $e) {
            file_put_contents(__DIR__ . "/ajax_debug.log", date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
            echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid request parameters"]);
    }
    exit;
}

// إذا لم يكن الطلب POST، نعيد خطأ
echo json_encode(["success" => false, "message" => "Invalid request method"]);
exit;
?>
