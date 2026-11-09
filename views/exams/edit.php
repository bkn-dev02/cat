<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ujian - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }
        
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        body {
            background: var(--light);
        }
        
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
        }
        
        .main-content {
            padding: 2rem 0;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-back:hover {
            color: var(--primary);
            transform: translateX(-3px);
        }
        
        .form-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border: 2px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.75rem;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 1px solid var(--border);
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
            
            .form-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <a href="<?= config('app_url') ?>/exam" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Ujian
        </a>
        
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">Edit Ujian</h1>
            </div>
            
            <?php if ($exam['exam_type'] === 'multi'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Ujian Multi-Subject</strong><br>
                    Mata pelajaran: <?php foreach ($examSubjects as $es): ?><?= htmlspecialchars($es['subject_name']) ?>, <?php endforeach; ?>
                    <br><small>Untuk mengubah mata pelajaran, silakan buat ujian baru.</small>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Judul Ujian</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($exam['title']) ?>" required>
                </div>
                
                <?php if ($exam['exam_type'] === 'single'): ?>
                    <div class="mb-3">
                        <label class="form-label">Mata Pelajaran</label>
                        <select name="subject_id" class="form-select" required>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>" <?= $exam['subject_id'] == $subject['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Durasi (menit)</label>
                    <input type="number" name="duration" class="form-control" value="<?= $exam['duration'] ?>" min="1" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-users"></i> Peserta Ujian
                    </label>
                    <?php
                    // Check current participants
                    $stmt = db()->prepare("
                        SELECT COUNT(*) as participant_count,
                               GROUP_CONCAT(DISTINCT u.kelas ORDER BY u.kelas SEPARATOR ', ') as classes
                        FROM exam_participants ep
                        JOIN users u ON ep.user_id = u.id
                        WHERE ep.exam_id = ? AND u.role = 'siswa'
                    ");
                    $stmt->execute([$exam['id']]);
                    $currentParticipants = $stmt->fetch();
                    $isClassRestricted = $currentParticipants['participant_count'] > 0;
                    ?>
                    
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                        <div class="form-check mb-2">
                            <input type="radio" name="participant_type" value="all" id="participantAll" 
                                   class="form-check-input" <?= !$isClassRestricted ? 'checked' : '' ?>
                                   style="width: 20px; height: 20px; cursor: pointer;">
                            <label class="form-check-label" for="participantAll" 
                                   style="margin-left: 0.5rem; cursor: pointer; font-weight: 500;">
                                Semua Siswa (Publik)
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" name="participant_type" value="class" id="participantClass" 
                                   class="form-check-input" <?= $isClassRestricted ? 'checked' : '' ?>
                                   style="width: 20px; height: 20px; cursor: pointer;">
                            <label class="form-check-label" for="participantClass" 
                                   style="margin-left: 0.5rem; cursor: pointer; font-weight: 500;">
                                Kelas Tertentu Saja
                            </label>
                        </div>
                        
                        <?php if ($isClassRestricted): ?>
                            <div class="alert alert-info mt-2 mb-0" style="padding: 0.5rem; font-size: 0.9em;">
                                <i class="fas fa-info-circle"></i> 
                                Saat ini terbatas untuk: <strong><?= htmlspecialchars($currentParticipants['classes']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3" id="classSelectionSection" style="display: <?= $isClassRestricted ? 'block' : 'none' ?>;">
                    <label class="form-label">
                        <i class="fas fa-school"></i> Pilih Kelas
                    </label>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; max-height: 300px; overflow-y: auto;">
                        <?php
                        // Get all unique classes
                        $stmt = db()->prepare("
                            SELECT
                                u.kelas,
                                COUNT(DISTINCT u.id) AS student_count,
                                COUNT(DISTINCT CASE WHEN ep.user_id IS NOT NULL THEN u.id END) AS selected_count
                            FROM users u
                            LEFT JOIN exam_participants ep
                                ON ep.user_id = u.id AND ep.exam_id = ?
                            WHERE u.role = 'siswa'
                              AND u.kelas IS NOT NULL
                              AND u.kelas != ''
                            GROUP BY u.kelas
                            ORDER BY u.kelas
                        ");
                        $stmt->execute([$exam['id']]);
                        $classStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php foreach ($classStats as $class): ?>
                            <div class="form-check mb-2" style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem; background: white; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                                <input type="checkbox" name="selected_classes[]" value="<?= htmlspecialchars($class['kelas']) ?>" 
                                       id="class_<?= htmlspecialchars($class['kelas']) ?>" class="form-check-input class-checkbox" 
                                       <?= (int)$class['selected_count'] > 0 ? 'checked' : '' ?>
                                       style="width: 20px; height: 20px; cursor: pointer; margin: 0;">
                                <label class="form-check-label" for="class_<?= htmlspecialchars($class['kelas']) ?>" 
                                       style="flex: 1; cursor: pointer; font-weight: 500; margin: 0;">
                                    <?= htmlspecialchars($class['kelas']) ?>
                                </label>
                                <small class="text-muted" style="font-size: 0.8em;"><?= (int)$class['student_count'] ?> siswa</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> 
                        Centang kelas yang boleh mengikuti ujian ini
                    </small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt"></i> Waktu Mulai
                    </label>
                    <input type="datetime-local" name="start_time" class="form-control" 
                           value="<?= toDatetimeLocal($exam['start_time']) ?>" required>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Waktu dalam zona WIB (Waktu Indonesia Barat)
                    </small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar-check"></i> Waktu Selesai
                    </label>
                    <input type="datetime-local" name="end_time" class="form-control" 
                           value="<?= toDatetimeLocal($exam['end_time']) ?>" required>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Waktu dalam zona WIB (Waktu Indonesia Barat)
                    </small>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="randomize_questions" 
                               id="randomize" <?= $exam['randomize_questions'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="randomize">
                            <i class="fas fa-random"></i> Acak urutan soal
                        </label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="<?= config('app_url') ?>/exam" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>

                <?php if ($isClassRestricted): ?>
                <div class="alert alert-info mt-3" style="font-size: 0.88em;">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>Ada siswa baru bergabung ke kelas ini?</strong>
                    Setelah menyimpan, gunakan
                    <a href="<?= config('app_url') ?>/sync/participants" target="_blank" style="font-weight:700;">
                        <i class="fas fa-users-cog"></i> Sync Peserta
                    </a>
                    untuk menambahkan siswa baru ke ujian ini secara otomatis.
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle between all students and class selection
        const participantAll = document.getElementById('participantAll');
        const participantClass = document.getElementById('participantClass');
        const classSelectionSection = document.getElementById('classSelectionSection');
        
        // Auto calculate end time based on start time and duration
        const startTimeInput = document.querySelector('input[name="start_time"]');
        const endTimeInput = document.querySelector('input[name="end_time"]');
        const durationInput = document.querySelector('input[name="duration"]');
        
        function toggleParticipantType() {
            if (participantClass.checked) {
                classSelectionSection.style.display = 'block';
            } else {
                classSelectionSection.style.display = 'none';
                document.querySelectorAll('.class-checkbox').forEach(cb => cb.checked = false);
            }
        }
        
        function calculateEndTime() {
            const startTime = startTimeInput.value;
            const duration = parseInt(durationInput.value);
            
            if (startTime && duration) {
                const start = new Date(startTime);
                const end = new Date(start.getTime() + (duration * 60 * 1000));
                
                // Format to datetime-local format
                const year = end.getFullYear();
                const month = String(end.getMonth() + 1).padStart(2, '0');
                const day = String(end.getDate()).padStart(2, '0');
                const hours = String(end.getHours()).padStart(2, '0');
                const minutes = String(end.getMinutes()).padStart(2, '0');
                
                endTimeInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
            }
        }
        
        participantAll.addEventListener('change', toggleParticipantType);
        participantClass.addEventListener('change', toggleParticipantType);
        startTimeInput.addEventListener('change', calculateEndTime);
        durationInput.addEventListener('input', calculateEndTime);
        
        // Format duration display
        durationInput.addEventListener('input', function() {
            const duration = parseInt(this.value);
            const label = this.previousElementSibling;
            
            if (duration) {
                let text = 'Durasi (';
                if (duration < 60) {
                    text += `${duration} menit`;
                } else {
                    const hours = Math.floor(duration / 60);
                    const minutes = duration % 60;
                    text += `${hours} jam`;
                    if (minutes > 0) {
                        text += ` ${minutes} menit`;
                    }
                }
                text += ')';
                label.textContent = text;
            } else {
                label.textContent = 'Durasi (menit)';
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            // Validate class selection
            if (participantClass.checked) {
                const checkedClasses = document.querySelectorAll('.class-checkbox:checked');
                if (checkedClasses.length === 0) {
                    e.preventDefault();
                    alert('Pilih minimal 1 kelas untuk ujian terbatas!');
                    return false;
                }
            }
            
            // Validate time
            const startTime = new Date(startTimeInput.value);
            const endTime = new Date(endTimeInput.value);
            
            if (endTime <= startTime) {
                e.preventDefault();
                alert('Waktu selesai harus lebih dari waktu mulai!');
                return false;
            }
        });
        
        // Initialize duration display
        durationInput.dispatchEvent(new Event('input'));
    </script>
    
    <!-- Bootstrap JS -->
    <script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
