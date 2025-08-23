<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

if ($is_logged_in->isLoggedIn()) {
    // Get temporal frequency data for dashboard
    try {
        // Dreams by year
        $stmt = $mla_database->prepare("
            SELECT YEAR(date_created) as year, COUNT(*) as count
            FROM dreams
            WHERE date_created IS NOT NULL
            GROUP BY YEAR(date_created)
            ORDER BY year
        ");
        $stmt->execute();
        $by_year = $stmt->fetchAll();

        // Dreams by month (across all years)
        $stmt = $mla_database->prepare("
            SELECT MONTH(date_created) as month, COUNT(*) as count
            FROM dreams
            WHERE date_created IS NOT NULL
            GROUP BY MONTH(date_created)
            ORDER BY month
        ");
        $stmt->execute();
        $by_month = $stmt->fetchAll();

        // Dreams by day of month (1-31)
        $stmt = $mla_database->prepare("
            SELECT DAY(date_created) as day, COUNT(*) as count
            FROM dreams
            WHERE date_created IS NOT NULL
            GROUP BY DAY(date_created)
            ORDER BY day
        ");
        $stmt->execute();
        $by_day = $stmt->fetchAll();

        // Total count
        $stmt = $mla_database->prepare("SELECT COUNT(*) FROM dreams");
        $stmt->execute();
        $total_dreams = $stmt->fetchColumn();

    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }

    $page = new \Template(config: $config);
    $page->setTemplate("admin/index.tpl.php");
    $page->set(name: "site_version", value: SENTIMENTAL_VERSION);
    $page->set(name: "username", value: $is_logged_in->getLoggedInUsername());

    $pending = $dbExistaroo->getPendingMigrations();
    $page->set(name: "has_pending_migrations", value: !empty($pending));

    // Add dream analysis data to dashboard
    $page->set("by_year", $by_year ?? []);
    $page->set("by_month", $by_month ?? []);
    $page->set("by_day", $by_day ?? []);
    $page->set("total_dreams", $total_dreams ?? 0);
    $page->set("error", $error ?? null);

    $inner = $page->grabTheGoods();

    $layout = new \Template(config: $config);
    $layout->setTemplate("layout/admin_base.tpl.php");
    $layout->set("page_title", "Dashboard");
    $layout->set("page_content", $inner);
    $layout->echoToScreen();
    exit;
} else {
    header(header: "Location: /login/");
    exit;
}
