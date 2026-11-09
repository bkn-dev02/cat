<?php
/**
 * EXPORT AKUN SISWA PPDB - FILTER NAMA TERTENTU
 * Tampilkan akun login siswa sesuai daftar nama
 * Siap di-copy untuk grup orang tua
 */

require_once __DIR__ . '/app/helpers.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
    die('<div style="font-family:sans-serif;padding:20px;color:red;">❌ Akses ditolak.</div>');
}

$defaultPassword = 'Siswa123!';
$appUrl = config('app_url');

// ── Daftar nama yang ingin ditampilkan ───────────────────────
$namaFilter = [
    'Abel Josua Leonidas Tambunan',
    'Adhe Aprillia Sitepu',
    'Aldi Nami Hutapea',
    'Alfian Panjaitan',
    'Alfredo Ramos',
    'Alvaro Timotius Sirait',
    'Arie Johnson Everest Silalahi',
    'Artha Uli Sinaga',
    'Asima Rohana Sitompul',
    'Benedictio Kim Sinaga',
    'Benedictus Siseanna Lumban Gaol',
    'Benjamin Polin Mangasi Hutasoit',
    'Bernice Evangeline Siregar',
    'Billie Evan Tampubolon',
    'Bintang Reynold Ginting',
    'Birka Bremana Sembiring',
    'Branch Field Sinaga',
    'Charly Elisabet Lumbantobing',
    'Chintya Mikhalia Manalu',
    'Darren Saverio Mattew Naburju Sagala',
    'Daud Maurice Silaban',
    'David Messi Bonardo Panggabean',
    'David Samuel Wira Ompusunggu',
    'Davine Jheremy Butar Butar',
    'Debora Margaretha Silitonga',
    'Della Octavia Damanik',
    'Desinta Simanjuntak',
    'Diego Arthur Hutabarat',
    'Dion Beanal Sirait',
    'Evo Yehezkiel Mangatur Sinaga',
    'Farel Lumbantobing',
    'Gabriel Jonathan Silitonga',
    'Gilbert Maulana Rajagukguk',
    'Given Lionel Sinaga',
    'Griselda Carisa Saragih',
    'Griselda Uli Sudiro Sirait',
    'Gunawan',
    'Gwen Pregiera Somara Br Saragih',
    'Hans Brad Fitt Lumbantoruan',
    'Heryanto Adriel Davin Butar-Butar',
    'Hiro Bastian Sembiring',
    'Hiro Tiodorman Damanik',
    'Immanuel Farel Simbolon',
    'Jenriko Situngkir',
    'Jeremia Hasiholan Silaen',
    'Jonathan WPS',
    'Jowen Septian Sihaloho',
    'Jubil Akbar Sampetua Simaremare',
    'Julio Timota Ginting',
    'Kathrine Adel Yana Lumbanraja',
    'Kelvin Nauli Sitorus',
    'Keren Hadasa Nababan',
    'Kevin Jastin Jubilium Simanjuntak',
    'Lionel Matthew Sembiring',
    'Louis Trisan Sinaga',
    'Marcelo Oktavianus Gultom',
    'Marshel Saputra Sihombing',
    'Miduk Berkat Situmorang',
    'Miracle Lumbangaol',
    'Mita Abigail Silalahi',
    'Muhammad Ikbal Nasution',
    'Muhammad Ridhonta S.Milala',
    'Naomi Eliza Pardede',
    'Natanael Lamtagon Manalu',
    'Neo Bastian Simamora',
    'Nikita Hutasoit',
    'Novithania Risnauli Pane',
    'Ozzio Benigno Simangunsong',
    'Paul Charismo Habeahan',
    'Prima Baringin Tua Panjaitan',
    'Putri Clarissa Siagian',
    'Putri Tondang',
    'Rachel Grecilia Br Girsang',
    'Rafael Nainggolan',
    'Ragnar Ray Justin Sipayung',
    'Raja Amos Jubilate Pasaribu',
    'Raja Steven Hans Nababan',
    'Razata Pratama',
    'Revi Berliana Bancin',
    'Reyza Hotananta Munthe',
    'Risti Mei Morintha Br Tarigan',
    'Rizky Kobiskum Aditya Debataraja',
    'Ruggrerio Mahatma Simanjuntak',
    'Sabrina Dear Zammora Tambunan',
    'Samuel Tjo Situmeang',
    'Sandro Dos Ginka Ginting',
    'Satrio Ramadan Yunanda',
    'Sebastian Gabriel Simanungkalit',
    'Sesilia Devita Maria Simanjuntak',
    'Setepen Edininta Karo Sekali',
    'Steven Imanuel Sihombing',
    'Tamarischa Nainggolan',
    'Teresia Ade Rapika',
    'Thomas Alfredo Situmorang',
    'Tio Faustin Exaudia Sinambela',
    'Vallery Trecy Anabel Hutapea',
    'Winny Marito Lumban Gaol',
    'Yehezkiel Trinandra Tampubolon',
    'Yoel Gian Manullang',
    'Yohana Putri Elisabeth Nauli Simanjuhntak',
    'Yosua Made Akbar Simbolon',
    'Yulia Citra Ketaren',
    'Zara Levana Abigail Silalahi',
];

// ── Normalisasi nama ─────────────────────────────────────────
function normName($n) {
    $n = strtolower(trim($n));
    $n = str_replace(['-', '.', ',', "'", '"'], ' ', $n);
    return preg_replace('/\s+/', ' ', $n);
}

// Pecah menjadi token kata (panjang > 2)
function nameTokens($n) {
    return array_filter(explode(' ', normName($n)), fn($w) => strlen($w) > 2);
}

// Skor kecocokan: berapa token dari filter yang ada di nama DB
function matchScore($dbName, $filterName) {
    $dbTokens  = nameTokens($dbName);
    $fTokens   = nameTokens($filterName);
    if (empty($fTokens)) return 0;
    $hits = 0;
    foreach ($fTokens as $ft) {
        if (in_array($ft, $dbTokens)) $hits++;
    }
    return $hits / count($fTokens); // 0.0 – 1.0
}

// ── Ambil semua siswa PPDB ───────────────────────────────────
$stmt = db()->query("
    SELECT full_name, username
    FROM users
    WHERE role = 'siswa'
      AND (kelas = 'PPDB-REGULER' OR kelas LIKE '%PPDB%')
    ORDER BY full_name ASC
");
$allPpdb = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Cocokkan setiap nama filter ke DB ────────────────────────
// Untuk setiap nama di filter, cari siswa DB dengan skor tertinggi
$matched   = []; // [username => row]
$notFound  = []; // nama filter yang tidak ketemu

foreach ($namaFilter as $nf) {
    $bestScore = 0;
    $bestRow   = null;
    foreach ($allPpdb as $row) {
        $score = matchScore($row['full_name'], $nf);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestRow   = $row;
        }
    }
    // Threshold: minimal 60% token cocok
    if ($bestScore >= 0.6 && $bestRow) {
        $matched[$bestRow['username']] = $bestRow; // key by username agar tidak duplikat
    } else {
        $notFound[] = $nf;
    }
}

$students = array_values($matched);
// Urutkan alfabetis
usort($students, fn($a, $b) => strcmp($a['full_name'], $b['full_name']));

// ── Format teks ──────────────────────────────────────────────
$lines = [];
foreach ($students as $s) {
    $lines[] = "👤 " . $s['full_name'] . "\n   Username : " . $s['username'] . "\n   Password : " . $defaultPassword;
}
$textAll = "=== AKUN LOGIN CAT PPDB ===\nURL: {$appUrl}\nPassword semua siswa: {$defaultPassword}\n(Login via aplikasi BKN CAT Browser)\n\n" . implode("\n\n", $lines);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Akun Siswa PPDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .copy-area { font-family: monospace; font-size: 0.82rem; background: #1e293b; color: #f1f5f9;
                     border-radius: 10px; padding: 16px; white-space: pre-wrap; max-height: 420px;
                     overflow-y: auto; border: none; width: 100%; resize: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary px-3">
    <a class="navbar-brand fw-bold" href="<?= $appUrl ?>/dashboard">
        <i class="bi bi-arrow-left me-2"></i>CAT Enterprise
    </a>
    <span class="text-white"><i class="bi bi-person-lines-fill me-1"></i> Export Akun Siswa PPDB</span>
</nav>

<div class="container py-4" style="max-width: 900px">

    <!-- RINGKASAN -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-primary text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= count($namaFilter) ?></div>
                <div class="text-muted small">Nama di daftar Anda</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success text-center py-3">
                <div class="fs-2 fw-bold text-success"><?= count($students) ?></div>
                <div class="text-muted small">Ditemukan di database</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-<?= count($notFound) > 0 ? 'danger' : 'secondary' ?> text-center py-3">
                <div class="fs-2 fw-bold text-<?= count($notFound) > 0 ? 'danger' : 'secondary' ?>"><?= count($notFound) ?></div>
                <div class="text-muted small">Tidak ditemukan</div>
            </div>
        </div>
    </div>

    <!-- NAMA TIDAK DITEMUKAN -->
    <?php if (!empty($notFound)): ?>
    <div class="alert alert-warning mb-4">
        <strong><i class="bi bi-exclamation-triangle-fill me-1"></i><?= count($notFound) ?> nama tidak ditemukan di database:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($notFound as $nf): ?>
                <li><?= htmlspecialchars($nf) ?></li>
            <?php endforeach; ?>
        </ul>
        <small class="text-muted mt-1 d-block">Kemungkinan ejaan nama di database berbeda. Cari manual di tabel bawah.</small>
    </div>
    <?php endif; ?>

    <!-- COPY FORMAT WA -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-whatsapp text-success me-1"></i>Format WA — Siap Kirim ke Grup Orang Tua</span>
            <button class="btn btn-success btn-sm" onclick="copyText('areaWA', this)">
                <i class="bi bi-clipboard me-1"></i> Copy Semua (<?= count($students) ?> siswa)
            </button>
        </div>
        <div class="card-body p-2">
            <textarea class="copy-area" id="areaWA" readonly><?= htmlspecialchars($textAll) ?></textarea>
        </div>
    </div>

    <!-- TABEL -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-table text-primary me-2"></i>Daftar (<?= count($students) ?> siswa ditemukan)</h6>
            <input type="text" id="searchInput" class="form-control form-control-sm"
                   style="max-width:220px" placeholder="🔍 Cari nama..."
                   oninput="filterTable()">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:500px; overflow-y:auto">
                <table class="table table-hover align-middle mb-0 table-sm" id="tabelSiswa">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="40">#</th>
                            <th>Nama di Database</th>
                            <th>Username / Email</th>
                            <th>Password</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $s): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                            <td><small class="text-muted"><?= htmlspecialchars($s['username']) ?></small></td>
                            <td><code><?= $defaultPassword ?></code></td>
                            <td>
                                <button class="btn btn-outline-success btn-sm"
                                        onclick="copySatu('<?= htmlspecialchars(addslashes($s['full_name'])) ?>', '<?= htmlspecialchars(addslashes($s['username'])) ?>', this)"
                                        title="Copy akun ini">
                                    <i class="bi bi-whatsapp"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- NAMA TIDAK DITEMUKAN - CARI MANUAL -->
    <?php if (!empty($notFound)): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-warning bg-opacity-25">
            <h6 class="mb-0"><i class="bi bi-search me-2"></i>Cari Manual — Nama Tidak Ditemukan</h6>
            <small class="text-muted">Cari di semua siswa PPDB untuk temukan ejaan yang berbeda</small>
        </div>
        <div class="card-body p-0">
            <div class="px-3 pt-3 pb-2">
                <input type="text" id="searchManual" class="form-control form-control-sm"
                       style="max-width:300px" placeholder="🔍 Ketik nama untuk cari..."
                       oninput="filterManual()">
            </div>
            <div class="table-responsive" style="max-height:350px; overflow-y:auto">
                <table class="table table-sm align-middle mb-0" id="tabelManual">
                    <thead class="table-light sticky-top">
                        <tr><th>#</th><th>Nama di Database</th><th>Email</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allPpdb as $j => $row): ?>
                        <tr>
                            <td class="text-muted small"><?= $j+1 ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($row['username']) ?></small></td>
                            <td>
                                <button class="btn btn-outline-success btn-sm"
                                        onclick="copySatu('<?= htmlspecialchars(addslashes($row['full_name'])) ?>', '<?= htmlspecialchars(addslashes($row['username'])) ?>', this)">
                                    <i class="bi bi-whatsapp"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-3 d-flex gap-2">
        <a href="<?= $appUrl ?>/CEK_PASSWORD_PPDB.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-key me-1"></i> Cek Password
        </a>
        <a href="<?= $appUrl ?>/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-house me-1"></i> Dashboard
        </a>
    </div>
</div>

<!-- TOAST -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="toast" class="toast align-items-center text-white bg-success border-0">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg">Tersalin!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const DEFAULT_PASS = '<?= $defaultPassword ?>';
const APP_URL = '<?= $appUrl ?>';

function copyText(areaId, btn) {
    navigator.clipboard.writeText(document.getElementById(areaId).value).then(() => {
        showToast('Tersalin ke clipboard!');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Tersalin!';
        setTimeout(() => btn.innerHTML = orig, 2000);
    });
}

function copySatu(nama, email, btn) {
    const text = `👤 ${nama}\n   Username : ${email}\n   Password : ${DEFAULT_PASS}\n   URL      : ${APP_URL}`;
    navigator.clipboard.writeText(text).then(() => {
        showToast('Akun ' + nama + ' tersalin!');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        btn.classList.replace('btn-outline-success', 'btn-success');
        setTimeout(() => { btn.innerHTML = orig; btn.classList.replace('btn-success', 'btn-outline-success'); }, 2000);
    });
}

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#tabelSiswa tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
}

function filterManual() {
    const q = document.getElementById('searchManual').value.toLowerCase();
    document.querySelectorAll('#tabelManual tbody tr').forEach(row => {
        row.style.display = q.length < 2 ? 'none' : (row.innerText.toLowerCase().includes(q) ? '' : 'none');
    });
}

// Sembunyikan tabel manual sampai ada input
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#tabelManual tbody tr').forEach(r => r.style.display = 'none');
});

function showToast(msg) {
    document.getElementById('toastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(document.getElementById('toast'), { delay: 2500 }).show();
}
</script>
</body>
</html>
