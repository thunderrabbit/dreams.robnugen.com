CREATE TABLE dream_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(100) NOT NULL,
    frequency INT NOT NULL DEFAULT 0,
    first_seen DATE,
    last_seen DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_keyword (keyword),
    INDEX idx_frequency (frequency DESC),
    INDEX idx_keyword (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE keyword_analysis_pointer (
    kap_id INT AUTO_INCREMENT PRIMARY KEY,
    last_analyzed_dream_id INT,
    last_analysis_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_dreams_analyzed INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
