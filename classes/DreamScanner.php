<?php

class DreamScanner {

    private $journal_base;
    private $pointer_file;
    private $failed_files;

    public function __construct(Config $config) {
        // Use paths from config to avoid duplication
        $this->journal_base = $config->post_path_journal;
        $this->pointer_file = $config->dreams_import_pointer_file;
        $this->failed_files = $config->dreams_failed_files;
    }

    /**
     * Step 0/1: Read pointer file, return last processed file path or empty string
     */
    public function getLastProcessedFile() {
        if (!file_exists($this->pointer_file)) {
            // Step 0: File doesn't exist, start from beginning
            return "";
        }

        // Step 1: Read existing pointer
        $contents = trim(file_get_contents($this->pointer_file));
        return $contents ?: "";
    }

    /**
     * Step 5: Update pointer to last processed file
     */
    public function updatePointer($file_path) {
        file_put_contents($this->pointer_file, $file_path);
    }

    /**
     * Reset pointer (start over from beginning)
     */
    public function resetPointer() {
        if (file_exists($this->pointer_file)) {
            unlink($this->pointer_file);
        }
    }

    /**
     * Add a failed file to the failed files list
     */
    public function addFailedFile($file_path) {
        file_put_contents($this->failed_files, $file_path . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Get list of failed files
     */
    public function getFailedFiles() {
        if (!file_exists($this->failed_files)) {
            return [];
        }
        $contents = file_get_contents($this->failed_files);
        return array_filter(array_map('trim', explode("\n", $contents)));
    }

    /**
     * Clear the failed files list
     */
    public function clearFailedFiles() {
        if (file_exists($this->failed_files)) {
            unlink($this->failed_files);
        }
    }

    /**
     * Step 2-4: Get next batch of dream files, starting after pointer
     */
    public function getNextBatch($limit = 50, $skip_failed = true) {
        $last_processed = $this->getLastProcessedFile();
        $failed_files = $skip_failed ? $this->getFailedFiles() : [];
        $dream_files = [];

        // Get the directory and filename of last processed file for comparison
        $start_scanning = empty($last_processed);

        // Get all valid dream files
        $all_files = $this->scanAllDreamFiles();

        // Find starting point and collect next batch
        foreach ($all_files as $file_path) {
            if (!$start_scanning) {
                // Still looking for the last processed file
                if ($file_path === $last_processed) {
                    $start_scanning = true; // Start collecting from next file
                }
                continue;
            }

            // Skip known failed files
            if (in_array($file_path, $failed_files)) {
                continue;
            }

            // Collect files for batch
            $dream_files[] = $file_path;

            // Stop when we have enough files
            if (count($dream_files) >= $limit) {
                break;
            }
        }

        return $dream_files;
    }

    /**
     * Get all valid dream files from the filesystem
     */
    private function scanAllDreamFiles() {
        $dream_files = [];

        // Recursively scan journal directory for dream files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->journal_base)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $full_path = $file->getPathname();

                // Skip system files and directories
                if (strpos($full_path, '/.git/') !== false ||
                    strpos($full_path, '/.') !== false ||
                    !preg_match('/\.(html|md)$/', $filename)) {
                    continue;
                }

                // Only include files in YYYY/MM/ directory structure
                if (!preg_match('#/\d{4}/\d{2}/[^/]+$#', $full_path)) {
                    continue;
                }

                // Check if filename contains "dream" (case insensitive)
                // but exclude files with "castle" in them (false positives)
                if (stripos($filename, 'dream') !== false && stripos($filename, 'castle') === false) {
                    $dream_files[] = $full_path;
                }
            }
        }

        // Sort files alphabetically for consistent processing
        sort($dream_files);

        return $dream_files;
    }

    /**
     * Get all dream files (only for stats - still inefficient but needed for totals)
     */
    private function getAllDreamFiles() {
        return $this->scanAllDreamFiles();
    }

    /**
     * Get stats for admin interface
     */
    public function getStats() {
        $all_files = $this->getAllDreamFiles();
        $last_processed = $this->getLastProcessedFile();

        $total_files = count($all_files);
        $processed_count = 0;

        if (!empty($last_processed)) {
            $last_index = array_search($last_processed, $all_files);
            $processed_count = ($last_index !== false) ? $last_index + 1 : 0;
        }

        return [
            'total_files' => $total_files,
            'processed_count' => $processed_count,
            'remaining_count' => $total_files - $processed_count,
            'last_processed_file' => $last_processed
        ];
    }
}
