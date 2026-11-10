<?php
/**
 * Buat 3 akun dummy siswa PPDB jalur reguler
 * Hapus file ini setelah selesai!
 */
$config = require __DIR__ . '/config.production.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'], $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $password = password_hash('Dummy123!', PASSWORD_DEFAULT);

    $dummies = [
        ['dummy.ppdb1@binakasihnusantara.sch.id', 'Dummy Peserta PPDB 1'],
        ['dummy.ppdb2@binakasihnusantara.sch.id', 'Dummy Peserta PPDB 2'],
        ['dummy.ppdb3@binakasihnusantara.sch.id', 'Dummy Peserta PPDB 3'],
    ];

    echo "<h3>Akun Dummy PPDB</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
    echo "<tr style='background:#f0f0f0'><th>#</th><th>Username (Email)</th><th>Nama</th><th>Password</th><th>Status</th></tr>";

    foreach ($dummies as $i => [$email, $nama]) {
        // Cek sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "<tr><td>" . ($i+1) . "</td><td>$email</td><td>$nama</td><td>Dummy123!</td><td style='color:orange'>Sudah ada</td></tr>";
            continue;
        }

        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, kelas, role, created_at)
            VALUES (?, ?, ?, 'X', 'siswa', NOW())
        ");
        $stmt->execute([$email, $password, $nama]);

        echo "<tr><td>" . ($i+1) . "</td><td>$email</td><td>$nama</td><td>Dummy123!</td><td style='color:green'>✅ Dibuat</td></tr>";
    }

    echo "</table>";
    echo "<br><b>Login URL:</b> <a href='https://cat-enterprise.binakasihnusantara.sch.id/auth/login'>https://cat-enterprise.binakasihnusantara.sch.id/auth/login</a>";
    echo "<br><br><b>Catatan:</b> Ujian PPDB harus di-publish dan bersifat publik (Semua Siswa) agar akun dummy bisa mengakses.";

} catch (PDOException $e) {
    echo "<div style='color:red'>Gagal: " . $e->getMessage() . "</div>";
}
