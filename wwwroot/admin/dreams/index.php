<?php

# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

if (!$is_logged_in->isLoggedIn()) {
    header("Location: /login/");
    exit;
}

$template = new Template(config: $config);
$template->page_title = "Dreams Import";

// Initialize scanner
$scanner = new DreamScanner();
$stats = $scanner->getStats();

// Get database stats
try {
    $stmt = $mla_database->prepare("SELECT COUNT(*) FROM dreams");
    $stmt->execute();
    $db_count = $stmt->fetchColumn();
    $stats['db_count'] = $db_count;
} catch (Exception $e) {
    $stats['db_count'] = "Error: " . $e->getMessage();
}

$message = "";
$import_results = [];

// Handle form submission
if ($_POST['action'] ?? '' === 'import') {
    $batch = $scanner->getNextBatch(50);

    if (empty($batch)) {
        $message = "No more dreams to import!";
    } else {
        // Import the batch to database
        $importer = new DreamImporter($mla_database);
        $results = $importer->importBatch($batch);

        $import_results = $batch;
        $message = sprintf(
            "Processed %d files: %d imported, %d skipped",
            count($batch),
            $results['imported'],
            $results['skipped']
        );

        if (!empty($results['errors'])) {
            $message .= " | Errors: " . implode('; ', $results['errors']);
        }

        // Debug: Show detailed results
        $message .= " | Debug: " . json_encode($results);

        // Update pointer to last file in batch
        if (!empty($batch)) {
            $scanner->updatePointer(end($batch));
        }
    }

    // Refresh stats after import
    $stats = $scanner->getStats();
}

// Handle reset pointer
if ($_POST['action'] ?? '' === 'reset') {
    $scanner->resetPointer();
    $stats = $scanner->getStats();
    $message = "Import pointer reset to beginning";
}

// Template variables
$template->setTemplate("admin/dreams/index.tpl.php");
$template->set("stats", $stats);
$template->set("message", $message);
$template->set("import_results", $import_results);
$inner = $template->grabTheGoods();

$layout = new \Template(config: $config);
$layout->setTemplate("layout/admin_base.tpl.php");
$layout->set("page_title", "Dreams Import");
$layout->set("page_content", $inner);
$layout->echoToScreen();
?>
