<?php
/**
 * 🚨 EMERGENCY: BATCH SUBMIT SEMUA SISWA YANG STUCK
 * Gunakan ini untuk submit banyak siswa sekaligus!
 */

session_start();
require_once 'config.php';
require_once 'app/helpers.php';

// HANYA ADMIN/GURU YANG BISA AKSES
requireAuth();
requireRole(['admin', 'guru']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚨 Emergency Batch Submit - CAT Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: #f8f9fa; }
        .container { max-width: 1200px; background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .alert-danger { background: #fee; border-left: 4px solid #dc3545; }
        .alert-success { background: #d1fae5; border-left: 4px solid #10b981; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .student-card { border: 1px solid #ddd; padding: 1rem; margin-bottom: 0.5rem; border-radius: 0.5rem; }
        .student-card:hover { background: #f8f9fa; }
        .btn-submit-all { font-size: 1.2rem; padding: 1rem 2rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #dc3545;">🚨 EMERGENCY: Batch Submit Siswa</h1>
        <p><strong>Submit banyak siswa sekaligus yang stuck di "Mengumpulkan..."</strong></p>
        
        <div class="alert alert-warning">
            <strong>⚠️ PERHATIAN:</strong><br>
            - Tool ini untuk EMERGENCY MASSAL<br>
            - Akan submit SEMUA siswa yang dipilih<br>
            - Pastikan siswa sudah selesai mengerjakan<br>
            - Tidak bisa di-undo!
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_batch'])) {
            $sessionIds = $_POST['session_ids'] ?? [];
            
            if (empty($sessionIds)) {
                echo '<div class="alert alert-danger">Tidak ada siswa yang dipilih!</div>';
            } else {
                echo '<div class="alert alert-success">';
                echo '<h4>✅ BATCH SUBMIT BERHASIL!</h4>';
                echo '<p>Total siswa yang di-submit: <strong>' . count($sessionIds) . '</strong></p>';
                echo '<hr>';
                
                $success = 0;
                $failed = 0;
                
                foreach ($sessionIds as $sessionId) {
                    try {
                        // Get session info
                        $stmt = db()->prepare("
                            SELECT es.*, e.title, u.full_name
                            FROM exam_sessions es
                            JOIN exams e ON es.exam_id = e.id
                            JOIN users u ON es.user_id = u.id
                            WHERE es.id = ?
                        ");
                        $stmt->execute([$sessionId]);
                        $session = $stmt->fetch();
                        
                        if ($session) {
                            // Force submit
                            $stmt = db()->prepare("
                                UPDATE exam_sessions 
                                SET status = 'submitted', submitted_at = NOW() 
                                WHERE id = ?
                            ");
                            $stmt->execute([$sessionId]);
                            
                            echo '<p>✅ <strong>' . htmlspecialchars($session['full_name']) . '</strong> - ' . htmlspecialchars($session['title']) . '</p>';
                            $success++;
                        } else {
                            echo '<p>❌ Session ID ' . $sessionId . ' tidak ditemukan</p>';
                            $failed++;
                        }
                    } catch (Exception $e) {
                        echo '<p>❌ Error session ID ' . $sessionId . ': ' . htmlspecialchars($e->getMessage()) . '</p>';
                        $failed++;
                    }
                }
                
                echo '<hr>';
                echo '<p><strong>Berhasil:</strong> ' . $success . ' siswa</p>';
                if ($failed > 0) {
                    echo '<p><strong>Gagal:</strong> ' . $failed . ' siswa</p>';
                }
                echo '<p><a href="' . config('app_url') . '/exam/monitor" class="btn btn-primary">Lihat Monitoring</a></p>';
                echo '</div>';
            }
        }
        ?>

        <form method="POST" id="batchForm">
            <h3>📋 Pilih Siswa yang Stuck:</h3>
            
            <!-- SEARCH BOX -->
            <div class="mb-3">
                <input type="text" id="searchBox" class="form-control" placeholder="🔍 Cari nama siswa atau username/kelas..." style="font-size: 1.1rem; padding: 0.75rem;">
                <small class="text-muted">Ketik nama, username, atau kelas untuk filter siswa</small>
            </div>
            
            <!-- MODE SELECTOR -->
            <div class="mb-3">
                <label style="font-weight: bold; margin-bottom: 0.5rem;">Mode Tampilan:</label><br>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="viewMode" id="modeNotSubmitted" value="not_submitted" checked>
                    <label class="btn btn-outline-primary" for="modeNotSubmitted">Belum Submit</label>
                    
                    <input type="radio" class="btn-check" name="viewMode" id="modeAll" value="all">
                    <label class="btn btn-outline-secondary" for="modeAll">Semua Siswa (Debug)</label>
                </div>
                <br><small class="text-muted">Pilih "Semua Siswa" untuk melihat semua session termasuk yang sudah submit</small>
            </div>
            
            <?php
            // DEBUG: Show all statuses in database
            try {
                $debugStmt = db()->query("
                    SELECT DISTINCT es.status, COUNT(*) as count
                    FROM exam_sessions es
                    GROUP BY es.status
                ");
                $statuses = $debugStmt->fetchAll();
                
                if (!empty($statuses)) {
                    echo '<div class="alert alert-info" style="background: #e0f2fe; border-left: 4px solid #0284c7;">';
                    echo '<strong>📊 Debug Info - Status Ujian di Database:</strong><br>';
                    foreach ($statuses as $s) {
                        $statusName = $s['status'] ?: '(null/empty)';
                        echo '- <strong>' . htmlspecialchars($statusName) . '</strong>: ' . $s['count'] . ' siswa<br>';
                    }
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Debug Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // Get sessions based on mode (default: not submitted)
            try {
                // Query for NOT submitted sessions (without email column)
                $stmtNotSubmitted = db()->query("
                    SELECT es.*, e.title, u.full_name, u.username, u.kelas,
                           TIMESTAMPDIFF(MINUTE, es.started_at, NOW()) as elapsed_minutes
                    FROM exam_sessions es
                    JOIN exams e ON es.exam_id = e.id
                    JOIN users u ON es.user_id = u.id
                    WHERE es.status != 'submitted' 
                       OR es.status IS NULL
                       OR es.status = ''
                    ORDER BY u.full_name ASC
                ");
                $sessionsNotSubmitted = $stmtNotSubmitted->fetchAll();
                
                // Query for ALL sessions (for debug mode, without email column)
                $stmtAll = db()->query("
                    SELECT es.*, e.title, u.full_name, u.username, u.kelas,
                           TIMESTAMPDIFF(MINUTE, es.started_at, NOW()) as elapsed_minutes
                    FROM exam_sessions es
                    JOIN exams e ON es.exam_id = e.id
                    JOIN users u ON es.user_id = u.id
                    ORDER BY u.full_name ASC
                ");
                $sessionsAll = $stmtAll->fetchAll();
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Query Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $sessionsNotSubmitted = [];
                $sessionsAll = [];
            }
            
            // Default to not submitted
            $sessions = $sessionsNotSubmitted;
            
            // Show info based on mode
            echo '<div class="alert alert-info" style="background: #dbeafe; border-left: 4px solid #3b82f6;">';
            echo '<strong>📋 Info Database:</strong><br>';
            echo '- Total session BELUM submit: <strong>' . count($sessionsNotSubmitted) . '</strong> siswa<br>';
            echo '- Total session SEMUA: <strong>' . count($sessionsAll) . '</strong> siswa<br>';
            echo '</div>';
            
            // Container for dynamic content
            echo '<div id="sessionContainer">';
            
            // Render not submitted sessions (default view)
            echo '<div id="notSubmittedView" style="display: block;">';
            if (empty($sessionsNotSubmitted)) {
                echo '<div class="alert alert-success" style="background: #d1fae5; border-left: 4px solid #10b981;">';
                echo '<strong>✅ Semua siswa sudah submit!</strong><br>';
                echo 'Tidak ada siswa yang perlu di-submit secara manual.<br>';
                echo '<br>';
                echo '<a href="' . config('app_url') . '/exam/monitor" class="btn btn-primary">Cek Monitoring Ujian</a>';
                echo '</div>';
            } else {
                renderSessionList($sessionsNotSubmitted, true);
            }
            echo '</div>';
            
            // Render all sessions (debug view)
            echo '<div id="allView" style="display: none;">';
            if (empty($sessionsAll)) {
                echo '<div class="alert alert-warning">';
                echo '<strong>⚠️ Tidak ada session ujian di database.</strong><br>';
                echo 'Belum ada siswa yang memulai ujian.<br>';
                echo '</div>';
            } else {
                renderSessionList($sessionsAll, false);
            }
            echo '</div>';
            
            echo '</div>'; // End sessionContainer
            
            // Function to render session list
            function renderSessionList($sessions, $allowSubmit) {
                echo '<div class="alert alert-success" style="background: #d1fae5; border-left: 4px solid #10b981;">';
                echo '<strong>✅ Ditemukan ' . count($sessions) . ' siswa</strong>';
                echo '</div>';
                
                if ($allowSubmit) {
                    echo '<div class="mb-3">';
                    echo '<button type="button" class="btn btn-secondary btn-sm" onclick="selectAll()">Pilih Semua</button> ';
                    echo '<button type="button" class="btn btn-secondary btn-sm" onclick="deselectAll()">Batal Pilih Semua</button> ';
                    echo '<span id="selectedCount" style="margin-left: 1rem; font-weight: bold; color: #6366f1;">0 siswa dipilih</span>';
                    echo '</div>';
                }
                
                echo '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 1rem; border-radius: 0.5rem;" class="studentList">';
                
                foreach ($sessions as $session) {
                    $kelas = !empty($session['kelas']) ? $session['kelas'] : '-';
                    $statusBadge = '';
                    
                    // Add status badge with color coding
                    switch($session['status']) {
                        case 'active':
                        case 'in_progress':
                            $statusBadge = '<span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">⏳ Sedang Ujian</span>';
                            break;
                        case 'submitted':
                        case 'completed':
                            $statusBadge = '<span style="background: #d1fae5; color: #166534; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">✅ Sudah Submit</span>';
                            break;
                        default:
                            $statusBadge = '<span style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">⚠️ ' . htmlspecialchars($session['status']) . '</span>';
                    }
                    
                    echo '<div class="student-card" data-name="' . strtolower(htmlspecialchars($session['full_name'])) . '" data-username="' . strtolower(htmlspecialchars($session['username'])) . '" data-kelas="' . strtolower(htmlspecialchars($kelas)) . '">';
                    
                    if ($allowSubmit) {
                        echo '<label style="cursor: pointer; display: block; margin: 0;">';
                        echo '<input type="checkbox" name="session_ids[]" value="' . $session['id'] . '" style="margin-right: 0.5rem;" onchange="updateSelectedCount()">';
                    } else {
                        echo '<div style="display: block; margin: 0;">';
                    }
                    
                    echo '<strong>' . htmlspecialchars($session['full_name']) . '</strong> ';
                    echo '<small>(' . htmlspecialchars($session['username']) . ')</small> ';
                    echo $statusBadge;
                    if (!empty($session['kelas'])) {
                        echo '<br><small style="color: #666;">🏫 Kelas: ' . htmlspecialchars($session['kelas']) . '</small>';
                    }
                    echo '<br><small style="color: #666;">';
                    echo 'Ujian: ' . htmlspecialchars($session['title']) . ' | ';
                    echo 'Session ID: ' . $session['id'] . ' | ';
                    echo 'Mulai: ' . $session['started_at'] . ' | ';
                    echo 'Elapsed: ' . $session['elapsed_minutes'] . ' menit';
                    echo '</small>';
                    
                    if ($allowSubmit) {
                        echo '</label>';
                    } else {
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>';
                
                if ($allowSubmit) {
                    echo '<div class="mt-4">';
                    echo '<button type="submit" name="submit_batch" class="btn btn-danger btn-submit-all" onclick="return confirm(\'YAKIN submit semua siswa yang dipilih?\\n\\nTotal: \' + document.querySelectorAll(\'input[name=\\\'session_ids[]\\\']:checked\').length + \' siswa\\n\\nTidak bisa di-undo!\');">';
                    echo '🚨 SUBMIT SEMUA SISWA YANG DIPILIH';
                    echo '</button>';
                    echo '</div>';
                }
            }
            ?>
        </form>

        <hr class="my-4">

        <h3>📋 Cara Menggunakan:</h3>
        <ol>
            <li><strong>Centang siswa</strong> yang stuck di "Mengumpulkan..."</li>
            <li>Atau klik <strong>"Pilih Semua"</strong> untuk submit semua siswa aktif</li>
            <li>Klik tombol <strong>"SUBMIT SEMUA SISWA YANG DIPILIH"</strong></li>
            <li>Konfirmasi dengan klik OK</li>
            <li>✅ Semua siswa yang dipilih akan langsung ter-submit!</li>
            <li>Siswa bisa lihat hasil ujian mereka</li>
        </ol>

        <div class="alert alert-info">
            <strong>💡 TIPS:</strong><br>
            - Pastikan siswa sudah selesai mengerjakan sebelum submit<br>
            - Cek di monitoring untuk lihat status siswa<br>
            - Setelah submit, siswa bisa lihat hasil ujian<br>
            - Jawaban siswa sudah tersimpan otomatis
        </div>

        <a href="<?= config('app_url') ?>/exam/monitor" class="btn btn-secondary">
            Kembali ke Monitoring
        </a>
    </div>

    <script>
        // Mode switching
        document.querySelectorAll('input[name="viewMode"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'not_submitted') {
                    document.getElementById('notSubmittedView').style.display = 'block';
                    document.getElementById('allView').style.display = 'none';
                } else {
                    document.getElementById('notSubmittedView').style.display = 'none';
                    document.getElementById('allView').style.display = 'block';
                }
                // Reset search when switching modes
                document.getElementById('searchBox').value = '';
                document.querySelectorAll('.student-card').forEach(card => {
                    card.style.display = 'block';
                });
            });
        });
        
        // Search/Filter functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const studentCards = document.querySelectorAll('.student-card');
            let visibleCount = 0;
            
            studentCards.forEach(card => {
                const name = card.getAttribute('data-name');
                const username = card.getAttribute('data-username');
                const kelas = card.getAttribute('data-kelas');
                
                if (name.includes(searchTerm) || username.includes(searchTerm) || kelas.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update border color
            document.querySelectorAll('.studentList').forEach(list => {
                if (searchTerm) {
                    list.style.borderColor = '#6366f1';
                } else {
                    list.style.borderColor = '#ddd';
                }
            });
        });
        
        function selectAll() {
            const visibleCheckboxes = document.querySelectorAll('.student-card:not([style*="display: none"]) input[name="session_ids[]"]');
            visibleCheckboxes.forEach(cb => cb.checked = true);
            updateSelectedCount();
        }
        
        function deselectAll() {
            document.querySelectorAll('input[name="session_ids[]"]').forEach(cb => cb.checked = false);
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checkedCount = document.querySelectorAll('input[name="session_ids[]"]:checked').length;
            const countElement = document.getElementById('selectedCount');
            if (countElement) {
                countElement.textContent = checkedCount + ' siswa dipilih';
                
                if (checkedCount > 0) {
                    countElement.style.color = '#10b981';
                } else {
                    countElement.style.color = '#6366f1';
                }
            }
        }
    </script>
</body>
</html>
