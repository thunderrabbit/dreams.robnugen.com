CREATE TABLE symbol_patterns (
    pattern_id INT AUTO_INCREMENT PRIMARY KEY,
    symbol_text VARCHAR(255) NOT NULL UNIQUE,
    first_occurrence DATE,
    last_occurrence DATE,
    total_count INT DEFAULT 0,
    dreams_count INT DEFAULT 0,
    yearly_frequency JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_symbol_text (symbol_text),
    INDEX idx_first_occurrence (first_occurrence),
    INDEX idx_total_count (total_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
