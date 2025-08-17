<?php

class DreamScanner {

    private $journal_base;
    private $pointer_file;

    public function __construct() {
        // Use the same journal base path as the main journal system
        $this->journal_base = "/home/barefoot_rob/robnugen.com/journal/journal";
        $this->pointer_file = "/home/barefoot_rob/dreams_import_pointer.txt";
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
     * Step 2-4: Get next batch of dream files, starting after pointer
     */
    public function getNextBatch($limit = 50) {
        $last_processed = $this->getLastProcessedFile();
        $dream_files = [];

        // Get the directory and filename of last processed file for comparison
        $start_scanning = empty($last_processed);

        // Recursively scan journal directory for dream files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->journal_base)
        );

        // Convert iterator to array and sort for consistent ordering
        $all_files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $full_path = $file->getPathname();

                // Check if filename contains "dream" (case insensitive)
                if (stripos($filename, 'dream') !== false) {
                    $all_files[] = $full_path;
                }
            }
        }

        // Sort files alphabetically for consistent processing
        sort($all_files);

        // Find starting point and collect next batch
        foreach ($all_files as $file_path) {
            if (!$start_scanning) {
                // Still looking for the last processed file
                if ($file_path === $last_processed) {
                    $start_scanning = true; // Start collecting from next file
                }
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
     * Get all dream files (only for stats - still inefficient but needed for totals)
     */
    private function getAllDreamFiles() {
        $dream_files = [];

        // Recursively scan journal directory for dream files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->journal_base)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $full_path = $file->getPathname();

                // Check if filename contains "dream" (case insensitive)
                if (stripos($filename, 'dream') !== false) {
                    $dream_files[] = $full_path;
                }
            }
        }

        // Sort files alphabetically for consistent processing
        sort($dream_files);

        return $dream_files;
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
