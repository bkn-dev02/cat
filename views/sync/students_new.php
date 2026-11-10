<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Siswa - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1rem 0; }
        .badge.primary { background: #dbeafe; color: #1e40af; }
        .badge.success { background: #d1fae5; color: #065f46; }
        .badge.warning { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="<?= config('app_url') ?>/dashboard">
                <img src="<?= asset_url('cat.png') ?>" alt="Logo" style="height: 40px;">
                CAT Enterprise
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="fas fa-sync-alt"></i> Sync Siswa dari E-Learning</h2>
        <p class="text-muted">Import data siswa dari sistem e-learning (Total: <?= count($elearningStudents) ?> siswa)</p>

        <div class="alert alert-warning">
            <strong>Reset & Sync Ulang:</strong> Jika banyak siswa yang tidak masuk, gunakan tombol di bawah untuk menghapus semua siswa lama dan sync ulang dari e-learning.
        </div>

        <form method="POST" action="<?= config('app_url') ?>/sync/resetsync" onsubmit="return confirm('PERINGATAN: Ini akan menghapus SEMUA siswa lama dan sync ulang dari e-learning. Lanjutkan?');">
            <button type="submit" class="btn btn-danger mb-3">
                <i class="fas fa-redo"></i> Reset & Sync Ulang Semua Siswa
            </button>
        </form>

        <hr>

        <div class="card mt-4">
            <div class="card-body">
                <form method="POST" action="<?= config('app_url') ?>/sync/syncStudents">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="form-check-input" id="selectAll"> Pilih Semua
                                </th>
                                <th>Username</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($elearningStudents as $student): ?>
                                <?php 
                                    $username = $student['email'];
                                    $fullName = $student['name'];
                                    $kelas = $student['class_id'] ?? '-';
                                    $exists = in_array($username, $existingStudents);
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input student-checkbox" name="students[]" value="<?= $username ?>" <?= $exists ? 'disabled' : '' ?>></td>
                                    <td><?= htmlspecialchars($username) ?></td>
                                    <td><?= htmlspecialchars($fullName) ?></td>
                                    <td><span class="badge primary"><?= htmlspecialchars($kelas) ?></span></td>
                                    <td>
                                        <?php if ($exists): ?>
                                            <span class="badge success">Sudah Ada</span>
                                        <?php else: ?>
                                            <span class="badge warning">Belum Sync</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-primary">Sync Siswa Terpilih</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Select all checkbox functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox:not([disabled])');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>
