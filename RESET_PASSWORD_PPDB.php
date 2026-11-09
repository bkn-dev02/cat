<?php
/**
 * RESET PASSWORD SISWA PPDB
 * Reset password siswa yang di-sync dari PPDB ke password default
 * 
 * Password default: Siswa123!
 */

require_once __DIR__ . '/app/helpers.php';

session_start();

// Keamanan: hanya admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
    die('<div style="font-family:sans-serif;padding:20px;color:red;">❌ Akses ditolak. Hanya admin yang boleh mengakses halaman ini.</div>');
}

$defaultPassword = 'Siswa123!';
$defaultPasswordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

$results = [];
$totalReset = 0;
$selectedIds = [];

// ============================================
// PROSES RESET
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Reset semua siswa PPDB saja
    if ($action === 'reset_all') {
        $filterKelasPost = $_POST['filter_kelas'] ?? [];
        if (!is_array($filterKelasPost)) $filterKelasPost = [$filterKelasPost];
        $filterKelasPost = array_filter($filterKelasPost);

        if (empty($filterKelasPost)) {
            $results[] = ['status' => 'warning', 'msg' => '⚠️ Tidak ada kelas yang dipilih untuk di-reset.'];
        } else {
            try {
                $placeholdersK = implode(',', array_fill(0, count($filterKelasPost), '?'));
                $params = array_merge([$defaultPasswordHash], array_values($filterKelasPost));
                $stmt = db()->prepare("UPDATE users SET password = ? WHERE role = 'siswa' AND kelas IN ({$placeholdersK})");
                $stmt->execute($params);
                $totalReset = $stmt->rowCount();
                $results[] = ['status' => 'success', 'msg' => "✅ Berhasil reset password {$totalReset} siswa ke: <strong>{$defaultPassword}</strong>"];
            } catch (\PDOException $e) {
                $results[] = ['status' => 'error', 'msg' => '❌ Gagal: ' . $e->getMessage()];
            }
        }
    }

    // Reset siswa yang dipilih
    if ($action === 'reset_selected') {
        $selectedIds = $_POST['student_ids'] ?? [];
        if (empty($selectedIds)) {
            $results[] = ['status' => 'warning', 'msg' => '⚠️ Tidak ada siswa yang dipilih.'];
        } else {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            try {
                $params = array_merge([$defaultPasswordHash], $selectedIds);
                $stmt = db()->prepare("UPDATE users SET password = ? WHERE id IN ({$placeholders}) AND role = 'siswa'");
                $stmt->execute($params);
                $totalReset = $stmt->rowCount();
                $results[] = ['status' => 'success', 'msg' => "✅ Berhasil reset password {$totalReset} siswa yang dipilih ke: <strong>{$defaultPassword}</strong>"];
            } catch (\PDOException $e) {
                $results[] = ['status' => 'error', 'msg' => '❌ Gagal: ' . $e->getMessage()];
            }
        }
    }

    // Reset satu siswa
    if ($action === 'reset_one') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            try {
                $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'siswa'");
                $stmt->execute([$defaultPasswordHash, $userId]);
                if ($stmt->rowCount() > 0) {
                    $results[] = ['status' => 'success', 'msg' => "✅ Password berhasil direset ke: <strong>{$defaultPassword}</strong>"];
                } else {
                    $results[] = ['status' => 'warning', 'msg' => '⚠️ Siswa tidak ditemukan atau bukan role siswa.'];
                }
            } catch (\PDOException $e) {
                $results[] = ['status' => 'error', 'msg' => '❌ Gagal: ' . $e->getMessage()];
            }
        }
    }
}

// ============================================
// AMBIL SEMUA KELAS YANG ADA (untuk filter)
// ============================================
try {
    $stmtKelas = db()->query("SELECT DISTINCT kelas FROM users WHERE role = 'siswa' ORDER BY kelas ASC");
    $allKelas = $stmtKelas->fetchAll(PDO::FETCH_COLUMN);
} catch (\PDOException $e) {
    $allKelas = [];
}

// Kelas yang dianggap PPDB (default: X dan yang mengandung PPDB)
$defaultPpdbKelas = array_values(array_filter($allKelas, function($k) {
    return $k === 'X' || stripos($k, 'PPDB') !== false;
}));

// Ambil filter kelas dari GET param, atau pakai default
$filterKelas = $_GET['kelas'] ?? $defaultPpdbKelas;
if (!is_array($filterKelas)) $filterKelas = [$filterKelas];
$filterKelas = array_filter($filterKelas); // buang kosong

// ============================================
// AMBIL DATA SISWA SESUAI FILTER KELAS
// ============================================
try {
    if (!empty($filterKelas)) {
        $placeholdersK = implode(',', array_fill(0, count($filterKelas), '?'));
        $stmt = db()->prepare("
            SELECT id, username, full_name, kelas, created_at
            FROM users
            WHERE role = 'siswa'
              AND kelas IN ({$placeholdersK})
            ORDER BY full_name ASC
        ");
        $stmt->execute(array_values($filterKelas));
    } else {
        // Jika tidak ada filter, tampilkan kosong
        $stmt = db()->query("
            SELECT id, username, full_name, kelas, created_at
            FROM users WHERE 1=0
        ");
    }
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $students = [];
    $results[] = ['status' => 'error', 'msg' => '❌ Gagal ambil data siswa: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Siswa PPDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .card { border-radius: 12px; }
        .navbar-brand { font-weight: 700; }
        .badge-kelas { font-size: 0.75rem; }
        #searchInput { max-width: 300px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-danger px-3">
    <a class="navbar-brand" href="<?= config('app_url') ?>/dashboard">
        <i class="bi bi-arrow-left me-2"></i>CAT Enterprise
    </a>
    <span class="text-white fw-semibold">
        <i class="bi bi-key-fill me-1"></i> Reset Password Siswa PPDB
    </span>
</nav>

<div class="container py-4">

    <!-- INFO BOX -->
    <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
        <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
        <div>
            <strong>Informasi Reset Password</strong><br>
            Password default yang akan diset: <code class="fs-6 fw-bold">Siswa123!</code><br>
            <small class="text-muted">Siswa login menggunakan <strong>email</strong> sebagai username dan password di atas.</small>
        </div>
    </div>

    <!-- FILTER KELAS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-funnel-fill text-primary me-2"></i>Filter Kelas yang Ditampilkan</h6>
            <small class="text-muted">Centang kelas yang ingin ditampilkan dan di-reset. Kelas PPDB sudah otomatis tercentang.</small>
        </div>
        <div class="card-body">
            <form method="GET" id="formFilter">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php foreach ($allKelas as $k): ?>
                        <?php
                        $checked = in_array($k, $filterKelas) ? 'checked' : '';
                        $isPpdb = ($k === 'X' || stripos($k, 'PPDB') !== false);
                        $badgeColor = $isPpdb ? 'bg-success' : 'bg-secondary';
                        ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input kelas-filter-check" type="checkbox"
                                   name="kelas[]" value="<?= htmlspecialchars($k) ?>"
                                   id="kelas_<?= md5($k) ?>" <?= $checked ?>>
                            <label class="form-check-label" for="kelas_<?= md5($k) ?>">
                                <span class="badge <?= $badgeColor ?>"><?= htmlspecialchars($k) ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i> Terapkan Filter
                </button>
                <a href="?" class="btn btn-outline-secondary btn-sm ms-1">Reset Filter</a>
            </form>
        </div>
    </div>

    <!-- HASIL PROSES -->
    <?php foreach ($results as $r): ?>
        <?php
        $alertClass = match($r['status']) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            default   => 'alert-warning',
        };
        ?>
        <div class="alert <?= $alertClass ?> alert-dismissible fade show">
            <?= $r['msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <!-- CARD UTAMA -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0"><i class="bi bi-people-fill text-danger me-2"></i>Daftar Siswa</h5>
                <small class="text-muted"><?= count($students) ?> siswa PPDB terdaftar di CAT</small>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                       placeholder="🔍 Cari nama / email..." oninput="filterTable()">
            </div>
        </div>

        <div class="card-body">

            <!-- TOMBOL AKSI MASSAL -->
            <form method="POST" id="formMassal" onsubmit="return konfirmasiMassal(event)">
                <input type="hidden" name="action" id="actionMassal" value="">
                <div id="hiddenIds"></div>
                <!-- Pass filter kelas aktif ke POST -->
                <?php foreach ($filterKelas as $fk): ?>
                    <input type="hidden" name="filter_kelas[]" value="<?= htmlspecialchars($fk) ?>">
                <?php endforeach; ?>

                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAll(true)">
                        <i class="bi bi-check-all"></i> Pilih Semua
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAll(false)">
                        <i class="bi bi-x-lg"></i> Batal Semua
                    </button>
                    <button type="button" class="btn btn-warning btn-sm ms-auto" onclick="submitMassal('reset_selected')">
                        <i class="bi bi-key me-1"></i> Reset Password yang Dipilih
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="submitMassal('reset_all')">
                        <i class="bi bi-exclamation-triangle me-1"></i> Reset SEMUA Siswa PPDB
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tabelSiswa">
                        <thead class="table-light">
                            <tr>
                                <th width="40"><input type="checkbox" id="checkAll" onchange="toggleAll(this.checked)"></th>
                                <th>#</th>
                                <th>Nama Lengkap</th>
                                <th>Email / Username</th>
                                <th>Kelas</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Belum ada siswa terdaftar
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $i => $s): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="student-check"
                                                   value="<?= (int)$s['id'] ?>">
                                        </td>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($s['full_name'] ?? '-') ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($s['username']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary badge-kelas">
                                                <?= htmlspecialchars($s['kelas'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y', strtotime($s['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <!-- Reset satu siswa -->
                                            <form method="POST" style="display:inline"
                                                  onsubmit="return confirm('Reset password <?= htmlspecialchars(addslashes($s['full_name'] ?? $s['username'])) ?> ke Siswa123! ?')">
                                                <input type="hidden" name="action" value="reset_one">
                                                <input type="hidden" name="user_id" value="<?= (int)$s['id'] ?>">
                                                <button type="submit" class="btn btn-outline-warning btn-sm">
                                                    <i class="bi bi-key"></i> Reset
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

        </div>
    </div>

    <div class="text-center mt-3">
        <a href="<?= config('app_url') ?>/sync/ppdb" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Sync PPDB
        </a>
        <a href="<?= config('app_url') ?>/dashboard" class="btn btn-outline-secondary btn-sm ms-2">
            <i class="bi bi-house me-1"></i> Dashboard
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleAll(checked) {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = checked);
    const ca = document.getElementById('checkAll');
    if (ca) ca.checked = checked;
}

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#tabelSiswa tbody tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
}

function submitMassal(action) {
    const checks = document.querySelectorAll('.student-check:checked');

    if (action === 'reset_selected' && checks.length === 0) {
        alert('Pilih minimal 1 siswa terlebih dahulu.');
        return;
    }

    const jumlah = action === 'reset_all'
        ? <?= count($students) ?>
        : checks.length;

    const konfirmasi = action === 'reset_all'
        ? `Reset password SEMUA ${jumlah} siswa PPDB ke "Siswa123!" ?\n\nTindakan ini tidak bisa dibatalkan.`
        : `Reset password ${jumlah} siswa yang dipilih ke "Siswa123!" ?`;

    if (!confirm(konfirmasi)) return;

    // Set action
    document.getElementById('actionMassal').value = action;

    // Inject hidden inputs untuk IDs yang dipilih
    const container = document.getElementById('hiddenIds');
    container.innerHTML = '';
    if (action === 'reset_selected') {
        checks.forEach(cb => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'student_ids[]';
            inp.value = cb.value;
            container.appendChild(inp);
        });
    }

    document.getElementById('formMassal').submit();
}

function konfirmasiMassal(e) {
    // Sudah ditangani di submitMassal, cegah double submit
    return true;
}
</script>
</body>
</html>
