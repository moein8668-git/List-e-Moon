<?php
require_once 'includes/auth.php';
require_once 'includes/api_handler.php';

// JSON Response header
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = $_GET['q'] ?? '';
$category = $_GET['cat'] ?? '';

if (empty($query) || empty($category)) {
    echo json_encode(['results' => []]);
    exit;
}

$results = search_online($query, $category);

if ($results === null) {
    // Timeout or Error
    echo json_encode(['error' => 'API Timeout']);
} else {
    echo json_encode(['results' => $results]);
}
