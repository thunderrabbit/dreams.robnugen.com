<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

$debugLevel = intval(value: $_GET['debug']) ?? 0;
if($debugLevel > 0) {
    echo "<pre>Debug Level: $debugLevel</pre>";
}

if($is_logged_in->isLoggedIn()){

    // Handle dream saving if form submitted
    $message = '';
    $error = '';

    if ($mla_request->post && isset($mla_request->post['title'])) {
        try {
            $poster = new QuickPoster($debugLevel);
            $success = $poster->createPost($config, $mla_request->post);

            if ($success) {
                $message = "Dream saved successfully to: " . $poster->post_path;
            } else {
                $error = "Failed to save dream.";
            }
        } catch (Exception $e) {
            $error = "Error saving dream: " . $e->getMessage();
        }
    }

    // Set timezone to JST like Quick site
    date_default_timezone_set('Asia/Tokyo');
    $current_time = date("H:i");
    $current_date = date("l j F Y T");

    // Template for dream writing interface
    $page = new Template(config: $config);
    $page->setTemplate("dream_writer.tpl.php");
    $page->set("username", $is_logged_in->getLoggedInUsername());
    $page->set("current_time", $current_time);
    $page->set("current_date", $current_date);
    $page->set("message", $message);
    $page->set("error", $error);
    $page->echoToScreen();
    exit;

} else {
    echo "<h1>Welcome to This Here Brand New Web Site</h1>";
    echo "<p><a href='/login/'>Click here to log in</a></p>";
    exit;
}
