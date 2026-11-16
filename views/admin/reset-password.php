<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Siswa</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/fontawesome/6.0.0/css/all.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--light, #f8fafc);
        }

        .page-header {
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
            padding: 1rem 0;
            margin: 1rem auto;
            text-align: center;
            border-radius: 1rem;
        }

        .page-header h1 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .page-header p {
            color: #64748b;
            margin: .25rem 0 0;
            font-size: .9rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 8px rgba(0, 0, 0, .07);
        }

        .result-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            margin-top: 8px;
        }

        .result-box.ok {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .result-box.err {
            background: #fef2f2;
            border-color: #fecaca;
        }
    </style>
</head>

<body>

    <?php require_once VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container" style="max-width: 540px">

        <div class="page-header">
            <div class="container">
                <h1><i class="fas fa-key text-primary me-2"></i> Reset Password Siswa</h1>
                <p>Reset password siswa berdasarkan email / username.</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body p-4">
                <form method="POST" action="<?= config('app_url') ?>/admin/resetPassword">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email / Username Siswa</label>
                        <input type="text" name="email" class="form-control"
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="email@gmail.com" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Baru</label>
                        <input type="text" name="password" class="form-control"
                            value="<?= htmlspecialchars($newPass) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-key me-1"></i> Reset Password
                    </button>
                </form>
            </div>
        </div>

        <?php if ($result): ?>
            <div class="result-box <?= !empty($result['success']) ? 'ok' : 'err' ?>">
                <?php if (!empty($result['success'])): ?>
                    <p class="fw-semibold text-success mb-2">
                        <i class="fas fa-check-circle me-1"></i> Berhasil! Password <b><?= htmlspecialchars($result['nama']) ?></b> direset.
                    </p>
                    <p class="mb-1">Email: <code><?= htmlspecialchars($result['email']) ?></code></p>
                    <p class="mb-1">Password baru: <code><?= htmlspecialchars($result['pass']) ?></code></p>
                <?php else: ?>
                    <p class="fw-semibold text-danger mb-0">
                        <i class="fas fa-times-circle me-1"></i> <?= $result['error'] ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>

</html>