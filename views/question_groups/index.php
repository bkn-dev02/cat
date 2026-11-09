<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grup Soal - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 1rem 0; border-bottom: 1px solid #e2e8f0; }
        .navbar-brand { font-weight: 800; font-size: 1.5rem; color: #6366f1; }
        .main-content { padding: 2rem 0; max-width: 1100px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
        .btn-primary-custom { background: #6366f1; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 0.5rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: background 0.2s; }
        .btn-primary-custom:hover { background: #4f46e5; color: white; }
        .group-card { background: white; border-radius: 1rem; border: 1px solid #e2e8f0; padding: 1.25rem 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); transition: box-shadow 0.2s, transform 0.2s; }
        .group-card:hover { box-shadow: 0 4px 12px rgba(99,102,241,.12); transform: translateY(-1px); }
        .group-name { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.3rem 0; }
        .group-meta { font-size: 0.82rem; color: #64748b; display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.4rem; }
        .badge-type { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-mc { background: #ede9fe; color: #6d28d9; }
        .badge-essay { background: #fef3c7; color: #92400e; }
        .badge-short { background: #d1fae5; color: #065f46; }
        .badge-listening { background: #dbeafe; color: #1e40af; }
        .badge-tkp { background: #fce7f3; color: #9d174d; }
        .q-count { background: #f1f5f9; color: #475569; padding: 2px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .action-btns { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .btn-sm-custom { padding: 0.3rem 0.75rem; border-radius: 0.4rem; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; }
        .btn-open { background: #6366f1; color: white; }
        .btn-open:hover { background: #4f46e5; color: white; }
        .btn-edit { background: #fef3c7; color: #92400e; }
        .btn-edit:hover { background: #fde68a; color: #92400e; }
        .btn-del { background: #fee2e2; color: #991b1b; }
        .btn-del:hover { background: #fecaca; color: #991b1b; }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }
        .alert-success { background: #d1fae5; color: #065f46; border: none; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: none; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fas fa-layer-group" style="color:#6366f1;"></i> Grup Soal</h1>
                <p style="color:#64748b; margin:0; font-size:0.875rem;">Kelola soal dalam grup — input sekali, tambah banyak soal tanpa isi ulang identitas</p>
            </div>
            <a href="<?= config('app_url') ?>/questionGroup/create" class="btn-primary-custom">
                <i class="fas fa-plus"></i> Buat Grup Baru
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (empty($groups)): ?>
            <div class="empty-state">
                <i class="fas fa-layer-group"></i>
                <h3 style="color:#475569; font-weight:700;">Belum ada grup soal</h3>
                <p>Buat grup soal untuk mengelompokkan soal berdasarkan identitas yang sama.<br>Setelah grup dibuat, tambahkan soal tanpa perlu isi ulang mata pelajaran, tipe, dll.</p>
                <a href="<?= config('app_url') ?>/questionGroup/create" class="btn-primary-custom" style="margin-top:1rem;">
                    <i class="fas fa-plus"></i> Buat Grup Pertama
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($groups as $g): ?>
                <?php
                    $typeLabels = [
                        'multiple_choice'  => ['label' => 'Pilihan Ganda', 'class' => 'badge-mc'],
                        'essay'            => ['label' => 'Essay',         'class' => 'badge-essay'],
                        'short_answer'     => ['label' => 'Isian Angka',   'class' => 'badge-short'],
                        'short_answer_text'=> ['label' => 'Isian Kalimat', 'class' => 'badge-short'],
                        'listening'        => ['label' => 'Listening',     'class' => 'badge-listening'],
                        'tkp'              => ['label' => '📊 TKP',        'class' => 'badge-tkp'],
                    ];
                    $tl = $typeLabels[$g['type']] ?? ['label' => $g['type'], 'class' => 'badge-mc'];
                ?>
                <div class="group-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.75rem;">
                        <div style="flex:1; min-width:200px;">
                            <p class="group-name"><?= htmlspecialchars($g['name']) ?></p>
                            <?php if ($g['description']): ?>
                                <p style="color:#64748b; font-size:0.85rem; margin:0 0 0.4rem 0;"><?= htmlspecialchars($g['description']) ?></p>
                            <?php endif; ?>
                            <div class="group-meta">
                                <span><i class="fas fa-book" style="color:#6366f1;"></i> <?= htmlspecialchars($g['subject_name']) ?></span>
                                <span class="badge-type <?= $tl['class'] ?>"><?= $tl['label'] ?></span>
                                <?php if ($g['sub_topic']): ?>
                                    <span><i class="fas fa-tag" style="color:#94a3b8;"></i> <?= htmlspecialchars($g['sub_topic']) ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-star" style="color:#f59e0b;"></i> <?= $g['default_points'] ?> poin/soal</span>
                                <span class="q-count"><i class="fas fa-list-ol"></i> <?= $g['question_count'] ?> soal</span>
                            </div>
                        </div>
                        <div class="action-btns">
                            <a href="<?= config('app_url') ?>/questionGroup/show/<?= $g['id'] ?>" class="btn-sm-custom btn-open">
                                <i class="fas fa-folder-open"></i> Buka
                            </a>
                            <a href="<?= config('app_url') ?>/questionGroup/edit/<?= $g['id'] ?>" class="btn-sm-custom btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button onclick="confirmDelete(<?= $g['id'] ?>, '<?= htmlspecialchars(addslashes($g['name'])) ?>')" class="btn-sm-custom btn-del">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top:1.5rem;">
            <a href="<?= config('app_url') ?>/question" style="color:#64748b; text-decoration:none; font-size:0.875rem;">
                <i class="fas fa-arrow-left"></i> Kembali ke Bank Soal
            </a>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="confirm" value="1">
    </form>

    <script>
        function confirmDelete(id, name) {
            if (confirm('Hapus grup "' + name + '"?\n\nSoal-soal di dalamnya TIDAK akan dihapus, hanya dilepas dari grup dan kembali ke Bank Soal.')) {
                window.location.href = '<?= config('app_url') ?>/questionGroup/delete/' + id;
            }
        }
    </script>
</body>
</html>
