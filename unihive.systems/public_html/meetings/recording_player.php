<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

// Check login status
if (!isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

// Check if recording information is available
if (!isset($_SESSION['play_recording'])) {
    header("Location: meetings.php");
    exit;
}

// Enable debug mode (can be removed in production)
$debugMode = isset($_GET['debug']);

$recording = $_SESSION['play_recording'];
$recordingId = $recording['id'];
$recordingName = $recording['name'];
$recordingUrl = $recording['url'];
$recordingDate = $recording['date'];

// Get user role for permission checks
$isProfessor = ($_SESSION['role'] === 'professor');
$userId = $_SESSION['user_id'];

// Function to format video timestamp
function formatTimestamp($seconds) {
    $seconds = floatval($seconds);
    $minutes = floor($seconds / 60);
    $secs = floor($seconds % 60);
    return sprintf("%02d:%02d", $minutes, $secs);
}

// Fetch professor notes for this recording
$professorNotes = [];
$fetchError = null;

try {
    $conn = connectDB();
    
    // Check if recording_notes table exists
    $tableExists = false;
    $tableResult = $conn->query("SHOW TABLES LIKE 'recording_notes'");
    
    if ($tableResult && $tableResult->num_rows > 0) {
        $tableExists = true;
    } else {
        // Create the table if it doesn't exist
        $createTableSql = "
        CREATE TABLE IF NOT EXISTS recording_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recording_id VARCHAR(255) NOT NULL,
            professor_id INT NOT NULL,
            note_content TEXT NOT NULL,
            note_category VARCHAR(20) DEFAULT 'general',
            note_color VARCHAR(20) DEFAULT '#f8f9fa',
            timestamp FLOAT DEFAULT 0,
            is_public TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (recording_id),
            INDEX (professor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        if ($conn->query($createTableSql)) {
            $tableExists = true;
        }
    }
    
    if ($tableExists) {
        $query = "SELECT * FROM recording_notes WHERE recording_id = ? AND is_public = 1 ORDER BY timestamp ASC";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("s", $recordingId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    // Add professor name
                    try {
                        $profQuery = "SELECT name FROM professors WHERE professor_id = ?";
                        $profStmt = $conn->prepare($profQuery);
                        
                        if ($profStmt) {
                            $profStmt->bind_param("i", $row['professor_id']);
                            $profStmt->execute();
                            $profResult = $profStmt->get_result();
                            
                            if ($profResult && $profResult->num_rows > 0) {
                                $profRow = $profResult->fetch_assoc();
                                $row['professor_name'] = $profRow['name'];
                            } else {
                                $row['professor_name'] = "Professor";
                            }
                            
                            $profStmt->close();
                        } else {
                            $row['professor_name'] = "Professor";
                        }
                    } catch (Exception $ex) {
                        $row['professor_name'] = "Professor";
                    }
                    
                    // Ensure all required fields are present
                    if (!isset($row['note_category']) || empty($row['note_category'])) {
                        $row['note_category'] = 'general';
                    }
                    
                    $professorNotes[] = $row;
                }
            }
            
            $stmt->close();
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    $fetchError = $e->getMessage();
    error_log("Error fetching professor notes: " . $fetchError);
}

// If debug mode or no notes found, add a test note
if (($debugMode || count($professorNotes) === 0) && $fetchError === null) {
    $professorNotes[] = array(
        'id' => 999,
        'note_content' => 'This is a test note to ensure notes are displaying correctly. If you see this, the basic display system is working, but there might be an issue with the actual notes in the database.',
        'professor_name' => 'Test Professor',
        'timestamp' => 30,
        'note_category' => 'important',
        'professor_id' => 0
    );
}

// If in debug mode, display the debug information
if ($debugMode) {
    echo '<div style="position: fixed; top: 10px; left: 10px; z-index: 9999; background: #fff; padding: 10px; border: 1px solid #ddd; max-width: 500px; max-height: 300px; overflow: auto;">';
    echo '<h4>Debug Information</h4>';
    echo '<p>Fetch Error: ' . ($fetchError ?? 'None') . '</p>';
    echo '<p>Professor Role: ' . ($isProfessor ? 'Yes' : 'No') . '</p>';
    echo '<p>Notes Count: ' . count($professorNotes) . '</p>';
    echo '<pre>' . print_r($professorNotes, true) . '</pre>';
    echo '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recordingName); ?> - UniHive Player</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --background-color: #f8f9fa;
            --text-color: #212529;
            --light-text: #6c757d;
            --card-bg: #ffffff;
            --border-radius: 10px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition-speed: 0.3s;
            
            /* Category Colors */
            --important-color: #dc3545;
            --explanation-color: #0d6efd;
            --question-color: #6f42c1;
            --reminder-color: #fd7e14;
            --general-color: #6c757d;
        }
        
        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            transition: background-color var(--transition-speed);
            padding-bottom: 30px;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            padding: 12px 20px;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color var(--transition-speed);
        }
        
        .breadcrumb a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .info-panel {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform var(--transition-speed);
        }
        
        .info-panel:hover {
            transform: translateY(-3px);
        }
        
        .meeting-title {
            margin-top: 0;
            margin-bottom: 10px;
            color: var(--text-color);
            font-weight: 600;
        }
        
        .meeting-date {
            color: var(--light-text);
            margin-bottom: 0;
            font-size: 0.95rem;
        }
        
        /* Video Player */
        .player-container {
            position: relative;
            background-color: #000;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
            transition: transform var(--transition-speed);
        }
        
        .player-container:hover {
            transform: translateY(-3px);
        }
        
        .video-container {
            width: 100%;
            aspect-ratio: 16/9;
        }
        
        #playerIframe {
            width: 100%;
            height: 100%;
            background-color: #000;
        }
        
        /* Professor Notes Card */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: transform var(--transition-speed);
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-3px);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }
        
        .card-body {
            padding: 20px;
            background-color: var(--card-bg);
        }
        
        /* Professor Notes List */
        .professor-notes-list {
            margin-top: 15px;
            max-height: 500px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--light-text) transparent;
            padding-right: 8px;
        }
        
        .professor-notes-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .professor-notes-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .professor-notes-list::-webkit-scrollbar-thumb {
            background-color: var(--light-text);
            border-radius: 10px;
        }
        
        .professor-note {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            border-left: 5px solid var(--general-color);
            background-color: #f8f9fa;
            transition: all var(--transition-speed);
            animation: fadeIn 0.5s ease;
        }
        
        .professor-note:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .professor-note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .professor-note-header span:first-child {
            font-weight: bold;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .professor-note-header span:first-child::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        
        .note-timestamp {
            cursor: pointer;
            color: var(--primary-color);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 15px;
            background-color: rgba(67, 97, 238, 0.1);
            transition: all var(--transition-speed);
        }
        
        .note-timestamp:hover {
            color: var(--secondary-color);
            background-color: rgba(67, 97, 238, 0.2);
            transform: scale(1.05);
        }
        
        .professor-note-content {
            padding: 5px 0;
            color: var(--text-color);
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        /* Note category styles */
        .note-category-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .note-category-badge::before {
            content: "";
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        
        .professor-note.important,
        .badge-important {
            border-left-color: var(--important-color);
        }
        
        .professor-note.explanation,
        .badge-explanation {
            border-left-color: var(--explanation-color);
        }
        
        .professor-note.question,
        .badge-question {
            border-left-color: var(--question-color);
        }
        
        .professor-note.reminder,
        .badge-reminder {
            border-left-color: var(--reminder-color);
        }
        
        .professor-note.general,
        .badge-general {
            border-left-color: var(--general-color);
        }
        
        .badge-important {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--important-color);
        }
        
        .badge-important::before {
            background-color: var(--important-color);
        }
        
        .badge-explanation {
            background-color: rgba(13, 110, 253, 0.15);
            color: var(--explanation-color);
        }
        
        .badge-explanation::before {
            background-color: var(--explanation-color);
        }
        
        .badge-question {
            background-color: rgba(111, 66, 193, 0.15);
            color: var(--question-color);
        }
        
        .badge-question::before {
            background-color: var(--question-color);
        }
        
        .badge-reminder {
            background-color: rgba(253, 126, 20, 0.15);
            color: var(--reminder-color);
        }
        
        .badge-reminder::before {
            background-color: var(--reminder-color);
        }
        
        .badge-general {
            background-color: rgba(108, 117, 125, 0.15);
            color: var(--general-color);
        }
        
        .badge-general::before {
            background-color: var(--general-color);
        }
        
        .badge-filter {
            font-size: 0.8rem;
            padding: 6px 12px;
            margin-right: 6px;
            margin-bottom: 6px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all var(--transition-speed);
            background-color: #f0f0f0;
        }
        
        .badge-filter:hover {
            transform: translateY(-2px);
        }
        
        .badge-filter.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(67, 97, 238, 0.3);
        }
        
        /* Form controls */
        .professor-controls {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        
        .professor-controls .btn {
            border-radius: 20px;
            padding: 8px 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all var(--transition-speed);
        }
        
        .professor-controls .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .professor-controls .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border: none;
        }
        
        #currentTimeDisplay {
            background-color: #f8f9fa;
            padding: 6px 12px;
            border-radius: 20px;
            font-family: monospace;
            box-shadow: inset 0 0 3px rgba(0, 0, 0, 0.1);
        }
        
        /* Form styling */
        #professorNoteForm {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
            animation: slideDown 0.4s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-control, .form-select {
            border-radius: 20px;
            padding: 10px 18px;
            border: 1px solid #e0e0e0;
            transition: all var(--transition-speed);
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            border-color: var(--primary-color);
        }
        
        .input-group {
            border-radius: 20px;
            overflow: hidden;
        }
        
        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .professor-note-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .controls-row {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .volume-container {
                width: 100%;
                margin-top: 10px;
            }
            
            .time-display {
                margin: 5px 0;
            }
            
            .professor-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .professor-controls .btn {
                width: 100%;
            }
        }
        
        /* Animations */
        .button-pulse {
            animation: pulse 0.5s ease;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .progress-ripple {
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            animation: ripple 1s ease;
        }
        
        @keyframes ripple {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(10); opacity: 0; }
        }
        
        .time-flash {
            animation: flash 0.5s ease;
        }
        
        @keyframes flash {
            0% { background-color: #f8f9fa; }
            50% { background-color: #fff; }
            100% { background-color: #f8f9fa; }
        }
        
        .note-current {
            animation: highlight 1s ease;
        }
        
        @keyframes highlight {
            0% { transform: scale(1); box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
            50% { transform: scale(1.03); box-shadow: 0 8px 16px rgba(0,0,0,0.15); }
            100% { transform: scale(1); box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="meetings.php">Meetings</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($recordingName); ?></li>
            </ol>
        </nav>
        
        <?php if (isset($fetchError)): ?>
            <div class="alert alert-danger">
                Error fetching recording data: <?php echo htmlspecialchars($fetchError); ?>
            </div>
        <?php endif; ?>
        
        <div class="info-panel">
            <h3 class="meeting-title"><?php echo htmlspecialchars($recordingName); ?></h3>
            <p class="meeting-date"><?php echo date('F d, Y - h:i A', strtotime($recordingDate)); ?></p>
        </div>
        
        <div class="player-container">
            <div class="video-container">
                <iframe id="playerIframe" class="player-iframe" src="<?php echo htmlspecialchars($recordingUrl); ?>" allowfullscreen></iframe>
            </div>
        </div>
        
        <!-- Professor Notes Section -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class='bx bx-notepad'></i> Professor Notes
                    <?php if (count($professorNotes) > 0): ?>
                    <span class="badge bg-primary"><?php echo count($professorNotes); ?></span>
                    <?php endif; ?>
                </h5>
                <div>
                    <div class="input-group input-group-sm">
                        <input type="text" id="professorNoteSearchInput" class="form-control" placeholder="Search notes">
                        <div class="input-group-append">
                            <button id="professorNoteSearchBtn" class="btn btn-outline-secondary"><i class='bx bx-search'></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Filter by Category</label>
                    <div class="d-flex flex-wrap">
                        <button class="btn btn-sm me-1 mb-1 badge-filter active" data-category="all">All</button>
                        <button class="btn btn-sm me-1 mb-1 badge-filter badge-important" data-category="important">Important</button>
                        <button class="btn btn-sm me-1 mb-1 badge-filter badge-explanation" data-category="explanation">Explanation</button>
                        <button class="btn btn-sm me-1 mb-1 badge-filter badge-question" data-category="question">Question</button>
                        <button class="btn btn-sm me-1 mb-1 badge-filter badge-reminder" data-category="reminder">Reminder</button>
                        <button class="btn btn-sm me-1 mb-1 badge-filter badge-general" data-category="general">General</button>
                    </div>
                </div>
                
                <?php if ($isProfessor): ?>
                <!-- Professor-only controls -->
                <div class="professor-controls mb-3">
                    <button id="addProfessorNoteBtn" class="btn btn-primary"><i class='bx bx-plus'></i> Add Note</button>
                    <button id="getCurrentTimeBtn" class="btn btn-secondary"><i class='bx bx-time'></i> Current Time</button>
                    <span id="currentTimeDisplay" class="ms-2"></span>
                </div>
                
                <!-- Note Form - only visible to professors -->
                <div id="professorNoteForm" class="mb-3" style="display: none;">
                    <form id="saveProfessorNoteForm">
                        <input type="hidden" id="noteId" name="noteId" value="">
                        <input type="hidden" id="recordingId" name="recordingId" value="<?php echo htmlspecialchars($recordingId); ?>">
                        <input type="hidden" id="timestamp" name="timestamp" value="0">
                        
                        <div class="mb-3">
                            <label for="noteCategory" class="form-label">Category</label>
                            <select id="noteCategory" name="noteCategory" class="form-select">
                                <option value="important">Important</option>
                                <option value="explanation">Explanation</option>
                                <option value="question">Question</option>
                                <option value="reminder">Reminder</option>
                                <option value="general" selected>General</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="noteContent" class="form-label">Note Content</label>
                            <textarea id="noteContent" name="noteContent" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isPublic" name="isPublic" checked>
                            <label class="form-check-label" for="isPublic">Make visible to students</label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="submit" class="btn btn-primary">Save Note</button>
                                <button type="button" id="cancelNoteBtn" class="btn btn-secondary">Cancel</button>
                            </div>
                            <div id="formFeedback"></div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Professor Notes List -->
                <div class="professor-notes-list">
                    <?php if (count($professorNotes) > 0): ?>
                        <?php foreach ($professorNotes as $note): ?>
                            <div class="professor-note <?php echo htmlspecialchars($note['note_category']); ?>">
                                <div class="professor-note-header">
                                    <span><?php echo htmlspecialchars($note['professor_name']); ?></span>
                                    <span class="note-timestamp" data-time="<?php echo htmlspecialchars($note['timestamp']); ?>">
                                        <i class='bx bx-play-circle'></i> <?php echo formatTimestamp($note['timestamp']); ?>
                                    </span>
                                    <span class="note-category-badge badge-<?php echo htmlspecialchars($note['note_category']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($note['note_category'])); ?>
                                    </span>
                                </div>
                                <div class="professor-note-content">
                                    <?php echo nl2br(htmlspecialchars($note['note_content'])); ?>
                                </div>
                                <?php if ($isProfessor && $userId == $note['professor_id']): ?>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-primary edit-note" data-note-id="<?php echo (int)$note['id']; ?>">
                                            <i class='bx bx-edit'></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-note" data-note-id="<?php echo (int)$note['id']; ?>">
                                            <i class='bx bx-trash'></i> Delete
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No professor notes available for this recording yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add animations to all professor notes
            const professorNotes = document.querySelectorAll('.professor-note');
            professorNotes.forEach((note, index) => {
                note.style.animationDelay = (index * 0.1) + 's';
            });
            
            // Professor notes functionality
            // Check the elements we're going to use
            const addProfessorNoteBtn = document.getElementById('addProfessorNoteBtn');
            const professorNoteForm = document.getElementById('professorNoteForm');
            const cancelNoteBtn = document.getElementById('cancelNoteBtn');
            const getCurrentTimeBtn = document.getElementById('getCurrentTimeBtn');
            const currentTimeDisplay = document.getElementById('currentTimeDisplay');
            
            // Initialize form with animation if elements exist
            if (addProfessorNoteBtn) {
                addProfessorNoteBtn.addEventListener('click', function() {
                    // Button click animation
                    this.classList.add('button-pulse');
                    setTimeout(() => this.classList.remove('button-pulse'), 300);
                    
                    // Reset form
                    document.getElementById('noteId').value = '';
                    document.getElementById('noteContent').value = '';
                    document.getElementById('timestamp').value = 0; // Default to 0 instead of getting from video
                    document.getElementById('isPublic').checked = true;
                    document.getElementById('noteCategory').value = 'general';
                    
                    // Show form with animation
                    if (professorNoteForm.style.display !== 'block') {
                        professorNoteForm.style.display = 'block';
                        professorNoteForm.style.opacity = '0';
                        setTimeout(() => {
                            professorNoteForm.style.opacity = '1';
                        }, 50);
                    }
                });
            }
            
            if (cancelNoteBtn) {
                cancelNoteBtn.addEventListener('click', function() {
                    // Button click animation
                    this.classList.add('button-pulse');
                    setTimeout(() => this.classList.remove('button-pulse'), 300);
                    
                    // Hide form with animation
                    professorNoteForm.style.opacity = '0';
                    setTimeout(() => {
                        professorNoteForm.style.display = 'none';
                    }, 300);
                });
            }
            
            if (getCurrentTimeBtn) {
                getCurrentTimeBtn.addEventListener('click', function() {
                    // Button click animation
                    this.classList.add('button-pulse');
                    setTimeout(() => this.classList.remove('button-pulse'), 300);
                    
                    // Prompt user to enter time manually
                    const timeInput = prompt("Please enter timestamp (in seconds):", "0");
                    if (timeInput !== null) {
                        const seconds = parseFloat(timeInput);
                        if (!isNaN(seconds)) {
                            document.getElementById('timestamp').value = seconds;
                            
                            // Show time in display with animation
                            if (currentTimeDisplay) {
                                currentTimeDisplay.textContent = formatTimestamp(seconds);
                                currentTimeDisplay.classList.add('time-flash');
                                setTimeout(() => currentTimeDisplay.classList.remove('time-flash'), 500);
                            }
                        }
                    }
                });
            }
            
            // Search notes functionality
            const searchInput = document.getElementById('professorNoteSearchInput');
            const searchButton = document.getElementById('professorNoteSearchBtn');
            
            if (searchInput) {
                const performSearch = function() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const notes = document.querySelectorAll('.professor-note');
                    
                    if (searchTerm.trim() === '') {
                        // If search is empty, show all notes but respect current category filter
                        const activeFilter = document.querySelector('.badge-filter.active');
                        const filterCategory = activeFilter ? activeFilter.getAttribute('data-category') : 'all';
                        
                        notes.forEach(note => {
                            if (filterCategory === 'all' || note.classList.contains(filterCategory)) {
                                note.style.display = 'block';
                            } else {
                                note.style.display = 'none';
                            }
                        });
                    } else {
                        // Filter by search term and current category
                        const activeFilter = document.querySelector('.badge-filter.active');
                        const filterCategory = activeFilter ? activeFilter.getAttribute('data-category') : 'all';
                        
                        notes.forEach(note => {
                            const content = note.querySelector('.professor-note-content').textContent.toLowerCase();
                            const professorName = note.querySelector('.professor-note-header span').textContent.toLowerCase();
                            const matchesSearch = content.includes(searchTerm) || professorName.includes(searchTerm);
                            const matchesCategory = filterCategory === 'all' || note.classList.contains(filterCategory);
                            
                            if (matchesSearch && matchesCategory) {
                                note.style.display = 'block';
                            } else {
                                note.style.display = 'none';
                            }
                        });
                    }
                };
                
                searchInput.addEventListener('input', performSearch);
                
                if (searchButton) {
                    searchButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        performSearch();
                    });
                }
            }
            
            // Filter notes by category
            const filterButtons = document.querySelectorAll('.badge-filter');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const categoryFilter = this.getAttribute('data-category');
                    const notes = document.querySelectorAll('.professor-note');
                    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                    
                    notes.forEach(note => {
                        // Check if note matches the search term
                        let matchesSearch = true;
                        if (searchTerm.trim() !== '') {
                            const content = note.querySelector('.professor-note-content').textContent.toLowerCase();
                            const professorName = note.querySelector('.professor-note-header span').textContent.toLowerCase();
                            matchesSearch = content.includes(searchTerm) || professorName.includes(searchTerm);
                        }
                        
                        // Check if note matches the category filter
                        const matchesCategory = categoryFilter === 'all' || note.classList.contains(categoryFilter);
                        
                        // Show/hide note based on both filters
                        if (matchesSearch && matchesCategory) {
                            note.style.display = 'block';
                        } else {
                            note.style.display = 'none';
                        }
                    });
                });
            });
            
            // Save professor note
            const saveProfessorNoteForm = document.getElementById('saveProfessorNoteForm');
            if (saveProfessorNoteForm) {
                saveProfessorNoteForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    const formData = {
                        noteId: document.getElementById('noteId').value,
                        recordingId: document.getElementById('recordingId').value,
                        noteContent: document.getElementById('noteContent').value.trim(),
                        timestamp: document.getElementById('timestamp').value,
                        isPublic: document.getElementById('isPublic').checked ? 1 : 0,
                        noteCategory: document.getElementById('noteCategory').value
                    };
                    
                    if (!formData.noteContent) {
                        alert('Please enter note content');
                        return;
                    }
                    
                    const formFeedback = document.getElementById('formFeedback');
                    formFeedback.innerHTML = '<span class="text-info">Saving...</span>';
                    
                    // If we have a note ID, we're updating an existing note
                    if (formData.noteId) {
                        fetch('update_professor_note.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(formData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                formFeedback.innerHTML = '<span class="text-success">Note updated!</span>';
                                setTimeout(() => {
                                    professorNoteForm.style.display = 'none';
                                    location.reload(); // Reload to show updated note
                                }, 1000);
                            } else {
                                formFeedback.innerHTML = '<span class="text-danger">Error: ' + (data.error || 'Unknown error') + '</span>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            formFeedback.innerHTML = '<span class="text-danger">Error saving note</span>';
                        });
                    } else {
                        // We're creating a new note
                        fetch('save_professor_note.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(formData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                formFeedback.innerHTML = '<span class="text-success">Note saved!</span>';
                                setTimeout(() => {
                                    professorNoteForm.style.display = 'none';
                                    location.reload(); // Reload to show new note
                                }, 1000);
                            } else {
                                formFeedback.innerHTML = '<span class="text-danger">Error: ' + (data.error || 'Unknown error') + '</span>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            formFeedback.innerHTML = '<span class="text-danger">Error saving note</span>';
                        });
                    }
                });
            }
            
            // Edit note
            document.querySelectorAll('.edit-note').forEach(function(button) {
                button.addEventListener('click', function() {
                    const noteId = this.getAttribute('data-note-id');
                    
                    fetch('get_professor_note.php?noteId=' + noteId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Populate form with note data
                                document.getElementById('noteId').value = data.note.id;
                                document.getElementById('noteContent').value = data.note.noteContent;
                                document.getElementById('timestamp').value = data.note.timestamp;
                                document.getElementById('isPublic').checked = data.note.isPublic == 1;
                                document.getElementById('noteCategory').value = data.note.noteCategory;
                                
                                // Show form
                                professorNoteForm.style.display = 'block';
                                
                                // Update current time display
                                if (currentTimeDisplay) {
                                    currentTimeDisplay.textContent = formatTimestamp(data.note.timestamp);
                                }
                            } else {
                                alert('Error loading note: ' + (data.error || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error loading note details');
                        });
                });
            });
            
            // Delete note
            document.querySelectorAll('.delete-note').forEach(function(button) {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this note?')) {
                        const noteId = this.getAttribute('data-note-id');
                        
                        fetch('delete_professor_note.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ noteId: noteId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Note deleted successfully');
                                location.reload(); // Reload to update the notes list
                            } else {
                                alert('Error deleting note: ' + (data.error || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error deleting note');
                        });
                    }
                });
            });
        });
        
        // Function to format video timestamp
        function formatTimestamp(seconds) {
            seconds = parseFloat(seconds);
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
    </script>
</body>
</html>
