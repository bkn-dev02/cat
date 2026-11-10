<?php
/**
 * Fix method previewData agar return JSON bersih
 * Hapus setelah dijalankan!
 */
session_start();
require_once 'app/helpers.php';
if (($_GET['key'] ?? '') !== 'fix2026') die('Akses ditolak.');

$filePath = __DIR__ . '/app/Controllers/QuestionController.php';
$content  = file_get_contents($filePath);

$oldMethod = '    public function previewData($id)
    {
        requireAuth();
        requireRole([\'admin\', \'guru\']);

        header(\'Content-Type: application/json\');

        $stmt = db()->prepare("
            SELECT q.*, s.name as subject_name
            FROM questions q
            JOIN subjects s ON q.subject_id = s.id
            WHERE q.id = ?
        ");
        $stmt->execute([$id]);
        $question = $stmt->fetch();

        if (!$question) {
            echo json_encode([\'success\' => false, \'message\' => \'Soal tidak ditemukan\']);
            exit;
        }

        echo json_encode([\'success\' => true, \'question\' => $question]);
        exit;
    }';

$newMethod = '    public function previewData($id)
    {
        while (ob_get_level()) ob_end_clean();
        requireAuth();
        requireRole([\'admin\', \'guru\']);
        header(\'Content-Type: application/json; charset=utf-8\');
        header(\'Cache-Control: no-cache\');
        try {
            $stmt = db()->prepare("SELECT q.*, s.name as subject_name FROM questions q JOIN subjects s ON q.subject_id = s.id WHERE q.id = ?");
            $stmt->execute([(int)$id]);
            $question = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$question) { echo json_encode([\'success\' => false, \'message\' => \'Soal tidak ditemukan\']); exit; }
            array_walk_recursive($question, function(&$val) { if (is_string($val)) $val = mb_convert_encoding($val, \'UTF-8\', \'UTF-8\'); });
            echo json_encode([\'success\' => true, \'question\' => $question], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([\'success\' => false, \'message\' => $e->getMessage()]);
        }
        exit;
    }';

if (strpos($content, $oldMethod) !== false) {
    $content = str_replace($oldMethod, $newMethod, $content);
    file_put_contents($filePath, $content);
    $msg = '<p style="color:#16a34a;background:#f0fdf4;padding:12px;border-radius:8px;font-weight:700">✅ Berhasil! method previewData sudah diperbaiki.</p>';
} elseif (strpos($content, 'ob_end_clean') !== false) {
    $msg = '<p style="color:#2563eb;background:#eff6ff;padding:12px;border-radius:8px;font-weight:700">ℹ️ Sudah up to date.</p>';
} else {
    // Coba patch dengan regex — cari method previewData apapun bentuknya
    $pattern = '/(public function previewData\(\$id\)\s*\{)(.*?)(^\s*\})/ms';
    $newBody = '$1
        while (ob_get_level()) ob_end_clean();
        requireAuth();
        requireRole([\'admin\', \'guru\']);
        header(\'Content-Type: application/json; charset=utf-8\');
        try {
            $stmt = db()->prepare("SELECT q.*, s.name as subject_name FROM questions q JOIN subjects s ON q.subject_id = s.id WHERE q.id = ?");
            $stmt->execute([(int)$id]);
            $question = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$question) { echo json_encode([\'success\' => false, \'message\' => \'Soal tidak ditemukan\']); exit; }
            echo json_encode([\'success\' => true, \'question\' => $question], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) { echo json_encode([\'success\' => false, \'message\' => $e->getMessage()]); }
        exit;
    $3';
    $new = preg_replace($pattern, $newBody, $content, 1, $count);
    if ($count > 0) {
        file_put_contents($filePath, $new);
        $msg = '<p style="color:#16a34a;background:#f0fdf4;padding:12px;border-radius:8px;font-weight:700">✅ Berhasil via regex!</p>';
    } else {
        $msg = '<p style="color:#dc2626;background:#fef2f2;padding:12px;border-radius:8px;font-weight:700">❌ Gagal. Cek manual.</p>';
    }
}

$appUrl = config('app_url');
echo "<style>body{font-family:sans-serif;padding:20px;max-width:600px}</style>";
echo "<h2>🔧 Fix Preview JSON</h2>$msg";
echo "<br><a href='$appUrl/question' style='background:#10b981;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>→ Buka Bank Soal</a>";
echo "<p style='color:#94a3b8;font-size:12px;margin-top:16px'>Hapus file FIX_PREVIEW_JSON.php setelah selesai.</p>";
