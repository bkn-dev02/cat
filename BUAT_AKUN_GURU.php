<?php

// ─── Konfigurasi & Koneksi ───────────────────────────────────────────────────

$config = require __DIR__ . '/config.production.php';

$pdo = new PDO(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
    $config['db_user'],
    $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ─── Data Calon Guru ─────────────────────────────────────────────────────────

$calonGuru = [
    [
        'nama'     => 'Ceria Glorinta Br Sembiring',
        'mapel'    => 'Matematika',
        'username' => 'ceria.sembiring@guru.ubkn.sch.id',
    ],
];

$password = 'Guru123!';
$hashed   = password_hash($password, PASSWORD_DEFAULT);
$kelas    = '';

// ─── Styles ──────────────────────────────────────────────────────────────────

echo <<<HTML
<style>
    body  { font-family: sans-serif; padding: 20px; max-width: 700px; margin: auto; }
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #e2e8f0; padding: 10px; }
    th    { background: #f1f5f9; }
    .ok   { color: green; }
    .skip { color: orange; }
</style>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Mata Pelajaran</th>
            <th>Username</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
HTML;

// ─── Proses Insert ───────────────────────────────────────────────────────────

foreach ($calonGuru as $i => $cg) {
    $no       = $i + 1;
    $nama     = htmlspecialchars($cg['nama']);
    $mapel    = htmlspecialchars($cg['mapel']);
    $username = htmlspecialchars($cg['username']);

    // Cek apakah username sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$cg['username']]);

    if ($stmt->fetch()) {
        echo "        <tr>
            <td>{$no}</td>
            <td>{$nama}</td>
            <td>{$mapel}</td>
            <td>{$username}</td>
            <td class='skip'>⚠️ Sudah ada</td>
        </tr>\n";
        continue;
    }

    // Insert user baru
    $pdo->prepare("
        INSERT INTO users (username, password, full_name, kelas, role, created_at)
        VALUES (?, ?, ?, ?, 'guru', NOW())
    ")->execute([$cg['username'], $hashed, $cg['nama'], $kelas]);

    echo "        <tr>
            <td>{$no}</td>
            <td>{$nama}</td>
            <td>{$mapel}</td>
            <td>{$username}</td>
            <td class='ok'>✅ Dibuat</td>
        </tr>\n";
}

// ─── Footer Info ─────────────────────────────────────────────────────────────

echo <<<HTML
    </tbody>
</table>

<div style="margin-top: 20px; background: #eff6ff; padding: 16px; border-radius: 8px;">
    <b>Password semua akun:</b> <code>Guru123!</code><br>
    <b>Kelas:</b> Guru<br>
    <b>Login URL:</b> cat-enterprise.binakasihnusantara.sch.id/auth/login
</div>
HTML;