<?php
/**
 * Hapus file-file temporary deploy
 * Jalankan SEKALI lalu file ini akan menghapus dirinya sendiri
 */
$files = [
    __DIR__ . '/DEPLOY_UPDATE.php',
    __DIR__ . '/SYNC_CHECK.php',
    __DIR__ . '/HAPUS_TEMP_FILES.php', // hapus diri sendiri
];

echo "<style>body{font-family:sans-serif;padding:20px;max-width:600px;margin:0 auto}.ok{color:#16a34a}.err{color:#dc2626}</style>";
echo "<h2>🗑️ Hapus File Temporary</h2>";

foreach ($files as $f) {
    $name = basename($f);
    if (file_exists($f)) {
        if (unlink($f)) {
            echo "<p class='ok'>✅ $name — berhasil dihapus</p>";
        } else {
            echo "<p class='err'>❌ $name — gagal dihapus (cek permission)</p>";
        }
    } else {
        echo "<p style='color:#94a3b8'>— $name — tidak ditemukan (sudah dihapus)</p>";
    }
}

$appUrl = 'https://cat-enterprise.binakasihnusantara.sch.id';
echo "<br><a href='$appUrl/question' style='background:#10b981;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>→ Buka Bank Soal</a>";
