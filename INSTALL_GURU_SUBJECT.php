<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN subject_id INT NULL DEFAULT NULL AFTER role");
    echo "✅ Kolom subject_id berhasil ditambahkan ke tabel users.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠️ Kolom subject_id sudah ada.";
    } else {
        echo "❌ Error: " . $e->getMessage();
    }
}
