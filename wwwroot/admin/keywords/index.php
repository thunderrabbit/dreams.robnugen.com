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

if ($action === 'analyze') {
    try {
        $results = $analyzer->analyzeAllDreams();
        $message = "Analysis complete! Processed {$results['total_dreams_processed']} dreams, found {$results['unique_keywords_found']} unique keywords ({$results['total_keyword_instances']} total instances).";
    } catch (Exception $e) {
        $error = "Error during analysis: " . $e->getMessage();
    }
}

// Get data for display
try {
    $top_keywords = $analyzer->getTopKeywords(100);
    $stats = $analyzer->getKeywordStats();
    $search_results = [];

    // Handle search
    $search_term = $mla_request->get['search'] ?? '';
    if ($search_term) {
        $search_results = $analyzer->searchKeywords($search_term);
    }

} catch (Exception $e) {
    $error = "Error loading data: " . $e->getMessage();
    $top_keywords = [];
    $stats = [];
}

$template = new Template(config: $config);
$template->setTemplate("admin/keywords/index.tpl.php");
$template->set("top_keywords", $top_keywords);
$template->set("stats", $stats);
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
