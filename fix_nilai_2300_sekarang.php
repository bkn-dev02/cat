<?php
require_once __DIR__ . '/app/helpers.php';

header('Content-Type: application/json');

try {
    $db = db();
    
    // Update nilai untuk session 2300
    $stmt = $db->prepare("
        UPDATE exam_sessions 
        SET score = 50, 
            is_best_score = 1 
        WHERE id = 2300
    ");
    $result = $stmt->execute();
    
    // Verify
    $stmt = $db->prepare("SELECT score FROM exam_sessions WHERE id = 2300");
    $stmt->execute();
    $newScore = $stmt->fetch()['score'];
    
    echo json_encode([
        'success' => true,
        'updated' => $result,
        'new_score' => $newScore,
        'message' => 'Nilai berhasil diupdate ke 50',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
