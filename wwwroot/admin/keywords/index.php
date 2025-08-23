<?php

# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

if (!$is_logged_in->isLoggedIn()) {
    header("Location: /login/");
    exit;
}

$analyzer = new DreamKeywordAnalyzer($mla_database);
$action = $mla_request->post['action'] ?? $mla_request->get['action'] ?? '';

// Handle actions
$message = '';
$error = '';

if ($action === 'analyze_all') {
    try {
        $results = $analyzer->analyzeAllDreams();
        $message = "Full analysis complete! Processed {$results['total_dreams_processed']} dreams, found {$results['unique_keywords_found']} unique keywords ({$results['total_keyword_instances']} total instances).";
    } catch (Exception $e) {
        $error = "Error during full analysis: " . $e->getMessage();
    }
} elseif ($action === 'analyze_new') {
    try {
        $results = $analyzer->analyzeNewDreams();
        if (isset($results['message'])) {
            $message = $results['message'];
        } else {
            $message = "Incremental analysis complete! Processed {$results['total_dreams_processed']} new dreams, found {$results['unique_keywords_found']} unique keywords ({$results['total_keyword_instances']} total instances).";
        }
    } catch (Exception $e) {
        $error = "Error during incremental analysis: " . $e->getMessage();
    }
}

// Check if tables exist first
$tables_exist = true;
try {
    $mla_database->query("SELECT 1 FROM dream_keywords LIMIT 1");
    $mla_database->query("SELECT 1 FROM keyword_analysis_pointer LIMIT 1");
} catch (Exception $e) {
    $tables_exist = false;
    if (!$error) {
        $error = "Keyword analysis tables not found. Please run database migrations first at <a href='/admin/migrate_tables.php'>/admin/migrate_tables.php</a>";
    }
}

// Get data for display
$top_keywords = [];
$stats = [];
$progress = [];
$search_results = [];

if ($tables_exist) {
    try {
        $top_keywords = $analyzer->getTopKeywords(100);
        $stats = $analyzer->getKeywordStats();
        $progress = $analyzer->getAnalysisProgress();

        // Handle search
        $search_term = $mla_request->get['search'] ?? '';
        if ($search_term) {
            $search_results = $analyzer->searchKeywords($search_term);
        }

    } catch (Exception $e) {
        $error = "Error loading data: " . $e->getMessage();
    }
}

$template = new Template(config: $config);
$template->setTemplate("admin/keywords/index.tpl.php");
$template->set("top_keywords", $top_keywords);
$template->set("stats", $stats);
$template->set("progress", $progress ?? []);
$template->set("search_term", $search_term);
$template->set("search_results", $search_results);
$template->set("message", $message);
$template->set("error", $error);
$inner = $template->grabTheGoods();

$layout = new Template(config: $config);
$layout->setTemplate("layout/admin_base.tpl.php");
$layout->set("page_title", "Dream Keyword Analysis");
$layout->set("page_content", $inner);
$layout->echoToScreen();
?>
