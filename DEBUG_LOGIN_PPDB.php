<?php
/**
 * Debug login siswa PPDB
 * Hapus file ini setelah selesai!
 */
$config = require __DIR__ . '/config.production.php';

$pdo = new PDO(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$result = null;
$testEmail = '';
$testPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail    = trim($_POST['email'] ?? '');
    $testPassword = $_POST['password'] ?? '';

    // Cari user di CAT
    $stmt = $pdo->prepare("SELECT id, username, full_name, kelas, password FROM users WHERE username = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Coba cari dengan LIKE (siapa tahu ada spasi/case berbeda)
        $stmt = $pdo->prepare("SELECT id, username, full_name, kelas, password FROM users WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$testEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['note'] = 'Tidak ditemukan exact match, dicari dengan LOWER()';
    }

    if ($user) {
        $result['found'] = true;
        $result['id']       = $user['id'];
        $result['username'] = $user['username'];
        $result['nama']     = $user['full_name'];
        $result['kelas']    = $user['kelas'];
        $result['pass_prefix'] = substr($user['password'], 0, 10) . '...';
        $result['pass_len']    = strlen($user['password']);
        $result['password_verify'] = password_verify($testPassword, $user['password']) ? '✅ COCOK' : '❌ TIDAK COCOK';
    } else {
        $result['found'] = false;
        $result['msg'] = 'User dengan email tersebut TIDAK ADA di database CAT';

        // Cek apakah ada di PPDB
        try {
            $ppdb = new PDO(
                'mysql:host=' . $config['ppdb_db_host'] . ';dbname=' . $config['ppdb_db_name'] . ';charset=utf8mb4',
                $config['ppdb_db_user'], $config['ppdb_db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $stmt = $ppdb->prepare("SELECT id, email FROM users WHERE email = ?");
            $stmt->execute([$testEmail]);
            $ppdbUser = $stmt->fetch(PDO::FETCH_ASSOC);
            $result['in_ppdb'] = $ppdbUser ? '✅ Ada di database PPDB (tapi belum di-sync ke CAT)' : '❌ Tidak ada di database PPDB juga';
        } catch (PDOException $e) {
            $result['in_ppdb'] = 'Gagal cek PPDB: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug Login PPDB</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; }
        input { width: 100%; padding: 8px; margin: 6px 0 14px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #6366f1; color: white; padding: 10px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-top: 20px; }
        .ok { color: green; } .err { color: red; } .warn { color: orange; }
    </style>
</head>
<body>
    <h2>🔍 Debug Login Siswa PPDB</h2>
    <p>Masukkan email dan password siswa yang gagal login untuk dicek.</p>

    <form method="POST">
        <label>Email (Username):</label>
        <input type="text" name="email" value="<?= htmlspecialchars($testEmail) ?>" placeholder="email@gmail.com" required>

        <label>Password:</label>
        <input type="password" name="password" placeholder="password siswa" required>

        <button type="submit">Cek Login</button>
    </form>

    <?php if ($result !== null): ?>
    <div class="box">
        <h3>Hasil Pengecekan</h3>
        <?php if (!empty($result['note'])): ?>
            <p class="warn">⚠️ <?= $result['note'] ?></p>
        <?php endif; ?>

        <?php if ($result['found']): ?>
            <p class="ok">✅ User ditemukan di database CAT</p>
            <table>
                <tr><td><b>ID</b></td><td><?= $result['id'] ?></td></tr>
                <tr><td><b>Username</b></td><td><?= htmlspecialchars($result['username']) ?></td></tr>
                <tr><td><b>Nama</b></td><td><?= htmlspecialchars($result['nama']) ?></td></tr>
                <tr><td><b>Kelas</b></td><td><?= htmlspecialchars($result['kelas']) ?></td></tr>
                <tr><td><b>Password prefix</b></td><td><code><?= $result['pass_prefix'] ?></code> (<?= $result['pass_len'] ?> char)</td></tr>
                <tr><td><b>Verifikasi Password</b></td><td><b><?= $result['password_verify'] ?></b></td></tr>
            </table>
        <?php else: ?>
            <p class="err">❌ <?= $result['msg'] ?></p>
            <?php if (!empty($result['in_ppdb'])): ?>
                <p><?= $result['in_ppdb'] ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>
