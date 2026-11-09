<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masukkan Token - <?= htmlspecialchars($exam['title']) ?></title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .token-card {
            background: white;
            border-radius: 1.25rem;
            padding: 2.5rem;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            text-align: center;
        }

        .token-icon {
            width: 70px;
            height: 70px;
            background: #eef2ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 28px;
            color: #6366f1;
        }

        .token-input {
            font-size: 1.4rem;
            letter-spacing: 3px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.9rem;
            border-radius: 0.75rem;
            border: 2px solid #e2e8f0;
        }

        .token-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .btn-start {
            background: #6366f1;
            color: white;
            font-weight: 700;
            padding: 0.85rem;
            border-radius: 0.75rem;
            border: none;
            width: 100%;
            font-size: 1rem;
            margin-top: 1rem;
            transition: all 0.2s;
        }

        .btn-start:hover {
            background: #4f46e5;
        }

        .exam-meta {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 0.9rem 1.1rem;
            margin-bottom: 1.25rem;
            text-align: left;
            font-size: 0.85rem;
            color: #475569;
        }
    </style>
</head>

<body>
    <div class="token-card">
        <div class="token-icon"><i class="fas fa-key"></i></div>
        <h4 style="font-weight:800;color:#1e293b;margin-bottom:0.4rem;">Masukkan Token Ujian</h4>
        <p style="color:#64748b;font-size:0.9rem;margin-bottom:1.5rem;">
            Ujian ini memerlukan token dari pengawas untuk memulai. Waktu ujian akan mulai dihitung setelah token benar.
        </p>

        <div class="exam-meta">
            <div><i class="fas fa-book"></i> <strong><?= htmlspecialchars($exam['title']) ?></strong></div>
            <div style="margin-top:4px;"><i class="fas fa-clock"></i> Durasi: <?= (int)$exam['duration'] ?> menit</div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="border-radius:0.75rem;font-size:0.85rem;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= config('app_url') ?>/exam/verifyToken/<?= $exam['id'] ?>">
            <input type="text" name="exam_token" class="form-control token-input"
                placeholder="MASUKKAN TOKEN" autocomplete="off" autofocus required>
            <button type="submit" class="btn-start">
                <i class="fas fa-play"></i> Mulai Ujian
            </button>
        </form>

        <a href="<?= config('app_url') ?>/exam" style="display:block;margin-top:1.25rem;color:#64748b;font-size:0.85rem;text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Ujian
        </a>
    </div>
</body>

</html>