<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Sesi Siswa</title>
<link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= asset_url('vendor/fontawesome/6.0.0/css/all.min.css') ?>" rel="stylesheet">
<link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
<style>
    * { font-family: 'Plus Jakarta Sans', sans-serif; }
    body { background: var(--light); }
    .page-header { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 1rem 0; margin: 1rem auto; text-align:center; border-radius:1rem;}
    .page-header h1 { font-size: 1.2rem; font-weight: 700; color: #1e293b; margin: 0; }
    .page-header p  { color: #64748b; margin: 0.25rem 0 0; font-size: 0.9rem; }
    .card { border: none; border-radius: 12px; box-shadow: 0 1px 8px rgba(0,0,0,0.07); }
    .student-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem 1.25rem;
                    display: flex; justify-content: space-between; align-items: center;
                    gap: 1rem; margin-bottom: .75rem; background: white; }
    .student-card .name { font-weight: 700; color: #1e293b; }
    .student-card .meta { color: #64748b; font-size: .82rem; margin-top: 2px; }
    .badge-locked { background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 20px;
                    font-size: .75rem; font-weight: 600; }
    .badge-free   { background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 20px;
                    font-size: .75rem; font-weight: 600; }
    .empty-state  { text-align: center; color: #94a3b8; padding: 2.5rem 0; }
</style>
</head>
<body>

<?php require_once VIEW_PATH . '/partials/navbar.php'; ?>

<div class="container" style="max-width:680px">

    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-key text-primary me-2"></i> Reset Sesi Siswa</h1>
            <p>Cari siswa berdasarkan nama atau username, lalu klik Reset untuk membukakan akses login.</p>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= config('app_url') ?>/admin/resetSesi" class="d-flex gap-2">
                <input type="text" name="q" class="form-control"
                       value="<?= htmlspecialchars($keyword) ?>"
                       placeholder="Cari nama atau username siswa..." autofocus>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Hasil pencarian -->
    <?php if ($keyword !== ''): ?>
        <?php if (empty($results)): ?>
            <div class="empty-state">
                <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                Tidak ada siswa ditemukan untuk "<strong><?= htmlspecialchars($keyword) ?></strong>"
            </div>
        <?php else: ?>
            <p class="text-muted small mb-3"><?= count($results) ?> siswa ditemukan</p>
            <?php foreach ($results as $s): ?>
            <div class="student-card">
                <div>
                    <div class="name"><?= htmlspecialchars($s['full_name'] ?: $s['username']) ?></div>
                    <div class="meta">
                        @<?= htmlspecialchars($s['username']) ?>
                        <?php if ($s['kelas']): ?>&nbsp;·&nbsp;<?= htmlspecialchars($s['kelas']) ?><?php endif; ?>
                        <?php if ($s['last_login_at']): ?>&nbsp;·&nbsp;Login: <?= date('d/m H:i', strtotime($s['last_login_at'])) ?><?php endif; ?>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="<?= $s['active_session_id'] ? 'badge-locked' : 'badge-free' ?>">
                        <?= $s['active_session_id'] ? '🔒 Terkunci' : '✅ Bebas' ?>
                    </span>
                    <?php if ($s['active_session_id']): ?>
                    <form method="POST" action="<?= config('app_url') ?>/admin/resetSesi?q=<?= urlencode($keyword) ?>"
                          onsubmit="return confirm('Reset sesi <?= htmlspecialchars(addslashes($s['full_name'] ?: $s['username'])) ?>?')">
                        <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-unlock-alt"></i> Reset Sesi
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search fa-2x mb-2 d-block"></i>
            Ketik nama atau username siswa di atas untuk mencari
        </div>
    <?php endif; ?>

</div>

<script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
