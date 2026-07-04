<?php
session_start();
require_once '../config.php';
require_once '../auth.php';
require_once 'bbb_api.php';
require_once 'meeting_functions.php';
require_once 'recording_functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

// الحصول على بيانات المستخدم الحالي
$userId = $_SESSION["user_id"];
$role = $_SESSION["role"];
$userData = [];

// جلب بيانات المستخدم من قاعدة البيانات
$conn = connectDB();
if ($role == "student") {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.email, s.name as user_name, s.level, d.name as department_name 
        FROM users u 
        JOIN students s ON u.user_id = s.student_id 
        JOIN departments d ON s.department_id = d.department_id 
        WHERE u.user_id = ?
    ");
} elseif ($role == "professor") {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.email, p.name as user_name, d.name as department_name 
        FROM users u 
        JOIN professors p ON u.user_id = p.professor_id 
        JOIN departments d ON p.department_id = d.department_id 
        WHERE u.user_id = ?
    ");
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $userData = $result->fetch_assoc();
    $userName = $userData['user_name'];
} else {
    $userName = "User";
}

// Handle form submissions
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $redirectNeeded = true; // سنقوم بإعادة التوجيه بعد معالجة POST
        
        switch ($_POST['action']) {
            case 'join':
                if (isset($_POST['meeting_id'])) {
                    $result = joinMeeting($_POST['meeting_id'], $userName, $role);
                    if (isset($result) && !$result['success']) {
                        $_SESSION['message'] = $result['message'];
                        $_SESSION['messageType'] = "danger";
                    } elseif (isset($result['url'])) {
                        // Store the URL in a session variable for later use
                        $_SESSION['meeting_url'] = $result['url'];
                        $redirectNeeded = false; // في هذه الحالة نريد فتح النافذة قبل إعادة التوجيه
                    }
                }
                break;
                
            case 'create':
                if ($role == "professor" && isset($_POST['meeting_name']) && isset($_POST['course_id'])) {
                    $welcomeMsg = isset($_POST['welcome_message']) ? $_POST['welcome_message'] : '';
                    $result = createMeeting(
                        $_POST['meeting_name'], 
                        $_POST['course_id'], 
                        $userId, 
                        $welcomeMsg
                    );
                    
                    if ($result["success"]) {
                        $_SESSION['message'] = $result["message"];
                        $_SESSION['messageType'] = "success";
                    } else {
                        $_SESSION['message'] = $result["message"];
                        $_SESSION['messageType'] = "danger";
                    }
                }
                break;
                
            case 'schedule':
                if ($role == "professor" && isset($_POST['meeting_name']) && isset($_POST['course_id']) && isset($_POST['scheduled_time'])) {
                    $welcomeMsg = isset($_POST['welcome_message']) ? $_POST['welcome_message'] : '';
                    $duration = isset($_POST['duration']) ? $_POST['duration'] : '60'; // Default 60 minutes
                    // Connect to database
                    $result = scheduleMeeting(
                        $_POST['meeting_name'],
                        $_POST['course_id'],
                        $userId,
                        $_POST['scheduled_time'],
                        $duration,
                        $welcomeMsg
                    );
                    
                    if ($result["success"]) {
                        $_SESSION['message'] = $result["message"];
                        $_SESSION['messageType'] = "success";
                    } else {
                        $_SESSION['message'] = $result["message"];
                        $_SESSION['messageType'] = "danger";
                    }   
                }
                break;
                
            case 'view_recordings':
                if (isset($_POST['meeting_id'])) {
                    $recordings = getMeetingRecordings($_POST['meeting_id']);
                    // Store in session to display in modal
                    $_SESSION['current_recordings'] = $recordings;
                    $_SESSION['current_meeting_name'] = $_POST['meeting_name'] ?? 'Meeting';
                    $_SESSION['current_meeting_id'] = $_POST['meeting_id'];
                    $_SESSION['show_recordings_modal'] = true;
                    
                    // Check if course_id is set in the POST data
                    if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
                        $_SESSION['current_course_id'] = $_POST['course_id'];
                    } else {
                        // Try to find the course_id from the meeting_id in the database
                        $meetingId = $_POST['meeting_id'];
                        $conn = connectDB(); // Reconnect to database
                        $stmt = $conn->prepare("SELECT course_id FROM meetings WHERE meeting_id = ? LIMIT 1");
                        $stmt->bind_param("s", $meetingId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result && $result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $_SESSION['current_course_id'] = $row['course_id'];
                        } else {
                            // If all else fails, set to null
                            $_SESSION['current_course_id'] = null;
                        }
                        
                        $stmt->close();
                        $conn->close();
                    }
                }
                break;
                
            case 'share_recording':
                if ($role == "professor" && isset($_POST['meeting_id']) && isset($_POST['course_id'])) {
                    $result = shareRecordingWithStudents($_POST['meeting_id'], $_POST['course_id']);
                    
                    if ($result["success"]) {
                        $_SESSION['message'] = $result["message"];
                        $_SESSION['messageType'] = "success";
                    } else {
                        $_SESSION['message'] = $result["message"];
                        $_SESSION['messageType'] = "danger";
                    }
                }
                break;
                
            case 'play_recording':
                if (isset($_POST['recording_url']) && isset($_POST['meeting_name']) && isset($_POST['recording_id'])) {
                    $_SESSION['play_recording'] = [
                        'url' => $_POST['recording_url'],
                        'name' => $_POST['meeting_name'],
                        'id' => $_POST['recording_id'],
                        'date' => $_POST['recording_date']
                    ];
                    // Redirect to player page instead of current page
                    header('Location: recording_player.php');
                    exit;
                }
                break;
                
            case 'delete':
                if ($role == "professor" && isset($_POST['meeting_id'])) {
                    $meetingId = $_POST['meeting_id'];
                    
                    // Connect to database
                    $conn = connectDB();
                    
                    // Check if the meeting belongs to this professor
                    $stmt = $conn->prepare("SELECT * FROM meetings WHERE meeting_id = ? AND professor_id = ?");
                    $stmt->bind_param("si", $meetingId, $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result && $result->num_rows > 0) {
                        // Proceed with deletion
                        $deleteStmt = $conn->prepare("DELETE FROM meetings WHERE meeting_id = ?");
                        $deleteStmt->bind_param("s", $meetingId);
                        
                        if ($deleteStmt->execute()) {
                            $_SESSION['message'] = "Meeting has been successfully deleted.";
                            $_SESSION['messageType'] = "success";
                        } else {
                            $_SESSION['message'] = "Failed to delete the meeting. Please try again.";
                            $_SESSION['messageType'] = "danger";
                        }
                        $deleteStmt->close();
                    } else {
                        $_SESSION['message'] = "You don't have permission to delete this meeting.";
                        $_SESSION['messageType'] = "danger";
                    }
                    
                    $stmt->close();
                    $conn->close();
                }
                break;
        }
        
        // إعادة توجيه بعد معالجة النموذج (نمط PRG)
        if ($redirectNeeded) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// استعادة رسائل الحالة من الجلسة إذا كانت موجودة
if (isset($_SESSION['message']) && !empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    // مسح الرسائل بعد استعادتها
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

// Get available meetings based on user role
$userMeetings = [];
if ($role == "student") {
    $userMeetings = getStudentMeetings($userId);
} elseif ($role == "professor") {
    $userMeetings = getProfessorMeetings($userId);
}
        
// Get all active BigBlueButton meetings
$bbbMeetings = getBigBlueButtonMeetings();
$activeMeetings = [];
if (isset($bbbMeetings->meetings) && isset($bbbMeetings->meetings->meeting)) {
    foreach ($bbbMeetings->meetings->meeting as $meeting) {
        $activeMeetings[(string)$meeting->meetingID] = [
            'meetingName' => (string)$meeting->meetingName,
            'attendees' => (int)$meeting->participantCount,
            'createdAt' => (string)$meeting->createTime
        ];
    }
}

// Pre-fetch all meeting stats and scheduled meetings before closing the connection
$courseScheduledMeetings = [];
$meetingStats = [];
if (!empty($userMeetings)) {
    foreach ($userMeetings as $course) {
        // Get scheduled meetings for this course
        $courseScheduledMeetings[$course['course_id']] = getScheduledMeetings($course['course_id']);
        // Get stats for all meetings in this course
        if (!empty($course['meetings'])) {
            foreach ($course['meetings'] as $meeting) {
                if (!empty($meeting['meeting_id'])) {
                    // Pre-fetch stats for this meeting
                    $meetingStats[$meeting['meeting_id']] = getMeetingStats($meeting['meeting_id']);
                }
            }
        }
    }
}

// تأكد من استدعاء ملف meeting_functions.php مرة واحدة فقط
if (!function_exists('checkScheduledMeetings')) {
    require_once 'meeting_functions.php';
}

// إنشاء اتصال منفصل بقاعدة البيانات للتحقق من الاجتماعات المجدولة
$autoStartedMeetings = checkScheduledMeetings();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-lang="<?php echo isset($_COOKIE['lang']) && $_COOKIE['lang'] == 'ar' ? 'ar' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHive - Online Meetings</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="meetings.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="../home/home.php" class="logo">UniHive</a>
                <h1>Online Meetings</h1>
                <div></div> <!-- Empty div for spacing -->
            </div>
        </div>
    </div>

    <?php if (!empty($autoStartedMeetings)): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h5><i class='bx bx-check-circle'></i> Scheduled meetings started automatically:</h5>
            <ul>
                <?php foreach ($autoStartedMeetings as $meeting): ?>
                <li><?php echo htmlspecialchars($meeting['name']); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Language Switcher -->
    <div class="language-switcher">
        <button class="btn btn-sm" id="langToggle">
            <i class='bx bx-globe'></i> <span id="currentLang">English</span>
        </button>
    </div>

    <div class="container">
        <a href="../home/home.php" class="back-link">
            <i class='bx bx-arrow-back'></i> Back to Home
        </a>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($role == "professor"): ?>
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="create-tab" data-bs-toggle="tab" data-bs-target="#create" type="button" role="tab" aria-controls="create" aria-selected="true">
                        <i class='bx bx-plus'></i> Instant Meeting
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab" aria-controls="schedule" aria-selected="false">
                        <i class='bx bx-calendar'></i> Schedule Meeting
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="create" role="tabpanel" aria-labelledby="create-tab">
                    <div class="create-meeting-card">
                        <h4>Create New Meeting</h4>
                        <form method="post" class="mt-3">
                            <input type="hidden" name="action" value="create">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="meeting_name" class="form-label">Meeting Name</label>
                                    <input type="text" class="form-control" id="meeting_name" name="meeting_name" required placeholder="Enter meeting name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="course_id" class="form-label">Course</label>
                                    <select class="form-control" id="course_id" name="course_id" required>
                                        <option value="">Select a course</option>
                                        <?php foreach ($userMeetings as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>">
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="welcome_message" class="form-label">Welcome Message (Optional)</label>
                                <textarea class="form-control" id="welcome_message" name="welcome_message" rows="2" placeholder="Enter a welcome message for participants"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Create & Start Meeting</button>
                        </form>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                    <div class="create-meeting-card">
                        <h4>Schedule Future Meeting</h4>
                        <form method="post" class="mt-3">
                            <input type="hidden" name="action" value="schedule">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="meeting_name_schedule" class="form-label">Meeting Name</label>
                                    <input type="text" class="form-control" id="meeting_name_schedule" name="meeting_name" required placeholder="Enter meeting name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="course_id_schedule" class="form-label">Course</label>
                                    <select class="form-control" id="course_id_schedule" name="course_id" required>
                                        <option value="">Select a course</option>
                                        <?php foreach ($userMeetings as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>">
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="scheduled_time" class="form-label">Scheduled Date & Time</label>
                                    <input type="text" class="form-control flatpickr" id="scheduled_time" name="scheduled_time" required placeholder="Select date and time">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="duration" class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" id="duration" name="duration" value="60" min="15" max="180" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="welcome_message_schedule" class="form-label">Welcome Message (Optional)</label>
                                <textarea class="form-control" id="welcome_message_schedule" name="welcome_message" rows="2" placeholder="Enter a welcome message for participants"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Schedule Meeting</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <h3 class="mb-4">Your Available Courses and Meetings</h3>
        
        <?php if (empty($userMeetings)): ?>
            <div class="alert alert-info">
                No courses or meetings available at this time.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($userMeetings as $course): 
                    // Use the pre-fetched scheduled meetings for this course
                    $scheduledMeetings = $courseScheduledMeetings[$course['course_id']] ?? [];
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                            <small><?php echo htmlspecialchars($course['department_name']); ?></small>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($scheduledMeetings)): ?>
                                <h6 class="mb-3"><i class='bx bx-calendar'></i> Upcoming Scheduled Meetings</h6>
                                <div class="scheduled-meetings mb-4">
                                    <?php foreach ($scheduledMeetings as $scheduled): ?>
                                    <div class="scheduled-meeting">
                                        <h6><?php echo htmlspecialchars($scheduled['meeting_name']); ?></h6>
                                        <div class="time">
                                            <?php echo date('l, F d, Y - h:i A', strtotime($scheduled['scheduled_time'])); ?>
                                        </div>
                                        <div class="duration">
                                            Duration: <?php echo $scheduled['duration']; ?> minutes
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#calendarExportModal" data-meeting-id="<?php echo $scheduled['meeting_id']; ?>" data-meeting-date="<?php echo $scheduled['scheduled_time']; ?>" data-duration="<?php echo $scheduled['duration']; ?>">
                                            <i class='bx bx-calendar'></i> Export to Calendar
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (empty($course['meetings'])): ?>
                                <p class="text-muted">No meetings available for this course.</p>
                                <?php if($role == "professor"): ?>
                                <form method="post" class="mt-2">
                                    <input type="hidden" name="action" value="create">
                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                    <input type="hidden" name="meeting_name" value="<?php echo $course['course_name']; ?> Meeting">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        Create New Meeting
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <h6><i class='bx bx-video'></i> Recent Meetings</h6>
                                <div class="list-group">
                                    <?php foreach ($course['meetings'] as $index => $meeting): 
                                        if ($index >= 3) break; // Show only 3 most recent meetings
                                        $isActive = isset($activeMeetings[$meeting['meeting_id']]);
                                        // Use pre-fetched stats instead of calling getMeetingStats()
                                        $stats = $meetingStats[$meeting['meeting_id']] ?? ['participant_count' => 0];
                                    ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($meeting['meeting_name']); ?>
                                                <?php if ($isActive): ?>
                                                    <span class="badge bg-success ms-2">Active</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php 
                                                    $date = new DateTime($meeting['last_updated']);
                                                    echo $date->format('M d, H:i');
                                                ?>
                                            </small>
                                        </div>
                                        
                                        <div class="row mt-2 mb-2">
                                            <div class="col-md-4">
                                                <div class="stats-box">
                                                    <div class="stats-label">Participants</div>
                                                    <div class="stats-value">
                                                        <?php echo $isActive ? $activeMeetings[$meeting['meeting_id']]['attendees'] : ($stats['participant_count'] ?? 0); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <!-- استخدام نموذج عادي بدلاً من AJAX -->
                                                <form method="post">
                                                    <input type="hidden" name="action" value="view_recordings">
                                                    <input type="hidden" name="meeting_id" value="<?php echo $meeting['meeting_id']; ?>">
                                                    <input type="hidden" name="meeting_name" value="<?php echo htmlspecialchars($meeting['meeting_name']); ?>">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                                        <i class='bx bx-video-recording'></i> View Recordings
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="mt-2">
                                            <?php if ($isActive || $role == "professor"): ?>
                                                <!-- استخدام نموذج عادي بدلاً من AJAX -->
                                                <form method="post" class="d-inline-block">
                                                    <input type="hidden" name="action" value="join">
                                                    <input type="hidden" name="meeting_id" value="<?php echo $meeting['meeting_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <?php echo $isActive ? 'Join Meeting' : 'Start Meeting'; ?>
                                                    </button>
                                                </form>
                                                
                                                <?php if ($role == "professor"): ?>
                                                <form method="post" class="d-inline-block delete-meeting-form ms-2">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="meeting_id" value="<?php echo $meeting['meeting_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class='bx bx-trash'></i> Delete
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                    Waiting for professor to start
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if($role == "professor"): ?>
                                <!-- استبدال نموذج إنشاء اجتماع جديد بنموذج عادي (لا نستخدم AJAX هنا) -->
                                <form method="post" class="mt-3">
                                    <input type="hidden" name="action" value="create">
                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                    <input type="hidden" name="meeting_name" value="<?php echo htmlspecialchars($course['course_name']); ?> Meeting">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        Create New Meeting
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Recordings Modal -->
        <div class="modal fade" id="recordingsModal" tabindex="-1" aria-labelledby="recordingsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="recordingsModalLabel">
                            <span class="en">Meeting Recordings</span>
                            <span class="ar">تسجيلات الاجتماع</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (isset($_SESSION['current_recordings']) && !empty($_SESSION['current_recordings'])): ?>
                            <h6>
                                <?php echo isset($_SESSION['current_meeting_name']) ? htmlspecialchars($_SESSION['current_meeting_name']) : "Meeting"; ?>
                                <span class="en">Recordings</span>
                                <span class="ar">التسجيلات</span>
                            </h6>
                            
                            <div class="search-box mb-3">
                                <input type="text" class="form-control" id="searchRecordings" placeholder="Search recordings...">
                            </div>
                            
                            <?php if ($role == "professor" && isset($_SESSION['current_course_id']) && $_SESSION['current_course_id'] !== null): ?>
                            <form method="post" class="mb-3">
                                <input type="hidden" name="action" value="share_recording">
                                <input type="hidden" name="meeting_id" value="<?php echo $_SESSION['current_meeting_id']; ?>">
                                <input type="hidden" name="course_id" value="<?php echo $_SESSION['current_course_id']; ?>">
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class='bx bx-share'></i> 
                                    <span class="en">Share with all students</span>
                                    <span class="ar">مشاركة مع جميع الطلاب</span>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <div class="recordings-container">
                                <?php
                                // Group recordings by month
                                $recordingsByMonth = [];
                                foreach ($_SESSION['current_recordings'] as $recording) {
                                    $month = date('F Y', (int)($recording['startTime'] / 1000));
                                    if (!isset($recordingsByMonth[$month])) {
                                        $recordingsByMonth[$month] = [];
                                    }
                                    $recordingsByMonth[$month][] = $recording;
                                }
                                
                                foreach ($recordingsByMonth as $month => $monthRecordings):
                                ?>
                                <div class="recordings-month">
                                    <h6 class="month-header"><?php echo $month; ?></h6>
                                    <div class="list-group">
                                        <?php foreach ($monthRecordings as $recording): 
                                            $recordingDate = date('Y-m-d H:i', (int)($recording['startTime'] / 1000));
                                        ?>
                                        <div class="recording-item" data-date="<?php echo $recordingDate; ?>">
                                            <div class="recording-info">
                                                <div class="recording-date">
                                                    <i class='bx bx-calendar'></i> <?php echo date('d M Y, H:i', (int)($recording['startTime'] / 1000)); ?>
                                                </div>
                                                <div class="recording-duration">
                                                    <i class='bx bx-time'></i> <?php echo floor($recording['duration'] / 60); ?> 
                                                    <span class="en">minutes</span>
                                                    <span class="ar">دقيقة</span>
                                                </div>
                                            </div>
                                            <div class="recording-actions">
                                                <form method="post">
                                                    <input type="hidden" name="action" value="play_recording">
                                                    <input type="hidden" name="recording_url" value="<?php echo $recording['playUrl']; ?>">
                                                    <input type="hidden" name="recording_id" value="<?php echo $recording['recordId']; ?>">
                                                    <input type="hidden" name="meeting_name" value="<?php echo isset($_SESSION['current_meeting_name']) ? $_SESSION['current_meeting_name'] : 'Recording'; ?>">
                                                    <input type="hidden" name="recording_date" value="<?php echo $recordingDate; ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class='bx bx-play'></i> 
                                                        <span class="en">Play</span>
                                                        <span class="ar">تشغيل</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center">
                                <span class="en">No recordings found for this meeting.</span>
                                <span class="ar">لا توجد تسجيلات لهذا الاجتماع.</span>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <span class="en">Close</span>
                            <span class="ar">إغلاق</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Export Modal -->
    <div class="modal fade" id="calendarExportModal" tabindex="-1" aria-labelledby="calendarExportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calendarExportModalLabel">
                        <span class="en">Export to Calendar</span>
                        <span class="ar">تصدير إلى التقويم</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>
                        <span class="en">Choose your calendar application:</span>
                        <span class="ar">اختر تطبيق التقويم الخاص بك:</span>
                    </p>
                    <div class="calendar-btn-wrapper">
                        <a href="#" id="googleCalendarLink" class="calendar-btn google-btn">
                            <i class='bx bxl-google'></i>
                            <span>Google Calendar</span>
                        </a>
                        <a href="#" id="outlookCalendarLink" class="calendar-btn outlook-btn">
                            <i class='bx bx-calendar'></i>
                            <span>Outlook / iCal</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // تهيئة Flatpickr
            if (document.querySelector(".flatpickr")) {
                flatpickr(".flatpickr", {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    minDate: "today"
                });
            }

            // عرض نافذة التسجيلات إذا كان مطلوبًا
            <?php if (isset($_SESSION['show_recordings_modal']) && $_SESSION['show_recordings_modal']): ?>
                var recordingsModal = new bootstrap.Modal(document.getElementById('recordingsModal'));
                recordingsModal.show();
                <?php unset($_SESSION['show_recordings_modal']); ?>
            <?php endif; ?>

            // فتح صفحة الاجتماع في نافذة جديدة إذا كان عنوان URL موجودًا
            <?php if (isset($_SESSION['meeting_url']) && !empty($_SESSION['meeting_url'])): ?>
                // توليد مفتاح فريد لهذه المحاولة للانضمام إلى الاجتماع
                const meetingUrl = '<?php echo $_SESSION['meeting_url']; ?>';
                
                // التحقق من آخر محاولة انضمام للاجتماع (خلال 3 ثوانٍ فقط)
                const lastJoinAttemptTime = sessionStorage.getItem('last_join_attempt_time');
                const currentTime = new Date().getTime();
                
                // إذا لم تكن هناك محاولة سابقة، أو مرت أكثر من 3 ثوانٍ منذ آخر محاولة
                if (!lastJoinAttemptTime || (currentTime - parseInt(lastJoinAttemptTime)) > 3000) {
                    console.log("Opening meeting URL:", meetingUrl);
                    window.open(meetingUrl, "_blank");
                    // تسجيل وقت هذه المحاولة
                    sessionStorage.setItem('last_join_attempt_time', currentTime.toString());
                } else {
                    console.log("Prevented duplicate meeting open - too soon after last attempt");
                }
                
                <?php unset($_SESSION['meeting_url']); ?>
            <?php endif; ?>

            // إضافة مستمع للبحث عن التسجيلات
            const searchInput = document.getElementById('searchRecordings');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    document.querySelectorAll('.recording-item').forEach(item => {
                        const dateText = item.getAttribute('data-date').toLowerCase();
                        if (dateText.includes(searchTerm)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }

            // تعديل رسالة تأكيد حذف الاجتماع حسب اللغة
            const deleteForms = document.querySelectorAll('.delete-meeting-form');
            if (deleteForms.length > 0) {
                const currentLang = document.documentElement.getAttribute('data-lang');
                
                deleteForms.forEach(form => {
                    form.onsubmit = function() {
                        if (currentLang === 'ar') {
                            return confirm('هل أنت متأكد من رغبتك في حذف هذا الاجتماع؟ لا يمكن التراجع عن هذا الإجراء.');
                        } else {
                            return confirm('Are you sure you want to delete this meeting? This action cannot be undone.');
                        }
                    };
                });
            }

            // منع ظهور تأكيد إعادة إرسال النموذج عند تحديث الصفحة
            if (window.history && window.history.replaceState) {
                // استبدال تاريخ المتصفح بنفس الصفحة لكن بطريقة GET بدلاً من POST
                window.history.replaceState(null, document.title, window.location.href);
                
                // إضافة مستمع للتحديث لتجنب تأكيدات إعادة الإرسال
                window.addEventListener('beforeunload', function() {
                    // إذا كان هناك نموذج مرسل، نقوم بإعادة توجيه لمنع رسالة التأكيد
                    if (sessionStorage.getItem('formSubmitted')) {
                        sessionStorage.removeItem('formSubmitted');
                        window.location.href = window.location.href;
                        return false;
                    }
                });
                
                // إضافة مستمع لكل النماذج في الصفحة
                document.querySelectorAll('form').forEach(form => {
                    form.addEventListener('submit', function() {
                        // عند إرسال أي نموذج، سجل ذلك في sessionStorage
                        sessionStorage.setItem('formSubmitted', 'true');
                    });
                });
            }

            // كود جافا سكريبت للتعامل مع أزرار تصدير التقويم
            const calendarExportModal = document.getElementById('calendarExportModal');
            if (calendarExportModal) {
                calendarExportModal.addEventListener('show.bs.modal', function (event) {
                    // الحصول على البيانات من زر التصدير
                    const button = event.relatedTarget;
                    const meetingId = button.getAttribute('data-meeting-id');
                    const meetingDate = button.getAttribute('data-meeting-date');
                    const duration = button.getAttribute('data-duration');
                    
                    // تحديث روابط التصدير
                    const googleLink = document.getElementById('googleCalendarLink');
                    const outlookLink = document.getElementById('outlookCalendarLink');
                    
                    if (googleLink) {
                        googleLink.href = `calendar_export.php?meeting_id=${meetingId}&date=${meetingDate}&duration=${duration}&format=google`;
                    }
                    
                    if (outlookLink) {
                        outlookLink.href = `calendar_export.php?meeting_id=${meetingId}&date=${meetingDate}&duration=${duration}&format=ics`;
                    }
                });
            }
            
            // ضبط الأبعاد للأجهزة المحمولة
            function adjustForMobile() {
                if (window.innerWidth <= 768) {
                    // تعديل عرض العناصر للشاشات الصغيرة
                    document.querySelectorAll('.row > .col-md-6').forEach(col => {
                        col.classList.add('col-12');
                    });
                    
                    // تكبير أزرار التفاعل على الشاشات الصغيرة
                    document.querySelectorAll('.btn').forEach(btn => {
                        if (btn.classList.contains('btn-sm')) {
                            btn.classList.remove('btn-sm');
                        }
                    });
                }
            }
            
            // تشغيل التعديلات عند تحميل الصفحة وتغيير حجم النافذة
            adjustForMobile();
            window.addEventListener('resize', adjustForMobile);
        });
    </script>
</body>
</html>
