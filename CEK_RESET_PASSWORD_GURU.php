<?php
/**
 * Cek & Reset Password Guru
 * Akses: /CEK_RESET_PASSWORD_GURU.php
 */

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
    $config['db_user'],
    $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$username = trim($_POST['username'] ?? $_GET['username'] ?? 'abeltha@guru.ubkn.sch.id');
$newPassword = trim($_POST['new_password'] ?? '');
$message = null;

// Cek akun
$stmt = $pdo->prepare("SELECT id, username, full_name, role, password FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset']) && $user && $newPassword) {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $upd->execute([$hashed, $user['id']]);
    $message = ['type' => 'success', 'text' => "✅ Password berhasil direset untuk <strong>" . htmlspecialchars($user['full_name']) . "</strong>. Silakan login dengan password baru."];
    // Refresh user data
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Proses test password
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test']) && $user) {
    $testPwd = $_POST['test_password'] ?? '';
    if (password_verify($testPwd, $user['password'])) {
        $testResult = ['type' => 'success', 'text' => '✅ Password <strong>' . htmlspecialchars($testPwd) . '</strong> COCOK dengan yang tersimpan di database. Masalah mungkin ada di tempat lain (capslock, spasi, dll).'];
    } else {
        $testResult = ['type' => 'error', 'text' => '❌ Password <strong>' . htmlspecialchars($testPwd) . '</strong> TIDAK COCOK. Silakan reset password di bawah.'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cek & Reset Password Guru</title>
<style>
    body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; padding: 24px; color: #1e293b; }
    .container { max-width: 600px; margin: 0 auto; }
    h2 { margin-bottom: 4px; }
    .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.1); margin-bottom: 16px; }
    label { font-weight: 600; font-size: .875rem; display: block; margin-bottom: 6px; }
    input[type=text], input[type=password] {
        width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0;
        border-radius: 8px; font-size: .95rem; margin-bottom: 12px;
    }
    .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: .9rem; }
    .btn-primary { background: #2563eb; color: white; }
    .btn-danger  { background: #dc2626; color: white; }
    .alert-success { background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
    .alert-error   { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    td, th { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; text-align: left; }
    th { color: #64748b; font-weight: 600; }
    .badge { padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
    .badge-ok  { background: #d1fae5; color: #065f46; }
    .badge-bad { background: #fee2e2; color: #991b1b; }
    .back-link { display: inline-block; margin-bottom: 20px; color: #2563eb; text-decoration: none; font-weight: 600; }
</style>
</head>
<body>
<div class="container">
    <a href="/exam" class="back-link">← Kembali ke Ujian</a>
    <h2>🔑 Cek & Reset Password Guru</h2>
    <p style="color:#64748b; margin-bottom:20px">Diagnosa dan perbaiki akun yang tidak bisa login.</p>

    <?php if ($message): ?>
        <div class="alert-<?= $message['type'] ?>"><?= $message['text'] ?></div>
    <?php endif; ?>

    <!-- Form cari akun -->
    <div class="card">
        <form method="GET">
            <label>Username / Email</label>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="contoh: guru@sekolah.sch.id">
            <button type="submit" class="btn btn-primary">🔍 Cek Akun</button>
        </form>
    </div>

    <?php if ($username): ?>
        <?php if (!$user): ?>
            <div class="alert-error">❌ Akun dengan username <strong><?= htmlspecialchars($username) ?></strong> tidak ditemukan di database.</div>
        <?php else: ?>
            <!-- Info akun -->
            <div class="card">
                <h3 style="margin-top:0">📋 Info Akun</h3>
                <table>
                    <tr><th>ID</th><td><?= $user['id'] ?></td></tr>
                    <tr><th>Username</th><td><?= htmlspecialchars($user['username']) ?></td></tr>
                    <tr><th>Nama</th><td><?= htmlspecialchars($user['full_name']) ?></td></tr>
                    <tr><th>Role</th><td><?= htmlspecialchars($user['role']) ?></td></tr>
                    <tr>
                        <th>Format Password</th>
                        <td>
                            <?php
                            $pwd = $user['password'];
                            $isHashed = (strlen($pwd) >= 60 && str_starts_with($pwd, '$2'));
                            ?>
                            <?php if ($isHashed): ?>
                                <span class="badge badge-ok">✅ bcrypt (benar)</span>
                            <?php else: ?>
                                <span class="badge badge-bad">❌ Plain text / format salah — harus direset</span>
                                <div style="margin-top:6px; font-size:.8rem; color:#64748b">
                                    Nilai saat ini: <code><?= htmlspecialchars(substr($pwd, 0, 30)) ?>...</code>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Form test password -->
            <div class="card">
                <h3 style="margin-top:0">🧪 Test Password</h3>
                <p style="color:#64748b; font-size:.875rem; margin-top:0">Cek apakah password yang diketik cocok dengan yang tersimpan.</p>
                <?php if ($testResult): ?>
                    <div class="alert-<?= $testResult['type'] ?>" style="margin-bottom:12px"><?= $testResult['text'] ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                    <label>Password yang ingin ditest</label>
                    <input type="text" name="test_password" placeholder="Ketik password yang dicoba guru" value="">
                    <button type="submit" name="test" value="1" class="btn btn-primary">🧪 Test Sekarang</button>
                </form>
            </div>

            <!-- Form reset password -->
            <div class="card">
                <h3 style="margin-top:0">🔄 Reset Password</h3>
                <form method="POST">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                    <label>Password Baru</label>
                    <input type="text" name="new_password" placeholder="Masukkan password baru" value="<?= htmlspecialchars($newPassword) ?>">
                    <button type="submit" name="reset" value="1" class="btn btn-danger"
                        onclick="return confirm('Reset password untuk <?= htmlspecialchars($user['full_name']) ?>?')">
                        🔑 Reset Password Sekarang
                    </button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <p style="color:#94a3b8; font-size:.75rem; text-align:center; margin-top:24px">
        Script ini hanya untuk Admin. Jangan bagikan URL ini ke siswa/guru.
    </p>
</div>
</body>
</html>
