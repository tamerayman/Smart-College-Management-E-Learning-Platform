<?php
require_once 'bbb_api.php';

// Record meeting in database
function recordMeetingInDatabase($meetingId, $courseId, $professorId, $meetingName) {
    global $conn;
    
    // First check if the meeting already exists
    $checkStmt = $conn->prepare("SELECT meeting_id FROM meetings WHERE meeting_id = ?");
    $checkStmt->bind_param("s", $meetingId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Meeting exists, update the last_updated field
        $updateStmt = $conn->prepare("UPDATE meetings SET last_updated = NOW() WHERE meeting_id = ?");
        $updateStmt->bind_param("s", $meetingId);
        $result = $updateStmt->execute();
        $updateStmt->close();
        return $result;
    } else {
        // Meeting doesn't exist, insert new record
        $stmt = $conn->prepare("INSERT INTO meetings (meeting_id, course_id, professor_id, meeting_name, created_at, last_updated) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("siss", $meetingId, $courseId, $professorId, $meetingName);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}

// Get all meetings for a student based on enrolled courses
function getStudentMeetings($studentId) {
    global $conn;
    $query = "SELECT m.meeting_id, m.meeting_name, c.course_id, c.name as course_name, 
                     d.name as department_name, p.name as professor_name, m.created_at, m.last_updated
              FROM student_courses sc 
              JOIN courses c ON sc.course_id = c.course_id 
              JOIN departments d ON c.department_id = d.department_id
              LEFT JOIN meetings m ON c.course_id = m.course_id
              LEFT JOIN professors p ON m.professor_id = p.professor_id
              WHERE sc.student_id = ?
              ORDER BY m.last_updated DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $courses = [];
    
    while ($row = $result->fetch_assoc()) {
        // Group by course_id to prevent duplicates
        if (!isset($courses[$row['course_id']])) {
            $courses[$row['course_id']] = [
                'course_id' => $row['course_id'],
                'course_name' => $row['course_name'],
                'department_name' => $row['department_name'],
                'meetings' => []
            ];
        }
        
        if (!empty($row['meeting_id'])) {
            $courses[$row['course_id']]['meetings'][] = [
                'meeting_id' => $row['meeting_id'],
                'meeting_name' => $row['meeting_name'],
                'professor_name' => $row['professor_name'],
                'created_at' => $row['created_at'],
                'last_updated' => $row['last_updated']
            ];
        }
    }
    
    return array_values($courses);
}

// Get all meetings created by a professor
function getProfessorMeetings($professorId) {
    global $conn;
    $query = "SELECT c.course_id, c.name as course_name, d.name as department_name,
                     m.meeting_id, m.meeting_name, m.created_at, m.last_updated
              FROM professor_courses pc 
              JOIN courses c ON pc.course_id = c.course_id 
              JOIN departments d ON c.department_id = d.department_id
              LEFT JOIN meetings m ON (c.course_id = m.course_id AND m.professor_id = ?)
              WHERE pc.professor_id = ?
              ORDER BY c.name ASC, m.last_updated DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $professorId, $professorId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $courses = [];
    
    while ($row = $result->fetch_assoc()) {
        // Group by course_id to prevent duplicates
        if (!isset($courses[$row['course_id']])) {
            $courses[$row['course_id']] = [
                'course_id' => $row['course_id'],
                'course_name' => $row['course_name'],
                'department_name' => $row['department_name'],
                'meetings' => []
            ];
        }
        
        if (!empty($row['meeting_id'])) {
            $courses[$row['course_id']]['meetings'][] = [
                'meeting_id' => $row['meeting_id'],
                'meeting_name' => $row['meeting_name'],
                'created_at' => $row['created_at'],
                'last_updated' => $row['last_updated']
            ];
        }
    }
    
    return array_values($courses);
}

// Schedule a future meeting
function scheduleMeeting($meetingName, $courseId, $professorId, $scheduledDate, $duration, $welcomeMessage = '') {
    global $conn;
    
    // Generate a unique meeting ID
    $meetingId = 'scheduled-' . $courseId . '-' . $professorId . '-' . time();
    
    // Convert scheduled date to MySQL datetime format
    $scheduledDateTime = date('Y-m-d H:i:s', strtotime($scheduledDate));
    
    // Insert into scheduled_meetings table
    $stmt = $conn->prepare("INSERT INTO scheduled_meetings 
        (meeting_id, course_id, professor_id, meeting_name, scheduled_time, duration, welcome_message, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->bind_param("siissss", $meetingId, $courseId, $professorId, $meetingName, $scheduledDateTime, $duration, $welcomeMessage);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        // Notify enrolled students
        notifyStudentsAboutMeeting($courseId, $meetingName, $scheduledDateTime, $duration);
        return ["success" => true, "message" => "Meeting scheduled successfully for " . date('F d, Y H:i', strtotime($scheduledDate))];
    } else {
        return ["success" => false, "message" => "Failed to schedule meeting"];
    }
}

// Create a new instant meeting
function createMeeting($meetingName, $courseId, $professorId, $welcomeMessage = '') {
    global $conn;
    
    // Generate a unique meeting ID
    $meetingId = generateUniqueMeetingId($courseId, $professorId);
    
    // Prepare the create meeting API call
    $callName = 'create';
    $welcomeMsg = "Welcome to $meetingName. This meeting is for educational purposes.";
    if (!empty($welcomeMessage)) {
        $welcomeMsg = $welcomeMessage;
    }
    $queryString = "name=" . urlencode($meetingName) .
                   "&meetingID=" . urlencode($meetingId) .
                   "&attendeePW=ap" .
                   "&moderatorPW=mp" .
                   "&welcome=" . urlencode($welcomeMsg) .
                   "&record=true" .
                   "&autoStartRecording=false" .
                   "&allowStartStopRecording=true" .
                   "&muteOnStart=true" .
                   "&allowModsToUnmuteUsers=true" .
                   "&logoutURL=" . urlencode("https://" . $_SERVER['HTTP_HOST'] . "/meetings/meetings.php");
    $checksum = getBBBChecksum($callName, $queryString);
    $url = BBB_SERVER_BASE_URL . $callName . '?' . $queryString . '&checksum=' . $checksum;
    
    // Call the BBB API to create the meeting
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Parse XML response
    $xml = simplexml_load_string($response);
    
    if (!$xml || $xml->returncode != 'SUCCESS') {
        return ["success" => false, "message" => "Failed to create meeting: " . (isset($xml->message) ? (string)$xml->message : "Unknown error")];
    }
    
    // Record the new meeting in the database
    recordMeetingInDatabase($meetingId, $courseId, $professorId, $meetingName);
    
    return ["success" => true, "message" => "Meeting created successfully"];
}

function getScheduledMeetings($courseId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM scheduled_meetings WHERE course_id = ? AND scheduled_time > NOW() ORDER BY scheduled_time ASC");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $scheduledMeetings = [];
    
    while ($row = $result->fetch_assoc()) {
        $scheduledMeetings[] = $row;
    }
    
    $stmt->close();
    return $scheduledMeetings;
}

// Notify students about upcoming meeting
function notifyStudentsAboutMeeting($courseId, $meetingName, $scheduledTime, $duration) {
    global $conn;
    
    // Get all students enrolled in the course
    $stmt = $conn->prepare("
        SELECT u.user_id, u.email, s.name 
        FROM student_courses sc
        JOIN users u ON sc.student_id = u.user_id
        JOIN students s ON u.user_id = s.student_id
        WHERE sc.course_id = ?
    ");
    
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // In a real implementation, you would send emails to students
    // For this example, we'll just log the notifications
    $logFile = fopen(__DIR__ . "/meeting_notifications.log", "a");
    
    while ($student = $result->fetch_assoc()) {
        // Log notification
        $logMessage = date('Y-m-d H:i:s') . " - Notification sent to " . $student['name'] . 
                     " (" . $student['email'] . ") about meeting: " . $meetingName . 
                     " scheduled for " . $scheduledTime . "\n";
        
        fwrite($logFile, $logMessage);
        
        // In a production environment, you would send actual emails here
    }
    
    fclose($logFile);
    $stmt->close();
}

// Get meeting statistics
function getMeetingStats($meetingId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT user_id) as participant_count,
               MIN(join_time) as first_join,
               MAX(join_time) as last_join
        FROM meeting_attendees
        WHERE meeting_id = ?
    ");
    
    $stmt->bind_param("s", $meetingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    return $stats;
}

// Join a meeting
function joinMeeting($meetingId, $userName, $userRole) {
    global $conn, $userId;
    
    // Check if meeting is running first
    $isRunning = isMeetingRunning($meetingId);
    
    // If meeting is not running and user is a professor, try to start it
    if (!$isRunning && $userRole == "professor") {
        // Get meeting details from the database
        $stmt = $conn->prepare("SELECT course_id, meeting_name FROM meetings WHERE meeting_id = ?");
        $stmt->bind_param("s", $meetingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $meetingData = $result->fetch_assoc();
            $courseId = $meetingData['course_id'];
            $meetingName = $meetingData['meeting_name'];
            
            // Start the meeting
            $callName = 'create';
            $welcomeMsg = "Welcome to $meetingName. This meeting is for educational purposes.";
            $queryString = "name=" . urlencode($meetingName) . 
                           "&meetingID=" . urlencode($meetingId) . 
                           "&attendeePW=ap" . 
                           "&moderatorPW=mp" . 
                           "&welcome=" . urlencode($welcomeMsg) . 
                           "&record=true" .
                           "&autoStartRecording=false" .
                           "&allowStartStopRecording=true" .
                           "&muteOnStart=true" .
                           "&allowModsToUnmuteUsers=true" .
                           "&logoutURL=" . urlencode("https://" . $_SERVER['HTTP_HOST'] . "/meetings/meetings.php");
            
            $checksum = getBBBChecksum($callName, $queryString);
            $url = BBB_SERVER_BASE_URL . $callName . '?' . $queryString . '&checksum=' . $checksum;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            // Parse XML response
            $xml = simplexml_load_string($response);
            
            if (!$xml || $xml->returncode != 'SUCCESS') {
                return ["success" => false, "message" => "Failed to start meeting: " . (isset($xml->message) ? (string)$xml->message : "Unknown error")];
            }
            
            // Meeting started successfully, update running status
            $isRunning = true;
            
            // Update last_updated in database
            $updateStmt = $conn->prepare("UPDATE meetings SET last_updated = NOW() WHERE meeting_id = ?");
            $updateStmt->bind_param("s", $meetingId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            return ["success" => false, "message" => "Meeting information not found"];
        }
    }
    
    // If meeting is not running but user is student, allow to join waiting room
    if (!$isRunning && $userRole == "student") {
        // Create meeting as if student was first to join (waiting room)
        $stmt = $conn->prepare("SELECT course_id, meeting_name, professor_id FROM meetings WHERE meeting_id = ?");
        $stmt->bind_param("s", $meetingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $meetingData = $result->fetch_assoc();
            
            // Create the meeting in waiting state
            $callName = 'create';
            $welcomeMsg = "Welcome to the waiting room. Meeting will begin when the professor joins.";
            $queryString = "name=" . urlencode($meetingData['meeting_name']) . 
                           "&meetingID=" . urlencode($meetingId) . 
                           "&attendeePW=ap" . 
                           "&moderatorPW=mp" . 
                           "&welcome=" . urlencode($welcomeMsg) . 
                           "&record=false" .
                           "&muteOnStart=true" .
                           "&allowModsToUnmuteUsers=true" .
                           "&logoutURL=" . urlencode("https://" . $_SERVER['HTTP_HOST'] . "/meetings/meetings.php");
            
            $checksum = getBBBChecksum($callName, $queryString);
            $url = BBB_SERVER_BASE_URL . $callName . '?' . $queryString . '&checksum=' . $checksum;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            // Parse XML response
            $xml = simplexml_load_string($response);
            
            if (!$xml || $xml->returncode != 'SUCCESS') {
                return ["success" => false, "message" => "Failed to create waiting room: " . (isset($xml->message) ? (string)$xml->message : "Unknown error")];
            }
            
            // Update waiting room status
            $isRunning = true;
            $updateStmt = $conn->prepare("UPDATE meetings SET last_updated = NOW() WHERE meeting_id = ?");
            $updateStmt->bind_param("s", $meetingId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    // Meeting is running or waiting room is active, proceed with join
    if ($isRunning) {
        $callName = 'join';
        $queryString = "meetingID=" . urlencode($meetingId) . 
                       "&fullName=" . urlencode($userName) . 
                       "&password=" . ($userRole == "professor" ? "mp" : "ap");
        
        $checksum = getBBBChecksum($callName, $queryString);
        $url = BBB_SERVER_BASE_URL . $callName . '?' . $queryString . '&checksum=' . $checksum;
        
        // Record the join action
        $stmt = $conn->prepare("INSERT INTO meeting_attendees (meeting_id, user_id, join_time) VALUES (?, ?, NOW())");
        $stmt->bind_param("si", $meetingId, $userId);
        $stmt->execute();
        $stmt->close();
        
        // Store the URL in session for later use
        $_SESSION['meeting_url'] = $url;
        
        // Return the URL instead of redirecting
        return ["success" => true, "url" => $url];
    } else {
        return ["success" => false, "message" => "Unable to create or join the meeting. Please try again later."];
    }
}

// Check scheduled meetings and start them if necessary
function checkScheduledMeetings() {
    $startedMeetings = [];
    
    try {
        // Create a new database connection
        $conn = connectDB();
        
        // Get current time in MySQL format
        $currentTime = date('Y-m-d H:i:s');
        
        // Find ALL scheduled meetings that should have started (not just in last 5 minutes)
        $query = "
            SELECT sm.id, sm.meeting_id, sm.meeting_name, sm.welcome_message, sm.course_id, sm.professor_id 
            FROM scheduled_meetings sm
            WHERE sm.scheduled_time <= ?
            AND sm.is_started = 0
        ";
        
        // Log the query for debugging
        error_log("Checking for overdue scheduled meetings with current time: " . $currentTime);
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $currentTime);
        $stmt->execute();
        $result = $stmt->get_result();
        
        error_log("Found " . $result->num_rows . " overdue meetings to start");
        
        if ($result && $result->num_rows > 0) {
            while ($scheduledMeeting = $result->fetch_assoc()) {
                error_log("Processing scheduled meeting: " . $scheduledMeeting['meeting_name'] . " (ID: " . $scheduledMeeting['id'] . ")");
                
                // Create the actual BBB meeting
                $meetingResult = createMeeting(
                    $scheduledMeeting['meeting_name'],
                    $scheduledMeeting['course_id'],
                    $scheduledMeeting['professor_id'],
                    $scheduledMeeting['welcome_message']
                );
                
                if ($meetingResult["success"]) {
                    // Mark the scheduled meeting as started
                    $updateStmt = $conn->prepare("UPDATE scheduled_meetings SET is_started = 1 WHERE id = ?");
                    $updateStmt->bind_param("i", $scheduledMeeting['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    $startedMeetings[] = [
                        'name' => $scheduledMeeting['meeting_name'],
                        'course_id' => $scheduledMeeting['course_id']
                    ];
                    
                    error_log("Successfully started meeting: " . $scheduledMeeting['meeting_name']);
                } else {
                    error_log("Failed to start meeting: " . ($meetingResult["message"] ?? "Unknown error"));
                }
            }
        }
        
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Error in checkScheduledMeetings: " . $e->getMessage());
    }
    
    return $startedMeetings;
}

/**
 * Generate Google Calendar URL for a meeting
 * @param array $meeting Meeting details
 * @param string $dateTime Date and time of the meeting
 * @param int $duration Duration in minutes
 * @return string Google Calendar URL
 */
function generateGoogleCalendarUrl($meeting, $dateTime, $duration = 60) {
    $startTime = new DateTime($dateTime);
    $endTime = clone $startTime;
    $endTime->add(new DateInterval('PT' . $duration . 'M'));
    
    $params = [
        'action' => 'TEMPLATE',
        'text' => $meeting['meeting_name'] ?? 'Online Meeting',
        'dates' => $startTime->format('Ymd\\THis') . '/' . $endTime->format('Ymd\\THis'),
        'details' => 'Join the online meeting: ' . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/meetings/meetings.php' : ''),
        'location' => 'Online',
    ];
    
    return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
}

/**
 * Generate iCalendar (ICS) data for a meeting
 * @param array $meeting Meeting details
 * @param string $dateTime Date and time of the meeting
 * @param int $duration Duration in minutes
 * @return string ICS file content
 */
function generateIcsData($meeting, $dateTime, $duration = 60) {
    $startTime = new DateTime($dateTime);
    $endTime = clone $startTime;
    $endTime->add(new DateInterval('PT' . $duration . 'M'));
    
    $uid = md5($meeting['meeting_id'] . $dateTime);
    $created = new DateTime();
    
    $icsContent = [
        "BEGIN:VCALENDAR",
        "VERSION:2.0",
        "PRODID:-//UniHive//Online Meetings//EN",
        "CALSCALE:GREGORIAN",
        "METHOD:PUBLISH",
        "BEGIN:VEVENT",
        "UID:" . $uid,
        "SUMMARY:" . ($meeting['meeting_name'] ?? 'Online Meeting'),
        "DTSTAMP:" . $created->format('Ymd\THis\Z'),
        "DTSTART:" . $startTime->format('Ymd\THis\Z'),
        "DTEND:" . $endTime->format('Ymd\THis\Z'),
        "DESCRIPTION:Join the online meeting through UniHive platform.",
        "LOCATION:Online",
        "STATUS:CONFIRMED",
        "SEQUENCE:0",
        "END:VEVENT",
        "END:VCALENDAR"
    ];
    
    return implode("\r\n", $icsContent);
}

/**
 * Get upcoming meetings for a user (for the calendar view)
 * @param int $userId User ID
 * @param string $role User role (student or professor)
 * @return array List of upcoming meetings
 */
function getUpcomingMeetingsForCalendar($userId, $role) {
    global $conn;
    
    if ($role == 'student') {
        $query = "
            SELECT sm.*, c.name as course_name, p.name as professor_name
            FROM scheduled_meetings sm
            JOIN courses c ON sm.course_id = c.course_id
            JOIN professors p ON sm.professor_id = p.professor_id
            JOIN student_courses sc ON sm.course_id = sc.course_id
            WHERE sc.student_id = ? AND sm.scheduled_time > NOW()
            ORDER BY sm.scheduled_time ASC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
    } else {
        $query = "
            SELECT sm.*, c.name as course_name
            FROM scheduled_meetings sm
            JOIN courses c ON sm.course_id = c.course_id
            WHERE sm.professor_id = ? AND sm.scheduled_time > NOW()
            ORDER BY sm.scheduled_time ASC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $upcomingMeetings = [];
    
    while ($row = $result->fetch_assoc()) {
        $upcomingMeetings[] = $row;
    }
    
    $stmt->close();
    return $upcomingMeetings;
}
?>
