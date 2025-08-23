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

    // HTML entity remnants to filter out
    private $html_entities = [
        'quot', 'amp', 'lt', 'gt', 'nbsp', 'copy', 'reg', 'trade', 'hellip', 'mdash', 'ndash',
        'laquo', 'raquo', 'ldquo', 'rdquo', 'lsquo', 'rsquo', 'bull', 'middot', 'deg'
    ];

    public function __construct($database) {
        $this->database = $database;
    }

    /**
     * Analyze only new dreams since last analysis
     */
    public function analyzeNewDreams() {
        $last_id = $this->getLastAnalyzedId();

        // Get new dreams since last analysis
        $stmt = $this->database->prepare("
            SELECT dream_id, content_clean, date_created
            FROM dreams
            WHERE dream_id > ? AND content_clean IS NOT NULL AND content_clean != ''
            ORDER BY dream_id
        ");
        $stmt->execute([$last_id]);
        $dreams = $stmt->fetchAll();

        if (empty($dreams)) {
            return [
                'total_dreams_processed' => 0,
                'unique_keywords_found' => 0,
                'total_keyword_instances' => 0,
                'message' => 'No new dreams to analyze'
            ];
        }

        $keyword_data = [];
        $keyword_dates = [];
        $last_processed_id = $last_id;

        foreach ($dreams as $dream) {
            $dream_date = $dream['date_created'];
            $keywords = $this->extractKeywords($dream['content_clean']);
            $last_processed_id = $dream['dream_id'];

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

        // Update existing keywords or insert new ones
        $this->updateKeywords($keyword_data, $keyword_dates);

        // Update pointer
        $this->updateAnalysisPointer($last_processed_id, count($dreams));

        return [
            'total_dreams_processed' => count($dreams),
            'unique_keywords_found' => count($keyword_data),
            'total_keyword_instances' => array_sum($keyword_data)
        ];
    }

    /**
     * Analyze all dreams (full reanalysis)
     */
    public function analyzeAllDreams() {
        // Clear existing keyword data
        $this->database->exec("TRUNCATE TABLE dream_keywords");
        $this->database->exec("TRUNCATE TABLE keyword_analysis_pointer");

        // Get all dream content and dates
        $stmt = $this->database->prepare("
            SELECT dream_id, content_clean, date_created
            FROM dreams
            WHERE content_clean IS NOT NULL AND content_clean != ''
            ORDER BY dream_id
        ");
        $stmt->execute();
        $dreams = $stmt->fetchAll();

        $keyword_data = [];
        $keyword_dates = [];
        $last_processed_id = 0;

        foreach ($dreams as $dream) {
            $dream_date = $dream['date_created'];
            $keywords = $this->extractKeywords($dream['content_clean']);
            $last_processed_id = $dream['dream_id'];

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
        $this->updateAnalysisPointer($last_processed_id, count($dreams));

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

        // Decode HTML entities first
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Replace punctuation but preserve contractions
        $text = preg_replace("/([a-z])'([a-z])/", '$1APOSTROPHE$2', $text);
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        $text = str_replace('APOSTROPHE', "'", $text);

        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $keywords = [];

        foreach ($words as $word) {
            // Skip short words, numbers, stop words, and HTML entities
            if (strlen($word) < 3 ||
                is_numeric($word) ||
                in_array($word, $this->stop_words) ||
                in_array($word, $this->html_entities)) {
                continue;
            }

            $keywords[] = $word;
        }

        return $keywords;
    }

    /**
     * Store keyword frequencies in database (for full analysis)
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
     * Update keyword frequencies (for incremental analysis)
     */
    private function updateKeywords($keyword_data, $keyword_dates) {
        foreach ($keyword_data as $keyword => $new_frequency) {
            // Try to update existing keyword
            $stmt = $this->database->prepare("
                UPDATE dream_keywords
                SET frequency = frequency + ?,
                    first_seen = LEAST(first_seen, ?),
                    last_seen = GREATEST(last_seen, ?),
                    updated_at = CURRENT_TIMESTAMP
                WHERE keyword = ?
            ");

            $updated = $stmt->execute([
                $new_frequency,
                $keyword_dates[$keyword]['first'],
                $keyword_dates[$keyword]['last'],
                $keyword
            ]);

            // If no rows were updated, insert new keyword
            if ($stmt->rowCount() === 0) {
                $insert_stmt = $this->database->prepare("
                    INSERT INTO dream_keywords (keyword, frequency, first_seen, last_seen)
                    VALUES (?, ?, ?, ?)
                ");

                $insert_stmt->execute([
                    $keyword,
                    $new_frequency,
                    $keyword_dates[$keyword]['first'],
                    $keyword_dates[$keyword]['last']
                ]);
            }
        }
    }

    /**
     * Get last analyzed dream ID
     */
    private function getLastAnalyzedId() {
        $stmt = $this->database->query("
            SELECT last_analyzed_dream_id
            FROM keyword_analysis_pointer
            ORDER BY kap_id DESC
            LIMIT 1
        ");

        $result = $stmt->fetch();
        return $result ? $result['last_analyzed_dream_id'] : 0;
    }

    /**
     * Update analysis pointer
     */
    private function updateAnalysisPointer($last_id, $count) {
        // Clear existing pointer and insert new one
        $this->database->exec("TRUNCATE TABLE keyword_analysis_pointer");

        $stmt = $this->database->prepare("
            INSERT INTO keyword_analysis_pointer (last_analyzed_dream_id, total_dreams_analyzed)
            VALUES (?, ?)
        ");

        $stmt->execute([$last_id, $count]);
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

    /**
     * Get analysis progress info
     */
    public function getAnalysisProgress() {
        $pointer_stmt = $this->database->query("
            SELECT last_analyzed_dream_id, last_analysis_date, total_dreams_analyzed
            FROM keyword_analysis_pointer
            ORDER BY kap_id DESC
            LIMIT 1
        ");
        $pointer = $pointer_stmt->fetch();

        $total_stmt = $this->database->query("
            SELECT COUNT(*) as total_dreams, MAX(dream_id) as max_dream_id
            FROM dreams
            WHERE content_clean IS NOT NULL AND content_clean != ''
        ");
        $totals = $total_stmt->fetch();

        if (!$pointer) {
            return [
                'last_analyzed_id' => 0,
                'total_dreams' => $totals['total_dreams'],
                'remaining_dreams' => $totals['total_dreams'],
                'last_analysis_date' => null,
                'max_dream_id' => $totals['max_dream_id']
            ];
        }

        $remaining_stmt = $this->database->prepare("
            SELECT COUNT(*) as remaining_count
            FROM dreams
            WHERE dream_id > ? AND content_clean IS NOT NULL AND content_clean != ''
        ");
        $remaining_stmt->execute([$pointer['last_analyzed_dream_id']]);
        $remaining = $remaining_stmt->fetch();

        return [
            'last_analyzed_id' => $pointer['last_analyzed_dream_id'],
            'total_dreams' => $totals['total_dreams'],
            'remaining_dreams' => $remaining['remaining_count'],
            'last_analysis_date' => $pointer['last_analysis_date'],
            'max_dream_id' => $totals['max_id']
        ];
    }
}
