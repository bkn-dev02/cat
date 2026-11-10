<?php
/**
 * Buat Akun: Antonius Ginting
 * Role: Pembina Yayasan (admin)
 * Dibuat: <?= date('Y-m-d') ?>
 */

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
    $config['db_user'],
    $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$nama     = 'Antonius Ginting';
$username = 'antonius.ginting@pembina.binakasihnusantara.sch.id';
$password = 'Pembina2026!';
$role     = 'admin'; // admin = bisa login dari browser biasa
$kelas    = 'PEMBINA-YAYASAN';

echo "<style>
    body { font-family: sans-serif; padding: 30px; max-width: 600px; margin: auto; background: #f8fafc; }
    .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    h2 { color: #1e293b; margin-top: 0; }
    .ok  { color: #16a34a; font-weight: bold; }
    .skip { color: #d97706; font-weight: bold; }
    .info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; margin-top: 20px; }
    code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 14px; }
    table { border-collapse: collapse; width: 100%; margin-top: 16px; }
    td, th { border: 1px solid #e2e8f0; padding: 10px 14px; text-align: left; }
    th { background: #f1f5f9; color: #475569; }
</style>";

echo "<div class='card'>";
echo "<h2>🏛️ Buat Akun Pembina Yayasan</h2>";

// Cek apakah username sudah ada
$stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE username = ?");
$stmt->execute([$username]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    echo "<p class='skip'>⚠️ Akun dengan username <code>{$username}</code> sudah ada.</p>";
    echo "<table>
        <tr><th>ID</th><td>{$existing['id']}</td></tr>
        <tr><th>Nama</th><td>{$existing['full_name']}</td></tr>
        <tr><th>Role</th><td>{$existing['role']}</td></tr>
        <tr><th>Username</th><td>{$username}</td></tr>
    </table>";
} else {
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $pdo->prepare("
        INSERT INTO users (username, password, full_name, kelas, role, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ")->execute([$username, $hashed, $nama, $kelas, $role]);

    $newId = $pdo->lastInsertId();

    echo "<p class='ok'>✅ Akun berhasil dibuat!</p>";
    echo "<table>
        <tr><th>ID</th><td>{$newId}</td></tr>
        <tr><th>Nama</th><td>{$nama}</td></tr>
        <tr><th>Role</th><td>{$role} (Pembina Yayasan)</td></tr>
        <tr><th>Username</th><td>{$username}</td></tr>
        <tr><th>Kelas/Grup</th><td>{$kelas}</td></tr>
    </table>";
}

echo "<div class='info'>
    <b>🔑 Kredensial Login:</b><br><br>
    <b>Username:</b> <code>{$username}</code><br>
    <b>Password:</b> <code>{$password}</code><br><br>
    <b>Login URL:</b> <a href='{$config['app_url']}/auth/login' target='_blank'>{$config['app_url']}/auth/login</a><br><br>
    <small style='color:#64748b'>ℹ️ Role <b>admin</b> dapat login dari browser biasa (tidak perlu BKN CAT Browser).</small>
</div>";

echo "</div>";
