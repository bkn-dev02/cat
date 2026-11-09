<?php
/**
 * Reset password satu siswa berdasarkan email
 * Hapus file ini setelah selesai!
 */
$config = require __DIR__ . '/config.production.php';

$pdo = new PDO(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $newPass  = $_POST['password'] ?? 'Ppdb2026!';

    $stmt = $pdo->prepare("SELECT id, full_name, kelas FROM users WHERE username = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $result = ['error' => "Email <b>$email</b> tidak ditemukan di CAT."];
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, kelas = 'PPDB-REGULER' WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);
        $result = ['success' => true, 'nama' => $user['full_name'], 'email' => $email, 'pass' => $newPass];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password Siswa</title>
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 40px auto; padding: 20px; }
        input { width: 100%; padding: 8px; margin: 6px 0 14px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #6366f1; color: white; padding: 10px 24px; border: none; border-radius: 4px; cursor: pointer; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-top: 20px; }
        .ok { color: green; } .err { color: red; }
    </style>
</head>
<body>
    <h2>🔑 Reset Password Siswa</h2>
    <form method="POST">
        <label>Email Siswa:</label>
        <input type="text" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="email@gmail.com" required>
        <label>Password Baru:</label>
        <input type="text" name="password" value="Ppdb2026!" required>
        <button type="submit">Reset Password</button>
    </form>

    <?php if ($result): ?>
    <div class="box">
        <?php if (!empty($result['success'])): ?>
            <p class="ok">✅ Berhasil! Password <b><?= htmlspecialchars($result['nama']) ?></b> direset.</p>
            <p>Email: <code><?= htmlspecialchars($result['email']) ?></code></p>
            <p>Password baru: <code><?= htmlspecialchars($result['pass']) ?></code></p>
            <p>Kelas diupdate ke: <code>PPDB-REGULER</code></p>
        <?php else: ?>
            <p class="err">❌ <?= $result['error'] ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>
