<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pindah Soal ke Grup - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 1rem 0; border-bottom: 1px solid #e2e8f0; }
        .navbar-brand { font-weight: 800; font-size: 1.5rem; color: #6366f1; }
        .main-content { padding: 2rem 0; max-width: 600px; margin: 0 auto; }
        .form-card { background: white; border-radius: 1rem; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); border: 1px solid #e2e8f0; }
        .form-label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem; font-size: 0.875rem; }
        .form-select { width: 100%; padding: 0.7rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem; }
        .form-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
        .btn { padding: 0.7rem 1.4rem; border-radius: 0.5rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; color: white; }
        .btn-secondary { background: white; color: #1e293b; border: 1px solid #e2e8f0; }
        .btn-back { display: inline-flex; align-items: center; gap: 0.5rem; color: #64748b; text-decoration: none; margin-bottom: 1.5rem; font-weight: 600; }
        .q-preview { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.9rem; color: #1e293b; }
        .warn-box { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; color: #92400e; }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>
    <div class="container main-content">
        <?php $fromGroup = $_GET['from'] ?? null; ?>
        <a href="<?= $fromGroup ? config('app_url') . '/questionGroup/show/' . $fromGroup : config('app_url') . '/questionGroup' ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <div class="form-card">
            <h1 style="font-size:1.3rem; font-weight:700; color:#1e293b; margin-bottom:1.5rem;">
                <i class="fas fa-exchange-alt" style="color:#6366f1;"></i> Pindah Soal ke Grup Lain
            </h1>

            <div class="q-preview">
                <strong>Soal yang akan dipindah:</strong><br>
                <?= htmlspecialchars(mb_substr($question['question_text'] ?? 'Soal Listening', 0, 200)) ?>
            </div>

            <div class="warn-box">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Perhatian:</strong> Soal akan dipindah dari grup sumber ke grup tujuan. Soal tidak diduplikasi.
            </div>

            <form method="POST">
                <input type="hidden" name="source_group_id" value="<?= $fromGroup ?>">
                <div class="mb-3">
                    <label class="form-label">Pilih Grup Tujuan</label>
                    <select name="target_group_id" class="form-select">
                        <option value="">Bank Soal (tanpa grup)</option>
                        <?php foreach ($groups as $g): ?>
                            <?php if ($g['id'] != $fromGroup): ?>
                                <option value="<?= $g['id'] ?>">
                                    <?= htmlspecialchars($g['name']) ?> (<?= htmlspecialchars($g['subject_name']) ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-exchange-alt"></i> Pindahkan</button>
                    <a href="<?= $fromGroup ? config('app_url') . '/questionGroup/show/' . $fromGroup : config('app_url') . '/questionGroup' ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
