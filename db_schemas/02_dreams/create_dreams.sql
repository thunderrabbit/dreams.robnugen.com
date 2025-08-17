CREATE TABLE dreams (
    dream_id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    date_created DATE,
    title VARCHAR(500),
    content_raw TEXT,
    content_clean TEXT,
    word_count INT DEFAULT 0,
    char_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date_created (date_created),
    INDEX idx_filename (filename),
    UNIQUE KEY unique_file_path (file_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
