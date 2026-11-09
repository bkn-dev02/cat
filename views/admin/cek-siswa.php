<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Siswa - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.2/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.5.1/css/all.min.css') ?>">
</head>
<body>

<?php require VIEW_PATH . '/partials/navbar.php'; ?>

<style>
    .cs-wrap {
        max-width: 1100px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }

    .cs-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--dark, #1f2937);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .cs-filter-card {
        background: white;
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: var(--shadow, 0 1px 3px rgba(0,0,0,0.08));
        margin-bottom: 1.5rem;
    }

    .cs-filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }

    .cs-filter-form select,
    .cs-filter-form input[type="text"] {
        border: 1.5px solid var(--border, #e5e7eb);
        border-radius: 8px;
        padding: 0.55rem 0.9rem;
        font-size: 0.9rem;
        font-family: inherit;
        color: #374151;
        outline: none;
        transition: border-color 0.2s;
    }

    .cs-filter-form select {
        min-width: 180px;
        background: #fff;
    }

    .cs-filter-form input[type="text"] {
        flex: 1;
        min-width: 220px;
    }

    .cs-filter-form select:focus,
    .cs-filter-form input[type="text"]:focus {
        border-color: var(--primary, #2563eb);
    }
    .cs-bulk-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        margin-bottom: 0.75rem;
        display: none; /* muncul via JS saat ada yang dicentang */
    }
    
    .cs-bulk-bar.show {
        display: flex;
    }
    
    .cs-bulk-count {
        font-size: 0.85rem;
        font-weight: 600;
        color: #9a3412;
    }
    
    .cs-checkbox {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }

    .cs-btn {
        border: none;
        border-radius: 8px;
        padding: 0.55rem 1.3rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .cs-btn-primary {
        background: var(--primary, #2563eb);
        color: white;
    }

    .cs-btn-primary:hover {
        background: var(--primary-dark, #1d4ed8);
    }

    .cs-btn-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .cs-btn-warning:hover {
        background: #fde68a;
    }

    .cs-btn-danger {
        background: #fee2e2;
        color: #b91c1c;
    }

    .cs-btn-danger:hover {
        background: #fecaca;
    }

    .cs-btn-sm {
        padding: 0.4rem 0.7rem;
        font-size: 0.8rem;
    }

    .cs-empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #94a3b8;
        background: white;
        border: 1px dashed var(--border, #e5e7eb);
        border-radius: 12px;
    }

    .cs-empty-state i {
        font-size: 2rem;
        margin-bottom: 0.75rem;
        display: block;
        color: #cbd5e1;
    }

    .cs-table-card {
        background: white;
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow, 0 1px 3px rgba(0,0,0,0.08));
    }

    .cs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }

    .cs-table thead {
        background: var(--light, #f8fafc);
    }

    .cs-table th {
        text-align: left;
        padding: 0.85rem 1rem;
        color: #64748b;
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 1px solid var(--border, #e5e7eb);
    }

    .cs-table td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: #374151;
        vertical-align: middle;
    }

    .cs-table tbody tr:last-child td {
        border-bottom: none;
    }

    .cs-table tbody tr:hover {
        background: #fafbff;
    }

    .cs-badge {
        display: inline-block;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 0.76rem;
        font-weight: 600;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        margin: 0.15rem 0.25rem 0.15rem 0;
    }

    .cs-muted {
        color: #94a3b8;
        font-style: italic;
        font-size: 0.85rem;
    }

    .cs-actions {
        display: flex;
        gap: 0.4rem;
    }

    @media (max-width: 640px) {
        .cs-filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        .cs-filter-form select,
        .cs-filter-form input[type="text"],
        .cs-btn-primary {
            width: 100%;
        }
    }
</style>

<div class="cs-wrap">
    <div class="cs-title">
        <i class="fas fa-user-graduate"></i> Cek Siswa
    </div>

    <div class="cs-filter-card">
        <form method="get" class="cs-filter-form">
            <select name="kelas">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelasList as $k): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= $kelas === $k ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="nama" placeholder="Cari nama / username..."
                   value="<?= htmlspecialchars($nama) ?>">

            <button type="submit" class="cs-btn cs-btn-primary">
                <i class="fas fa-search"></i> Filter
            </button>
        </form>
    </div>

    <?php if (!$filterDiisi): ?>
        <div class="cs-empty-state">
            <i class="fas fa-filter"></i>
            Silakan pilih kelas atau masukkan nama siswa untuk menampilkan data.
        </div>
        <?php else: ?>
        <form method="post" action="<?= config('app_url') ?>/admin/hapusSiswa" id="bulkDeleteForm"
              onsubmit="return confirm('Yakin ingin menghapus semua siswa yang dipilih beserta nilainya?');">
            <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>">
            <input type="hidden" name="nama" value="<?= htmlspecialchars($nama) ?>">

            <div class="cs-bulk-bar" id="bulkBar">
                <span class="cs-bulk-count" id="bulkCount">0 siswa dipilih</span>
                <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">
                    <i class="fas fa-trash-alt"></i> Hapus yang Dipilih
                </button>
            </div>

            <div class="cs-table-card">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" class="cs-checkbox" id="checkAll">
                            </th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Kelas</th>
                            <th>Nilai per Mapel</th>
                            <th style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($siswaList)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:2rem; color:#94a3b8;">
                                    Tidak ada siswa yang cocok dengan filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($siswaList as $s): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="cs-checkbox cs-row-check"
                                               name="ids[]" value="<?= $s['id'] ?>">
                                    </td>
                                    <td style="font-weight:600; color:#1e293b;"><?= htmlspecialchars($s['full_name']) ?></td>
                                    <td><?= htmlspecialchars($s['username']) ?></td>
                                    <td><?= htmlspecialchars($s['kelas'] ?? '-') ?></td>
                                    <td>
                                        <?php if (empty($s['nilai'])): ?>
                                            <span class="cs-muted">Belum ada nilai</span>
                                        <?php else: ?>
                                            <?php foreach ($s['nilai'] as $n): ?>
                                                <span class="cs-badge">
                                                    <?= htmlspecialchars($n['subject']) ?>: <?= htmlspecialchars($n['nilai']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= config('app_url') ?>/admin/editSiswa?id=<?= $s['id'] ?>&kelas=<?= urlencode($kelas) ?>&nama=<?= urlencode($nama) ?>"
                                           class="cs-btn cs-btn-warning cs-btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php endif; ?>
</div>
<script>
    (function() {
        var checkAll   = document.getElementById('checkAll');
        var rowChecks  = document.querySelectorAll('.cs-row-check');
        var bulkBar    = document.getElementById('bulkBar');
        var bulkCount  = document.getElementById('bulkCount');

        if (!checkAll) return; // tidak ada tabel (state kosong)

        function updateBulkBar() {
            var checked = document.querySelectorAll('.cs-row-check:checked');
            if (checked.length > 0) {
                bulkBar.classList.add('show');
                bulkCount.textContent = checked.length + ' siswa dipilih';
            } else {
                bulkBar.classList.remove('show');
            }
            checkAll.checked = checked.length === rowChecks.length && rowChecks.length > 0;
        }

        checkAll.addEventListener('change', function() {
            rowChecks.forEach(function(cb) { cb.checked = checkAll.checked; });
            updateBulkBar();
        });

        rowChecks.forEach(function(cb) {
            cb.addEventListener('change', updateBulkBar);
        });
    })();
</script>
<script src="<?= asset_url('vendor/bootstrap/5.3.2/dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>