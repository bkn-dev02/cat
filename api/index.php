<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-API-Key, Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/endpoints/scores.php';
require_once __DIR__ . '/endpoints/summary.php';

authenticate();

// Ambil path setelah /api
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri      = preg_replace('#^/api#', '', $uri);
$uri      = rtrim($uri, '/');
$segments = array_values(array_filter(explode('/', $uri)));

// Kategori yang valid
$validCategories = ['calon-guru', 'ppdb', 'siswa'];

// Routing
if (empty($segments) || ($segments[0] === 'scores' && !isset($segments[1]))) {
    // GET /api/scores
    handleScoresList();

} elseif (isset($segments[0], $segments[1]) && $segments[0] === 'scores' && $segments[1] === 'summary') {
    // GET /api/scores/summary
    handleScoresSummary();

} elseif (isset($segments[0], $segments[1]) && $segments[0] === 'scores' && is_numeric($segments[1])) {
    // GET /api/scores/{user_id} -> khusus guru & staff
    handleScoresByUser((int)$segments[1]);

} elseif (isset($segments[0], $segments[1]) && $segments[0] === 'scores' && in_array($segments[1], $validCategories)) {

    if (isset($segments[2]) && is_numeric($segments[2])) {
        // GET /api/scores/{category}/{user_id} -> detail ujian user
        handleScoresByUserAndCategory($segments[1], (int)$segments[2]);

    } elseif ($_GET['title'] ?? null) {
        // GET /api/scores/{category}?title=... -> nilai peserta per ujian
        handleScoresByCategory($segments[1], $_GET['title']);

    } else {
        // GET /api/scores/{category} -> list ujian tersedia
        handleExamsByCategory($segments[1]);
    }

} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint tidak ditemukan']);
}