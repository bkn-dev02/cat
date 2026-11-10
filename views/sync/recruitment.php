<?php
$user = $_SESSION['user'];
$appUrl = config('app_url');

// Pastikan variabel tersedia dengan nilai default jika belum ada
$recruitmentApplicants = $recruitmentApplicants ?? [];
$existingUsernames = $existingUsernames ?? [];
$ppdbError = $ppdbError ?? null;
$ppdbStudents = $ppdbStudents ?? [];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync e-recruitment</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/bootstrap-icons/1.10.0/font/bootstrap-icons.css') ?>" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= asset_url('cat.png') ?>">
    <link rel="manifest" href="<?= app_path_url('manifest.json') ?>">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="CAT Enterprise">
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
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
            line-height: 1.6;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        
        /* Navbar */
        .navbar {
            background: white;
            box-shadow: var(--shadow);
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .navbar-brand:hover {
            color: var(--primary-dark);
        }
        
        .nav-link {
            color: var(--dark);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-link:hover {
            background: var(--light);
            color: var(--primary);
        }
        
        .nav-link.active {
            background: var(--primary);
            color: white;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--light);
            border-radius: 0.5rem;
            margin-right: 0.75rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--dark);
        }
        
        .btn-logout {
            background: white;
            border: 1px solid var(--border);
            color: var(--dark);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-logout:hover {
            background: var(--danger);
            border-color: var(--danger);
            color: white;
        }

        /* ── User Dropdown ── */
        .user-dropdown-wrap {
            position: relative;
        }

        .user-trigger {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: none;
            border: 1.5px solid var(--border);
            border-radius: 50px;
            padding: 0.3rem 0.9rem 0.3rem 0.3rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .user-trigger:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.04);
        }

        .user-trigger .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
            margin-right: 0;
        }

        .user-trigger .user-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.875rem;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-chevron {
            font-size: 0.65rem;
            color: #94a3b8;
            transition: transform 0.25s;
        }

        .user-chevron.open {
            transform: rotate(180deg);
        }

        .user-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: white;
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            box-shadow: 0 12px 30px rgba(0,0,0,0.13);
            min-width: 220px;
            padding: 0.5rem;
            z-index: 9999;
            animation: dropIn 0.18s ease;
        }

        .user-dropdown-menu.open {
            display: block;
        }

        @keyframes dropIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .udm-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.75rem 0.5rem;
        }

        .udm-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .udm-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.875rem;
        }

        .udm-role {
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 1px;
        }

        .udm-divider {
            height: 1px;
            background: var(--border);
            margin: 0.3rem 0;
        }

        .udm-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            text-decoration: none;
            transition: all 0.15s;
        }

        .udm-item:hover {
            background: rgba(99, 102, 241, 0.08);
            color: #6366f1;
            text-decoration: none;
        }

        .udm-item i {
            width: 16px;
            text-align: center;
            font-size: 0.8rem;
        }

        .udm-logout { color: #dc2626; }
        .udm-logout:hover {
            background: rgba(220, 38, 38, 0.08);
            color: #dc2626;
        }
        
        /* Main Content */
        .main-content {
            padding: 2rem 0;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #64748b;
            font-size: 1rem;
        }
        
        /* Alert */
        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .stat-icon.primary {
            background: #eef2ff;
            color: var(--primary);
        }
        
        .stat-icon.success {
            background: #d1fae5;
            color: var(--success);
        }
        
        .stat-icon.warning {
            background: #fef3c7;
            color: var(--warning);
        }
        
        .stat-icon.secondary {
            background: #f3e8ff;
            color: var(--secondary);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.75rem;
        }
        
        .stat-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .stat-role {
            font-size: 0.875rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-stat {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-stat:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-1px);
        }
        
        /* Activity Card */
        .activity-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .activity-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .activity-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            margin: 0;
        }
        
        .table thead th {
            background: var(--light);
            color: #64748b;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.75rem 1rem;
            border: none;
        }
        
        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table tbody tr:hover {
            background: var(--light);
        }
        
        /* Badge */
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge.primary {
            background: #dbeafe;
            color: #1e40af;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-icon {
            width: 64px;
            height: 64px;
            background: var(--light);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }
        
        .empty-text {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .back-navigation { 
            display: inline-flex; 
            align-items: center; 
            gap: 0.55rem; 
            padding: 0.45rem 0.75rem 0.45rem 0.45rem; 
            margin: 0; 
            border-radius: 10px; 
            color: #475569 !important; 
            text-decoration: none; 
            font-size: 13px; 
            font-weight: 600; 
            line-height: 1; 
            transition: all 0.2s ease; 
        } 
        .back-navigation:hover {
            color: #2563eb !important; 
            background: #eff6ff; 
        } 
        .back-icon { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            width: 30px; 
            height: 30px; 
            border-radius: 8px; 
            background: #f1f5f9; 
            color: #64748b; 
            font-size: 12px; 
            transition: all 0.2s ease; 
        } 
        .back-navigation:hover .back-icon { 
            background: #dbeafe; 
            color: #2563eb; 
            transform: translateX(-2px); 
        } 
        .back-text {
            white-space: 
            nowrap; 
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .user-info {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-light">
    
    <!--navbar-->
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container py-4">
    
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    
        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="bi bi-info-circle-fill me-2"></i><?= $_SESSION['info'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>
    
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <a class="navbar-brand back-navigation" href="<?= config('app_url') ?>/dashboard" > <span class="back-icon"> <i class="fas fa-arrow-left"></i> </span> <span class="back-text"> Kembali </span> </a>
    
        <div class="card shadow-sm mt-2">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bi bi-people-fill text-primary me-2"></i>User E-recruitment Tahap Tes Akademik</h5>
                    <small class="text-muted">Sumber: e-recruitment.binakasihnusantara.sch.id</small>
                </div>
                <?php if (!$ppdbError && !empty($recruitmentApplicants)): ?>
                    <span class="badge bg-primary fs-6"><?= count($recruitmentApplicants) ?> Pelamar ditemukan</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
    
                <?php if ($ppdbError): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Koneksi e-recruitment Gagal</strong><br>
                        <?= htmlspecialchars($ppdbError) ?>
                        <hr>
                        <small>Solusi: Login ke Hostinger hPanel → Databases → MySQL Databases → Add User To Database → pilih user <code>u245247469_enterprise</code> dan database <code>u245247469_ppdb_bkn</code> → grant <strong>SELECT</strong>.</small>
                    </div>
    
                <?php elseif (empty($recruitmentApplicants)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Tidak ada data pelamar</strong><br>
                        Belum ada pelamar yang masuk ke tahap tes akademik dari e-recruitment.
                    </div>
    
                <?php else: ?>
                    <form method="POST" action="<?= $appUrl ?>/sync/syncRecruitment">
                        <div class="d-flex gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-sm ms-auto">
                                <i class="bi bi-cloud-download-fill me-1"></i> Sync yang Dipilih
                            </button>
                        </div>
    
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40"><input type="checkbox" id="checkAll" onchange="toggleCheckAll(this.checked)"></th>
                                        <th width="50">No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email (Username)</th>
                                        <th>Posisi Melamar</th>
                                        <th>Departemen</th>
                                        <th width="130">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recruitmentApplicants as $i => $applicant): ?>
                                    <?php 
                                        // Cek apakah email sudah ada di sistem CAT
                                        $sudahAda = in_array($applicant['email'], $existingUsernames);
                                    ?>
                                    <tr class="<?= $sudahAda ? 'table-secondary' : '' ?>">
                                        <td>
                                            <?php if (!$sudahAda): ?>
                                                <input
                                                    type="checkbox"
                                                    name="students[]"
                                                    value="<?= $applicant['application_id'] ?>"
                                                    <?= $applicant['is_synced'] ? 'disabled' : '' ?>
                                                    class="student-check"
                                                >
                                            <?php else: ?>
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($applicant['full_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($applicant['email']) ?></td>
                                        <td><?= htmlspecialchars($applicant['job_title'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($applicant['department'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($applicant['is_synced']) : ?>
                                                <span class="badge bg-success">
                                                    Sudah Sync
                                                </span>
                                            <?php else : ?>
                                                <span class="badge bg-secondary">
                                                    Belum Sync
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
    
                        <?php if (!empty($recruitmentApplicants)): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                Centang pelamar yang akan disinkronkan ke sistem CAT
                            </small>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cloud-download-fill me-1"></i> Sync yang Dipilih ke CAT
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
    
            </div>
        </div>
    </div>

<script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
<script>
// Fungsi untuk toggle semua checkbox
function toggleAll(checked) {
    const checkboxes = document.querySelectorAll('.student-check');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = checked;
    });
    
    // Update status checkAll jika ada
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.checked = checked;
    }
}

// Fungsi untuk toggle berdasarkan checkbox master
function toggleCheckAll(checked) {
    const checkboxes = document.querySelectorAll('.student-check');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = checked;
    });
}

// Update status checkbox master saat checkbox individual berubah
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.student-check');
    const checkAll = document.getElementById('checkAll');
    
    if (checkAll && checkboxes.length > 0) {
        // Event listener untuk setiap checkbox
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(checkboxes).every(function(cb) {
                    return cb.checked === true;
                });
                checkAll.checked = allChecked;
                
                const someChecked = Array.from(checkboxes).some(function(cb) {
                    return cb.checked === true;
                });
                checkAll.indeterminate = !allChecked && someChecked;
            });
        });
        
        // Inisialisasi status awal
        const allChecked = Array.from(checkboxes).every(function(cb) {
            return cb.checked === true;
        });
        checkAll.checked = allChecked;
        
        const someChecked = Array.from(checkboxes).some(function(cb) {
            return cb.checked === true;
        });
        checkAll.indeterminate = !allChecked && someChecked;
    }
});

</script>
</body>
</html>