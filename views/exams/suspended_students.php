<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siswa Ter-Suspend - CAT System</title>
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .suspended-list {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .student-card {
            background: #fff;
            border: 1px solid #ddd;
            border-left: 4px solid #ff6b6b;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
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
        .btn-reactivate {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-reactivate:hover {
            background: #218838;
        }
        .btn-reactivate:disabled {
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
    </style>
</head>
<body>
    <div class="suspended-list">
        <a href="/exam" class="back-link">← Kembali ke Daftar Ujian</a>
        
        <h1>🚫 Siswa Ter-Suspend</h1>
        <p>Daftar siswa yang ter-suspend karena pelanggaran saat ujian</p>
        
        <div id="message-container"></div>
        
        <?php if (empty($suspendedStudents)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2>Tidak Ada Siswa Ter-Suspend</h2>
                <p>Semua siswa dalam kondisi baik. Tidak ada pelanggaran yang terdeteksi.</p>
            </div>
        <?php else: ?>
            <?php foreach ($suspendedStudents as $student): ?>
                <div class="student-card" id="card-<?= $student['user_id'] ?>-<?= $student['exam_id'] ?>">
                    <div class="student-header">
                        <div>
                            <div class="student-name"><?= htmlspecialchars($student['student_name']) ?></div>
                            <div class="exam-title">📝 <?= htmlspecialchars($student['exam_title']) ?></div>
                        </div>
                        <button 
                            class="btn-reactivate" 
                            onclick="reactivateStudent(<?= $student['user_id'] ?>, <?= $student['exam_id'] ?>)"
                            id="btn-<?= $student['user_id'] ?>-<?= $student['exam_id'] ?>">
                            🔓 Reaktivasi
                        </button>
                    </div>
                    
                    <div class="violation-info">
                        <strong>Pelanggaran:</strong> <span class="violation-count"><?= $student['violation_count'] ?>x</span><br>
                        <strong>Alasan:</strong> <?= htmlspecialchars($student['suspension_reason'] ?? 'Tidak ada keterangan') ?><br>
                        <strong>Waktu:</strong> <?= date('d M Y, H:i:s', strtotime($student['last_violation_time'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function reactivateStudent(userId, examId) {
            const btn = document.getElementById(`btn-${userId}-${examId}`);
            const card = document.getElementById(`card-${userId}-${examId}`);
            
            if (!confirm('Yakin ingin mengaktifkan kembali siswa ini? Siswa akan dapat melanjutkan ujian.')) {
                return;
            }
            
            btn.disabled = true;
            btn.textContent = '⏳ Memproses...';
            
            fetch('/exam/reactivateStudent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${userId}&exam_id=${examId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const messageContainer = document.getElementById('message-container');
                    messageContainer.innerHTML = `
                        <div class="success-message">
                            ✅ ${data.message}
                        </div>
                    `;
                    
                    // Remove card with animation
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(100px)';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        // Check if no more cards
                        const remainingCards = document.querySelectorAll('.student-card');
                        if (remainingCards.length === 0) {
                            location.reload();
                        }
                    }, 500);
                    
                    // Auto-hide message after 3 seconds
                    setTimeout(() => {
                        messageContainer.innerHTML = '';
                    }, 3000);
                } else {
                    alert('Gagal: ' + data.message);
                    btn.disabled = false;
                    btn.textContent = '🔓 Reaktivasi';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                btn.disabled = false;
                btn.textContent = '🔓 Reaktivasi';
            });
        }
    </script>
</body>
</html>
