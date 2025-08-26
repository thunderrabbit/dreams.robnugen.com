<?php

# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

if (!$is_logged_in->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

header('Content-Type: application/json');

$keyword = $mla_request->post['keyword'] ?? '';
$is_stop_word = filter_var($mla_request->post['is_stop_word'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!$keyword) {
    http_response_code(400);
    echo json_encode(['error' => 'Keyword required']);
    exit;
}

try {
    $sql = "UPDATE dream_keywords SET is_stop_word = ? WHERE keyword = ?";
    $stmt = $mla_database->prepare($sql);
    $result = $stmt->execute([$is_stop_word ? 1 : 0, $keyword]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'keyword' => $keyword, 'is_stop_word' => $is_stop_word]);
    } else {
        echo json_encode(['error' => 'Keyword not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
