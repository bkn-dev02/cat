<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$message = null;
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $email) {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE username = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = ['type'=>'error', 'text'=>"❌ Email tidak ditemukan: $email"];
    } else {
        // Ambil semua session active yang belum submit
        $stmt = $pdo->prepare("
            SELECT es.id, e.title FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            WHERE es.user_id = ? AND es.submitted_at IS NULL
            AND es.status NOT IN ('submitted','completed')
        ");
        $stmt->execute([$user['id']]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($sessions)) {
            $message = ['type'=>'error', 'text'=>"Tidak ada session aktif yang perlu di-submit untuk siswa ini."];
        } else {
            $done = [];
            foreach ($sessions as $s) {
                // Hitung nilai dari jawaban yang sudah ada
                $stmt2 = $pdo->prepare("
                    SELECT a.answer, q.correct_answer, q.points
                    FROM answers a
                    JOIN questions q ON a.question_id = q.id
                    WHERE a.session_id = ?
                ");
                $stmt2->execute([$s['id']]);
                $answers = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                $score = 0;
                foreach ($answers as $a) {
                    if (strtolower($a['answer']) === strtolower($a['correct_answer'])) {
                        $score += $a['points'];
                    }
                }

                // Force submit
                $pdo->prepare("
                    UPDATE exam_sessions 
                    SET status='submitted', submitted_at=NOW(), score=?, is_best_score=1
                    WHERE id=?
                ")->execute([$score, $s['id']]);

                $done[] = "{$s['title']} — Nilai: <b>$score</b> (dari ".count($answers)." jawaban)";
            }
            $message = ['type'=>'success', 'text'=>"✅ Force submit berhasil untuk <b>{$user['full_name']}</b>:<br>" . implode('<br>', $done)];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Force Submit</title>
<style>body{font-family:sans-serif;background:#f8fafc;padding:20px}.container{max-width:500px;margin:40px auto}.card{background:white;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.1)}label{font-weight:600;display:block;margin-bottom:6px}input{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:1rem;box-sizing:border-box;margin-bottom:14px}button{width:100%;padding:12px;background:#ef4444;color:white;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer}.alert{padding:14px;border-radius:8px;margin-bottom:16px;line-height:1.6}.ok{background:#d1fae5;color:#065f46}.err{background:#fee2e2;color:#991b1b}</style>
</head>
<body>
<div class="container">
    <h2>⚡ Force Submit Ujian Siswa</h2>
    <p style="color:#64748b">Gunakan ini untuk submit jawaban siswa yang belum sempat kumpulkan ujian.</p>

    <?php if ($message): ?>
        <div class="alert <?= $message['type']==='success'?'ok':'err' ?>"><?= $message['text'] ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" onsubmit="return confirm('Force submit ujian siswa ini?')">
            <label>Email Siswa:</label>
            <input type="text" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="email@gmail.com" autofocus required>
            <button type="submit">⚡ Force Submit Sekarang</button>
        </form>
    </div>
</div>
</body>
</html>
