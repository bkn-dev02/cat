<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Grup Soal - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 1rem 0; border-bottom: 1px solid #e2e8f0; }
        .navbar-brand { font-weight: 800; font-size: 1.5rem; color: #6366f1; }
        .main-content { padding: 2rem 0; max-width: 700px; margin: 0 auto; }
        .form-card { background: white; border-radius: 1rem; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); border: 1px solid #e2e8f0; }
        .form-title { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0; }
        .form-label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem; font-size: 0.875rem; }
        .form-control, .form-select { width: 100%; padding: 0.7rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem; transition: all 0.2s; }
        .form-control:focus, .form-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
        .btn { padding: 0.7rem 1.4rem; border-radius: 0.5rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; color: white; }
        .btn-secondary { background: white; color: #1e293b; border: 1px solid #e2e8f0; }
        .btn-back { display: inline-flex; align-items: center; gap: 0.5rem; color: #64748b; text-decoration: none; margin-bottom: 1.5rem; font-weight: 600; }
        .btn-back:hover { color: #6366f1; }
        .warn-box { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; color: #92400e; }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <a href="<?= config('app_url') ?>/questionGroup/show/<?= $group['id'] ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Grup
        </a>

        <div class="form-card">
            <div style="margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid #e2e8f0;">
                <h1 class="form-title"><i class="fas fa-edit" style="color:#6366f1;"></i> Edit Grup Soal</h1>
            </div>

            <div class="warn-box">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Perhatian:</strong> Mengubah identitas grup tidak otomatis mengubah soal-soal yang sudah ada di dalamnya. Perubahan hanya berlaku untuk soal baru yang ditambahkan setelah ini.
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Grup <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($group['name']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi (Opsional)</label>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($group['description'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mata Pelajaran <span style="color:#dc2626;">*</span></label>
                    <select name="subject_id" class="form-select" required>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $s['id'] == $group['subject_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipe Soal <span style="color:#dc2626;">*</span></label>
                    <select name="type" id="typeSelect" class="form-select" required onchange="toggleTkpMode()">
                        <?php
                        $types = [
                            'multiple_choice'   => 'Pilihan Ganda',
                            'short_answer'      => 'Isian Singkat (Angka)',
                            'short_answer_text' => 'Isian Singkat (Kalimat)',
                            'essay'             => 'Essay',
                            'listening'         => 'Listening',
                            'tkp'               => 'TKP (Skala Likert)',
                        ];
                        foreach ($types as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $group['type'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="tkpInfoBox" class="info-box" style="display:none; background:#fefce8; border-left:4px solid #f59e0b; color:#854d0e; padding:1rem; border-radius:0.5rem; margin-bottom:1.5rem; font-size:0.875rem;">
                    <strong><i class="fas fa-sliders-h"></i> Mode TKP Aktif:</strong><br>
                    Setiap opsi jawaban (A–E) akan diberi skor sendiri (biasanya 1–5, skala Likert), bukan satu jawaban benar.
                    Poin per soal dihitung otomatis dari skor tertinggi — field "Poin Default" di bawah tidak berlaku untuk tipe ini.
                </div>

                <div class="mb-3">
                    <label class="form-label">Sub Materi (Opsional)</label>
                    <input type="text" name="sub_topic" class="form-control"
                           value="<?= htmlspecialchars($group['sub_topic'] ?? '') ?>">
                </div>

                <div class="mb-3" id="defaultPointsWrapper">
                    <label class="form-label">Poin Default per Soal</label>
                    <input type="number" name="default_points" id="defaultPointsInput" class="form-control"
                           value="<?= $group['default_points'] ?>" min="1" required>
                </div>

                <div style="display:flex; gap:1rem; margin-top:1.5rem; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="<?= config('app_url') ?>/questionGroup/show/<?= $group['id'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function toggleTkpMode() {
            const type = document.getElementById('typeSelect').value;
            const tkpBox = document.getElementById('tkpInfoBox');
            const pointsWrapper = document.getElementById('defaultPointsWrapper');
            const pointsInput = document.getElementById('defaultPointsInput');

            if (type === 'tkp') {
                tkpBox.style.display = 'block';
                pointsWrapper.style.display = 'none';
                pointsInput.required = false;
            } else {
                tkpBox.style.display = 'none';
                pointsWrapper.style.display = 'block';
                pointsInput.required = true;
            }
        }

        document.addEventListener('DOMContentLoaded', toggleTkpMode);
    </script>
</body>
</html>
