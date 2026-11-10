<?php
/**
 * CEK & RESET PASSWORD SISWA PPDB
 * Tampilkan semua siswa PPDB, cek status password, reset massal
 */

require_once __DIR__ . '/app/helpers.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
    die('<div style="font-family:sans-serif;padding:20px;color:red;">❌ Akses ditolak.</div>');
}

$defaultPassword = 'Siswa123!';

// ── AJAX: cek password satu siswa ────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'cek' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = (int)$_GET['id'];
    $stmt = db()->prepare("SELECT password FROM users WHERE id = ? AND role = 'siswa' LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $cocok = $row ? password_verify($defaultPassword, $row['password']) : false;
    echo json_encode(['id' => $id, 'cocok' => $cocok]);
    exit;
}

// ── AJAX: reset password satu siswa ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'reset_one') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'siswa'");
        $stmt->execute([$hash, $id]);
        echo json_encode(['success' => $stmt->rowCount() > 0]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// ── AJAX: reset massal (array of IDs) ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'reset_batch') {
    header('Content-Type: application/json');
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    $ids = array_map('intval', (array)$ids);
    $ids = array_filter($ids);
    $count = 0;
    if (!empty($ids)) {
        $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$hash], $ids);
        $stmt = db()->prepare("UPDATE users SET password = ? WHERE id IN ($placeholders) AND role = 'siswa'");
        $stmt->execute($params);
        $count = $stmt->rowCount();
    }
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

// ── Ambil semua siswa PPDB ───────────────────────────────────
$stmt = db()->query("
    SELECT id, username, full_name, kelas
    FROM users
    WHERE role = 'siswa'
      AND (kelas = 'X' OR kelas LIKE '%PPDB%' OR kelas LIKE '%ppdb%')
    ORDER BY full_name ASC
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Password Siswa PPDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .status-badge { min-width: 110px; display: inline-block; text-align: center; }
        .row-belum { background: #fff8f0; }
        .row-cocok { background: #f0fff4; }
        #progressBar { transition: width .3s; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary px-3">
    <a class="navbar-brand fw-bold" href="<?= config('app_url') ?>/dashboard">
        <i class="bi bi-arrow-left me-2"></i>CAT Enterprise
    </a>
    <span class="text-white"><i class="bi bi-key-fill me-1"></i> Status Password Siswa PPDB</span>
</nav>

<div class="container-fluid py-4" style="max-width:960px">

    <!-- RINGKASAN & AKSI -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-1">
                        <i class="bi bi-people-fill text-primary me-2"></i>
                        <?= count($students) ?> Siswa PPDB
                    </h5>
                    <div class="d-flex gap-3 mt-2 flex-wrap">
                        <span class="text-muted small">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Belum dicek: <strong id="countPending"><?= count($students) ?></strong>
                        </span>
                        <span class="text-success small">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            Cocok: <strong id="countCocok">0</strong>
                        </span>
                        <span class="text-danger small">
                            <i class="bi bi-x-circle-fill me-1"></i>
                            Belum reset: <strong id="countBelum">0</strong>
                        </span>
                    </div>
                    <!-- Progress bar -->
                    <div class="progress mt-2" style="height:6px">
                        <div id="progressBar" class="progress-bar bg-primary" style="width:0%"></div>
                    </div>
                    <small class="text-muted" id="progressLabel">Klik "Cek Semua" untuk mulai</small>
                </div>
                <div class="col-md-6 d-flex gap-2 flex-wrap justify-content-md-end">
                    <button class="btn btn-primary" id="btnCekSemua" onclick="cekSemua()">
                        <i class="bi bi-search me-1"></i> Cek Semua Password
                    </button>
                    <button class="btn btn-danger" id="btnResetBelum" onclick="resetYangBelum()" disabled>
                        <i class="bi bi-key-fill me-1"></i> Reset Semua yang Belum Cocok
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="filterTabel('semua')">Semua</button>
                    <button class="btn btn-outline-danger btn-sm" onclick="filterTabel('belum')">Belum Saja</button>
                </div>
            </div>
        </div>
    </div>

    <!-- INFO PASSWORD DEFAULT -->
    <div class="alert alert-info py-2 mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Password default: <code class="fw-bold">Siswa123!</code> &nbsp;|&nbsp;
        Username = email siswa
    </div>

    <!-- TABEL SISWA -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="px-3 pt-3 pb-2 d-flex gap-2 align-items-center">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                       style="max-width:280px" placeholder="🔍 Cari nama / email..."
                       oninput="filterSearch()">
                <span class="text-muted small ms-auto" id="labelTampil">Menampilkan <?= count($students) ?> siswa</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabelSiswa">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="checkAll" onchange="toggleCheckAll(this.checked)">
                            </th>
                            <th>#</th>
                            <th>Nama Lengkap</th>
                            <th>Email / Username</th>
                            <th>Kelas</th>
                            <th>Status Password</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $s): ?>
                        <tr id="row-<?= $s['id'] ?>" data-id="<?= $s['id'] ?>"
                            data-nama="<?= strtolower(htmlspecialchars($s['full_name'])) ?>"
                            data-email="<?= strtolower(htmlspecialchars($s['username'])) ?>"
                            data-status="pending">
                            <td>
                                <input type="checkbox" class="row-check" value="<?= $s['id'] ?>">
                            </td>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                            <td><small class="text-muted"><?= htmlspecialchars($s['username']) ?></small></td>
                            <td>
                                <span class="badge bg-primary"><?= htmlspecialchars($s['kelas']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary status-badge" id="status-<?= $s['id'] ?>">
                                    <i class="bi bi-hourglass-split me-1"></i>Belum dicek
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-outline-warning btn-sm reset-btn"
                                        id="resetbtn-<?= $s['id'] ?>"
                                        onclick="resetSatu(<?= $s['id'] ?>, this)"
                                        style="display:none">
                                    <i class="bi bi-key"></i> Reset
                                </button>
                                <span class="text-muted small" id="resetbtn-ok-<?= $s['id'] ?>" style="display:none">
                                    <i class="bi bi-check-circle-fill text-success"></i> OK
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <a href="<?= config('app_url') ?>/CEK_PASSWORD_PPDB.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-search me-1"></i> Cek Per Siswa
        </a>
        <a href="<?= config('app_url') ?>/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-house me-1"></i> Dashboard
        </a>
    </div>
</div>

<!-- TOAST NOTIF -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="toastNotif" class="toast align-items-center text-white border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= config('app_url') ?>';
const DEFAULT_PASS = '<?= $defaultPassword ?>';
let totalSiswa = <?= count($students) ?>;
let countCocok = 0, countBelum = 0, countDone = 0;
let belumIds = []; // IDs yang belum cocok
let isCekRunning = false;

// ── Tampilkan toast ──────────────────────────────────────────
function showToast(msg, type = 'success') {
    const el = document.getElementById('toastNotif');
    el.className = `toast align-items-center text-white border-0 bg-${type}`;
    document.getElementById('toastMsg').innerHTML = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 }).show();
}

// ── Update counter & progress ────────────────────────────────
function updateCounter() {
    document.getElementById('countCocok').textContent = countCocok;
    document.getElementById('countBelum').textContent = countBelum;
    document.getElementById('countPending').textContent = totalSiswa - countDone;
    const pct = totalSiswa > 0 ? Math.round(countDone / totalSiswa * 100) : 0;
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressLabel').textContent =
        countDone < totalSiswa
            ? `Mengecek... ${countDone}/${totalSiswa}`
            : `Selesai dicek. ${countCocok} cocok, ${countBelum} belum direset.`;

    if (countBelum > 0) {
        document.getElementById('btnResetBelum').disabled = false;
        document.getElementById('btnResetBelum').innerHTML =
            `<i class="bi bi-key-fill me-1"></i> Reset ${countBelum} yang Belum Cocok`;
    }
}

// ── Set status baris ─────────────────────────────────────────
function setRowStatus(id, cocok) {
    const row = document.getElementById('row-' + id);
    const badge = document.getElementById('status-' + id);
    const resetBtn = document.getElementById('resetbtn-' + id);
    const resetOk  = document.getElementById('resetbtn-ok-' + id);

    row.dataset.status = cocok ? 'cocok' : 'belum';

    if (cocok) {
        row.classList.remove('row-belum');
        row.classList.add('row-cocok');
        badge.className = 'badge bg-success status-badge';
        badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Cocok';
        resetBtn.style.display = 'none';
        resetOk.style.display  = 'inline';
    } else {
        row.classList.remove('row-cocok');
        row.classList.add('row-belum');
        badge.className = 'badge bg-danger status-badge';
        badge.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Belum reset';
        resetBtn.style.display = 'inline-block';
        resetOk.style.display  = 'none';
        if (!belumIds.includes(id)) belumIds.push(id);
    }
}

// ── Cek semua (sequential, 1 per 1 agar tidak timeout) ───────
async function cekSemua() {
    if (isCekRunning) return;
    isCekRunning = true;
    countCocok = 0; countBelum = 0; countDone = 0;
    belumIds = [];
    document.getElementById('btnCekSemua').disabled = true;
    document.getElementById('btnCekSemua').innerHTML =
        '<span class="spinner-border spinner-border-sm me-1"></span> Mengecek...';

    const rows = document.querySelectorAll('#tabelSiswa tbody tr');
    for (const row of rows) {
        if (row.style.display === 'none') continue; // skip hidden (filter)
        const id = parseInt(row.dataset.id);
        try {
            const res = await fetch(`${BASE_URL}/CEK_PASSWORD_PPDB.php?ajax=cek&id=${id}`);
            const data = await res.json();
            countDone++;
            if (data.cocok) countCocok++; else countBelum++;
            setRowStatus(id, data.cocok);
            updateCounter();
        } catch(e) {
            countDone++;
            updateCounter();
        }
    }

    document.getElementById('btnCekSemua').disabled = false;
    document.getElementById('btnCekSemua').innerHTML =
        '<i class="bi bi-arrow-clockwise me-1"></i> Cek Ulang';
    isCekRunning = false;
    showToast(`Selesai! ${countCocok} cocok, ${countBelum} belum direset.`,
              countBelum > 0 ? 'warning' : 'success');
}

// ── Reset satu siswa ─────────────────────────────────────────
async function resetSatu(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    const fd = new FormData();
    fd.append('ajax', 'reset_one');
    fd.append('id', id);
    const res = await fetch(`${BASE_URL}/CEK_PASSWORD_PPDB.php`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        setRowStatus(id, true);
        countBelum = Math.max(0, countBelum - 1);
        countCocok++;
        belumIds = belumIds.filter(x => x !== id);
        updateCounter();
        showToast('Password berhasil direset ke <strong>' + DEFAULT_PASS + '</strong>', 'success');
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-key"></i> Reset';
        showToast('Gagal reset password.', 'danger');
    }
}

// ── Reset semua yang belum cocok ─────────────────────────────
async function resetYangBelum() {
    // Kumpulkan semua ID yang statusnya 'belum'
    const ids = [];
    document.querySelectorAll('#tabelSiswa tbody tr[data-status="belum"]').forEach(row => {
        ids.push(parseInt(row.dataset.id));
    });

    if (ids.length === 0) {
        showToast('Tidak ada siswa yang perlu direset.', 'info');
        return;
    }

    if (!confirm(`Reset password ${ids.length} siswa yang belum cocok ke "${DEFAULT_PASS}" ?`)) return;

    const btn = document.getElementById('btnResetBelum');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mereset...';

    const fd = new FormData();
    fd.append('ajax', 'reset_batch');
    fd.append('ids', JSON.stringify(ids));
    const res = await fetch(`${BASE_URL}/CEK_PASSWORD_PPDB.php`, { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
        // Update tampilan semua baris yang direset
        ids.forEach(id => setRowStatus(id, true));
        countCocok += data.count;
        countBelum = 0;
        belumIds = [];
        updateCounter();
        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Semua Sudah Direset';
        showToast(`✅ Berhasil reset ${data.count} siswa ke password default.`, 'success');
    } else {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-key-fill me-1"></i> Reset ${ids.length} yang Belum Cocok`;
        showToast('Gagal reset massal.', 'danger');
    }
}

// ── Filter tampilan tabel ────────────────────────────────────
function filterTabel(mode) {
    let tampil = 0;
    document.querySelectorAll('#tabelSiswa tbody tr').forEach(row => {
        const status = row.dataset.status;
        let show = true;
        if (mode === 'belum' && status !== 'belum') show = false;
        row.style.display = show ? '' : 'none';
        if (show) tampil++;
    });
    document.getElementById('labelTampil').textContent = `Menampilkan ${tampil} siswa`;
}

function filterSearch() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    let tampil = 0;
    document.querySelectorAll('#tabelSiswa tbody tr').forEach(row => {
        const match = row.dataset.nama.includes(q) || row.dataset.email.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) tampil++;
    });
    document.getElementById('labelTampil').textContent = `Menampilkan ${tampil} siswa`;
}

function toggleCheckAll(checked) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = checked);
}
</script>
</body>
</html>
