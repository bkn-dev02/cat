<?php
require_once __DIR__ . '/../config/api.php';
function authenticate(): void {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $apiKey  = $headers['x-api-key'] ?? null;
    if (!$apiKey || !in_array($apiKey, VALID_API_KEYS, true)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error'   => 'Unauthorized',
            'message' => 'API key tidak valid atau tidak ditemukan.',
        ]);
        exit;
    }
}