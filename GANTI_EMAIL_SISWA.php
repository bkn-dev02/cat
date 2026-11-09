<?php
/**
 * Ganti email/username siswa di CAT
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
    $emailLama = trim($_POST['email_lama'] ?? '');
    $emailBaru = trim($_POST['email_baru'] ?? '');

    if (empty($emailLama) || empty($emailBaru)) {
        $result = ['error' => 'Kedua field wajib diisi.'];
    } else {
        // Cek email lama ada
        $stmt = $pdo->prepare("SELECT id, username, full_name, kelas FROM users WHERE username = ?");
        $stmt->execute([$emailLama]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $result = ['error' => "Email lama <b>$emailLama</b> tidak ditemukan di CAT."];
        } else {
            // Cek email baru belum dipakai
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$emailBaru]);
            if ($stmt->fetch()) {
                $result = ['error' => "Email baru <b>$emailBaru</b> sudah dipakai akun lain."];
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute([$emailBaru, $user['id']]);
                $result = ['success' => true, 'nama' => $user['full_name'], 'kelas' => $user['kelas'], 'email_baru' => $emailBaru];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ganti Email Siswa</title>
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 40px auto; padding: 20px; }
        input { width: 100%; padding: 8px; margin: 6px 0 14px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #6366f1; color: white; padding: 10px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-top: 20px; }
        .ok { color: green; } .err { color: red; }
    </style>
</head>
<body>
    <h2>✏️ Ganti Email Login Siswa</h2>

    <form method="POST">
        <label>Email Lama (saat ini):</label>
        <input type="text" name="email_lama" value="<?= htmlspecialchars($_POST['email_lama'] ?? '') ?>" placeholder="email.lama@gmail.com" required>

        <label>Email Baru:</label>
        <input type="text" name="email_baru" value="<?= htmlspecialchars($_POST['email_baru'] ?? '') ?>" placeholder="email.baru@gmail.com" required>

        <button type="submit">Ganti Email</button>
    </form>

    <?php if ($result): ?>
    <div class="box">
        <?php if (!empty($result['success'])): ?>
            <p class="ok">✅ Berhasil! Email login <b><?= htmlspecialchars($result['nama']) ?></b> (<?= $result['kelas'] ?>) diubah ke:<br>
            <code><?= htmlspecialchars($result['email_baru']) ?></code></p>
            <p>Password tetap sama: <code>Ppdb2026!</code></p>
        <?php else: ?>
            <p class="err">❌ <?= $result['error'] ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>
