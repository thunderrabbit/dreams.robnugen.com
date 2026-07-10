CREATE TABLE keywords_2_dreams (
    k2d_id INT AUTO_INCREMENT PRIMARY KEY,
    dream_keyword_id INT NOT NULL,
    dream_id INT NOT NULL,
    occurrence_count INT DEFAULT 1,
    INDEX idx_dream_keyword_id (dream_keyword_id),
    INDEX idx_dream_id (dream_id),
    UNIQUE KEY unique_keyword_dream (dream_keyword_id, dream_id),
    FOREIGN KEY (dream_keyword_id) REFERENCES dream_keywords(dream_keyword_id) ON DELETE CASCADE,
    FOREIGN KEY (dream_id) REFERENCES dreams(dream_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
