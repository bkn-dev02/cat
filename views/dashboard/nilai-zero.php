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
    <title>Fix Nilai 0 - CAT Enterprise</title>
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
            color: var(--danger);
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
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
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
        }
        
        .table-header h2 {
            margin: 0;
            font-size: 1.25rem;
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
        
        .badge-zero {
            background: var(--danger);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-recovery {
            background: var(--success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .btn-detail {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-detail:hover {
            background: #4f46e5;
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--success);
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

        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .answer-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--border);
        }

        .answer-item.correct {
            border-left-color: var(--success);
            background: #f0fdf4;
        }

        .answer-item.wrong {
            border-left-color: var(--danger);
            background: #fef2f2;
        }

        .answer-question {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .answer-details {
            font-size: 0.875rem;
            color: #64748b;
        }

        .answer-details div {
            margin: 0.25rem 0;
        }

        .summary-box {
            background: #f1f5f9;
            padding: 1.5rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
        }

        .summary-value {
            color: var(--primary);
            font-weight: 700;
        }

        .btn-recovery {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-recovery:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-recovery:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
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
        <h1><i class="fas fa-exclamation-circle"></i> Fix Nilai 0</h1>
        <p>Perbaiki nilai siswa yang bernilai 0 dengan recovery dari jawaban yang tersimpan</p>
        
        <div class="stats-row">
            <div class="stat-card">
                <div class="number"><?= count($nilaiZero) ?></div>
                <div class="label">Total Nilai 0</div>
            </div>
        </div>
    </div>
    
    <!-- Alert Info -->
    <div class="alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Informasi:</strong> Tabel di bawah menampilkan semua siswa dengan nilai 0. 
        Klik tombol "Lihat Detail" untuk melihat jawaban siswa dan nilai yang bisa di-recovery. 
        Anda bisa memilih untuk menggunakan nilai recovery atau input nilai manual.
    </div>
    
    <!-- Table Section -->
    <div class="table-section">
        <?php if (empty($nilaiZero)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>Tidak Ada Nilai 0</h3>
                <p>Semua siswa sudah memiliki nilai yang sesuai. Bagus!</p>
            </div>
        <?php else: ?>
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Daftar Siswa dengan Nilai 0</h2>
            </div>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Ujian</th>
                            <th>Nilai Saat Ini</th>
                            <th>Jawaban</th>
                            <th>Recovery</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($nilaiZero as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['kelas']) ?></td>
                                <td><?= htmlspecialchars($row['ujian']) ?></td>
                                <td>
                                    <span class="badge-zero"><?= $row['score'] ?></span>
                                </td>
                                <td><?= $row['jumlah_jawaban'] ?></td>
                                <td>
                                    <?php if ($row['nilai_recovery'] > 0): ?>
                                        <span class="badge-recovery"><?= $row['nilai_recovery'] ?></span>
                                    <?php else: ?>
                                        <span style="color: #64748b;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-detail" 
                                            onclick="showDetail(<?= $row['session_id'] ?>)">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Detail Jawaban Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
<script>
let detailModal;

function showDetail(sessionId) {
    if (!detailModal) {
        detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
    }
    
    const content = document.getElementById('detailContent');
    content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    fetch('<?= config('app_url') ?>/dashboard/detailNilaiZero?session_id=' + sessionId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderDetail(data);
            } else {
                content.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(err => {
            console.error('Error:', err);
            content.innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
        });
    
    detailModal.show();
}

function renderDetail(data) {
    const session = data.session;
    const answers = data.answers;
    const summary = data.summary;
    
    let html = `
        <div class="summary-box">
            <div style="margin-bottom: 1rem;">
                <strong>${session.full_name}</strong> (${session.kelas})<br>
                <small style="color: #64748b;">${session.ujian}</small>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Soal:</span>
                <span class="summary-value">${summary.total_questions}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Jawaban Benar:</span>
                <span class="summary-value">${summary.correct_answers}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Nilai Recovery:</span>
                <span class="summary-value" style="color: var(--success);">${summary.calculated_score}</span>
            </div>
        </div>
    `;
    
    if (answers.length > 0) {
        html += '<h6 style="margin-top: 1.5rem; margin-bottom: 1rem;">Jawaban Siswa:</h6>';
        
        answers.forEach((answer, idx) => {
            const isCorrect = answer.is_correct;
            const className = isCorrect ? 'correct' : 'wrong';
            const icon = isCorrect ? '<i class="fas fa-check" style="color: var(--success);"></i>' : '<i class="fas fa-times" style="color: var(--danger);"></i>';
            
            html += `
                <div class="answer-item ${className}">
                    <div class="answer-question">${icon} Soal ${idx + 1}</div>
                    <div class="answer-details">
                        <div><strong>Pertanyaan:</strong> ${answer.question_text.substring(0, 100)}...</div>
                        <div><strong>Jawaban Siswa:</strong> <span style="background: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">${answer.student_answer}</span></div>
                        <div><strong>Jawaban Benar:</strong> <span style="background: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">${answer.correct_answer}</span></div>
                        <div><strong>Poin:</strong> ${answer.points} ${isCorrect ? '✓' : '✗'}</div>
                    </div>
                </div>
            `;
        });
    } else {
        html += '<div class="alert alert-warning">Tidak ada jawaban yang tersimpan</div>';
    }
    
    // Add action buttons
    html += `
        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; flex-direction: column;">
            ${summary.calculated_score > 0 ? `
                <button class="btn-recovery" onclick="fixWithRecovery(${session.id})">
                    <i class="fas fa-check"></i> Gunakan Nilai Recovery (${summary.calculated_score})
                </button>
            ` : ''}
            <button class="btn-recovery" style="background: var(--warning);" onclick="showManualInput(${session.id})">
                <i class="fas fa-edit"></i> Input Nilai Manual
            </button>
        </div>
    `;
    
    document.getElementById('detailContent').innerHTML = html;
}

function fixWithRecovery(sessionId) {
    if (!confirm('Yakin ingin menggunakan nilai recovery?')) {
        return;
    }
    
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Proses...';
    
    const formData = new FormData();
    formData.append('session_id', sessionId);
    formData.append('use_recovery', '1');
    
    fetch('<?= config('app_url') ?>/dashboard/fixNilaiZero', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`✓ Nilai berhasil diperbaiki!\n\nSiswa: ${data.data.student}\nUjian: ${data.data.exam}\nNilai Baru: ${data.data.nilai_baru}`);
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('❌ Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Gunakan Nilai Recovery';
        }
    })
    .catch(err => {
        alert('❌ Error: ' + err.message);
        btn.disabled = false;
    });
}

function showManualInput(sessionId) {
    const nilai = prompt('Masukkan nilai (0-100):');
    if (nilai === null) return;
    
    const nilaiNum = parseFloat(nilai);
    if (isNaN(nilaiNum) || nilaiNum < 0 || nilaiNum > 100) {
        alert('Nilai harus antara 0-100');
        return;
    }
    
    if (!confirm(`Yakin ingin mengubah nilai menjadi ${nilaiNum}?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('session_id', sessionId);
    formData.append('nilai_baru', nilaiNum);
    
    fetch('<?= config('app_url') ?>/dashboard/fixNilaiZero', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`✓ Nilai berhasil diperbaiki!\n\nSiswa: ${data.data.student}\nUjian: ${data.data.exam}\nNilai Baru: ${data.data.nilai_baru}`);
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(err => {
        alert('❌ Error: ' + err.message);
    });
}
</script>

</body>
</html>
