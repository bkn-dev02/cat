<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= htmlspecialchars($exam['title']) ?> - CAT Enterprise</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: fixed;
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #1e293b;
            -webkit-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        #timer {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #ef4444;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            z-index: 9999;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        
        #info {
            position: fixed;
            top: 10px;
            left: 10px;
            background: #6366f1;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            z-index: 9999;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            font-size: 14px;
            max-width: calc(100% - 150px);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        #pdfContainer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        #pdfViewer {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }
        
        #warning {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(239, 68, 68, 0.95);
            color: white;
            z-index: 10000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
        }
        
        #warning p {
            margin: 15px 0;
            line-height: 1.4;
        }
        
        .btn-finish {
            position: fixed;
            bottom: 15px;
            right: 15px;
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            z-index: 9999;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
        
        .btn-finish:active {
            background: #059669;
            transform: scale(0.95);
        }
        
        /* Zoom controls for mobile */
        #zoomControls {
            position: fixed;
            bottom: 80px;
            right: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 9999;
        }
        
        .zoom-btn {
            width: 45px;
            height: 45px;
            background: rgba(99, 102, 241, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
        
        .zoom-btn:active {
            background: rgba(79, 70, 229, 0.9);
            transform: scale(0.9);
        }
        
        /* Mobile specific */
        @media (max-width: 768px) {
            #timer {
                font-size: 14px;
                padding: 6px 12px;
                top: 5px;
                right: 5px;
            }
            
            #info {
                font-size: 11px;
                padding: 6px 10px;
                top: 5px;
                left: 5px;
                max-width: calc(100% - 100px);
            }
            
            #info strong {
                display: block;
                margin-bottom: 2px;
            }
            
            #warning {
                font-size: 18px;
                padding: 15px;
            }
            
            .btn-finish {
                bottom: 10px;
                right: 10px;
                padding: 10px 20px;
                font-size: 14px;
            }
            
            #zoomControls {
                bottom: 65px;
                right: 10px;
                gap: 8px;
            }
            
            .zoom-btn {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
        }
        
        /* Landscape mode */
        @media (orientation: landscape) and (max-width: 768px) {
            #info {
                max-width: calc(100% - 200px);
            }
        }
        
        /* Very small screens */
        @media (max-width: 360px) {
            #timer {
                font-size: 12px;
                padding: 5px 10px;
            }
            
            #info {
                font-size: 10px;
                padding: 5px 8px;
            }
            
            .btn-finish {
                padding: 8px 16px;
                font-size: 12px;
            }
            
            .zoom-btn {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }
        }
        
        /* Prevent zoom on iOS */
        input, select, textarea, button {
            font-size: 16px !important;
        }
    </style>
</head>
<body>
    <div id="timer">00:00</div>
    
    <div id="info">
        <div><strong><?= htmlspecialchars($exam['title']) ?></strong></div>
        <div style="font-size: 12px; margin-top: 3px;">
            <?= htmlspecialchars($user['full_name']) ?>
        </div>
    </div>
    
    <div id="warning">
        <p>⚠️ PERINGATAN!</p>
        <p id="warningText">Jangan keluar dari aplikasi!</p>
        <p id="warningCount"></p>
    </div>
    
    <div id="pdfContainer">
        <iframe id="pdfViewer" src="<?= config('app_url') ?>/uploads/exams/<?= $exam['exam_pdf'] ?>"></iframe>
    </div>
    
    <div id="zoomControls">
        <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">+</button>
        <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">−</button>
        <button class="zoom-btn" onclick="resetZoom()" title="Reset">⟲</button>
    </div>
    
    <button class="btn-finish" onclick="finishExam()">Selesai</button>
    
    <script>
        const examId = <?= $exam['id'] ?>;
        const duration = <?= $exam['duration'] ?> * 60;
        let timeLeft = duration;
        let warningCount = 0;
        const maxWarnings = 5; // Lebih toleran untuk mobile
        let isFullscreen = false;
        let isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        // Detect if running in standalone mode (PWA)
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                            window.navigator.standalone || 
                            document.referrer.includes('android-app://');
        
        // Timer
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            if (timeLeft <= 0) {
                autoFinish();
            } else {
                timeLeft--;
            }
        }
        
        setInterval(updateTimer, 1000);
        updateTimer();
        
        // Request fullscreen (desktop) or lock orientation (mobile)
        function enterFullscreen() {
            if (isMobile) {
                // Mobile: try to lock orientation
                if (screen.orientation && screen.orientation.lock) {
                    screen.orientation.lock('portrait').catch(() => {
                        console.log('Orientation lock not supported');
                    });
                }
            } else {
                // Desktop: fullscreen
                const elem = document.documentElement;
                if (elem.requestFullscreen) {
                    elem.requestFullscreen().catch(() => {});
                } else if (elem.webkitRequestFullscreen) {
                    elem.webkitRequestFullscreen().catch(() => {});
                } else if (elem.msRequestFullscreen) {
                    elem.msRequestFullscreen().catch(() => {});
                }
            }
        }
        
        // Detect fullscreen change (desktop only)
        if (!isMobile) {
            document.addEventListener('fullscreenchange', function() {
                isFullscreen = !!document.fullscreenElement;
                if (!isFullscreen) {
                    showWarning('Jangan keluar dari fullscreen!');
                }
            });
            
            document.addEventListener('webkitfullscreenchange', function() {
                isFullscreen = !!document.webkitFullscreenElement;
                if (!isFullscreen) {
                    showWarning('Jangan keluar dari fullscreen!');
                }
            });
        }
        
        // Detect visibility change (works on mobile & desktop)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                showWarning('Jangan keluar dari aplikasi!');
            }
        });
        
        // Detect window blur (works on mobile & desktop)
        window.addEventListener('blur', function() {
            showWarning('Jangan berpindah aplikasi!');
        });
        
        // Detect page unload attempt
        window.addEventListener('beforeunload', function(e) {
            if (timeLeft > 0) {
                e.preventDefault();
                e.returnValue = '';
                showWarning('Jangan tutup aplikasi!');
            }
        });
        
        // Show warning
        function showWarning(message) {
            warningCount++;
            
            document.getElementById('warningText').textContent = message;
            document.getElementById('warningCount').textContent = 
                `Peringatan ke-${warningCount} dari ${maxWarnings}`;
            document.getElementById('warning').style.display = 'flex';
            
            // Log violation
            fetch('<?= config('app_url') ?>/exam/logActivity', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: <?= $session['id'] ?>,
                    activity_type: 'violation_' + warningCount
                })
            });
            
            setTimeout(() => {
                document.getElementById('warning').style.display = 'none';
                
                if (warningCount >= maxWarnings) {
                    suspendExam();
                } else {
                    if (!isMobile) {
                        enterFullscreen();
                    }
                }
            }, 3000);
        }
        
        // Suspend exam
        function suspendExam() {
            fetch('<?= config('app_url') ?>/exam/suspendToken', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    session_id: <?= $session['id'] ?>,
                    reason: 'Terlalu banyak pelanggaran (' + warningCount + 'x)',
                    violation_count: warningCount
                })
            }).then(() => {
                alert('Ujian Anda telah di-suspend karena melanggar aturan!');
                window.location.href = '<?= config('app_url') ?>/exam/suspended/<?= $session['id'] ?>';
            });
        }
        
        // Finish exam
        function finishExam() {
            if (confirm('Yakin ingin mengakhiri ujian?')) {
                fetch('<?= config('app_url') ?>/exam/submit/<?= $session['id'] ?>', {
                    method: 'POST'
                }).then(() => {
                    alert('Ujian selesai! Terima kasih.');
                    window.location.href = '<?= config('app_url') ?>/dashboard';
                });
            }
        }
        
        // Auto finish when time's up
        function autoFinish() {
            alert('Waktu habis! Ujian otomatis selesai.');
            finishExam();
        }
        
        // Prevent context menu (right click / long press)
        document.addEventListener('contextmenu', e => e.preventDefault());
        
        // Prevent text selection on mobile
        document.addEventListener('selectstart', e => e.preventDefault());
        
        // Prevent keyboard shortcuts (desktop)
        document.addEventListener('keydown', function(e) {
            // Prevent F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
            if (e.keyCode === 123 || 
                (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) ||
                (e.ctrlKey && e.keyCode === 85)) {
                e.preventDefault();
                return false;
            }
            
            // Prevent Ctrl+P (print)
            if (e.ctrlKey && e.keyCode === 80) {
                e.preventDefault();
                return false;
            }
        });
        
        // Start fullscreen/lock on load
        window.onload = function() {
            enterFullscreen();
            
            // Show instruction for mobile users
            if (isMobile && !isStandalone) {
                setTimeout(() => {
                    alert('Tips: Jangan keluar dari browser atau berpindah aplikasi selama ujian!');
                }, 1000);
            }
        };
        
        // Prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
            showWarning('Jangan gunakan tombol back!');
        };
        
        // Zoom functionality for mobile
        let currentZoom = 1.0;
        const pdfContainer = document.getElementById('pdfContainer');
        
        function zoomIn() {
            currentZoom = Math.min(currentZoom + 0.2, 3.0);
            applyZoom();
        }
        
        function zoomOut() {
            currentZoom = Math.max(currentZoom - 0.2, 0.5);
            applyZoom();
        }
        
        function resetZoom() {
            currentZoom = 1.0;
            applyZoom();
        }
        
        function applyZoom() {
            const iframe = document.getElementById('pdfViewer');
            iframe.style.transform = `scale(${currentZoom})`;
            iframe.style.transformOrigin = 'top left';
            iframe.style.width = `${100 / currentZoom}%`;
            iframe.style.height = `${100 / currentZoom}%`;
        }
    </script>
</body>
</html>
