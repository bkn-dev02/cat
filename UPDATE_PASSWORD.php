<?php
// ============================================================
//  UPDATE PASSWORD USER — Versi Lengkap
//  ⚠️  HAPUS FILE INI SETELAH DIGUNAKAN
// ============================================================

// ── KONFIGURASI ─────────────────────────────────────────────
$username     = 'karl.batoeta@student.ubkn.sch.id';
$new_password = 'Siswa1234!';   // ← Ganti di sini
$debug_mode   = true;                  // false = sembunyikan detail teknis
// ────────────────────────────────────────────────────────────

$config = require __DIR__ . '/config.production.php';

// Koneksi DB
try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die(render_box('error', '❌ Koneksi Database Gagal', [
        'Pesan' => $debug_mode ? $e->getMessage() : 'Periksa config.production.php'
    ]));
}

// ── DETEKSI KOLOM YANG ADA ──────────────────────────────────
function column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool) $stmt->fetch();
}

$has_updated_at = column_exists($pdo, 'users', 'updated_at');
$has_email      = column_exists($pdo, 'users', 'email');

// ── CEK USER ────────────────────────────────────────────────
$cols = "id, full_name, username" . ($has_email ? ", email" : "");
$stmt = $pdo->prepare("SELECT $cols FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    // Cari username yang mirip untuk bantu debug
    $like_stmt = $pdo->prepare("SELECT username, full_name FROM users WHERE username LIKE ? LIMIT 5");
    $like_stmt->execute(['%' . explode('@', $username)[0] . '%']);
    $similar = $like_stmt->fetchAll();

    $detail = ['Username dicari' => htmlspecialchars($username)];
    if ($debug_mode && $similar) {
        $detail['Username serupa di DB'] = implode('<br>', array_map(
            fn($r) => htmlspecialchars($r['username']), $similar
        ));
    }
    die(render_box('error', '❌ User Tidak Ditemukan', $detail));
}

// ── VALIDASI PASSWORD BARU ──────────────────────────────────
$errors = [];
if (strlen($new_password) < 8)             $errors[] = 'Minimal 8 karakter';
if (!preg_match('/[A-Z]/', $new_password)) $errors[] = 'Harus ada huruf kapital';
if (!preg_match('/[0-9]/', $new_password)) $errors[] = 'Harus ada angka';

if ($errors) {
    die(render_box('warning', '⚠️ Password Tidak Valid', [
        'Syarat tidak terpenuhi' => implode(', ', $errors)
    ]));
}

// ── CEK APAKAH SAMA DENGAN PASSWORD LAMA ───────────────────
$old_stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
$old_stmt->execute([$username]);
$old_hash = $old_stmt->fetchColumn();

if (password_verify($new_password, $old_hash)) {
    die(render_box('warning', '⚠️ Tidak Ada Perubahan', [
        'Info' => 'Password baru sama dengan password lama.'
    ]));
}

// ── UPDATE PASSWORD ─────────────────────────────────────────
try {
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);

    if ($has_updated_at) {
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE username = ?";
    } else {
        $sql = "UPDATE users SET password = ? WHERE username = ?";
    }

    $update = $pdo->prepare($sql);
    $update->execute([$hashed, $username]);
    $affected = $update->rowCount();

} catch (PDOException $e) {
    die(render_box('error', '❌ Gagal Update Database', [
        'Pesan' => $debug_mode ? $e->getMessage() : 'Terjadi kesalahan saat update.'
    ]));
}

// ── VERIFIKASI HASIL ────────────────────────────────────────
$verify_stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
$verify_stmt->execute([$username]);
$saved_hash = $verify_stmt->fetchColumn();
$verified   = password_verify($new_password, $saved_hash);

// ── TAMPILKAN HASIL ─────────────────────────────────────────
if ($verified) {
    $detail = [
        'Nama'          => htmlspecialchars($user['full_name']),
        'Username'      => '<code>' . htmlspecialchars($user['username']) . '</code>',
        'Password Baru' => '<code>' . htmlspecialchars($new_password) . '</code>',
    ];
    if ($has_email && !empty($user['email'])) {
        $detail['Email'] = htmlspecialchars($user['email']);
    }
    if ($debug_mode) {
        $detail['Rows Affected']   = $affected;
        $detail['updated_at']      = $has_updated_at ? '✓ Diperbarui' : '— Kolom tidak ada';
        $detail['Verifikasi Hash'] = '✓ Cocok';
    }
    echo render_box('success', '✅ Password Berhasil Diupdate!', $detail);
    echo "<p style='text-align:center;font-family:sans-serif;color:#dc2626;font-size:13px;margin-top:12px'>
            ⚠️ <b>Segera hapus file ini dari server setelah digunakan!</b>
          </p>";
} else {
    die(render_box('error', '❌ Verifikasi Gagal', [
        'Info' => 'Data tersimpan tapi hash tidak cocok. Coba jalankan ulang.'
    ]));
}

// ── HELPER: RENDER BOX ──────────────────────────────────────
function render_box(string $type, string $title, array $rows = []): string {
    $colors = [
        'success' => ['bg' => '#d1fae5', 'title' => '#065f46', 'border' => '#6ee7b7'],
        'error'   => ['bg' => '#fee2e2', 'title' => '#991b1b', 'border' => '#fca5a5'],
        'warning' => ['bg' => '#fef3c7', 'title' => '#92400e', 'border' => '#fcd34d'],
    ];
    $c = $colors[$type] ?? $colors['error'];

    $rows_html = '';
    foreach ($rows as $label => $value) {
        $rows_html .= "<tr>
            <td style='padding:6px 10px;color:#6b7280;white-space:nowrap'><b>$label</b></td>
            <td style='padding:6px 10px'>$value</td>
        </tr>";
    }

    return "
    <div style='font-family:sans-serif;max-width:480px;margin:40px auto;
                padding:24px;background:{$c['bg']};border-radius:10px;
                border:1px solid {$c['border']}'>
        <h3 style='color:{$c['title']};margin:0 0 16px'>$title</h3>
        " . ($rows_html ? "<table style='width:100%;border-collapse:collapse'>$rows_html</table>" : "") . "
    </div>";
}
?>