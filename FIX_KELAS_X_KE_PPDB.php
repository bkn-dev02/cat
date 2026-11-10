<?php
/**
 * FIX KELAS X → PPDB-REGULER
 * Update semua siswa dengan kelas = 'X' menjadi 'PPDB-REGULER'
 */

require_once __DIR__ . '/app/helpers.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
    die('<div style="font-family:sans-serif;padding:20px;color:red;">❌ Akses ditolak.</div>');
}

$updated = 0;
$preview = [];
$done = false;

// Ambil preview dulu
$stmt = db()->query("SELECT id, username, full_name, kelas FROM users WHERE role = 'siswa' AND kelas = 'X' ORDER BY full_name ASC");
$preview = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses update jika dikonfirmasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_fix'])) {
    $stmt = db()->prepare("UPDATE users SET kelas = 'PPDB-REGULER' WHERE role = 'siswa' AND kelas = 'X'");
    $stmt->execute();
    $updated = $stmt->rowCount();
    $done = true;

    // Refresh preview (seharusnya kosong setelah update)
    $stmt2 = db()->query("SELECT id, username, full_name, kelas FROM users WHERE role = 'siswa' AND kelas = 'X'");
    $preview = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Kelas X → PPDB-REGULER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-warning px-3">
    <a class="navbar-brand fw-bold text-dark" href="<?= config('app_url') ?>/dashboard">
        <i class="bi bi-arrow-left me-2"></i>CAT Enterprise
    </a>
    <span class="text-dark fw-semibold"><i class="bi bi-pencil-fill me-1"></i> Fix Kelas X → PPDB-REGULER</span>
</nav>

<div class="container py-4" style="max-width:760px">

    <?php if ($done): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Berhasil!</strong> <?= $updated ?> siswa diupdate dari kelas <code>X</code> ke <code>PPDB-REGULER</code>.
        </div>
        <?php if (empty($preview)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-1"></i> Tidak ada lagi siswa dengan kelas <code>X</code>.
            </div>
        <?php endif; ?>
        <a href="<?= config('app_url') ?>/CEK_PASSWORD_PPDB.php" class="btn btn-primary me-2">
            <i class="bi bi-key-fill me-1"></i> Kembali ke Cek Password
        </a>
        <a href="<?= config('app_url') ?>/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-house me-1"></i> Dashboard
        </a>

    <?php elseif (empty($preview)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            Tidak ada siswa dengan kelas <code>X</code>. Semua sudah benar.
        </div>
        <a href="<?= config('app_url') ?>/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-house me-1"></i> Dashboard
        </a>

    <?php else: ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Preview: <?= count($preview) ?> siswa akan diubah
                </h5>
                <small class="text-muted">
                    Kelas <span class="badge bg-secondary">X</span> →
                    <span class="badge bg-success">PPDB-REGULER</span>
                </small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Lengkap</th>
                                <th>Email / Username</th>
                                <th>Kelas Sekarang</th>
                                <th>Kelas Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview as $i => $s): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                                <td><small class="text-muted"><?= htmlspecialchars($s['username']) ?></small></td>
                                <td><span class="badge bg-secondary">X</span></td>
                                <td><span class="badge bg-success">PPDB-REGULER</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <form method="POST" onsubmit="return confirm('Update <?= count($preview) ?> siswa dari kelas X ke PPDB-REGULER?')">
            <button type="submit" name="do_fix" value="1" class="btn btn-warning fw-bold px-4">
                <i class="bi bi-pencil-fill me-1"></i> Update <?= count($preview) ?> Siswa Sekarang
            </button>
            <a href="<?= config('app_url') ?>/dashboard" class="btn btn-outline-secondary ms-2">Batal</a>
        </form>
    <?php endif; ?>

</div>
</body>
</html>
