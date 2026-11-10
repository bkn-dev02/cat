<?php
require_once 'config.php';

echo "=== DIAGNOSA PHILIPE CORNELLIUS GOKLAS HAMONANGAN SIHOTANG ===\n\n";

$user_id = 1692;

// 1. Cek data user
echo "1. CEK DATA USER:\n";
$stmt = $pdo->prepare("SELECT id, username, name, class FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);
echo "\n";

// 2. Cek semua exam sessions
echo "2. CEK SEMUA EXAM SESSIONS:\n";
$stmt = $pdo->prepare("
    SELECT es.id, es.exam_id, e.title, es.status, es.start_time, es.end_time, es.score
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    WHERE es.user_id = ?
    ORDER BY es.id DESC
");
$stmt->execute([$user_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total sessions: " . count($sessions) . "\n";
foreach ($sessions as $session) {
    echo "Session ID: {$session['id']}, Exam: {$session['title']}, Status: {$session['status']}\n";
}
echo "\n";

// 3. Cek exam assignments untuk user ini
echo "3. CEK EXAM ASSIGNMENTS:\n";
$stmt = $pdo->prepare("
    SELECT ea.id, ea.exam_id, e.title, ea.class_id, c.name as class_name
    FROM exam_assignments ea
    JOIN exams e ON ea.exam_id = e.id
    LEFT JOIN classes c ON ea.class_id = c.id
    WHERE ea.class_id IN (SELECT class_id FROM users WHERE id = ?)
    ORDER BY ea.id DESC
");
$stmt->execute([$user_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total assignments untuk kelas siswa: " . count($assignments) . "\n";
foreach ($assignments as $assignment) {
    echo "Assignment ID: {$assignment['id']}, Exam: {$assignment['title']}, Class: {$assignment['class_name']}\n";
}
echo "\n";

// 4. Cek apakah ada session yang suspended
echo "4. CEK SESSION SUSPENDED:\n";
$stmt = $pdo->prepare("
    SELECT es.id, e.title, es.status, es.suspended_at, es.suspension_reason
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    WHERE es.user_id = ? AND es.status = 'suspended'
");
$stmt->execute([$user_id]);
$suspended = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($suspended) > 0) {
    echo "ADA SESSION SUSPENDED!\n";
    foreach ($suspended as $s) {
        echo "Session ID: {$s['id']}, Exam: {$s['title']}, Reason: {$s['suspension_reason']}\n";
    }
} else {
    echo "Tidak ada session suspended\n";
}
echo "\n";

// 5. Cek apakah session 2853 masih ada
echo "5. CEK SESSION 2853:\n";
$stmt = $pdo->prepare("SELECT * FROM exam_sessions WHERE id = 2853");
$stmt->execute();
$session_2853 = $stmt->fetch(PDO::FETCH_ASSOC);
if ($session_2853) {
    echo "SESSION 2853 MASIH ADA!\n";
    print_r($session_2853);
} else {
    echo "Session 2853 sudah terhapus\n";
}
echo "\n";

// 6. Cek exam yang tersedia untuk user
echo "6. CEK EXAM YANG TERSEDIA:\n";
$stmt = $pdo->prepare("
    SELECT DISTINCT e.id, e.title, e.status
    FROM exams e
    JOIN exam_assignments ea ON e.id = ea.exam_id
    JOIN users u ON ea.class_id = u.class_id
    WHERE u.id = ?
    ORDER BY e.id DESC
");
$stmt->execute([$user_id]);
$available_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total exam tersedia: " . count($available_exams) . "\n";
foreach ($available_exams as $exam) {
    echo "Exam ID: {$exam['id']}, Title: {$exam['title']}, Status: {$exam['status']}\n";
}
echo "\n";

echo "=== SOLUSI ===\n";
echo "Pilih tindakan:\n";
echo "1. Hapus SEMUA session user ini (fresh start)\n";
echo "2. Reset session suspended saja\n";
echo "3. Cek lebih detail exam tertentu\n";
echo "\nJalankan dengan parameter: php FIX_PHILIPE_CORNELLIUS.php [action]\n";
echo "action: reset_all, reset_suspended, atau detail\n";

if (isset($argv[1])) {
    $action = $argv[1];
    
    if ($action === 'reset_all') {
        echo "\n=== MENGHAPUS SEMUA SESSION ===\n";
        $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        echo "Berhasil menghapus " . $stmt->rowCount() . " session\n";
        echo "User sekarang bisa mulai ujian dari awal\n";
    }
    
    if ($action === 'reset_suspended') {
        echo "\n=== RESET SESSION SUSPENDED ===\n";
        $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE user_id = ? AND status = 'suspended'");
        $stmt->execute([$user_id]);
        echo "Berhasil menghapus " . $stmt->rowCount() . " session suspended\n";
    }
}
