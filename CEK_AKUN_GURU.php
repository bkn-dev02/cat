<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$message = null;

// Reset password jika diminta
if (isset($_GET['reset'])) {
    $uid = (int)$_GET['reset'];
    $newPass = password_hash('Guru123!', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$newPass, $uid]);
    $message = "✅ Password direset ke: <b>Guru123!</b>";
}

$stmt = $pdo->query("SELECT id, username, full_name, role, created_at FROM users WHERE role IN ('guru','admin') ORDER BY role, full_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Akun Guru</title>
<style>body{font-family:sans-serif;padding:20px;max-width:900px;margin:auto}
table{border-collapse:collapse;width:100%}td,th{border:1px solid #e2e8f0;padding:10px;font-size:13px}
th{background:#f1f5f9}.alert{background:#d1fae5;color:#065f46;padding:12px;border-radius:8px;margin-bottom:16px}
.btn{padding:5px 12px;background:#6366f1;color:white;border:none;border-radius:4px;cursor:pointer;text-decoration:none;font-size:12px}</style>
</head>
<body>
<h2>👨‍🏫 Daftar Akun Guru & Admin</h2>
<?php if ($message): ?><div class="alert"><?= $message ?></div><?php endif; ?>
<table>
<tr><th>ID</th><th>Username</th><th>Nama</th><th>Role</th><th>Dibuat</th><th>Aksi</th></tr>
<?php foreach ($users as $u): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= htmlspecialchars($u['full_name']) ?></td>
    <td><b><?= $u['role'] ?></b></td>
    <td><?= $u['created_at'] ?></td>
    <td><a href="?reset=<?= $u['id'] ?>" class="btn" onclick="return confirm('Reset password ke Guru123!?')">Reset Password</a></td>
</tr>
<?php endforeach; ?>
</table>
</body></html>
