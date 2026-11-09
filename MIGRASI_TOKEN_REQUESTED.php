<?php
/**
 * MIGRASI: Tambah kolom token_requested_at dan nilai enum 'requested' ke exam_sessions
 * Jalankan sekali di server production.
 */

require_once __DIR__ . '/app/helpers.php';

$db = db();

echo "<h2>Migrasi: token_requested_at & token_status 'requested'</h2>";
echo "<pre>";

try {
    // 1. Cek apakah kolom token_requested_at sudah ada
    $stmt = $db->query("SHOW COLUMNS FROM exam_sessions LIKE 'token_requested_at'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "✓ Kolom token_requested_at sudah ada, skip.\n";
    } else {
        $db->exec("ALTER TABLE exam_sessions ADD COLUMN token_requested_at DATETIME NULL AFTER token_status");
        echo "✓ Kolom token_requested_at berhasil ditambahkan.\n";
    }

    // 2. Cek apakah enum token_status sudah punya nilai 'requested'
    $stmt = $db->query("SHOW COLUMNS FROM exam_sessions LIKE 'token_status'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    $colType = $col['Type'] ?? '';

    if (strpos($colType, "'requested'") !== false) {
        echo "✓ Enum token_status sudah punya nilai 'requested', skip.\n";
    } else {
        // Tambah 'requested' dan 'rejected' ke enum yang sudah ada
        $db->exec("ALTER TABLE exam_sessions MODIFY COLUMN token_status ENUM('active','suspended','revoked','expired','requested','rejected') DEFAULT 'active'");
        echo "✓ Enum token_status berhasil diupdate (ditambah 'requested' dan 'rejected').\n";
    }

    echo "\n<strong style='color:green'>Migrasi selesai. Halaman /exam/tokenRequests sekarang bisa diakses.</strong>\n";

} catch (Exception $e) {
    echo "<strong style='color:red'>ERROR: " . $e->getMessage() . "</strong>\n";
}

echo "</pre>";
