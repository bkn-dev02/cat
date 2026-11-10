<?php
require_once 'app/helpers.php';
define('ADMIN_SECRET', 'bkn2026');

$pdo = db();
$message = '';
$msgType = '';
$results = [];

// Proses reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    if (($_POST['secret'] ?? '') !== ADMIN_SECRET) {
        $message = 'Password salah!';
        $msgType = 'error';
    } else {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId) {
            $stmt = $pdo->prepare("UPDATE users SET active_session_id = NULL WHERE id = ? AND role IN ('siswa','student')");
            $stmt->execute([$userId]);
            $message = $stmt->rowCount() > 0 ? '✅ Sesi berhasil direset. Siswa bisa login kembali.' : '❌ Akun tidak ditemukan.';
            $msgType = $stmt->rowCount() > 0 ? 'success' : 'error';
        }
    }
}

// Proses pencarian
$keyword = trim($_GET['q'] ?? '');
if ($keyword !== '') {
    $stmt = $pdo->prepare("
        SELECT id, username, full_name, kelas, active_session_id, last_login_at
        FROM users
        WHERE role IN ('siswa','student')
        AND (username LIKE ? OR full_name LIKE ?)
        ORDER BY full_name ASC
        LIMIT 20
    ");
    $like = '%' . $keyword . '%';
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Sesi Siswa</title>
<style>
*{box-sizing:border-box;font-family:'Segoe UI',sans-serif;margin:0;padding:0}
body{background:#f1f5f9;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:2rem}
.card{background:white;border-radius:16px;padding:2rem;width:100%;max-width:560px;box-shadow:0 4px 24px rgba(0,0,0,0.1)}
h2{font-size:1.4rem;color:#1e293b;margin-bottom:.25rem}
.sub{color:#64748b;font-size:.85rem;margin-bottom:1.5rem}
.search-row{display:flex;gap:.5rem;margin-bottom:1rem}
.search-row input{flex:1;padding:10px 14px;border:2px solid #e2e8f0;border-radius:8px;font-size:15px}
.search-row input:focus{outline:none;border-color:#3b82f6}
.search-row button{padding:10px 18px;background:#3b82f6;color:white;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer}
.msg{padding:10px 14px;border-radius:8px;margin-bottom:1rem;font-size:.9rem;font-weight:500}
.success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
.error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.student-card{border:1px solid #e2e8f0;border-radius:10px;padding:1rem;margin-bottom:.75rem;display:flex;justify-content:space-between;align-items:center;gap:1rem}
.student-info .name{font-weight:700;color:#1e293b;font-size:.95rem}
.student-info .meta{color:#64748b;font-size:.82rem;margin-top:2px}
.status-badge{font-size:.75rem;padding:3px 10px;border-radius:20px;font-weight:600;white-space:nowrap}
.locked{background:#fef3c7;color:#92400e}
.free{background:#dcfce7;color:#166534}
.btn-reset{padding:8px 16px;background:#ef4444;color:white;border:none;border-radius:7px;font-size:.85rem;font-weight:600;cursor:pointer}
.btn-reset:hover{background:#dc2626}
.empty{color:#94a3b8;font-size:.9rem;text-align:center;padding:1.5rem 0}
</style>
</head>
<body>
<div class="card">
    <h2>🔑 Reset Sesi Siswa</h2>
    <p class="sub">Cari siswa berdasarkan nama atau username, lalu klik Reset.</p>

    <?php if ($message): ?>
        <div class="msg <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="GET" class="search-row">
        <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama atau username siswa..." autofocus>
        <button type="submit">Cari</button>
    </form>

    <?php if ($keyword !== ''): ?>
        <?php if (empty($results)): ?>
            <div class="empty">Tidak ada siswa ditemukan untuk "<strong><?= htmlspecialchars($keyword) ?></strong>"</div>
        <?php else: ?>
            <?php foreach ($results as $s): ?>
            <div class="student-card">
                <div class="student-info">
                    <div class="name"><?= htmlspecialchars($s['full_name'] ?? $s['username']) ?></div>
                    <div class="meta">
                        @<?= htmlspecialchars($s['username']) ?>
                        <?php if ($s['kelas']): ?> &nbsp;·&nbsp; <?= htmlspecialchars($s['kelas']) ?><?php endif; ?>
                        <?php if ($s['last_login_at']): ?> &nbsp;·&nbsp; Login: <?= date('d/m H:i', strtotime($s['last_login_at'])) ?><?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.4rem">
                    <span class="status-badge <?= $s['active_session_id'] ? 'locked' : 'free' ?>">
                        <?= $s['active_session_id'] ? '🔒 Terkunci' : '✅ Bebas' ?>
                    </span>
                    <?php if ($s['active_session_id']): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset">
                        <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="secret" id="secret-<?= $s['id'] ?>">
                        <button type="submit" class="btn-reset"
                            onclick="document.getElementById('secret-<?= $s['id'] ?>').value=prompt('Password admin:');return document.getElementById('secret-<?= $s['id'] ?>').value!==''&&document.getElementById('secret-<?= $s['id'] ?>').value!==null">
                            Reset Sesi
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
