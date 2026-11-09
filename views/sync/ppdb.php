<?php
$user = $_SESSION['user'];
$appUrl = config('app_url');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync PPDB Jalur Reguler - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/bootstrap-icons/1.10.0/font/bootstrap-icons.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/bootstrap-icons/1.10.0/font/bootstrap-icons.css') ?>" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= asset_url('cat.png') ?>">
    <link rel="manifest" href="<?= app_path_url('manifest.json') ?>">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="CAT Enterprise">
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        body {
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }
    </style>
</head>
<body class="bg-light">

    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container py-4">
    
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    
        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?= $_SESSION['info'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>
    
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Siswa PPDB - Jalur Reguler</h5>
                    <small class="text-muted">Sumber: ppdb.binakasihnusantara.sch.id</small>
                </div>
                <?php if (!$ppdbError): ?>
                    <span class="badge bg-primary fs-6"><?= count($ppdbStudents) ?> siswa ditemukan</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
    
                <?php if ($ppdbError): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Koneksi PPDB Gagal</strong><br>
                        <?= htmlspecialchars($ppdbError) ?>
                        <hr>
                        <small>Solusi: Login ke Hostinger hPanel → Databases → MySQL Databases → Add User To Database → pilih user <code>u245247469_enterprise</code> dan database <code>u245247469_ppdb_bkn</code> → grant <strong>SELECT</strong>.</small>
                    </div>
    
                <?php elseif (empty($ppdbStudents)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>Tidak ada siswa jalur reguler yang ditemukan di database PPDB.
                    </div>
    
                <?php else: ?>
                    <form method="POST" action="<?= $appUrl ?>/sync/syncPpdb">
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAll(true)">
                                <i class="bi bi-check-all"></i> Pilih Semua
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAll(false)">
                                <i class="bi bi-x-lg"></i> Batal Semua
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm ms-auto">
                                <i class="bi bi-cloud-download-fill me-1"></i> Sync yang Dipilih
                            </button>
                        </div>
    
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40"><input type="checkbox" id="checkAll" onchange="toggleAll(this.checked)"></th>
                                        <th>#</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email (Username)</th>
                                        <th>NISN</th>
                                        <th>Jalur</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ppdbStudents as $i => $s): ?>
                                        <?php $sudahAda = in_array($s['email'], $existingUsernames); ?>
                                        <tr class="<?= $sudahAda ? 'table-secondary' : '' ?>">
                                            <td>
                                                <?php if (!$sudahAda): ?>
                                                    <input type="checkbox" name="students[]" value="<?= $s['id'] ?>" class="student-check" checked>
                                                <?php else: ?>
                                                    <i class="bi bi-check-circle-fill text-success" title="Sudah ada di CAT"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= htmlspecialchars($s['nama_lengkap'] ?? '-') ?></td>
                                            <td><small><?= htmlspecialchars($s['email']) ?></small></td>
                                            <td><?= htmlspecialchars($s['nisn'] ?? '-') ?></td>
                                            <td><span class="badge bg-success"><?= htmlspecialchars($s['jalur']) ?></span></td>
                                            <td>
                                                <?php if ($sudahAda): ?>
                                                    <span class="badge bg-secondary">Sudah di CAT</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Belum di-sync</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
    
                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cloud-download-fill me-1"></i> Sync yang Dipilih ke CAT
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
    
            </div>
        </div>
    </div>

<script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
<script>
function toggleAll(checked) {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = checked);
    const ca = document.getElementById('checkAll');
    if (ca) ca.checked = checked;
}
</script>
</body>
</html>
