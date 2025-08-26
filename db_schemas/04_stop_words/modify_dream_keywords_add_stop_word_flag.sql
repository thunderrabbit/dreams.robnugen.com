-- Add is_stop_word column to dream_keywords table
ALTER TABLE dream_keywords
ADD COLUMN is_stop_word TINYINT(1) DEFAULT 0,
ADD INDEX idx_is_stop_word (is_stop_word);

-- Rename primary key from id to dream_keyword_id
ALTER TABLE dream_keywords
CHANGE COLUMN id dream_keyword_id INT AUTO_INCREMENT;
