<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Grup Soal - CAT Enterprise</title>
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
        .form-title { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0 0 0.3rem 0; }
        .form-label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem; font-size: 0.875rem; }
        .form-control, .form-select { width: 100%; padding: 0.7rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem; transition: all 0.2s; }
        .form-control:focus, .form-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
        .btn { padding: 0.7rem 1.4rem; border-radius: 0.5rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; color: white; }
        .btn-secondary { background: white; color: #1e293b; border: 1px solid #e2e8f0; }
        .btn-back { display: inline-flex; align-items: center; gap: 0.5rem; color: #64748b; text-decoration: none; margin-bottom: 1.5rem; font-weight: 600; }
        .btn-back:hover { color: #6366f1; }
        .info-box { background: #eff6ff; border-left: 4px solid #6366f1; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; color: #1e40af; }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <a href="<?= config('app_url') ?>/questionGroup" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Grup Soal
        </a>

        <div class="form-card">
            <div style="margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid #e2e8f0;">
                <h1 class="form-title"><i class="fas fa-layer-group" style="color:#6366f1;"></i> Buat Grup Soal Baru</h1>
                <p style="color:#64748b; margin:0.3rem 0 0 0; font-size:0.875rem;">Isi identitas grup sekali — semua soal di dalamnya akan mewarisi identitas ini secara otomatis.</p>
            </div>

            <div class="info-box">
                <strong><i class="fas fa-lightbulb"></i> Cara kerja Grup Soal:</strong><br>
                1. Buat grup dengan identitas lengkap (mapel, tipe, sub materi, poin)<br>
                2. Buka grup → tambahkan soal tanpa isi ulang identitas<br>
                3. Soal bisa dicopy atau dipindah ke grup lain kapan saja
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div style="background:#fee2e2;color:#991b1b;border-radius:0.5rem;padding:1rem;margin-bottom:1rem;">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Grup <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           placeholder="Contoh: UTS Matematika Kelas X — Persamaan Linear">
                    <small style="color:#64748b;">Nama yang deskriptif memudahkan pencarian nanti</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi (Opsional)</label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Contoh: Soal-soal untuk UTS semester 1, materi bab 3-4"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mata Pelajaran <span style="color:#dc2626;">*</span></label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">Pilih Mata Pelajaran</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

               <div class="mb-3">
                    <label class="form-label">Tipe Soal <span style="color:#dc2626;">*</span></label>
                    <select name="type" id="typeSelect" class="form-select" required onchange="toggleTkpMode()">
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="short_answer">Isian Singkat (Angka)</option>
                        <option value="short_answer_text">Isian Singkat (Kalimat)</option>
                        <option value="essay">Essay</option>
                        <option value="listening">Listening</option>
                        <option value="tkp">TKP (Skala Likert)</option>
                    </select>
                </div>
                
                <div id="tkpInfoBox" class="info-box" style="display:none; background:#fefce8; border-left-color:#f59e0b; color:#854d0e;">
                    <strong><i class="fas fa-sliders-h"></i> Mode TKP Aktif:</strong><br>
                    Setiap opsi jawaban (A–E) akan diberi skor sendiri (biasanya 1–5, skala Likert), bukan satu jawaban benar.
                    Poin per soal dihitung otomatis dari skor tertinggi — field "Poin Default" di bawah tidak berlaku untuk tipe ini.
                </div>

                <div class="mb-3">
                    <label class="form-label">Sub Materi (Opsional)</label>
                    <input type="text" name="sub_topic" class="form-control"
                           placeholder="Contoh: Persamaan Linear Satu Variabel">
                </div>

                <div class="mb-3" id="defaultPointsWrapper">
                    <label class="form-label">Poin Default per Soal <span style="color:#dc2626;">*</span></label>
                    <input type="number" name="default_points" id="defaultPointsInput" class="form-control" value="1" min="1" required>
                    <small style="color:#64748b;">Setiap soal baru di grup ini akan otomatis menggunakan poin ini</small>
                </div>

                <div style="display:flex; gap:1rem; margin-top:1.5rem; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Buat Grup & Mulai Tambah Soal
                    </button>
                    <a href="<?= config('app_url') ?>/questionGroup" class="btn btn-secondary">
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

        // Jalankan sekali saat halaman load (jaga-jaga kalau browser restore form state)
        document.addEventListener('DOMContentLoaded', toggleTkpMode);
    </script>
</body>
</html>
