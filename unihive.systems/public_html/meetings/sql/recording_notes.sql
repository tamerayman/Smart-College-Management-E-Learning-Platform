CREATE TABLE IF NOT EXISTS recording_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recording_id VARCHAR(255) NOT NULL,
    professor_id INT NOT NULL,
    note_content TEXT NOT NULL,
    timestamp FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_public TINYINT(1) DEFAULT 1,
    UNIQUE KEY recording_professor (recording_id, professor_id)
);
