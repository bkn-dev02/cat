<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Mapel Guru - CAT Enterprise</title>
<link rel="icon" type="image/png" href="<?= asset_url('cat.png') ?>">
<link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
<link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
<style>
* { font-family: 'Plus Jakarta Sans', sans-serif; }
body { background: #f8fafc; }
.main-content { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; }
.page-title { font-size: 1.75rem; font-weight: 800; color: #1e293b; margin-bottom: 0.25rem; }
.card { background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); border: 1px solid #e2e8f0; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
th { background: #f8fafc; font-weight: 700; color: #374151; }
select { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; width: 100%; }
.btn-save { background: #6366f1; color: white; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 600; }
.btn-save:hover { background: #4f46e5; }
.badge-mapel { background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.badge-none { background: #f1f5f9; color: #94a3b8; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; }
.alert-success { background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
</style>
</head>
<body>
<?php include VIEW_PATH . '/partials/navbar.php'; ?>

<div class="main-content">
    <div style="margin-bottom: 1.5rem;">
        <h1 class="page-title"><i class="fas fa-chalkboard-teacher" style="color:#6366f1"></i> Assign Mata Pelajaran Guru</h1>
        <p style="color:#64748b">Tentukan mata pelajaran yang diajarkan oleh setiap guru.</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Nama Guru</th>
                    <th>Username</th>
                    <th>Mata Pelajaran Saat Ini</th>
                    <th>Ubah Mapel</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $t): ?>
                <tr>
                    <td><b><?= htmlspecialchars($t['full_name']) ?></b></td>
                    <td style="color:#64748b;font-size:0.8rem"><?= htmlspecialchars($t['username']) ?></td>
                    <td>
                        <?php if ($t['subject_name']): ?>
                            <span class="badge-mapel"><i class="fas fa-book"></i> <?= htmlspecialchars($t['subject_name']) ?></span>
                        <?php else: ?>
                            <span class="badge-none">Belum diassign</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;align-items:center">
                            <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                            <select name="subject_id">
                                <option value="">-- Pilih Mapel --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $t['subject_id'] == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-save">Simpan</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
