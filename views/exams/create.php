<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Ujian - CAT Enterprise</title>
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
        
        .main-content {
            padding: 2rem 0;
            max-width: 1140px;
            margin: 0 auto;
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
        }
        
        .form-group-box{
            width: 100%;
            display: flex;
            gap:1rem;
        }
        
        .form-group-box .form-group{
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
            cursor: pointer;
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
        
        .btn-secondary:hover {
            background: var(--light);
        }
        
        .btn-back{
            background: var(--primary);
            color: var(--light);
            margin-bottom: .5rem;
        }
        
        .btn-back:hover{
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
            
            .form-card {
                padding: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <div class="form-card">
            <div class="form-header">
                <a href="<?= config('app_url') ?>/exam" class="btn btn-back" style="display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <h1 class="form-title">Buat Ujian Baru</h1>
            </div>

            <form method="POST" id="examForm">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-file-signature"></i> Judul Ujian</label>
                    <input type="text" name="title" class="form-control" 
                           placeholder="Contoh: Tryout UTBK 2026" required>
                </div>
                
                <div class="form-group-box">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-folder"></i> Kategori Ujian</label>
                        <select name="category" class="form-select" required>
                            <option value="reguler">📚 Ujian Sekolah (Reguler)</option>
                            <option value="ppdb">🎓 Ujian Saringan Masuk (PPDB)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="singleSubjectSection">
                        <label class="form-label"><i class="fas fa-graduation-cap"></i> Mata Pelajaran</label>
                        <select name="subject_id" id="subjectSelect" class="form-select">
                            <option value="">Pilih Mata Pelajaran</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group-box">
                    <div class="form-group" id="typeExamSection">
                        <label class="form-label">
                            <i class="fas fa-layer-group"></i> Tipe Ujian
                        </label>
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                            <div class="form-check mb-2">
                                <input type="radio" name="exam_type" value="single" id="typeSingle" class="form-check-input" checked style="width: 20px; height: 20px; cursor: pointer;">
                                <label class="form-check-label" for="typeSingle" style="margin-left: 0.5rem; cursor: pointer; font-weight: 500;">
                                    Single Subject (Satu Mata Pelajaran)
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="exam_type" value="multi" id="typeMulti" class="form-check-input" style="width: 20px; height: 20px; cursor: pointer;">
                                <label class="form-check-label" for="typeMulti" style="margin-left: 0.5rem; cursor: pointer; font-weight: 500;">
                                    Multi Subject (Paket/Tryout)
                                </label>
                            </div>
                            <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i> 
                                Pilih Multi Subject untuk tryout atau ujian paket dengan beberapa mata pelajaran
                            </small>
                        </div>
                    </div>

                    <div class="form-group" id="multiSubjectSection" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-books"></i> Pilih Mata Pelajaran & Jumlah Soal
                    </label>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; max-height: 400px; overflow-y: auto;">
                        <?php foreach ($subjects as $index => $subject): ?>
                            <div class="form-check mb-3" style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: white; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                                <input type="checkbox" name="subjects[]" value="<?= $subject['id'] ?>" 
                                       id="subject_<?= $subject['id'] ?>" class="form-check-input subject-checkbox" 
                                       style="width: 20px; height: 20px; cursor: pointer; margin: 0;">
                                <label class="form-check-label" for="subject_<?= $subject['id'] ?>" 
                                       style="flex: 1; cursor: pointer; font-weight: 500; margin: 0;">
                                    <?= htmlspecialchars($subject['name']) ?>
                                </label>
                                <input type="number" name="question_counts[<?= $subject['id'] ?>]" 
                                       class="form-control question-count-input" 
                                       placeholder="Jumlah soal" min="1" value="10"
                                       style="width: 120px; display: none;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> 
                        Centang mata pelajaran yang ingin dimasukkan, lalu atur jumlah soal per mata pelajaran
                    </small>
                </div>
                
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-users"></i> Peserta Ujian
                        </label>
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                            <div class="form-check mb-2">
                                <input type="radio" name="participant_type" value="all" id="participantAll" class="form-check-input" checked style="width: 20px; height: 20px; cursor: pointer;">
                                <label class="form-check-label" for="participantAll" style="margin-left: 0.5rem; cursor: pointer; font-weight: 500;">
                                    Semua Siswa (Publik)
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="participant_type" value="class" id="participantClass" class="form-check-input" style="width: 20px; height: 20px; cursor: pointer;">
                                <label class="form-check-label" for="participantClass" style="margin-left: 0.5rem; cursor: pointer; font-weight: 500;">
                                    Kelas Tertentu Saja
                                </label>
                            </div>
                            <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i> 
                                Pilih "Kelas Tertentu" untuk membatasi ujian hanya untuk siswa di kelas yang dipilih
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group" id="classSelectionSection" style="display: none;">
                        <label class="form-label">
                            <i class="fas fa-school"></i> Pilih Kelas
                        </label>
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; max-height: 300px; overflow-y: auto;">
                            <?php
                            // Get all unique classes from users
                            $classes = db()->query("
                                SELECT kelas, COUNT(*) AS student_count
                                FROM users
                                WHERE role = 'siswa' AND kelas IS NOT NULL AND kelas != ''
                                GROUP BY kelas
                                ORDER BY kelas
                            ")->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <?php foreach ($classes as $class): ?>
                                <div class="form-check mb-2" style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem; background: white; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                                    <input type="checkbox" name="selected_classes[]" value="<?= htmlspecialchars($class['kelas']) ?>" 
                                           id="class_<?= htmlspecialchars($class['kelas']) ?>" class="form-check-input class-checkbox" 
                                           style="width: 20px; height: 20px; cursor: pointer; margin: 0;">
                                    <label class="form-check-label" for="class_<?= htmlspecialchars($class['kelas']) ?>" 
                                           style="flex: 1; cursor: pointer; font-weight: 500; margin: 0;">
                                        <?= htmlspecialchars($class['kelas']) ?>
                                    </label>
                                    <small class="text-muted" style="font-size: 0.8em;">
                                        <?= (int)$class['student_count'] ?> siswa
                                    </small>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($classes)): ?>
                                <p class="text-muted" style="text-align: center; margin: 1rem 0;">
                                    <i class="fas fa-info-circle"></i> 
                                    Belum ada data kelas siswa
                                </p>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> 
                            Centang kelas yang boleh mengikuti ujian ini
                        </small>
                    </div>
                </div>
                
                <div class="form-group-box">
                    <div class="form-group">
                        <label class="form-label">Durasi (menit)</label>
                        <input type="number" name="duration" class="form-control" 
                               placeholder="90" min="1" required>
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> 
                            Untuk multi subject, hitung total waktu untuk semua mata pelajaran
                        </small>
                    </div>
                
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt"></i> Waktu Mulai
                        </label>
                        <input type="datetime-local" name="start_time" class="form-control" 
                               value="<?= toDatetimeLocal(waktuSekarang()) ?>" required>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Waktu dalam zona WIB (Waktu Indonesia Barat)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-check"></i> Waktu Selesai
                        </label>
                        <input type="datetime-local" name="end_time" class="form-control" required>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Waktu dalam zona WIB (Waktu Indonesia Barat)
                        </small>
                    </div>
                </div>
                
                <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-random"></i> Pengaturan Soal
                        </label>
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                            <div class="form-check">
                                <input type="checkbox" name="randomize_questions" value="1" id="randomizeCheck" class="form-check-input" style="width: 20px; height: 20px; cursor: pointer;">
                                <label class="form-check-label" for="randomizeCheck" style="margin-left: 0.5rem; cursor: pointer; font-weight: 500;">
                                    Acak urutan soal untuk setiap siswa
                                </label>
                            </div>
                            <small class="text-muted" style="display: block; margin-top: 0.5rem; margin-left: 1.75rem;">
                                <i class="fas fa-info-circle"></i> 
                                Jika dicentang, setiap siswa akan mendapat urutan soal yang berbeda (anti-cheating)
                            </small>
                        </div>
                    </div>
                
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="<?= config('app_url') ?>/exam" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle between single and multi subject
        const typeSingle = document.getElementById('typeSingle');
        const typeMulti = document.getElementById('typeMulti');
        const singleSection = document.getElementById('singleSubjectSection');
        const multiSection = document.getElementById('multiSubjectSection');
        const subjectSelect = document.getElementById('subjectSelect');
        
        // Toggle between all students and class selection
        const participantAll = document.getElementById('participantAll');
        const participantClass = document.getElementById('participantClass');
        const classSelectionSection = document.getElementById('classSelectionSection');
        
        function toggleExamType() {
            if (typeMulti.checked) {
                singleSection.style.display = 'none';
                multiSection.style.display = 'block';
                subjectSelect.removeAttribute('required');
            } else {
                singleSection.style.display = 'block';
                multiSection.style.display = 'none';
                subjectSelect.setAttribute('required', 'required');
            }
        }
        
        function toggleParticipantType() {
            if (participantClass.checked) {
                classSelectionSection.style.display = 'block';
            } else {
                classSelectionSection.style.display = 'none';
                // Uncheck all class checkboxes
                document.querySelectorAll('.class-checkbox').forEach(cb => cb.checked = false);
            }
        }
        
        typeSingle.addEventListener('change', toggleExamType);
        typeMulti.addEventListener('change', toggleExamType);
        participantAll.addEventListener('change', toggleParticipantType);
        participantClass.addEventListener('change', toggleParticipantType);
        
        function toggleExamType() {
            if (typeMulti.checked) {
                singleSection.style.display = 'none';
                multiSection.style.display = 'block';
                subjectSelect.removeAttribute('required');
            } else {
                singleSection.style.display = 'block';
                multiSection.style.display = 'none';
                subjectSelect.setAttribute('required', 'required');
            }
        }
        
        typeSingle.addEventListener('change', toggleExamType);
        typeMulti.addEventListener('change', toggleExamType);
        
        // Show/hide question count input when checkbox is checked
        document.querySelectorAll('.subject-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const input = this.closest('.form-check').querySelector('.question-count-input');
                if (this.checked) {
                    input.style.display = 'block';
                    input.setAttribute('required', 'required');
                } else {
                    input.style.display = 'none';
                    input.removeAttribute('required');
                }
            });
        });
        
        // Auto calculate end time based on start time and duration
        const startTimeInput = document.querySelector('input[name="start_time"]');
        const endTimeInput = document.querySelector('input[name="end_time"]');
        const durationInput = document.querySelector('input[name="duration"]');
        
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
        
        startTimeInput.addEventListener('change', calculateEndTime);
        durationInput.addEventListener('input', calculateEndTime);
        
        // Format duration display
        durationInput.addEventListener('input', function() {
            const duration = parseInt(this.value);
            const small = this.nextElementSibling;
            
            if (duration) {
                let text = '<i class="fas fa-clock"></i> ';
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
                text += ' - Untuk multi subject, hitung total waktu untuk semua mata pelajaran';
                small.innerHTML = text;
            }
        });
        
        // Form validation for multi-subject and class selection
        document.getElementById('examForm').addEventListener('submit', function(e) {
            if (typeMulti.checked) {
                const checkedSubjects = document.querySelectorAll('.subject-checkbox:checked');
                if (checkedSubjects.length === 0) {
                    e.preventDefault();
                    alert('Pilih minimal 1 mata pelajaran untuk ujian multi subject!');
                    return false;
                }
            }
            
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
            const now = new Date();
            
            if (startTime <= now) {
                e.preventDefault();
                alert('Waktu mulai harus lebih dari waktu sekarang!');
                return false;
            }
            
            if (endTime <= startTime) {
                e.preventDefault();
                alert('Waktu selesai harus lebih dari waktu mulai!');
                return false;
            }
        });
        
        // Initialize end time calculation
        calculateEndTime();
    </script>
</body>
</html>
