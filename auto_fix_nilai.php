<?php
/**
 * AUTO FIX NILAI - BACKGROUND PROCESS
 * 
 * Script ini akan otomatis fix semua nilai yang 0
 * Bisa dijalankan via cron job atau manual
 * 
 * CRON JOB SETUP (Hostinger):
 * 1. Login ke Hostinger panel
 * 2. Cari "Cron Jobs" di menu
 * 3. Tambah cron job baru:
 *    - Interval: setiap 5 menit
 *    - Command: curl https://cat-enterprise.binakasihnusantara.sch.id/auto_fix_nilai.php
 */

require_once __DIR__ . '/app/helpers.php';

// Set header untuk JSON response
header('Content-Type: application/json');

try {
    $db = db();
    
    // Fix semua nilai yang masih 0 tapi punya jawaban
    // Limit 100 per run untuk avoid timeout
    $stmt = $db->prepare("
        UPDATE exam_sessions es
        SET es.score = (
            SELECT COALESCE(SUM(
                CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN q.points ELSE 0 END
            ), 0)
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = es.id
        ),
        es.is_best_score = 1
        WHERE es.score = 0
        AND es.status IN ('submitted', 'graded', 'completed')
        AND EXISTS (SELECT 1 FROM answers WHERE session_id = es.id)
        LIMIT 100
    ");
    
    $stmt->execute();
    $fixed = $stmt->rowCount();
    
    // Log hasil
    error_log("Auto fix nilai: Fixed $fixed sessions at " . date('Y-m-d H:i:s'));
    
    echo json_encode([
        'success' => true,
        'fixed' => $fixed,
        'message' => $fixed > 0 ? "Berhasil memperbaiki $fixed nilai" : "Tidak ada nilai yang perlu diperbaiki",
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Auto fix nilai error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
