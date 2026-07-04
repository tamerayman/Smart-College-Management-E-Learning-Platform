<?php
// BigBlueButton API settings
define('BBB_SECRET', 'jf9COwaEslbPkWxWNirckeA1QJdW2MyBdipeJIACN4E'); // replace with your actual secret key
define('BBB_SERVER_BASE_URL', 'https://meet.unihive.systems/bigbluebutton/api/'); // replace with your BBB server URL

// Generate unique meeting ID based on course, professor, and timestamp
function generateUniqueMeetingId($courseId, $professorId) {
    return 'course-' . $courseId . '-' . $professorId . '-' . time();
}

// Generate BigBlueButton API checksum
function getBBBChecksum($callName, $queryString) {
    $checksum = sha1($callName . $queryString . BBB_SECRET);
    return $checksum;
}

// Connect to BigBlueButton API and get meetings
function getBigBlueButtonMeetings() {
    $callName = 'getMeetings';
    $queryString = '';
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
    return $xml;
}

// Check if a specific meeting is running
function isMeetingRunning($meetingId) {
    $callName = 'isMeetingRunning';
    $queryString = 'meetingID=' . urlencode($meetingId);
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
    return (isset($xml->running) && (string)$xml->running === 'true');
}

// Check meeting recordings
function getMeetingRecordings($meetingId) {
    $callName = 'getRecordings';
    $queryString = 'meetingID=' . urlencode($meetingId);
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
    $recordings = [];
    
    if (isset($xml->recordings) && isset($xml->recordings->recording)) {
        foreach ($xml->recordings->recording as $recording) {
            $recordings[] = [
                'recordId' => (string)$recording->recordID,
                'startTime' => (string)$recording->startTime,
                'duration' => (int)$recording->playback->format->length,
                'playUrl' => (string)$recording->playback->format->url,
                'size' => isset($recording->size) ? (int)$recording->size : 0
            ];
        }
    }
    
    return $recordings;
}
?>
