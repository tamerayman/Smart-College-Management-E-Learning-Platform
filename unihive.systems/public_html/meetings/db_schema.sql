-- Meetings table schema
CREATE TABLE IF NOT EXISTS meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id VARCHAR(255) NOT NULL UNIQUE,
    course_id INT NOT NULL,
    professor_id INT NOT NULL,
    meeting_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    last_updated DATETIME NOT NULL,
    recording_url VARCHAR(255) NULL,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (professor_id) REFERENCES professors(professor_id),
    INDEX (meeting_id),
    INDEX (course_id),
    INDEX (professor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Meeting attendees tracking
CREATE TABLE IF NOT EXISTS meeting_attendees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    join_time DATETIME NOT NULL,
    leave_time DATETIME NULL,
    FOREIGN KEY (meeting_id) REFERENCES meetings(meeting_id),
    INDEX (meeting_id),
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Meeting recordings
CREATE TABLE IF NOT EXISTS meeting_recordings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id VARCHAR(255) NOT NULL,
    record_id VARCHAR(255) NOT NULL,
    recording_url VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    duration INT NOT NULL,
    file_size BIGINT NOT NULL,
    FOREIGN KEY (meeting_id) REFERENCES meetings(meeting_id),
    INDEX (meeting_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Scheduled meetings table
CREATE TABLE IF NOT EXISTS scheduled_meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id VARCHAR(255) NOT NULL UNIQUE,
    course_id INT NOT NULL,
    professor_id INT NOT NULL,
    meeting_name VARCHAR(255) NOT NULL,
    scheduled_time DATETIME NOT NULL,
    duration VARCHAR(10) NOT NULL DEFAULT '60',
    welcome_message TEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX (meeting_id),
    INDEX (course_id),
    INDEX (professor_id),
    INDEX (scheduled_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shared recordings table
CREATE TABLE IF NOT EXISTS shared_recordings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id VARCHAR(255) NOT NULL,
    course_id INT NOT NULL,
    shared_at DATETIME NOT NULL,
    UNIQUE KEY unique_meeting_course (meeting_id, course_id),
    INDEX (meeting_id),
    INDEX (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
