<?php
$user   = $user ?? $_SESSION['user'] ?? [];
$exams  = $exams ?? [];
$appUrl = config('app_url');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Peserta Ujian - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/bootstrap-icons/1.10.0/font/bootstrap-icons.css') ?>" rel="stylesheet">
    <style>
        .exam-row:hover { background: #f8f9fa; }
        .badge-kelas { font-size: 0.75em; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary px-3">
    <a class="navbar-brand" href="<?= $appUrl ?>/dashboard">
        <i class="bi bi-arrow-left me-2"></i>CAT Enterprise
    </a>
    <span class="text-white fw-semibold">
        <i class="bi bi-people-fill me-1"></i> Sync Peserta Ujian
    </span>
</nav>

<div class="container py-4">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Kapan perlu sync?</strong>
        Gunakan halaman ini jika ada siswa yang <strong>baru didaftarkan atau pindah kelas</strong> setelah ujian dibuat.
        Sistem akan otomatis menambahkan siswa baru ke ujian yang kelasnya sudah terdaftar.
    </div>

    <!-- Sync Semua -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-arrow-repeat me-2"></i> Sync Semua Ujian Sekaligus
        </div>
        <div class="card-body">
            <p class="mb-3 text-muted">
                Periksa semua ujian yang menggunakan kelas tertentu dan tambahkan siswa baru
                yang belum terdaftar sebagai peserta.
            </p>
            <form method="POST" onsubmit="return confirm('Sync semua ujian? Proses ini aman dan tidak akan menghapus data.')">
                <input type="hidden" name="sync_all" value="1">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat me-2"></i> Sync Semua Ujian
                </button>
            </form>
        </div>
    </div>

    <!-- Sync Per Ujian -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-clipboard-check me-2"></i>
            Ujian dengan Peserta Terbatas (<?= count($exams) ?> ujian)
        </div>
        <div class="card-body p-0">
            <?php if (empty($exams)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size:2rem"></i>
                    <p class="mt-2">Tidak ada ujian dengan peserta terbatas.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Judul Ujian</th>
                                <th>Kelas</th>
                                <th>Peserta</th>
                                <th>Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $i => $exam): ?>
                            <tr class="exam-row">
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($exam['title']) ?></strong>
                                </td>
                                <td>
                                    <?php foreach (explode(', ', $exam['kelas_list'] ?? '') as $kl): ?>
                                        <span class="badge bg-primary badge-kelas me-1"><?= htmlspecialchars(trim($kl)) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= (int)$exam['participant_count'] ?> siswa</span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d M Y H:i', strtotime($exam['start_time'])) ?>
                                        –
                                        <?= date('H:i', strtotime($exam['end_time'])) ?>
                                    </small>
                                    <?php
                                    $now = time();
                                    $end = strtotime($exam['end_time']);
                                    $start = strtotime($exam['start_time']);
                                    if ($now > $end): ?>
                                        <span class="badge bg-secondary ms-1">Selesai</span>
                                    <?php elseif ($now >= $start): ?>
                                        <span class="badge bg-success ms-1">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark ms-1">Akan Datang</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST"
                                          onsubmit="return confirm('Sync peserta untuk ujian ini?')">
                                        <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-arrow-repeat"></i> Sync
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3 text-muted small">
        <i class="bi bi-shield-check me-1"></i>
        Proses sync bersifat <strong>aman</strong> — hanya menambah siswa baru,
        tidak menghapus peserta yang sudah ada atau data jawaban.
    </div>
</div>

<script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
