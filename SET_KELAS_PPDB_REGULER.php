<?php
/**
 * Set kelas 'PPDB-REGULER' untuk semua siswa hasil sync PPDB
 * Hapus file ini setelah selesai!
 */
$config = require __DIR__ . '/config.production.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'], $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Update semua siswa berkelas 'X' yang emailnya dari PPDB (termasuk dummy)
    // Identifikasi: siswa dengan kelas 'X' yang dibuat setelah fitur sync PPDB
    // Lebih aman: update berdasarkan username yang ada di database PPDB

    // Koneksi ke PPDB untuk ambil daftar email
    $ppdb = new PDO(
        'mysql:host=' . $config['ppdb_db_host'] . ';dbname=' . $config['ppdb_db_name'] . ';charset=utf8mb4',
        $config['ppdb_db_user'], $config['ppdb_db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $ppdb->query("SELECT u.email FROM users u JOIN registrations r ON r.user_id = u.id WHERE r.jalur = 'reguler'");
    $ppdbEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Tambah dummy ke list
    $ppdbEmails[] = 'dummy.ppdb1@binakasihnusantara.sch.id';
    $ppdbEmails[] = 'dummy.ppdb2@binakasihnusantara.sch.id';
    $ppdbEmails[] = 'dummy.ppdb3@binakasihnusantara.sch.id';

    if (empty($ppdbEmails)) {
        echo "Tidak ada email PPDB ditemukan.";
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($ppdbEmails), '?'));
    $stmt = $pdo->prepare("UPDATE users SET kelas = 'PPDB-REGULER' WHERE username IN ($placeholders) AND role = 'siswa'");
    $stmt->execute($ppdbEmails);
    $updated = $stmt->rowCount();

    echo "<div style='color:green; font-size:1.2em'><b>✅ Berhasil!</b> $updated siswa diupdate ke kelas <b>PPDB-REGULER</b></div>";
    echo "<br>Sekarang di halaman Edit Ujian PPDB, pilih <b>Kelas Tertentu Saja</b> → centang <b>PPDB-REGULER</b>.";

} catch (PDOException $e) {
    echo "<div style='color:red'>Gagal: " . $e->getMessage() . "</div>";
}
