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
    <title>Audit Aktivitas - CAT Enterprise</title>
    <link rel="icon" type="image/png" href="<?= asset_url('cat.png') ?>">
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --warning: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
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
            max-width: 1400px;
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
            color: var(--warning);
            margin-bottom: 0.5rem;
        }
        
        .header-section p {
            color: #64748b;
            margin: 0;
        }
        
        .controls-section {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
        
        .filter-group select, .filter-group input {
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            font-size: 0.875rem;
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
            font-size: 0.875rem;
        }
        
        tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }
        
        tbody tr:hover {
            background: #f8fafc;
        }
        
        .badge-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-status.submitted {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-status.in_progress {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-status.graded {
            background: #dbeafe;
            color: #1e40af;
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
            color: var(--warning);
            margin-bottom: 1rem;
        }
        
        .alert-info {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
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

        .answer-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .answer-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }

        .answer-item {
            position: relative;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.375rem;
            border-left: 4px solid var(--border);
        }

        .answer-item::before {
            content: '';
            position: absolute;
            left: -2.5rem;
            top: 1.5rem;
            width: 12px;
            height: 12px;
            background: var(--primary);
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 0 0 2px var(--primary);
        }

        .answer-item.correct {
            border-left-color: var(--success);
            background: #f0fdf4;
        }

        .answer-item.correct::before {
            background: var(--success);
            box-shadow: 0 0 0 2px var(--success);
        }

        .answer-item.wrong {
            border-left-color: var(--danger);
            background: #fef2f2;
        }

        .answer-item.wrong::before {
            background: var(--danger);
            box-shadow: 0 0 0 2px var(--danger);
        }

        .answer-time {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.5rem;
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

        .sessions-list {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-top: 1rem;
        }

        .session-item {
            padding: 0.75rem;
            background: white;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid var(--warning);
        }

        .session-item.current {
            border-left-color: var(--primary);
            background: #eef2ff;
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
        <h1><i class="fas fa-history"></i> Audit Aktivitas Siswa</h1>
        <p>Lihat detail aktivitas ujian siswa - jawaban, waktu, status submit, dan multiple submit</p>
    </div>
    
    <!-- Alert Info -->
    <div class="alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Informasi:</strong> Gunakan fitur ini untuk diagnosa siswa yang nilainya 0 atau mencurigakan. Lihat berapa jawaban yang dikerjakan, kapan login, apakah sudah submit, dan jika ada multiple submit.
    </div>
    
    <!-- Controls -->
    <div class="controls-section">
        <h3><i class="fas fa-filter"></i> Filter</h3>
        <div class="filter-group">
            <div>
                <label for="filterExam">Ujian:</label>
                <select id="filterExam" class="form-select" onchange="filterTable()">
                    <option value="">Semua Ujian</option>
                    <?php foreach ($allExams as $exam): ?>
                        <option value="<?= htmlspecialchars($exam['title']) ?>"><?= htmlspecialchars($exam['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filterKelas">Kelas:</label>
                <select id="filterKelas" class="form-select" onchange="filterTable()">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($allKelas as $k): ?>
                        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filterNama">Cari Nama:</label>
                <input type="text" id="filterNama" class="form-control" placeholder="Ketik nama siswa..." onkeyup="filterTable()">
            </div>
        </div>
    </div>
    
    <!-- Table Section -->
    <div class="table-section">
        <?php if (empty($auditData)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Tidak Ada Data</h3>
                <p>Tidak ada aktivitas ujian yang ditemukan.</p>
            </div>
        <?php else: ?>
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Daftar Aktivitas Siswa (<?= count($auditData) ?> data)</h2>
            </div>
            
            <div style="overflow-x: auto;">
                <table id="auditTable">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Ujian</th>
                            <th>Status</th>
                            <th>Nilai</th>
                            <th>Jawaban</th>
                            <th>Login</th>
                            <th>Submit</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditData as $row): ?>
                            <tr class="audit-row" 
                                data-exam="<?= htmlspecialchars($row['ujian'] ?? '') ?>" 
                                data-kelas="<?= htmlspecialchars($row['kelas'] ?? '') ?>"
                                data-nama="<?= htmlspecialchars($row['full_name'] ?? '') ?>">
                                <td><?= htmlspecialchars($row['full_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['ujian'] ?? '-') ?></td>
                                <td>
                                    <span class="badge-status <?= $row['status'] ?? 'unknown' ?>">
                                        <?= ucfirst($row['status'] ?? 'unknown') ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= number_format($row['score'] ?? 0, 2) ?></strong>
                                </td>
                                <td>
                                    <span style="background: #dbeafe; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 600; color: #1e40af;">
                                        <?= $row['jumlah_jawaban'] ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= $row['started_at'] ? date('d M H:i', strtotime($row['started_at'])) : '-' ?></small>
                                </td>
                                <td>
                                    <small><?= $row['submitted_at'] ? date('d M H:i', strtotime($row['submitted_at'])) : '-' ?></small>
                                </td>
                                <td>
                                    <button class="btn-detail" onclick="showDetail(<?= $row['session_id'] ?>)">
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
                <h5 class="modal-title"><i class="fas fa-eye"></i> Detail Aktivitas Siswa</h5>
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

function filterTable() {
    const examFilter = document.getElementById('filterExam').value.toLowerCase();
    const kelasFilter = document.getElementById('filterKelas').value.toLowerCase();
    const namaFilter = document.getElementById('filterNama').value.toLowerCase();
    const rows = document.querySelectorAll('.audit-row');
    
    rows.forEach(row => {
        const exam = row.dataset.exam.toLowerCase();
        const kelas = row.dataset.kelas.toLowerCase();
        const nama = row.dataset.nama.toLowerCase();
        
        const examMatch = !examFilter || exam.includes(examFilter);
        const kelasMatch = !kelasFilter || kelas.includes(kelasFilter);
        const namaMatch = !namaFilter || nama.includes(namaFilter);
        
        row.style.display = (examMatch && kelasMatch && namaMatch) ? '' : 'none';
    });
}

function showDetail(sessionId) {
    if (!detailModal) {
        detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
    }
    
    const content = document.getElementById('detailContent');
    content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    fetch('<?= config('app_url') ?>/dashboard/detailAuditAktivitas?session_id=' + sessionId)
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
    const allSessions = data.all_sessions;
    const summary = data.summary;
    
    let html = `
        <div class="summary-box">
            <div style="margin-bottom: 1rem;">
                <strong>${session.full_name}</strong> (${session.kelas})<br>
                <small style="color: #64748b;">${session.ujian}</small>
            </div>
            <div class="summary-item">
                <span class="summary-label">Status:</span>
                <span class="summary-value">${session.status.toUpperCase()}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Nilai Saat Ini:</span>
                <span class="summary-value">${session.score}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Login:</span>
                <span class="summary-value">${session.started_at ? new Date(session.started_at).toLocaleString('id-ID') : '-'}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Submit:</span>
                <span class="summary-value">${session.submitted_at ? new Date(session.submitted_at).toLocaleString('id-ID') : 'BELUM SUBMIT'}</span>
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
    
    // Show all sessions for this exam
    if (summary.total_sessions > 1) {
        html += `
            <div class="sessions-list">
                <h6 style="margin-bottom: 1rem;"><i class="fas fa-exclamation-triangle"></i> ⚠️ MULTIPLE SUBMIT DETECTED (${summary.total_sessions} kali)</h6>
        `;
        allSessions.forEach((s, idx) => {
            const isCurrent = s.id == session.id;
            html += `
                <div class="session-item ${isCurrent ? 'current' : ''}">
                    <strong>Submit #${idx + 1}</strong> - 
                    Nilai: ${s.score} | 
                    Jawaban: ${s.jumlah_jawaban} | 
                    Login: ${s.started_at ? new Date(s.started_at).toLocaleString('id-ID') : '-'} | 
                    Submit: ${s.submitted_at ? new Date(s.submitted_at).toLocaleString('id-ID') : 'BELUM'}
                    ${isCurrent ? ' <span style="color: var(--primary); font-weight: 600;">(CURRENT)</span>' : ''}
                </div>
            `;
        });
        html += `</div>`;
    }
    
    // Show answers timeline
    if (answers.length > 0) {
        html += '<h6 style="margin-top: 1.5rem; margin-bottom: 1rem;"><i class="fas fa-list"></i> Timeline Jawaban:</h6>';
        html += '<div class="answer-timeline">';
        
        answers.forEach((answer, idx) => {
            const isCorrect = answer.is_correct;
            const className = isCorrect ? 'correct' : 'wrong';
            const icon = isCorrect ? '<i class="fas fa-check" style="color: var(--success);"></i>' : '<i class="fas fa-times" style="color: var(--danger);"></i>';
            const questionNum = idx + 1;
            
            html += `
                <div class="answer-item ${className}">
                    <div class="answer-time">
                        <i class="fas fa-clock"></i> ${new Date(answer.waktu_jawab).toLocaleString('id-ID')}
                    </div>
                    <div class="answer-question">${icon} Soal ${questionNum}</div>
                    <div class="answer-details">
                        <div><strong>Pertanyaan:</strong> ${answer.question_text.substring(0, 80)}...</div>
                        <div><strong>Jawaban Siswa:</strong> <span style="background: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 600;">${answer.student_answer}</span></div>
                        <div><strong>Jawaban Benar:</strong> <span style="background: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 600;">${answer.correct_answer}</span></div>
                        <div><strong>Poin:</strong> ${answer.points} ${isCorrect ? '✓' : '✗'}</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
    } else {
        html += '<div class="alert alert-warning">Tidak ada jawaban yang tersimpan</div>';
    }
    
    document.getElementById('detailContent').innerHTML = html;
}
</script>

</body>
</html>
