<?php
/**
 * Tambah kolom category ke tabel exams
 * Jalankan SEKALI, lalu hapus file ini!
 */
$config = require __DIR__ . '/config.production.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'], $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Cek apakah kolom sudah ada
    $cols = $pdo->query("SHOW COLUMNS FROM exams LIKE 'category'")->fetchAll();
    if ($cols) {
        echo "<div style='color:orange'>Kolom <b>category</b> sudah ada, tidak perlu dijalankan lagi.</div>";
    } else {
        $pdo->exec("ALTER TABLE exams ADD COLUMN category ENUM('reguler','ppdb') NOT NULL DEFAULT 'reguler' AFTER title");
        echo "<div style='color:green'><b>Berhasil!</b> Kolom <b>category</b> ditambahkan ke tabel exams.<br>Semua ujian lama otomatis jadi kategori <b>reguler</b>.</div>";
    }
} catch (PDOException $e) {
    echo "<div style='color:red'>Gagal: " . $e->getMessage() . "</div>";
}
echo "<br><a href='/'>Kembali ke Dashboard</a>";
