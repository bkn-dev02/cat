<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$username = 'devon.christianaritonang@siswa.ubkn.sch.id';
$nama     = 'Devon Christian Aritonang';
$kelas    = 'X-ARCHIMEDES';
$password = 'Siswa2026!';

// Cek sudah ada belum
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "<p style='color:orange;font-family:sans-serif'>⚠️ Akun sudah ada. Tidak perlu dibuat ulang.</p>";
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (username, password, full_name, kelas, role, created_at) VALUES (?, ?, ?, ?, 'siswa', NOW())")
    ->execute([$username, $hashed, $nama, $kelas]);

echo "<div style='font-family:sans-serif;max-width:400px;margin:40px auto;padding:20px;background:#d1fae5;border-radius:8px'>";
echo "<h3 style='color:#065f46'>✅ Akun berhasil dibuat!</h3>";
echo "<p><b>Nama:</b> $nama</p>";
echo "<p><b>Username:</b> <code>$username</code></p>";
echo "<p><b>Password:</b> <code>$password</code></p>";
echo "<p><b>Kelas:</b> $kelas</p>";
echo "</div>";
?>
