<?php
/**
 * HAPUS_DEBUG.php
 * Hapus semua file debug dari server — jalankan sekali lalu hapus file ini juga
 */

$files = [
    'CEK_EXAMBRO.php',
    'CEK_HELPERS_SERVER.php',
    'CEK_HELPERS2.php',
    'CEK_LIVE.php',
    'CEK_SESSION_NAME.php',
    'CEK_SESSION_FILE.php',
    'CEK_ISI_SESSION.php',
    'CEK_INGRID.php',
    'CEK_SESSION_NAME.php',
    'RESET_CACHE.php',
    'FIX_OPCACHE_SESSION.php',
    'FORCE_FIX_EXAMBRO.php',
    'PATCH_AUTH.php',
    'CEK_SESSION_FILE.php',
    'HAPUS_DEBUG.php', // hapus dirinya sendiri
];

echo "<pre style='font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem;min-height:100vh'>";
echo "Menghapus file debug...\n\n";

$deleted = 0;
$notFound = 0;

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        if (unlink($path)) {
            echo "✅ Dihapus : $file\n";
            $deleted++;
        } else {
            echo "❌ Gagal   : $file\n";
        }
    } else {
        echo "⚪ Tidak ada: $file\n";
        $notFound++;
    }
}

echo "\n--- Selesai: $deleted file dihapus, $notFound tidak ditemukan ---\n";
echo "</pre>";
