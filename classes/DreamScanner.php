<?php

class DreamScanner
{
    private readonly string $journal_base;
    private readonly string $pointer_file;
    private readonly string $failed_files;

    public function __construct(Config $config)
    {
        // Validate required config properties
        if (empty($config->post_path_journal)) {
            throw new Exception("Config property 'post_path_journal' is required but empty. Please check your Config.php file.");
        }
        if (empty($config->dreams_import_pointer_file)) {
            throw new Exception("Config property 'dreams_import_pointer_file' is required but empty. Please check your Config.php file.");
        }
        if (empty($config->dreams_failed_files)) {
            throw new Exception("Config property 'dreams_failed_files' is required but empty. Please check your Config.php file.");
        }

        // Use paths from config to avoid duplication
        $this->journal_base = $config->post_path_journal;
        $this->pointer_file = $config->dreams_import_pointer_file;
        $this->failed_files = $config->dreams_failed_files;
    }

    /**
     * Step 0/1: Read pointer file, return last processed file path or empty string
     */
    public function getLastProcessedFile(): string
    {
        if (!file_exists($this->pointer_file)) {
            // Step 0: File doesn't exist, start from beginning
            return "";
        }

        // Step 1: Read existing pointer
        $contents = file_get_contents($this->pointer_file);
        if ($contents === false) {
            return "";
        }
        return trim($contents) ?: "";
    }

    /**
     * Step 5: Update pointer to last processed file
     */
    public function updatePointer(string $file_path): void
    {
        file_put_contents($this->pointer_file, $file_path);
    }

    /**
     * Reset pointer (start over from beginning)
     */
    public function resetPointer(): void
    {
        if (file_exists($this->pointer_file)) {
            unlink($this->pointer_file);
        }
    }

    /**
     * Add a failed file to the failed files list
     */
    public function addFailedFile(string $file_path): void
    {
        file_put_contents($this->failed_files, $file_path . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Get list of failed files
     *
     * @return array<int, string> keyed by line number, blank lines dropped
     */
    public function getFailedFiles(): array
    {
        if (!file_exists($this->failed_files)) {
            return [];
        }
        $contents = file_get_contents($this->failed_files);
        if ($contents === false) {
            return [];
        }
        return array_filter(array_map('trim', explode("\n", $contents)));
    }

    /**
     * Clear the failed files list
     */
    public function clearFailedFiles(): void
    {
        if (file_exists($this->failed_files)) {
            unlink($this->failed_files);
        }
    }

    /**
     * Step 2-4: Get next batch of dream files, starting after pointer
     *
     * @return list<string>
     */
    public function getNextBatch(int $limit = 50, bool $skip_failed = true): array
    {
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
     *
     * @return list<string> full paths, sorted
     */
    private function scanAllDreamFiles(): array
    {
        $dream_files = [];

        // Recursively scan journal directory for dream files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->journal_base)
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $filename = $file->getFilename();
                $full_path = $file->getPathname();

                // Skip system files and directories
                if (
                    str_contains($full_path, '/.git/') ||
                    str_contains($full_path, '/.') ||
                    !preg_match('/\.(html|md)$/', $filename)
                ) {
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
     *
     * @return list<string>
     */
    private function getAllDreamFiles(): array
    {
        return $this->scanAllDreamFiles();
    }

    /**
     * Get stats for admin interface
     *
     * @return array{
     *     total_files: int,
     *     processed_count: int,
     *     remaining_count: int,
     *     last_processed_file: string
     * }
     */
    public function getStats(): array
    {
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
