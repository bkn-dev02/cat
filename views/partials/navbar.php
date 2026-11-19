<?php
$user = auth();
$currentPage = $_GET['url'] ?? '';
?>
<script>
    // Set MathJax config only if not already configured by the page
    if (typeof window.MathJax === 'undefined') {
        window.MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)'], ['$', '$']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true,
                processEnvironments: true,
                packages: { '[+]': ['ams'] }
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre'],
                processHtmlClass: 'mathjax-process'
            }
        };
    }
    // Load MathJax only once — skip if id already exists OR MathJax already initialized
    if (!document.getElementById('MathJax-script') && !window.MathJax?.version) {
        var mjScript = document.createElement('script');
        mjScript.id = 'MathJax-script';
        mjScript.async = true;
        mjScript.src = '<?= asset_url('mathjax/tex-mml-chtml.js') ?>';
        document.head.appendChild(mjScript);
    }
</script>

<nav class="navbar navbar-expand-lg fixed-top-navbar">
    <div class="container">
        <a class="navbar-brand" href="<?= config('app_url') ?>/dashboard">
            <img src="<?= asset_url('cat.png') ?>" alt="CAT Logo" style="height: 36px; width: auto; margin-right: 8px;">
            <span>CAT Enterprise</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-4">
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentPage, 'dashboard') !== false ? 'active' : '' ?>"
                        href="<?= config('app_url') ?>/dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentPage, 'exam') !== false && strpos($currentPage, 'exam/monitor') === false ? 'active' : '' ?>"
                        href="<?= config('app_url') ?>/exam">
                        <i class="fas fa-file-alt"></i>
                        <span>Ujian</span>
                    </a>
                </li>
                <?php if ($user['role'] !== 'siswa'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'admin/cekSiswa') !== false ? 'active' : '' ?>"
                            href="<?= config('app_url') ?>/admin/cekSiswa">
                            <i class="fas fa-user-graduate"></i>
                            <span>Cek Siswa</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'question') !== false ? 'active' : '' ?>"
                            href="<?= config('app_url') ?>/question">
                            <i class="fas fa-question-circle"></i>
                            <span>Bank Soal</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'questionGroup') !== false ? 'active' : '' ?>"
                            href="<?= config('app_url') ?>/questionGroup">
                            <i class="fas fa-layer-group"></i>
                            <span>Grup Soal</span>
                        </a>
                    </li>

                    <?php
                    $canUseOCR = false;
                    if (in_array($user['role'], ['admin', 'guru'])) {
                        $canUseOCR = true;
                    }
                    ?>

                    <?php if ($canUseOCR): ?>
                        <li class="nav-item">
                            <button class="nav-link btn-scan-ocr" onclick="OcrScanner.open()">
                                <i class="fas fa-camera"></i>
                                <span>Scan OCR</span>
                            </button>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'exam/monitor') !== false ? 'active' : '' ?>"
                            href="<?= config('app_url') ?>/exam/monitor">
                            <i class="fas fa-desktop"></i>
                            <span>Monitor</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) === 'ANALISIS_SOAL_TPS.php') ? 'active' : '' ?>"
                            href="<?= config('app_url') ?>/ANALISIS_SOAL_TPS.php"
                            title="Analisis tingkat kesulitan soal & nilai skala 200–1000">
                            <i class="fas fa-chart-bar"></i>
                            <span>Analisis TPS</span>
                        </a>
                    </li>
                    <?php if ($user['role'] === 'guru'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentPage, 'admin/resetSesi') !== false ? 'active' : '' ?>"
                                href="<?= config('app_url') ?>/admin/resetSesi">
                                <i class="fas fa-unlock-alt"></i>
                                <span>Reset Sesi</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentPage, 'admin/suspendAkun') !== false ? 'active' : '' ?>"
                                href="<?= config('app_url') ?>/admin/suspendAkun">
                                <i class="fas fa-undo-alt"></i>
                                <span>Reset Ujian</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'sync') !== false ? 'active' : '' ?>"
                            href="<?= config('app_url') ?>/sync/ppdb">
                            <i class="fas fa-sync-alt"></i>
                            <span>Sync PPDB</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'admin/guruMapel') !== false ? 'active' : '' ?>"
                            href="<?= config('app_url') ?>/admin/guruMapel">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Mapel Guru</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'admin/resetSesi') !== false ? 'active' : '' ?>"
                            href="<?= config('app_url') ?>/admin/resetSesi">
                            <i class="fas fa-unlock-alt"></i>
                            <span>Reset Sesi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'admin/suspendAkun') !== false ? 'active' : '' ?>"
                            href="<?= config('app_url') ?>/admin/suspendAkun">
                            <i class="fas fa-undo-alt"></i>
                            <span>Reset Ujian</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- User Menu -->
            <div class="d-flex align-items-center">
                <div class="user-dropdown-wrap">
                    <button class="user-trigger" type="button" onclick="toggleUserMenu()" id="userTrigger">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        </div>
                        <span class="user-name"><?= htmlspecialchars($user['full_name']) ?></span>
                        <i class="fas fa-chevron-down user-chevron" id="userChevron"></i>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <div class="udm-header">
                            <div class="udm-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
                            <div>
                                <div class="udm-name"><?= htmlspecialchars($user['full_name']) ?></div>
                                <div class="udm-role"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
                            </div>
                        </div>
                        <div class="udm-divider"></div>
                        <a href="<?= config('app_url') ?>/profile/changePassword" class="udm-item">
                            <i class="fas fa-lock"></i> Ubah Password
                        </a>
                        <div class="udm-divider"></div>
                        <a href="<?= config('app_url') ?>/auth/logout" class="udm-item udm-logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --success: #16a34a;
        --warning: #d97706;
        --danger: #dc2626;
        --dark: #1f2937;
        --light: #f8fafc;
        --border: #e2e8f0;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }

    /* Mencegah konten tertutup navbar yang fixed */
    body {
        padding-top: 72px; 
    }

    .fixed-top-navbar {
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--border);
        box-shadow: var(--shadow);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        padding: 0.4rem 0;
        height: 72px;
    }

    .navbar-brand {
        font-weight: 700;
        color: var(--primary) !important;
        font-size: 1.15rem;
        display: flex;
        align-items: center;
        text-decoration: none;
        flex-shrink: 0;
    }

    .navbar-brand:hover {
        color: var(--primary-dark) !important;
    }

    .navbar-nav {
        gap: 0.15rem;
        flex-wrap: nowrap; 
    }

    .nav-item {
        display: flex;
        align-items: center;
    }

    .nav-link {
        color: #64748b !important;
        font-weight: 600;
        padding: 0.45rem 0.5rem !important;
        border-radius: 8px;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        min-width: 68px;
        line-height: 1.2;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .nav-link i {
        font-size: 1.15rem;
        margin-bottom: 0.15rem;
        display: block;
        transition: transform 0.2s ease;
    }

    .nav-link span {
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
        display: block;
        letter-spacing: -0.01em;
    }

    .nav-link:hover {
        background: rgba(37, 99, 235, 0.08);
        color: var(--primary) !important;
        border-color: rgba(37, 99, 235, 0.15);
    }

    .nav-link:hover i {
        transform: translateY(-1px);
    }

    .nav-link.active {
        background: var(--primary);
        color: white !important;
        border-color: var(--primary);
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    .nav-link.active i,
    .nav-link.active span {
        color: white !important;
    }

    /* Tombol Scan OCR */
    .btn-scan-ocr {
        background: none;
        border: 1px solid transparent;
        font-family: inherit;
        cursor: pointer;
        color: #64748b !important;
    }

    .btn-scan-ocr:hover {
        background: rgba(37, 99, 235, 0.08);
        color: var(--primary) !important;
        border-color: rgba(37, 99, 235, 0.15);
    }

    /* ── User Dropdown ── */
    .user-dropdown-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .user-trigger {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: white;
        border: 1.5px solid var(--border);
        border-radius: 50px;
        padding: 0.3rem 0.75rem 0.3rem 0.3rem;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
    }

    .user-trigger:hover {
        border-color: var(--primary);
        background: rgba(37, 99, 235, 0.03);
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
    }

    .user-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #3b82f6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .user-name {
        font-weight: 600;
        color: var(--dark);
        font-size: 0.85rem;
        max-width: 130px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .user-chevron {
        font-size: 0.65rem;
        color: #94a3b8;
        transition: transform 0.25s;
    }

    .user-chevron.open {
        transform: rotate(180deg);
    }

    /* Dropdown panel */
    .user-dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 10px);
        background: white;
        border: 1px solid var(--border);
        border-radius: 0.875rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
        min-width: 230px;
        padding: 0.5rem;
        z-index: 9999;
        animation: dropIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .user-dropdown-menu.open {
        display: block;
    }

    @keyframes dropIn {
        from {
            opacity: 0;
            transform: translateY(-8px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .udm-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.75rem 0.5rem;
    }

    .udm-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .udm-name {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.9rem;
    }

    .udm-role {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 2px;
        font-weight: 500;
    }

    .udm-divider {
        height: 1px;
        background: var(--border);
        margin: 0.4rem 0;
    }

    .udm-item {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        text-decoration: none;
        transition: all 0.15s;
    }

    .udm-item:hover {
        background: rgba(99, 102, 241, 0.08);
        color: #6366f1;
        text-decoration: none;
    }

    .udm-item i {
        width: 18px;
        text-align: center;
        font-size: 0.85rem;
    }

    .udm-logout {
        color: #dc2626;
    }

    .udm-logout:hover {
        background: rgba(220, 38, 38, 0.08);
        color: #dc2626;
    }

    .navbar-toggler {
        border: none;
        padding: 0.3rem 0.5rem;
        border-radius: 6px;
    }

    .navbar-toggler:hover {
        background: rgba(0,0,0,0.05);
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    /* ── Responsive: Laptop Kecil (1366px ke bawah) ── */
    @media (max-width: 1366px) {
        .nav-link {
            min-width: 62px;
            padding: 0.4rem 0.4rem !important;
        }
        .nav-link i {
            font-size: 1.05rem;
        }
        .nav-link span {
            font-size: 0.7rem;
        }
        .user-name {
            max-width: 100px;
        }
    }

    @media (max-width: 991px) {
        body {
            padding-top: 64px;
        }
    
        .fixed-top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: auto;
            padding: 0;
            z-index: 1030;
        }
    
        .fixed-top-navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 64px;
            position: relative;
        }
    
        .navbar-collapse {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            max-height: calc(100vh - 64px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            border-top: 1px solid var(--border);
            padding: 1rem;
            margin: 0;
        }
    
        .navbar-nav {
            margin-top: 0;
            gap: 0.4rem;
            flex-wrap: wrap;
        }
    
        .nav-link {
            flex-direction: row;
            padding: 0.65rem 0.75rem !important;
            min-width: auto;
            width: 100%;
            justify-content: flex-start;
            border: 1px solid var(--border);
        }
    
        .nav-link i {
            margin-bottom: 0;
            margin-right: 0.75rem;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
    
        .nav-link span {
            font-size: 0.9rem;
            text-align: left;
            letter-spacing: 0;
        }
    
        .user-dropdown-wrap {
            margin-top: 1rem;
            width: 100%;
        }
    
        .user-trigger {
            width: 100%;
            justify-content: space-between;
        }
    
        .user-dropdown-menu {
            position: static;
            box-shadow: none;
            border: 1px solid var(--border);
            animation: none;
            margin-top: 0.5rem;
        }
    }
    
    @media (max-width: 991px) and (min-width: 576px) {
        .navbar-nav {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }
    
        .navbar-nav .nav-item {
            width: 100%;
        }
    }
    
    @media (max-width: 575px) {
        .navbar-collapse {
            padding: 0.85rem;
        }
        .nav-link {
            font-size: 0.85rem;
            padding: 0.6rem 0.65rem !important;
        }
    }
</style>

<script>
    (function() {
        function toggleUserMenu() {
            var menu = document.getElementById('userDropdownMenu');
            var chevron = document.getElementById('userChevron');
            var isOpen = menu.classList.contains('open');
            menu.classList.toggle('open', !isOpen);
            chevron.classList.toggle('open', !isOpen);
        }

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            var wrap = document.querySelector('.user-dropdown-wrap');
            if (wrap && !wrap.contains(e.target)) {
                var menu = document.getElementById('userDropdownMenu');
                var chevron = document.getElementById('userChevron');
                if (menu) menu.classList.remove('open');
                if (chevron) chevron.classList.remove('open');
            }
        });

        // Expose to global scope for onclick
        window.toggleUserMenu = toggleUserMenu;
    })();
</script>

<?php if ($user['role'] !== 'siswa'): ?>
    <script>
        window.APP_URL = <?= json_encode(config('app_url')) ?>;
        window.ASSET_BASE_URL = <?= json_encode(asset_url('')) ?>;
    </script>
    <?php include VIEW_PATH . '/partials/ocr_scanner.php'; ?>
    <script src="<?= asset_url('js/ocr_scanner.js') ?>"></script>
<?php endif; ?>