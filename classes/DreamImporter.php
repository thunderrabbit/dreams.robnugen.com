<?php

class DreamImporter {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Import a batch of dream files to database
     */
    public function importBatch($file_paths) {
        $results = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
            'last_successful_file' => null
        ];

        foreach ($file_paths as $file_path) {
            try {
                $result = $this->importSingleFile($file_path);
                if ($result === 'imported') {
                    $results['imported']++;
                    $results['last_successful_file'] = $file_path;
                } elseif ($result === 'skipped') {
                    $results['skipped']++;
                    $results['last_successful_file'] = $file_path;
                }
            } catch (Exception $e) {
                $results['errors'][] = "Error importing $file_path: " . $e->getMessage();
                // Stop processing on first error to preserve correct pointer position
                break;
            }
        }

        return $results;
    }

    /**
     * Import a single dream file
     */
    private function importSingleFile($file_path) {
        // Check if already imported
        if ($this->isAlreadyImported($file_path)) {
            return 'skipped';
        }

        // Read and parse file
        $dream_data = $this->parseFile($file_path);
        if (!$dream_data) {
            throw new Exception("Could not parse file");
        }

        // Insert into database
        $this->insertDream($dream_data);

        return 'imported';
    }

    /**
     * Check if file already exists in database
     */
    private function isAlreadyImported($file_path) {
        $stmt = $this->pdo->prepare("SELECT dream_id FROM dreams WHERE file_path = ?");
        $stmt->execute([$file_path]);
        return $stmt->fetch() !== false;
    }

    /**
     * Parse dream file and extract metadata
     */
    private function parseFile($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            return false;
        }

        // Extract filename for title/date parsing
        $filename = basename($file_path);

        // Parse date from file path (YYYY/MM/DD format)
        $date_created = $this->extractDateFromPath($file_path);

        // Parse title from filename (remove date prefix and extension)
        $title = $this->extractTitleFromFilename($filename);

        // Clean content (strip HTML tags, normalize whitespace)
        $content_clean = $this->cleanContent($content);

        return [
            'filename' => $filename,
            'file_path' => $file_path,
            'date_created' => $date_created,
            'title' => $title,
            'content_raw' => $content,
            'content_clean' => $content_clean,
            'word_count' => str_word_count($content_clean),
            'char_count' => strlen($content_clean)
        ];
    }

    /**
     * Extract date from file path like /path/YYYY/MM/DDtitle.ext
     */
    private function extractDateFromPath($file_path) {
        // Match pattern: /YYYY/MM/DDsomething
        if (preg_match('#/(\d{4})/(\d{2})/(\d{2})#', $file_path, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            return "$year-$month-$day";
        }
        return null;
    }

    /**
     * Extract title from filename like "DDtitle.ext"
     */
    private function extractTitleFromFilename($filename) {
        // Remove extension
        $title = pathinfo($filename, PATHINFO_FILENAME);

        // Remove date prefix (DD at start)
        $title = preg_replace('/^\d{2}/', '', $title);

        // Convert underscores to spaces
        $title = str_replace('_', ' ', $title);

        // Trim and clean up
        return trim($title);
    }

    /**
     * Clean content for analysis
     */
    private function cleanContent($content) {
        // Strip HTML/markdown frontmatter if present
        $content = preg_replace('/^---.*?---\s*/s', '', $content);

        // Strip HTML tags
        $content = strip_tags($content);

        // Normalize whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }

    /**
     * Insert dream into database
     */
    private function insertDream($dream_data) {
        $sql = "INSERT INTO dreams (
            filename, file_path, date_created, title,
            content_raw, content_clean, word_count, char_count
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dream_data['filename'],
            $dream_data['file_path'],
            $dream_data['date_created'],
            $dream_data['title'],
            $dream_data['content_raw'],
            $dream_data['content_clean'],
            $dream_data['word_count'],
            $dream_data['char_count']
        ]);
    }
}
