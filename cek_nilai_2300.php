<?php
require_once __DIR__ . '/app/helpers.php';

header('Content-Type: application/json');

try {
    $db = db();
    
    // Cek nilai di database
    $stmt = $db->prepare("
        SELECT 
            es.id,
            es.score as nilai_database,
            es.status,
            (SELECT COALESCE(SUM(
                CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN q.points ELSE 0 END
            ), 0)
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = es.id) as nilai_dihitung,
            (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as jumlah_jawaban
        FROM exam_sessions es
        WHERE es.id = 2300
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'session_id' => 2300,
        'nilai_database' => $result['nilai_database'],
        'nilai_dihitung' => $result['nilai_dihitung'],
        'status' => $result['status'],
        'jumlah_jawaban' => $result['jumlah_jawaban'],
        'match' => ($result['nilai_database'] == $result['nilai_dihitung']),
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
