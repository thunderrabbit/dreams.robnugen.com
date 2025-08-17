CREATE TABLE dream_themes (
    theme_id INT AUTO_INCREMENT PRIMARY KEY,
    dream_id INT NOT NULL,
    theme_name VARCHAR(255) NOT NULL,
    confidence_score DECIMAL(3,2) DEFAULT 0.00,
    ai_reasoning TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dream_id) REFERENCES dreams(dream_id) ON DELETE CASCADE,
    INDEX idx_dream_id (dream_id),
    INDEX idx_theme_name (theme_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
