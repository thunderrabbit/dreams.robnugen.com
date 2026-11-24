<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

/**
 * Set by prepend.php.
 *
 * @var \Config $config
 * @var \PDO $mla_database
 * @var \Database\DBExistaroo $dbExistaroo
 * @var \Auth\IsLoggedIn $is_logged_in
 */

if ($is_logged_in->isLoggedIn()) {
    // Get temporal frequency data for dashboard
    try {
        $dreams_repository = new \Database\DreamsRepository($mla_database);
        $by_year = $dreams_repository->countByYear();
        $by_month = $dreams_repository->countByMonth();
        $by_day = $dreams_repository->countByDay();
        $total_dreams = $dreams_repository->countAll();
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
