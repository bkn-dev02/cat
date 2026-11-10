<?php
/**
 * Script EMERGENCY: Nonaktifkan Visibility Detection
 * Untuk mengatasi komplain orang tua/guru tentang suspend otomatis
 * 
 * Script ini akan comment out kode visibility detection di take.php
 */

$file = __DIR__ . '/views/exams/take.php';

if (!file_exists($file)) {
    die("ERROR: File take.php tidak ditemukan!\n");
}

echo "=== MENONAKTIFKAN VISIBILITY DETECTION ===\n\n";

$content = file_get_contents($file);

// Backup file original
$backup = $file . '.backup.' . date('YmdHis');
file_put_contents($backup, $content);
echo "✓ Backup dibuat: $backup\n\n";

// Comment out visibility event listener
$content = str_replace(
    "document.addEventListener('visibilitychange', handleVisibilityChange);",
    "// DISABLED: document.addEventListener('visibilitychange', handleVisibilityChange);",
    $content
);

// Comment out removeEventListener juga
$content = str_replace(
    "document.removeEventListener('visibilitychange', handleVisibilityChange);",
    "// DISABLED: document.removeEventListener('visibilitychange', handleVisibilityChange);",
    $content
);

// Simpan perubahan
file_put_contents($file, $content);

echo "✓ Visibility detection berhasil dinonaktifkan!\n\n";
echo "=== PERUBAHAN ===\n";
echo "- Siswa tidak akan di-suspend jika layar mati\n";
echo "- Siswa tidak akan di-suspend jika minimize browser\n";
echo "- Siswa tidak akan di-suspend jika switch tab\n\n";
echo "=== FITUR YANG MASIH AKTIF ===\n";
echo "- Fullscreen wajib\n";
echo "- Block context menu (klik kanan)\n";
echo "- Block copy-paste\n";
echo "- Timer countdown\n\n";
echo "=== UNTUK RESTORE ===\n";
echo "Jika ingin mengembalikan seperti semula:\n";
echo "1. Hapus file: views/exams/take.php\n";
echo "2. Rename file: $backup\n";
echo "3. Jadi: views/exams/take.php\n\n";
echo "✓ SELESAI!\n";
