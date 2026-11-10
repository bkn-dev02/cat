<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Token - CAT System</title>
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .token-requests-list {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .request-card {
            background: #fff;
            border: 1px solid #ddd;
            border-left: 4px solid #ff9800;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .student-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .exam-title {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .violation-info {
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 14px;
        }
        .violation-count {
            color: #ff6b6b;
            font-weight: bold;
        }
        .request-time {
            color: #999;
            font-size: 13px;
            margin-top: 10px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-approve:hover {
            background: #218838;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-reject:hover {
            background: #c82333;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .badge-pending {
            background: #ffc107;
            color: #000;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="token-requests-list">
        <a href="/exam" class="back-link">← Kembali ke Daftar Ujian</a>
        
        <h1>🔑 Permintaan Token Baru</h1>
        <p>Daftar siswa yang meminta token baru setelah ter-suspend</p>
        
        <div id="message-container"></div>
        
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2>Tidak Ada Permintaan Token</h2>
                <p>Semua permintaan sudah diproses atau belum ada siswa yang ter-suspend.</p>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="request-card" id="card-<?= $request['id'] ?>">
                    <div class="request-header">
                        <div>
                            <div class="student-name"><?= htmlspecialchars($request['student_name']) ?></div>
                            <div class="exam-title">📝 <?= htmlspecialchars($request['exam_title']) ?></div>
                        </div>
                        <span class="badge-pending">PENDING</span>
                    </div>
                    
                    <div class="violation-info">
                        <strong>Pelanggaran:</strong> <span class="violation-count"><?= $request['violation_count'] ?>x</span><br>
                        <strong>Alasan Suspend:</strong> <?= htmlspecialchars($request['suspension_reason'] ?? $request['request_reason'] ?? 'Tidak ada keterangan') ?><br>
                        <strong>Total Log Aktivitas:</strong> <?= $request['total_logs'] ?? 0 ?> kejadian
                    </div>
                    
                    <div class="request-time">
                        📅 Diminta pada: <?= date('d M Y, H:i:s', strtotime($request['requested_at'])) ?>
                    </div>
                    
                    <div class="action-buttons">
                        <button 
                            class="btn-approve" 
                            onclick="approveRequest(<?= $request['id'] ?>)"
                            id="btn-approve-<?= $request['id'] ?>">
                            ✅ Setujui & Aktifkan
                        </button>
                        <button 
                            class="btn-reject" 
                            onclick="rejectRequest(<?= $request['id'] ?>)"
                            id="btn-reject-<?= $request['id'] ?>">
                            ❌ Tolak
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function approveRequest(requestId) {
            const btnApprove = document.getElementById(`btn-approve-${requestId}`);
            const btnReject = document.getElementById(`btn-reject-${requestId}`);
            const card = document.getElementById(`card-${requestId}`);
            
            if (!confirm('Yakin ingin menyetujui permintaan ini? Siswa akan dapat melanjutkan ujian.')) {
                return;
            }
            
            btnApprove.disabled = true;
            btnReject.disabled = true;
            btnApprove.textContent = '⏳ Memproses...';
            
            fetch('/exam/approveToken/' + requestId, {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                // Show success message
                const messageContainer = document.getElementById('message-container');
                messageContainer.innerHTML = `
                    <div class="success-message">
                        ✅ Token berhasil disetujui! Siswa dapat melanjutkan ujian.
                    </div>
                `;
                
                // Remove card with animation
                card.style.transition = 'opacity 0.5s, transform 0.5s';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-100px)';
                
                setTimeout(() => {
                    card.remove();
                    
                    // Check if no more cards
                    const remainingCards = document.querySelectorAll('.request-card');
                    if (remainingCards.length === 0) {
                        location.reload();
                    }
                }, 500);
                
                // Auto-hide message after 3 seconds
                setTimeout(() => {
                    messageContainer.innerHTML = '';
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                btnApprove.disabled = false;
                btnReject.disabled = false;
                btnApprove.textContent = '✅ Setujui & Aktifkan';
            });
        }
        
        function rejectRequest(requestId) {
            const btnApprove = document.getElementById(`btn-approve-${requestId}`);
            const btnReject = document.getElementById(`btn-reject-${requestId}`);
            const card = document.getElementById(`card-${requestId}`);
            
            if (!confirm('Yakin ingin menolak permintaan ini? Siswa tidak akan dapat melanjutkan ujian.')) {
                return;
            }
            
            btnApprove.disabled = true;
            btnReject.disabled = true;
            btnReject.textContent = '⏳ Memproses...';
            
            fetch('/exam/rejectToken/' + requestId, {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                // Show message
                const messageContainer = document.getElementById('message-container');
                messageContainer.innerHTML = `
                    <div class="success-message" style="background: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                        ❌ Permintaan ditolak.
                    </div>
                `;
                
                // Remove card
                card.style.transition = 'opacity 0.5s, transform 0.5s';
                card.style.opacity = '0';
                card.style.transform = 'translateX(100px)';
                
                setTimeout(() => {
                    card.remove();
                    
                    const remainingCards = document.querySelectorAll('.request-card');
                    if (remainingCards.length === 0) {
                        location.reload();
                    }
                }, 500);
                
                setTimeout(() => {
                    messageContainer.innerHTML = '';
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                btnApprove.disabled = false;
                btnReject.disabled = false;
                btnReject.textContent = '❌ Tolak';
            });
        }
        
        // Auto refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
