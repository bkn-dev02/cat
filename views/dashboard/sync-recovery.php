<?php
if (!isLoggedIn() || auth()['role'] === 'siswa') {
    redirect('dashboard');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Recovery - CAT Enterprise</title>
    <link rel="icon" type="image/png" href="<?= asset_url('cat.png') ?>">
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --purple: #8b5cf6;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        body {
            background: var(--light);
            color: var(--dark);
        }
        
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-bottom: 1px solid var(--border);
        }
        
        .navbar-brand {
            font-weight: 800;
            color: var(--primary);
        }
        
        .container-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .header-section {
            background: white;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .header-section h1 {
            color: var(--purple);
            margin-bottom: 0.5rem;
        }
        
        .header-section p {
            color: #64748b;
            margin: 0;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--purple) 0%, #7c3aed 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .label {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .table-section {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--dark);
            color: white;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h2 {
            margin: 0;
            font-size: 1.25rem;
        }
        
        .btn-sync-all {
            background: var(--purple);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .btn-sync-all:hover {
            background: #7c3aed;
        }
        
        .btn-sync-all:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead th {
            background: #f1f5f9;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid var(--border);
        }
        
        tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        tbody tr:hover {
            background: #f8fafc;
        }
        
        .checkbox-cell {
            text-align: center;
        }
        
        .badge-nilai {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-nilai.low {
            background: #fef2f2;
            color: var(--danger);
        }
        
        .badge-nilai.recovery {
            background: #f0fdf4;
            color: var(--success);
        }
        
        .badge-nilai.high {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--purple);
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .alert-info {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }

        .controls-section {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .controls-section h3 {
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .filter-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }

        .filter-group select {
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= config('app_url') ?>/dashboard">
            <i class="fas fa-graduation-cap"></i> CAT Enterprise
        </a>
        <span class="navbar-text ms-auto">
            <i class="fas fa-user-circle"></i> <?= htmlspecialchars(auth()['full_name'] ?? auth()['name']) ?>
        </span>
    </div>
</nav>

<!-- Main Content -->
<div class="container-main">
    <a href="<?= config('app_url') ?>/dashboard" class="back-link">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
    </a>
    
    <!-- Header -->
    <div class="header-section">
        <h1><i class="fas fa-sync-alt"></i> Sync Recovery Nilai</h1>
        <p>Sinkronisasi nilai dari jawaban siswa yang tersimpan untuk seleksi dan perbaikan nilai</p>
        
        <div class="stats-row">
            <div class="stat-card">
                <div class="number"><?= count($syncData) ?></div>
                <div class="label">Total Data</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, var(--success) 0%, #059669 100%);">
                <div class="number"><?= count(array_filter($syncData, fn($d) => $d['nilai_recovery'] > $d['nilai_saat_ini'])) ?></div>
                <div class="label">Bisa Di-Recovery</div>
            </div>
        </div>
    </div>
    
    <!-- Alert Info -->
    <div class="alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Informasi:</strong> Pilih siswa yang ingin di-sync recovery nilainya. Sistem akan menghitung nilai dari jawaban yang tersimpan dan membandingkan dengan nilai saat ini.
    </div>
    
    <!-- Controls -->
    <div class="controls-section">
        <h3><i class="fas fa-filter"></i> Filter & Aksi</h3>
        <div class="filter-group">
            <div>
                <label for="filterExam">Ujian:</label>
                <select id="filterExam" class="form-select" onchange="filterTable()">
                    <option value="">Semua Ujian</option>
                    <?php
                    $exams = array_unique(array_column($syncData, 'ujian'));
                    foreach ($exams as $exam):
                    ?>
                        <option value="<?= htmlspecialchars($exam) ?>"><?= htmlspecialchars($exam) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filterKelas">Kelas:</label>
                <select id="filterKelas" class="form-select" onchange="filterTable()">
                    <option value="">Semua Kelas</option>
                    <?php
                    $kelas = array_unique(array_column($syncData, 'kelas'));
                    foreach ($kelas as $k):
                    ?>
                        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                <button class="btn-sync-all" onclick="selectAll()">
                    <i class="fas fa-check-square"></i> Pilih Semua
                </button>
                <button class="btn-sync-all" style="background: #64748b;" onclick="deselectAll()">
                    <i class="fas fa-square"></i> Batal Pilih
                </button>
            </div>
        </div>
    </div>
    
    <!-- Table Section -->
    <div class="table-section">
        <?php if (empty($syncData)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>Tidak Ada Data</h3>
                <p>Semua siswa sudah memiliki nilai yang sesuai atau tidak ada jawaban yang tersimpan.</p>
            </div>
        <?php else: ?>
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Daftar Sync Recovery</h2>
                <button class="btn-sync-all" onclick="applySyncRecovery()" id="btnApplySync">
                    <i class="fas fa-sync-alt"></i> Sync Pilihan
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table id="syncTable">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                            </th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Ujian</th>
                            <th>Nilai Saat Ini</th>
                            <th>Nilai Recovery</th>
                            <th>Selisih</th>
                            <th>Jawaban</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($syncData as $row): ?>
                            <tr class="sync-row" data-exam="<?= htmlspecialchars($row['ujian']) ?>" data-kelas="<?= htmlspecialchars($row['kelas']) ?>">
                                <td class="checkbox-cell">
                                    <input type="checkbox" class="sync-checkbox" value="<?= $row['session_id'] ?>">
                                </td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['kelas']) ?></td>
                                <td><?= htmlspecialchars($row['ujian']) ?></td>
                                <td>
                                    <span class="badge-nilai low"><?= number_format($row['nilai_saat_ini'], 2) ?></span>
                                </td>
                                <td>
                                    <span class="badge-nilai recovery"><?= number_format($row['nilai_recovery'], 2) ?></span>
                                </td>
                                <td>
                                    <?php $diff = $row['nilai_recovery'] - $row['nilai_saat_ini']; ?>
                                    <span style="color: <?= $diff > 0 ? 'var(--success)' : '#64748b' ?>; font-weight: 600;">
                                        <?= $diff > 0 ? '+' : '' ?><?= number_format($diff, 2) ?>
                                    </span>
                                </td>
                                <td><?= $row['jumlah_jawaban'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
<script>
function filterTable() {
    const examFilter = document.getElementById('filterExam').value.toLowerCase();
    const kelasFilter = document.getElementById('filterKelas').value.toLowerCase();
    const rows = document.querySelectorAll('.sync-row');
    
    rows.forEach(row => {
        const exam = row.dataset.exam.toLowerCase();
        const kelas = row.dataset.kelas.toLowerCase();
        
        const examMatch = !examFilter || exam.includes(examFilter);
        const kelasMatch = !kelasFilter || kelas.includes(kelasFilter);
        
        row.style.display = (examMatch && kelasMatch) ? '' : 'none';
    });
}

function selectAll() {
    document.querySelectorAll('.sync-checkbox:not([style*="display: none"])').forEach(cb => {
        cb.checked = true;
    });
}

function deselectAll() {
    document.querySelectorAll('.sync-checkbox').forEach(cb => {
        cb.checked = false;
    });
}

function toggleSelectAll(checkbox) {
    document.querySelectorAll('.sync-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

function applySyncRecovery() {
    const selected = Array.from(document.querySelectorAll('.sync-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Pilih minimal satu siswa untuk di-sync');
        return;
    }
    
    if (!confirm(`Yakin ingin sync ${selected.length} siswa?`)) {
        return;
    }
    
    const btn = document.getElementById('btnApplySync');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Proses...';
    
    const formData = new FormData();
    selected.forEach(id => formData.append('session_ids[]', id));
    
    fetch('<?= config('app_url') ?>/dashboard/applySyncRecovery', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`✓ Sync selesai!\n\nBerhasil: ${data.updated}\nGagal: ${data.failed}`);
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('❌ Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Sync Pilihan';
        }
    })
    .catch(err => {
        alert('❌ Error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Sync Pilihan';
    });
}
</script>

</body>
</html>
