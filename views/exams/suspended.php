<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditangguhkan - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .suspended-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        .icon-warning {
            font-size: 5rem;
            color: #dc2626;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        h1 {
            color: #dc2626;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
            border-radius: 8px;
        }
        
        .info-box h5 {
            color: #991b1b;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-primary {
            background: #667eea;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="suspended-container">
        <i class="fas fa-exclamation-triangle icon-warning"></i>
        <h1>Akses Ujian Ditangguhkan</h1>
        <p style="font-size: 1.1rem; color: #64748b;">
            Akses Anda ke ujian <strong><?= htmlspecialchars($session['title']) ?></strong> telah ditangguhkan.
        </p>
        
        <div class="info-box">
            <h5><i class="fas fa-info-circle"></i> Alasan Penangguhan:</h5>
            <p style="margin: 0;"><?= htmlspecialchars($session['suspension_reason'] ?? 'Tidak ada keterangan') ?></p>
        </div>
        
        <?php if ($tokenRequest): ?>
            <div class="info-box" style="background: #eff6ff; border-color: #3b82f6;">
                <h5 style="color: #1e40af;"><i class="fas fa-clock"></i> Status Permintaan Token:</h5>
                <span class="status-badge status-<?= $tokenRequest['status'] ?>">
                    <?php
                    $statusText = [
                        'pending' => 'Menunggu Persetujuan Guru',
                        'approved' => 'Disetujui - Silakan Refresh Halaman',
                        'rejected' => 'Ditolak'
                    ];
                    echo $statusText[$tokenRequest['status']];
                    ?>
                </span>
                <p style="margin-top: 10px; font-size: 0.9rem; color: #64748b;">
                    Diminta pada: <?= date('d M Y, H:i', strtotime($tokenRequest['requested_at'])) ?>
                </p>
            </div>
            
            <?php if ($tokenRequest['status'] === 'approved'): ?>
                <a href="<?= config('app_url') ?>/exam/take/<?= $session['id'] ?>" class="btn btn-success btn-lg">
                    <i class="fas fa-play"></i> Lanjutkan Ujian
                </a>
            <?php elseif ($tokenRequest['status'] === 'pending'): ?>
                <button class="btn btn-secondary" disabled>
                    <i class="fas fa-hourglass-half"></i> Menunggu Persetujuan...
                </button>
                <p style="margin-top: 15px; font-size: 0.9rem; color: #64748b;">
                    Halaman akan otomatis refresh setiap 10 detik
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p style="color: #64748b; margin-top: 20px;">
                Permintaan token baru sedang diproses...
            </p>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <div style="text-align: left;">
            <h5 style="color: #1e293b; margin-bottom: 15px;">Apa yang harus dilakukan?</h5>
            <ol style="color: #64748b; line-height: 1.8;">
                <li>Tunggu guru menyetujui permintaan token baru Anda</li>
                <li>Jika ditolak, hubungi guru untuk penjelasan lebih lanjut</li>
                <li>Pastikan tidak melakukan pelanggaran lagi saat ujian</li>
                <li>Jangan keluar dari tab ujian atau membuka aplikasi lain</li>
            </ol>
        </div>
        
        <a href="<?= config('app_url') ?>/exam" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Ujian
        </a>
    </div>
    
    <?php if ($tokenRequest && $tokenRequest['status'] === 'pending'): ?>
    <script>
        // Auto refresh every 10 seconds if pending
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
    <?php endif; ?>
</body>
</html>
