<?php
/**
 * INSTALL QUESTION GROUPS
 * Jalankan sekali untuk membuat tabel question_groups dan kolom group_id di questions
 */
require_once __DIR__ . '/app/helpers.php';

$pdo = db();
$results = [];

// 1. Buat tabel question_groups
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS question_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            subject_id INT NOT NULL,
            type ENUM('multiple_choice','short_answer','short_answer_text','essay','listening') NOT NULL DEFAULT 'multiple_choice',
            sub_topic VARCHAR(255) NULL,
            default_points INT NOT NULL DEFAULT 1,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $results[] = ['status' => 'OK', 'msg' => 'Tabel question_groups berhasil dibuat'];
} catch (Exception $e) {
    $results[] = ['status' => 'ERROR', 'msg' => 'Tabel question_groups: ' . $e->getMessage()];
}

// 2. Tambah kolom group_id ke tabel questions (jika belum ada)
try {
    $check = $pdo->query("SHOW COLUMNS FROM questions LIKE 'group_id'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN group_id INT NULL DEFAULT NULL AFTER id");
        $pdo->exec("ALTER TABLE questions ADD CONSTRAINT fk_questions_group FOREIGN KEY (group_id) REFERENCES question_groups(id) ON DELETE SET NULL");
        $results[] = ['status' => 'OK', 'msg' => 'Kolom group_id berhasil ditambahkan ke tabel questions'];
    } else {
        $results[] = ['status' => 'SKIP', 'msg' => 'Kolom group_id sudah ada'];
    }
} catch (Exception $e) {
    $results[] = ['status' => 'ERROR', 'msg' => 'Kolom group_id: ' . $e->getMessage()];
}

// 3. Tambah index untuk performa
try {
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_questions_group_id ON questions(group_id)");
    $results[] = ['status' => 'OK', 'msg' => 'Index group_id berhasil dibuat'];
} catch (Exception $e) {
    $results[] = ['status' => 'SKIP', 'msg' => 'Index: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install Question Groups</title>
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
    <h2>🔧 Install Question Groups</h2>
    <?php foreach ($results as $r): ?>
        <div class="<?= strtolower($r['status']) ?>">
            <strong><?= $r['status'] ?>:</strong> <?= htmlspecialchars($r['msg']) ?>
        </div>
    <?php endforeach; ?>
    <br>
    <a href="<?= config('app_url') ?>/questionGroup" class="btn">→ Buka Halaman Grup Soal</a>
    <p style="color:#64748b; font-size:0.85rem; margin-top:20px;">File ini bisa dihapus setelah instalasi berhasil.</p>
</body>
</html>
