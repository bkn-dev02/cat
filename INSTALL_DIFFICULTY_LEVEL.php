<?php
/**
 * INSTALL DIFFICULTY LEVEL
 * Jalankan sekali untuk menambahkan kolom difficulty_level ke tabel questions
 */
require_once __DIR__ . '/app/helpers.php';

$pdo = db();
$results = [];

// Tambah kolom difficulty_level ke tabel questions (jika belum ada)
try {
    $check = $pdo->query("SHOW COLUMNS FROM questions LIKE 'difficulty_level'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN difficulty_level ENUM('mudah','sedang','sulit','hots') DEFAULT NULL AFTER sub_topic");
        $results[] = ['status' => 'OK', 'msg' => 'Kolom difficulty_level berhasil ditambahkan ke tabel questions'];
    } else {
        $results[] = ['status' => 'SKIP', 'msg' => 'Kolom difficulty_level sudah ada'];
    }
} catch (Exception $e) {
    $results[] = ['status' => 'ERROR', 'msg' => 'Kolom difficulty_level: ' . $e->getMessage()];
}

// Tambah index untuk performa filtering
try {
    $checkIdx = $pdo->query("SHOW INDEX FROM questions WHERE Key_name = 'idx_questions_difficulty'")->fetch();
    if (!$checkIdx) {
        $pdo->exec("CREATE INDEX idx_questions_difficulty ON questions(difficulty_level)");
        $results[] = ['status' => 'OK', 'msg' => 'Index difficulty_level berhasil dibuat'];
    } else {
        $results[] = ['status' => 'SKIP', 'msg' => 'Index difficulty_level sudah ada'];
    }
} catch (Exception $e) {
    $results[] = ['status' => 'SKIP', 'msg' => 'Index: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install Difficulty Level</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; }
        .ok { color: #16a34a; background: #f0fdf4; padding: 8px 12px; border-radius: 6px; margin: 6px 0; }
        .error { color: #dc2626; background: #fef2f2; padding: 8px 12px; border-radius: 6px; margin: 6px 0; }
        .skip { color: #92400e; background: #fffbeb; padding: 8px 12px; border-radius: 6px; margin: 6px 0; }
        h2 { color: #1e293b; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
    </style>
</head>
<body>
    <h2>🔧 Install Difficulty Level</h2>
    <?php foreach ($results as $r): ?>
        <div class="<?= strtolower($r['status']) ?>">
            <strong><?= $r['status'] ?>:</strong> <?= htmlspecialchars($r['msg']) ?>
        </div>
    <?php endforeach; ?>
    <br>
    <a href="<?= config('app_url') ?>/question" class="btn">→ Buka Bank Soal</a>
    <p style="color:#64748b; font-size:0.85rem; margin-top:20px;">File ini bisa dihapus setelah instalasi berhasil.</p>
</body>
</html>
