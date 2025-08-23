<?php

class DreamKeywordAnalyzer {

    private $database;

    // Common English stop words to filter out
    private $stop_words = [
        'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
        'a', 'an', 'as', 'are', 'was', 'were', 'been', 'be', 'have', 'has', 'had',
        'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might',
        'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them',
        'my', 'your', 'his', 'her', 'its', 'our', 'their', 'this', 'that', 'these', 'those',
        'is', 'am', 'was', 'were', 'been', 'being', 'have', 'has', 'had', 'having',
        'so', 'if', 'then', 'than', 'when', 'where', 'why', 'how', 'what', 'who', 'which',
        'up', 'down', 'out', 'off', 'over', 'under', 'again', 'further', 'then', 'once',
        'very', 'too', 'can', 'now', 'just', 'only', 'also', 'back', 'still', 'well',
        'get', 'go', 'come', 'see', 'know', 'think', 'take', 'make', 'say', 'tell',
        'like', 'look', 'feel', 'seem', 'find', 'give', 'put', 'use', 'work', 'call'
    ];

    public function __construct($database) {
        $this->database = $database;
    }

    /**
     * Extract and count keywords from all dreams
     */
    public function analyzeAllDreams() {
        // Clear existing keyword data
        $this->database->exec("TRUNCATE TABLE dream_keywords");

        // Get all dream content and dates
        $stmt = $this->database->prepare("SELECT content_clean, date_created FROM dreams WHERE content_clean IS NOT NULL AND content_clean != ''");
        $stmt->execute();
        $dreams = $stmt->fetchAll();

        $keyword_data = [];
        $keyword_dates = [];

        foreach ($dreams as $dream) {
            $dream_date = $dream['date_created'];
            $keywords = $this->extractKeywords($dream['content_clean']);

            foreach ($keywords as $keyword) {
                if (!isset($keyword_data[$keyword])) {
                    $keyword_data[$keyword] = 0;
                    $keyword_dates[$keyword] = ['first' => $dream_date, 'last' => $dream_date];
                }

                $keyword_data[$keyword]++;

                // Update date range
                if ($dream_date < $keyword_dates[$keyword]['first']) {
                    $keyword_dates[$keyword]['first'] = $dream_date;
                }
                if ($dream_date > $keyword_dates[$keyword]['last']) {
                    $keyword_dates[$keyword]['last'] = $dream_date;
                }
            }
        }

        // Store results in database
        $this->storeKeywords($keyword_data, $keyword_dates);

        return [
            'total_dreams_processed' => count($dreams),
            'unique_keywords_found' => count($keyword_data),
            'total_keyword_instances' => array_sum($keyword_data)
        ];
    }

    /**
     * Extract meaningful keywords from dream text
     */
    private function extractKeywords($text) {
        // Convert to lowercase and remove HTML tags
        $text = strtolower(strip_tags($text));

        // Remove punctuation and split into words
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $keywords = [];

        foreach ($words as $word) {
            // Skip short words, numbers, and stop words
            if (strlen($word) < 3 || is_numeric($word) || in_array($word, $this->stop_words)) {
                continue;
            }

            $keywords[] = $word;
        }

        return $keywords;
    }

    /**
     * Store keyword frequencies in database
     */
    private function storeKeywords($keyword_data, $keyword_dates) {
        $stmt = $this->database->prepare("
            INSERT INTO dream_keywords (keyword, frequency, first_seen, last_seen)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($keyword_data as $keyword => $frequency) {
            $stmt->execute([
                $keyword,
                $frequency,
                $keyword_dates[$keyword]['first'],
                $keyword_dates[$keyword]['last']
            ]);
        }
    }

    /**
     * Get top keywords by frequency
     */
    public function getTopKeywords($limit = 50) {
        $stmt = $this->database->prepare("
            SELECT keyword, frequency, first_seen, last_seen,
                   DATEDIFF(last_seen, first_seen) + 1 as span_days
            FROM dream_keywords
            ORDER BY frequency DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Search for specific keyword patterns
     */
    public function searchKeywords($pattern) {
        $stmt = $this->database->prepare("
            SELECT keyword, frequency, first_seen, last_seen
            FROM dream_keywords
            WHERE keyword LIKE ?
            ORDER BY frequency DESC
        ");
        $stmt->execute(['%' . $pattern . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Get keyword statistics
     */
    public function getKeywordStats() {
        $stmt = $this->database->query("
            SELECT
                COUNT(*) as total_keywords,
                SUM(frequency) as total_instances,
                AVG(frequency) as avg_frequency,
                MAX(frequency) as max_frequency,
                MIN(first_seen) as earliest_date,
                MAX(last_seen) as latest_date
            FROM dream_keywords
        ");
        return $stmt->fetch();
    }
}
