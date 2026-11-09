<?php require VIEW_PATH . '/partials/navbar.php'; ?>

<div class="container py-4" style="max-width:600px;">
    <h4 class="mb-3"><i class="fas fa-edit"></i> Edit Siswa</h4>
    <form method="post" action="<?= config('app_url') ?>/admin/editSiswa?id=<?= $siswa['id'] ?>">
        <input type="hidden" name="back_kelas" value="<?= htmlspecialchars($backKelas) ?>">
        <input type="hidden" name="back_nama" value="<?= htmlspecialchars($backNama) ?>">
        <input type="hidden" name="id" value="<?= $siswa['id'] ?>">
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($siswa['full_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($siswa['username']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Kelas</label>
            <input type="text" name="kelas" class="form-control" value="<?= htmlspecialchars($siswa['kelas'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?= config('app_url') ?>/admin/cekSiswa?kelas=<?= urlencode($backKelas) ?>&nama=<?= urlencode($backNama) ?>"
           class="btn btn-secondary">Batal</a>
    </form>
</div>