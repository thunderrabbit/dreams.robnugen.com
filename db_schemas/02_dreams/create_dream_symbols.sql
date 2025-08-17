CREATE TABLE dream_symbols (
    symbol_id INT AUTO_INCREMENT PRIMARY KEY,
    dream_id INT NOT NULL,
    symbol_text VARCHAR(255) NOT NULL,
    symbol_type ENUM('person', 'place', 'object', 'emotion', 'action', 'other') DEFAULT 'other',
    confidence_score DECIMAL(3,2) DEFAULT 0.00,
    context_snippet TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dream_id) REFERENCES dreams(dream_id) ON DELETE CASCADE,
    INDEX idx_dream_id (dream_id),
    INDEX idx_symbol_text (symbol_text),
    INDEX idx_symbol_type (symbol_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
