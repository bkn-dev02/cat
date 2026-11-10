<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Siswa dari E-Learning - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
        }
        
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        body {
            background: var(--light);
        }
        
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .main-content {
            padding: 2rem 0;
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
        
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 1.5rem;
            font-weight: 700;
            font-size: 1.125rem;
        }
        
        .table {
            margin: 0;
        }
        
        .table thead th {
            background: var(--light);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            color: #64748b;
            border: none;
            padding: 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 0.5rem;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="<?= config('app_url') ?>/dashboard">
                <img src="<?= asset_url('cat.png') ?>" alt="CAT Logo" style="height: 40px; width: auto;">
                <span>CAT Enterprise</span>
            </a>
        </div>
    </nav>

    <div class="container main-content">
        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <?= $_SESSION['info']; unset($_SESSION['info']); ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-sync-alt"></i> Sync Siswa dari E-Learning
            </h1>
            <p class="text-muted">Import data siswa dari sistem e-learning ke CAT Enterprise</p>
        </div>

        <!-- Students List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Siswa E-Learning</span>
                <a href="<?= config('app_url') ?>/dashboard" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($elearningStudents)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Tidak ada data siswa dari e-learning</p>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?= config('app_url') ?>/sync/students/sync">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Kelas</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($elearningStudents as $student): ?>
                                        <?php 
                                            // Mapping kolom dari e-learning
                                            $username = $student['email'] ?? 'N/A';
                                            $fullName = $student['name'] ?? $username;
                                            $kelas = $student['class_id'] ?? '-';
                                            
                                            // Debug: tampilkan raw data
                                            // var_dump($student);
                                            
                                            $exists = in_array($username, $existingStudents);
                                        ?>
                                        <tr>
                                            <td>
                                                <input 
                                                    type="checkbox" 
                                                    class="form-check-input student-checkbox" 
                                                    name="students[]" 
                                                    value="<?= htmlspecialchars($username) ?>"
                                                    <?= $exists ? 'disabled' : '' ?>
                                                >
                                            </td>
                                            <td><?= htmlspecialchars($username) ?></td>
                                            <td><?= htmlspecialchars($fullName) ?></td>
                                            <td>
                                                <span class="badge primary">
                                                    <?= htmlspecialchars($kelas) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($exists): ?>
                                                    <span class="badge success">
                                                        <i class="fas fa-check"></i> Sudah Ada
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge warning">
                                                        <i class="fas fa-clock"></i> Belum Sync
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="p-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync-alt"></i> Sync Siswa Terpilih
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        // Select all checkbox
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox:not([disabled])');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>
