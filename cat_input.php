<?php
declare(strict_types=1);
session_start();

/* =====================================================================
   CAT Input Console — satu file, taruh di root web (mis. public_html/)
   Konfigurasi lewat environment variable ATAU ubah nilai default di bawah.
   Buat hash password:  php -r "echo password_hash('PASSWORD_ANDA', PASSWORD_DEFAULT);"
   ===================================================================== */
$CFG = [
  'host' => getenv('CAT_DB_HOST') ?: 'localhost',
  'port' => getenv('CAT_DB_PORT') ?: '3306',
  'name' => getenv('CAT_DB_NAME') ?: 'cat_enterprise',
  'user' => getenv('CAT_DB_USER') ?: 'root',
  'pass' => getenv('CAT_DB_PASS') ?: '',
  'pass_hash' => getenv('CAT_INPUT_PASS_HASH') ?: '',   // wajib diisi, kalau kosong halaman terkunci
  'default_created_by' => 1,                            // users.id pembuat data
  'audit_log' => __DIR__ . '/cat_input_audit.log',
  'pdf_dir' => __DIR__ . '/public/uploads/pdfs',        // folder tujuan upload PDF
  'pdf_prefix' => '',                                   // isi 'uploads/pdfs/' jika kolom DB menyimpan path, bukan nama file saja
  'pdf_max_mb' => 20,
  // Database SUMBER (offline/lokal) untuk import langsung. Kosongkan src_name jika tidak ada; pakai Paket ekspor sebagai gantinya.
  'src_host' => getenv('CAT_SRC_HOST') ?: 'localhost', 'src_port' => getenv('CAT_SRC_PORT') ?: '3306',
  // Folder file di server INI (tujuan). Sesuaikan dengan struktur aplikasi CAT; cek statusnya di tab Import ujian.
  'dirs' => ['gambar' => getenv('CAT_DIR_IMG') ?: __DIR__ . '/public/uploads/questions', 'pdf' => '', 'audio' => getenv('CAT_DIR_AUDIO') ?: __DIR__ . '/public/uploads/audio'],
  // Folder file di SUMBER, hanya untuk import langsung (harus bisa dibaca dari server ini: mount/NFS/server sama). Kosong = pakai Paket ZIP.
  'src_dirs' => ['gambar' => getenv('CAT_SRC_DIR_IMG') ?: '', 'pdf' => getenv('CAT_SRC_DIR_PDF') ?: '', 'audio' => getenv('CAT_SRC_DIR_AUDIO') ?: ''],
  // Fitur KIRIM: dipakai di mesin lokal. push_url = alamat cat_input.php di production, push_token = token yang dibuat di tab Pengaturan production.
  'push_url' => getenv('CAT_PUSH_URL') ?: '', 'push_token' => getenv('CAT_PUSH_TOKEN') ?: '',
  'push_token_hash' => '',   // dipakai di production untuk menerima kiriman (diisi dari tab Pengaturan)
  'src_name' => getenv('CAT_SRC_NAME') ?: '', 'src_user' => getenv('CAT_SRC_USER') ?: '', 'src_pass' => getenv('CAT_SRC_PASS') ?: '',
];

$CFG_FILE = __DIR__ . '/cat_input.config.php';   // dibuat otomatis oleh halaman setup, mengalahkan nilai di atas
if (is_file($CFG_FILE)) { $saved = include $CFG_FILE; if (is_array($saved)) $CFG = array_replace_recursive($CFG, $saved); }
$CFG['dirs']['pdf'] = $CFG['pdf_dir'];

function writeConfig(array $patch): void {
  global $CFG_FILE; $cur = is_file($CFG_FILE) ? include $CFG_FILE : [];
  $cur = array_replace_recursive(is_array($cur) ? $cur : [], $patch);
  $code = "<?php\n// Dibuat otomatis oleh cat_input.php. Berisi kredensial: jangan dibagikan atau di-commit.\nreturn " . var_export($cur, true) . ";\n";
  if (file_put_contents($CFG_FILE, $code, LOCK_EX) === false) throw new Exception('Tidak bisa menulis ' . basename($CFG_FILE) . '. Beri izin tulis pada folder tempat file ini berada.');
  @chmod($CFG_FILE, 0600); if (function_exists('opcache_invalidate')) @opcache_invalidate($CFG_FILE, true);
}

function db(): PDO {
  global $CFG; static $p;
  return $p ??= new PDO("mysql:host={$CFG['host']};port={$CFG['port']};dbname={$CFG['name']};charset=utf8mb4",
    $CFG['user'], $CFG['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
}

/* ---------- auth ---------- */
$err = '';
if (isset($_GET['logout'])) { session_destroy(); header('Location: ?'); exit; }
if (isset($_POST['login'])) {
  if ($CFG['pass_hash'] !== '' && password_verify($_POST['password'] ?? '', $CFG['pass_hash'])) {
    session_regenerate_id(true); $_SESSION['ok'] = 1;
  } else { sleep(1); $err = 'Password salah.'; }
}
$auth = !empty($_SESSION['ok']);
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));

/* ---------- setup pertama kali ---------- */
if ($CFG['pass_hash'] === '') {
  $flag = __DIR__ . '/cat_input.setup'; $msg = '';
  if (is_file($flag) && isset($_POST['setup'])) {
    try {
      if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) throw new Exception('Sesi tidak valid, muat ulang halaman.');
      $pw = (string)($_POST['pw'] ?? '');
      if (strlen($pw) < 10) throw new Exception('Password minimal 10 karakter.');
      if ($pw !== ($_POST['pw2'] ?? '')) throw new Exception('Konfirmasi password tidak sama.');
      $g = fn($k, $d = '') => trim((string)($_POST[$k] ?? '')) ?: $d;
      $t = new PDO("mysql:host={$g('host','localhost')};port={$g('port','3306')};dbname={$g('name')};charset=utf8mb4", $g('user'), (string)($_POST['dbpass'] ?? ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
      foreach (['exams', 'questions', 'subjects', 'exam_questions'] as $tb) { try { $t->query("SELECT 1 FROM `$tb` LIMIT 1"); } catch (Throwable $e) { throw new Exception("Koneksi berhasil, tetapi tabel `$tb` tidak ada di database {$g('name')}."); } }
      writeConfig(['host' => $g('host', 'localhost'), 'port' => $g('port', '3306'), 'name' => $g('name'), 'user' => $g('user'), 'pass' => (string)($_POST['dbpass'] ?? ''),
        'pass_hash' => password_hash($pw, PASSWORD_DEFAULT), 'default_created_by' => max(1, (int)$g('by', '1')),
        'src_host' => $g('shost', 'localhost'), 'src_port' => $g('sport', '3306'), 'src_name' => $g('sname'), 'src_user' => $g('suser'), 'src_pass' => (string)($_POST['spass'] ?? ''),
        'pdf_dir' => $g('d_pdf', $CFG['pdf_dir']), 'dirs' => ['gambar' => $g('d_img', $CFG['dirs']['gambar']), 'audio' => $g('d_aud', $CFG['dirs']['audio'])]]);
      @unlink($flag); header('Location: ?'); exit;
    } catch (Throwable $e) { $msg = $e->getMessage(); }
  }
  $v = fn($k, $d = '') => htmlspecialchars((string)($_POST[$k] ?? $d), ENT_QUOTES);
  ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup CAT Input Console</title>
<style>:root{--bg:#eef2f5;--fg:#14212b;--card:#fff;--line:#cfd8df;--ac:#0b6e6e;--mut:#5d6f7b}@media(prefers-color-scheme:dark){:root{--bg:#10181e;--fg:#e4ecf1;--card:#18232b;--line:#2c3a45;--ac:#4fc3c3;--mut:#93a5b1}}
body{margin:0;background:var(--bg);color:var(--fg);font:14px/1.5 system-ui,sans-serif;padding:24px 12px}main{max-width:640px;margin:0 auto}
.p{background:var(--card);border:1px solid var(--line);border-radius:8px;padding:18px;margin-bottom:14px}h1{font-size:18px;margin:0 0 6px}h2{font-size:14px;margin:0 0 10px}p{color:var(--mut);margin:0 0 10px}
.g{display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}label{display:block;font-size:12px;color:var(--mut);margin-bottom:3px}
input,button{width:100%;box-sizing:border-box;padding:9px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--fg);font:inherit}
button{background:var(--ac);color:#fff;border:0;font-weight:600;cursor:pointer;padding:11px}.e{background:#fdecea;color:#b42318;border:1px solid #b42318;padding:9px 12px;border-radius:6px;margin-bottom:12px}code{background:var(--bg);padding:1px 5px;border-radius:4px}</style></head><body><main>
<div class="p"><h1>Setup CAT Input Console</h1>
<?php if (!is_file($flag)): ?>
<p>Demi keamanan, setup hanya bisa dibuka kalau Anda punya akses ke server ini.</p>
<p>Buat file kosong bernama <code>cat_input.setup</code> di folder yang sama dengan <code><?= htmlspecialchars(basename(__FILE__)) ?></code> (lewat File Manager atau FTP), lalu muat ulang halaman ini. File itu terhapus otomatis setelah setup selesai.</p></div>
<?php else: ?>
<p>Isi sekali saja. Pengaturan disimpan di <code>cat_input.config.php</code> dan password disimpan dalam bentuk hash.</p></div>
<?php if ($msg): ?><div class="e"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
<div class="p"><h2>1. Password untuk masuk ke halaman ini</h2><p>Karang sendiri. Ini bukan password database.</p><div class="g"><div><label>Password (min. 10 karakter)</label><input type="password" name="pw" required></div><div><label>Ulangi password</label><input type="password" name="pw2" required></div></div></div>
<div class="p"><h2>2. Database di server ini (target)</h2><div class="g">
<div><label>Host</label><input name="host" value="<?= $v('host', 'localhost') ?>"></div><div><label>Port</label><input name="port" value="<?= $v('port', '3306') ?>"></div>
<div><label>Nama database</label><input name="name" value="<?= $v('name', 'cat_enterprise') ?>" required></div><div><label>User MySQL</label><input name="user" value="<?= $v('user') ?>" required></div>
<div><label>Password MySQL</label><input type="password" name="dbpass"></div><div><label>ID user pembuat data (users.id)</label><input type="number" min="1" name="by" value="<?= $v('by', '1') ?>"></div></div></div>
<div class="p"><h2>3. Database sumber (opsional)</h2><p>Untuk import langsung. Kosongkan nama database jika memakai Paket ZIP.</p><div class="g">
<div><label>Host</label><input name="shost" value="<?= $v('shost', 'localhost') ?>"></div><div><label>Port</label><input name="sport" value="<?= $v('sport', '3306') ?>"></div>
<div><label>Nama database</label><input name="sname" value="<?= $v('sname') ?>"></div><div><label>User</label><input name="suser" value="<?= $v('suser') ?>"></div><div><label>Password</label><input type="password" name="spass"></div></div></div>
<div class="p"><h2>4. Folder file di server ini</h2><div class="g">
<div><label>Gambar soal</label><input name="d_img" value="<?= $v('d_img', $CFG['dirs']['gambar']) ?>"></div><div><label>Audio</label><input name="d_aud" value="<?= $v('d_aud', $CFG['dirs']['audio']) ?>"></div><div><label>PDF</label><input name="d_pdf" value="<?= $v('d_pdf', $CFG['pdf_dir']) ?>"></div></div></div>
<button name="setup" value="1">Uji koneksi dan simpan</button></form>
<?php endif; ?></main></body></html>
<?php exit; }

/* ---------- helpers tulis (satu pintu, nanti mudah diganti ke antrian sinkronisasi) ---------- */
function audit(string $action, array $info): void {
  global $CFG;
  @file_put_contents($CFG['audit_log'], json_encode(['t' => date('c'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'action' => $action] + $info, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}
function audit_if(bool $on, string $a, array $i): void { if ($on) audit($a, ['result' => array_diff_key($i, ['files' => 1])]); }
function nn($v) { $v = is_string($v) ? trim($v) : $v; return ($v === '' || $v === null) ? null : $v; }
function dtv(string $s): string { $t = strtotime($s); if (!$t) throw new Exception("Format waktu tidak valid: $s"); return date('Y-m-d H:i:s', $t); }

const QTYPES = ['multiple_choice', 'essay', 'short_answer', 'short_answer_text', 'listening', 'tkp'];

function insertQuestion(PDO $db, array $q, int $subject, ?int $group, int $by): int {
  $type = $q['type'] ?? 'multiple_choice';
  if (!in_array($type, QTYPES, true)) throw new Exception("Tipe soal tidak dikenal: $type");
  $text = trim((string)($q['question_text'] ?? '')); $img = nn($q['question_image'] ?? null);
  if ($text === '' && !$img) throw new Exception('Teks soal atau gambar wajib diisi.');
  $o = []; foreach (['a', 'b', 'c', 'd', 'e'] as $k) $o[$k] = nn($q["option_$k"] ?? null);
  $ans = nn($q['correct_answer'] ?? null); $scores = null;
  if (in_array($type, ['multiple_choice', 'listening'], true)) {
    $ans = strtoupper((string)$ans);
    if (!in_array($ans, ['A', 'B', 'C', 'D', 'E'], true)) throw new Exception("Kunci jawaban harus A–E (soal: " . mb_substr($text, 0, 40) . ")");
    if ($o[strtolower($ans)] === null) throw new Exception("Opsi $ans kosong (soal: " . mb_substr($text, 0, 40) . ")");
  } elseif ($type === 'tkp') {
    $s = is_array($q['option_scores'] ?? null) ? $q['option_scores'] : json_decode((string)($q['option_scores'] ?? ''), true);
    if (!is_array($s) || !$s) throw new Exception('Soal TKP butuh skor per opsi, mis. A=5,B=4,C=3.');
    $scores = json_encode($s, JSON_UNESCAPED_UNICODE); $ans = null;
  } elseif ($type === 'essay') { $ans = null; }
  $diff = nn($q['difficulty_level'] ?? null);
  if ($diff !== null && !in_array($diff, ['mudah', 'sedang', 'sulit', 'hots'], true)) throw new Exception('Level kesulitan tidak valid.');
  $st = $db->prepare('INSERT INTO questions (group_id,subject_id,type,sub_topic,difficulty_level,question_text,question_image,audio_file,max_plays,option_a,option_b,option_c,option_d,option_e,correct_answer,option_scores,hide_correct_answer,points,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $st->execute([$group, $subject, $type, nn($q['sub_topic'] ?? null), $diff, $text, $img, nn($q['audio_file'] ?? null),
    nn($q['max_plays'] ?? null), $o['a'], $o['b'], $o['c'], $o['d'], $o['e'], $ans, $scores, 0, max(0, (int)($q['points'] ?? 1)), $by]);
  return (int)$db->lastInsertId();
}

function users(string $q): array {
  try {
    $cols = db()->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users'")->fetchAll(PDO::FETCH_COLUMN);
  } catch (Throwable $e) { return []; }
  $label = null; foreach (['name', 'full_name', 'nama', 'username', 'email'] as $c) if (in_array($c, $cols, true)) { $label = $c; break; }
  if (!$label) return [];
  $st = db()->prepare("SELECT id, `$label` AS label FROM users WHERE `$label` LIKE ? OR id = ? ORDER BY id DESC LIMIT 30");
  $st->execute(["%$q%", (int)$q]); return $st->fetchAll();
}

/* ---------- import ujian (sumber -> database ini) ---------- */
function srcdb(): ?PDO {
  global $CFG; static $p;
  if ($CFG['src_name'] === '') return null;
  return $p ??= new PDO("mysql:host={$CFG['src_host']};port={$CFG['src_port']};dbname={$CFG['src_name']};charset=utf8mb4", $CFG['src_user'], $CFG['src_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
}
function findExams(PDO $c, string $q): array {
  $st = $c->prepare('SELECT e.id,e.title,e.category,e.status,e.start_time,(SELECT COUNT(*) FROM exam_questions x WHERE x.exam_id=e.id) AS n FROM exams e WHERE e.title LIKE ? OR e.id=? ORDER BY e.id DESC LIMIT 30');
  $st->execute(["%$q%", (int)$q]); return $st->fetchAll();
}
function readBundle(PDO $s, int $id): array {
  $st = $s->prepare('SELECT * FROM exams WHERE id=?'); $st->execute([$id]); $e = $st->fetch();
  if (!$e) throw new Exception('Ujian tidak ditemukan di database sumber.');
  $st = $s->prepare('SELECT q.*, eq.order_num AS _ord FROM exam_questions eq JOIN questions q ON q.id=eq.question_id WHERE eq.exam_id=? ORDER BY eq.order_num, eq.id'); $st->execute([$id]); $qs = $st->fetchAll();
  $st = $s->prepare('SELECT subject_id,question_count,order_num FROM exam_subjects WHERE exam_id=? ORDER BY order_num'); $st->execute([$id]); $es = $st->fetchAll();
  $sid = array_unique(array_map('intval', array_merge([$e['subject_id']], array_column($qs, 'subject_id'), array_column($es, 'subject_id'))));
  $gid = array_unique(array_map('intval', array_filter(array_column($qs, 'group_id'))));
  $grps = []; foreach ($s->query('SELECT * FROM question_groups WHERE id IN (' . implode(',', $gid ?: [0]) . ')') as $r) { $grps[$r['id']] = $r; $sid[] = (int)$r['subject_id']; }
  $subs = []; foreach ($s->query('SELECT * FROM subjects WHERE id IN (' . implode(',', array_unique($sid) ?: [0]) . ')') as $r) $subs[$r['id']] = $r;
  return ['v' => 1, 'exam' => $e, 'subjects' => $subs, 'groups' => $grps, 'questions' => $qs, 'exam_subjects' => $es];
}
function bundleFiles(array $b): array {
  $f = [];
  foreach ($b['questions'] as $q) foreach (['question_image' => 'gambar', 'pdf_file' => 'pdf', 'audio_file' => 'audio'] as $c => $k)
    if (!empty($q[$c])) $f[$k . '|' . basename($q[$c])] = [$k, basename($q[$c])];
  if (!empty($b['exam']['exam_pdf'])) $f['pdf|' . basename($b['exam']['exam_pdf'])] = ['pdf', basename($b['exam']['exam_pdf'])];
  return array_values($f);
}
function syncFiles(array $files, array $srcDirs, bool $dry): array {
  global $CFG; $rep = [];
  foreach ($files as [$k, $n]) {
    $dst = rtrim($CFG['dirs'][$k], '/'); $to = "$dst/$n"; $from = !empty($srcDirs[$k]) ? rtrim($srcDirs[$k], '/') . "/$n" : '';
    if (is_file($to)) $st = 'sudah ada';
    elseif ($from === '' || !is_file($from)) $st = 'sumber tidak ditemukan';
    elseif ($dry) $st = 'akan disalin';
    else {
      if (!is_dir($dst) && !mkdir($dst, 0755, true)) throw new Exception("Folder tujuan tidak bisa dibuat: $dst");
      if (!copy($from, $to)) throw new Exception("Gagal menyalin $n ke $dst (cek izin tulis)");
      $st = 'disalin';
    }
    $rep[] = ['kind' => $k, 'name' => $n, 'status' => $st];
  }
  return $rep;
}
function rmtree(string $d): void {
  if (!is_dir($d)) return;
  foreach (scandir($d) as $x) { if ($x === '.' || $x === '..') continue; is_dir("$d/$x") ? rmtree("$d/$x") : unlink("$d/$x"); }
  rmdir($d);
}
function buildZip(array $b): array {
  global $CFG; if (!class_exists('ZipArchive')) throw new Exception('Ekstensi PHP zip belum aktif.');
  $tmp = tempnam(sys_get_temp_dir(), 'catz'); $z = new ZipArchive; $z->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
  $z->addFromString('bundle.json', json_encode($b, JSON_UNESCAPED_UNICODE)); $miss = [];
  foreach (bundleFiles($b) as [$k, $n]) { $p = rtrim($CFG['dirs'][$k], '/') . "/$n"; is_file($p) ? $z->addFile($p, "files/$k/$n") : $miss[] = "$k/$n"; }
  $z->addFromString('missing.txt', implode("\n", $miss)); $z->close(); return [$tmp, $miss];
}
function remote(string $api, array $post = [], int $timeout = 30): array {
  global $CFG;
  if ($CFG['push_url'] === '' || $CFG['push_token'] === '') throw new Exception('Alamat production dan token belum diatur di tab Pengaturan.');
  if (!function_exists('curl_init')) throw new Exception('Ekstensi PHP curl belum aktif.');
  $ch = curl_init(strtok($CFG['push_url'], '?') . '?api=' . $api);
  curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post ?: ['x' => '1'], CURLOPT_HTTPHEADER => ['X-Push-Token: ' . $CFG['push_token']],
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_FOLLOWLOCATION => false]);
  $res = curl_exec($ch); $err = curl_error($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($res === false) throw new Exception('Tidak bisa terhubung ke production: ' . $err);
  $j = json_decode((string)$res, true);
  if (!is_array($j)) throw new Exception("Respons production tidak valid (HTTP $code). Cek alamat dan batas upload PHP di production.");
  if (empty($j['ok'])) throw new Exception('Production: ' . ($j['error'] ?? "gagal (HTTP $code)"));
  return $j;
}
function importBundle(PDO $db, array $b, bool $dry, int $by, string $status, array $srcDirs = [], bool $filesOnly = false): array {
  global $CFG;
  if (($b['v'] ?? 0) !== 1 || empty($b['exam']) || empty($b['questions'])) throw new Exception('Paket tidak valid atau tidak berisi soal.');
  $e = $b['exam']; $title = trim((string)$e['title']);
  $files = bundleFiles($b);
  if ($filesOnly) return ['title' => $title, 'files_only' => true, 'files' => syncFiles($files, $srcDirs, $dry)];
  $c = $db->prepare('SELECT id FROM exams WHERE title=?'); $c->execute([$title]);
  if ($c->fetch()) throw new Exception("Ujian \"$title\" sudah ada di database ini. Ganti judul di sumber atau hapus yang lama dulu agar tidak dobel.");
  $db->beginTransaction();
  try {
    $sm = []; $gm = []; $nS = $nG = $nQ = $rQ = 0;
    foreach ($b['subjects'] as $id => $s) {
      $f = $db->prepare('SELECT id FROM subjects WHERE name=?'); $f->execute([$s['name']]); $r = $f->fetch();
      if (!$r) { $db->prepare('INSERT INTO subjects (name,description) VALUES (?,?)')->execute([$s['name'], $s['description']]); $r = ['id' => $db->lastInsertId()]; $nS++; }
      $sm[(int)$id] = (int)$r['id'];
    }
    foreach ($b['groups'] as $id => $g) {
      $f = $db->prepare('SELECT id FROM question_groups WHERE name=? AND subject_id=? AND type=?'); $f->execute([$g['name'], $sm[(int)$g['subject_id']], $g['type']]); $r = $f->fetch();
      if (!$r) { $db->prepare('INSERT INTO question_groups (name,description,subject_id,type,sub_topic,default_points,created_by) VALUES (?,?,?,?,?,?,?)')
        ->execute([$g['name'], $g['description'], $sm[(int)$g['subject_id']], $g['type'], $g['sub_topic'], $g['default_points'], $by]); $r = ['id' => $db->lastInsertId()]; $nG++; }
      $gm[(int)$id] = (int)$r['id'];
    }
    $find = $db->prepare('SELECT id FROM questions WHERE subject_id=? AND type=? AND question_text=? AND option_a <=> ? AND correct_answer <=> ? AND question_image <=> ? LIMIT 1');
    $ins = $db->prepare('INSERT INTO questions (group_id,subject_id,type,sub_topic,difficulty_level,question_text,question_image,pdf_file,audio_file,max_plays,option_a,option_b,option_c,option_d,option_e,correct_answer,option_scores,hide_correct_answer,points,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $qm = [];
    foreach ($b['questions'] as $q) {
      $sid = $sm[(int)$q['subject_id']]; $gid = $q['group_id'] ? ($gm[(int)$q['group_id']] ?? null) : null;
      $find->execute([$sid, $q['type'], $q['question_text'], $q['option_a'], $q['correct_answer'], $q['question_image']]); $r = $find->fetch();
      if ($r) { $rQ++; $nid = (int)$r['id']; }
      else { $ins->execute([$gid, $sid, $q['type'], $q['sub_topic'], $q['difficulty_level'], $q['question_text'], $q['question_image'], $q['pdf_file'], $q['audio_file'], $q['max_plays'],
        $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['option_e'], $q['correct_answer'], $q['option_scores'], $q['hide_correct_answer'], $q['points'], $by]); $nQ++; $nid = (int)$db->lastInsertId(); }
      $qm[] = [$nid, $sid, (int)$q['_ord']];
    }
    $token = strtoupper((string)$e['token']); $t = $db->prepare('SELECT 1 FROM exams WHERE token=?'); $t->execute([$token]);
    if ($token === '' || $t->fetch()) $token = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $st = $status !== '' ? $status : $e['status'];
    $db->prepare('INSERT INTO exams (title,category,subject_id,exam_type,duration,start_time,end_time,token,use_token,exam_pdf,randomize_questions,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
      ->execute([$title, $e['category'], $e['subject_id'] ? ($sm[(int)$e['subject_id']] ?? null) : null, $e['exam_type'], $e['duration'], $e['start_time'], $e['end_time'], $token, $e['use_token'], $e['exam_pdf'], $e['randomize_questions'], $st, $by]);
    $eid = (int)$db->lastInsertId();
    $iq = $db->prepare('INSERT INTO exam_questions (exam_id,question_id,order_num,subject_id) VALUES (?,?,?,?)');
    foreach ($qm as $n => $m) $iq->execute([$eid, $m[0], $m[2] ?: $n + 1, $m[1]]);
    $is = $db->prepare('INSERT INTO exam_subjects (exam_id,subject_id,question_count,order_num) VALUES (?,?,?,?)');
    foreach ($b['exam_subjects'] as $x) $is->execute([$eid, $sm[(int)$x['subject_id']], $x['question_count'], $x['order_num']]);
    $fl = syncFiles($files, $srcDirs, $dry);   // gagal tulis file = batal total (rollback)
    $dry ? $db->rollBack() : $db->commit();
  } catch (Throwable $ex) { if ($db->inTransaction()) $db->rollBack(); throw $ex; }
  return ['title' => $title, 'exam_id' => $dry ? null : $eid, 'token' => $token, 'status' => $st, 'subjects_new' => $nS, 'groups_new' => $nG, 'questions_new' => $nQ, 'questions_reused' => $rQ, 'files' => $fl];
}

/* ---------- API ---------- */
if (isset($_GET['api'])) {
  header('Content-Type: application/json; charset=utf-8');
  try {
    $tok = false; $ph = (string)($CFG['push_token_hash'] ?? '');
    if (!$auth && $ph !== '' && in_array($_GET['api'], ['exam_import_zip', 'push_ping'], true)) {
      $t = (string)($_SERVER['HTTP_X_PUSH_TOKEN'] ?? ''); if ($t !== '' && password_verify($t, $ph)) $tok = true; else sleep(1);
    }
    if (!$auth && !$tok) { http_response_code(401); throw new Exception('Sesi berakhir atau token tidak valid.'); }
    if (!$tok && !hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF'] ?? '')) { http_response_code(403); throw new Exception('Token CSRF tidak valid.'); }
    $d = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
    $dry = !empty($d['dry_run']); $db = db(); $by = (int)($d['created_by'] ?? $CFG['default_created_by']);
    $out = [];
    switch ($_GET['api']) {
      case 'meta':
        $out = ['db' => $CFG['name'], 'src' => $CFG['src_name'] !== '', 'zip' => class_exists('ZipArchive'), 'push' => $CFG['push_url'] !== '' && $CFG['push_token'] !== '', 'dirs' => array_map(fn($p) => ['path' => $p, 'ok' => is_dir($p) && is_writable($p)], $CFG['dirs']), 'default_by' => $CFG['default_created_by'],
          'subjects' => $db->query('SELECT id,name FROM subjects ORDER BY name')->fetchAll(),
          'groups' => $db->query('SELECT id,name,subject_id,type FROM question_groups ORDER BY id DESC LIMIT 400')->fetchAll(),
          'counts' => ['questions' => (int)$db->query('SELECT COUNT(*) FROM questions')->fetchColumn(),
            'exams' => (int)$db->query('SELECT COUNT(*) FROM exams')->fetchColumn(),
            'groups' => (int)$db->query('SELECT COUNT(*) FROM question_groups')->fetchColumn()]];
        break;
      case 'users': $out = ['users' => users((string)($d['q'] ?? ''))]; break;
      case 'questions':
        $st = $db->prepare('SELECT id,type,points,group_id,LEFT(question_text,90) AS t FROM questions WHERE subject_id=? AND (?=0 OR group_id=?) ORDER BY id DESC LIMIT 500');
        $g = (int)($d['group_id'] ?? 0); $st->execute([(int)($d['subject_id'] ?? 0), $g, $g]); $out = ['questions' => $st->fetchAll()]; break;
      case 'add_subject':
        $n = trim((string)($d['name'] ?? '')); if ($n === '') throw new Exception('Nama mata pelajaran wajib diisi.');
        $ex = $db->prepare('SELECT id FROM subjects WHERE name=?'); $ex->execute([$n]);
        if ($ex->fetch()) throw new Exception('Mata pelajaran dengan nama itu sudah ada.');
        $db->beginTransaction();
        $db->prepare('INSERT INTO subjects (name,description) VALUES (?,?)')->execute([$n, nn($d['description'] ?? null)]);
        $out = ['id' => (int)$db->lastInsertId()]; $dry ? $db->rollBack() : $db->commit(); break;
      case 'add_group':
        $n = trim((string)($d['name'] ?? '')); if ($n === '') throw new Exception('Nama grup wajib diisi.');
        if (!in_array($d['type'] ?? '', QTYPES, true)) throw new Exception('Tipe grup tidak valid.');
        $db->beginTransaction();
        $db->prepare('INSERT INTO question_groups (name,description,subject_id,type,sub_topic,default_points,created_by) VALUES (?,?,?,?,?,?,?)')
          ->execute([$n, nn($d['description'] ?? null), (int)$d['subject_id'], $d['type'], nn($d['sub_topic'] ?? null), max(0, (int)($d['default_points'] ?? 1)), $by]);
        $out = ['id' => (int)$db->lastInsertId()]; $dry ? $db->rollBack() : $db->commit(); break;
      case 'add_questions':
        $items = $d['items'] ?? []; if (!$items) throw new Exception('Antrian soal kosong.');
        $db->beginTransaction(); $ids = [];
        try {
          foreach ($items as $i => $q) {
            try {
              $sid = (int)($q['subject_id'] ?? 0); if (!$sid) throw new Exception('Mata pelajaran belum dipilih.');
              $ids[] = insertQuestion($db, $q, $sid, ($q['group_id'] ?? 0) ? (int)$q['group_id'] : null, $by);
            } catch (Throwable $e) { throw new Exception('Soal #' . ($i + 1) . ': ' . $e->getMessage()); }
          }
          $dry ? $db->rollBack() : $db->commit();
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
        $out = ['inserted' => count($ids), 'ids' => $dry ? [] : $ids]; break;
      case 'add_exam':
        $title = trim((string)($d['title'] ?? '')); if ($title === '') throw new Exception('Judul ujian wajib diisi.');
        $dur = (int)($d['duration'] ?? 0); if ($dur < 1) throw new Exception('Durasi (menit) harus lebih dari 0.');
        $start = dtv((string)($d['start_time'] ?? '')); $end = dtv((string)($d['end_time'] ?? ''));
        if ($end <= $start) throw new Exception('Waktu selesai harus setelah waktu mulai.');
        $cat = $d['category'] ?? 'reguler'; $et = $d['exam_type'] ?? 'single'; $stt = $d['status'] ?? 'draft';
        if (!in_array($cat, ['reguler', 'ppdb'], true) || !in_array($et, ['single', 'multi'], true) || !in_array($stt, ['draft', 'published', 'archived'], true)) throw new Exception('Kategori/tipe/status tidak valid.');
        $token = strtoupper(trim((string)($d['token'] ?? ''))) ?: strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $c = $db->prepare('SELECT 1 FROM exams WHERE token=?'); $c->execute([$token]); if ($c->fetch()) throw new Exception("Token $token sudah dipakai ujian lain.");
        $qids = array_values(array_unique(array_map('intval', $d['question_ids'] ?? [])));
        if (!$qids) throw new Exception('Pilih minimal satu soal.');
        $db->beginTransaction();
        try {
          $in = implode(',', array_fill(0, count($qids), '?'));
          $r = $db->prepare("SELECT id,subject_id FROM questions WHERE id IN ($in)"); $r->execute($qids);
          $subj = array_column($r->fetchAll(), 'subject_id', 'id');
          if (count($subj) !== count($qids)) throw new Exception('Sebagian soal yang dipilih tidak ditemukan di database.');
          $db->prepare('INSERT INTO exams (title,category,subject_id,exam_type,duration,start_time,end_time,token,use_token,randomize_questions,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$title, $cat, nn($d['subject_id'] ?? null), $et, $dur, $start, $end, $token, empty($d['use_token']) ? 0 : 1, empty($d['randomize']) ? 0 : 1, $stt, $by]);
          $eid = (int)$db->lastInsertId();
          $iq = $db->prepare('INSERT INTO exam_questions (exam_id,question_id,order_num,subject_id) VALUES (?,?,?,?)');
          foreach ($qids as $n => $qid) $iq->execute([$eid, $qid, $n + 1, $subj[$qid]]);
          if ($et === 'multi') {
            $is = $db->prepare('INSERT INTO exam_subjects (exam_id,subject_id,question_count,order_num) VALUES (?,?,?,?)'); $o = 0;
            foreach (array_count_values($subj) as $sid => $cnt) $is->execute([$eid, $sid, $cnt, ++$o]);
          }
          $pids = array_values(array_unique(array_filter(array_map('intval', $d['participants'] ?? []))));
          if ($pids) { $ip = $db->prepare('INSERT IGNORE INTO exam_participants (exam_id,user_id) VALUES (?,?)'); foreach ($pids as $u) $ip->execute([$eid, $u]); }
          $dry ? $db->rollBack() : $db->commit();
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
        $out = ['exam_id' => $dry ? null : $eid, 'token' => $token, 'questions' => count($qids), 'participants' => count($pids)]; break;
      case 'settings_info':
        $sx = 'tidak dikonfigurasi'; if ($CFG['src_name'] !== '') { try { srcdb()->query('SELECT 1'); $sx = "terhubung ({$CFG['src_user']}@{$CFG['src_host']}/{$CFG['src_name']})"; } catch (Throwable $e) { $sx = 'GAGAL: ' . $e->getMessage(); } }
        $out = ['php' => PHP_VERSION, 'zip' => class_exists('ZipArchive'), 'upload_max' => ini_get('upload_max_filesize'), 'post_max' => ini_get('post_max_size'), 'memory' => ini_get('memory_limit'),
          'exec' => ini_get('max_execution_time'), 'db' => "{$CFG['user']}@{$CFG['host']}/{$CFG['name']}", 'src' => $sx, 'config_file' => is_file($CFG_FILE), 'push_url' => $CFG['push_url'], 'push_has_token' => $CFG['push_token'] !== '', 'accepts' => $ph !== '',
          'dirs' => array_map(fn($p) => ['path' => $p, 'ok' => is_dir($p) && is_writable($p)], $CFG['dirs']), 'audit' => $CFG['audit_log'], 'audit_ok' => is_writable(dirname($CFG['audit_log']))]; break;
      case 'change_password':
        if (!password_verify((string)($d['old'] ?? ''), $CFG['pass_hash'])) { sleep(1); throw new Exception('Password lama salah.'); }
        if (strlen((string)($d['new'] ?? '')) < 10) throw new Exception('Password baru minimal 10 karakter.');
        writeConfig(['pass_hash' => password_hash((string)$d['new'], PASSWORD_DEFAULT)]); audit('change_password', []); $out = []; break;
      case 'exam_find':
        $c = ($d['source'] ?? '') === 'src' ? srcdb() : $db; if (!$c) throw new Exception('Database sumber belum dikonfigurasi (src_name).');
        $out = ['exams' => findExams($c, trim((string)($d['q'] ?? '')))]; break;
      case 'exam_import_src':
        $src = srcdb(); if (!$src) throw new Exception('Database sumber belum dikonfigurasi (src_name).');
        $out = importBundle($db, readBundle($src, (int)($d['id'] ?? 0)), $dry, $by, (string)($d['status'] ?? 'draft'), $CFG['src_dirs'], !empty($d['files_only']));
        audit_if(!$dry, 'import_exam_src', $out); break;
      case 'exam_export_zip':
        [$tmp, $miss] = buildZip(readBundle($db, (int)($d['id'] ?? 0)));
        header('Content-Type: application/zip'); header('X-Missing: ' . count($miss)); header('Content-Length: ' . filesize($tmp)); readfile($tmp); unlink($tmp); exit;
      case 'exam_push':
        $b = readBundle($db, (int)($d['id'] ?? 0)); [$tmp, $miss] = buildZip($b);
        try {
          $j = remote('exam_import_zip', ['file' => new CURLFile($tmp, 'application/zip', 'paket.zip'), 'dry_run' => $dry ? '1' : '0',
            'status' => (string)($d['status'] ?? 'draft'), 'files_only' => !empty($d['files_only']) ? '1' : '0'], 900);
        } finally { @unlink($tmp); }
        $out = array_diff_key($j, ['ok' => 1]) + ['missing_local' => count($miss)]; audit_if(!$dry, 'push_exam', $out); break;
      case 'push_ping': $out = ['db' => $CFG['name']]; break;
      case 'push_test': $j = remote('push_ping'); $out = ['remote_db' => $j['db'] ?? '?']; break;
      case 'save_push':
        $u = trim((string)($d['url'] ?? '')); $tk = trim((string)($d['token'] ?? ''));
        if (!preg_match('#^https://#i', $u) && !preg_match('#^http://(localhost|127\.0\.0\.1)#i', $u)) throw new Exception('Alamat production harus diawali https:// agar token tidak terkirim tanpa enkripsi.');
        writeConfig(['push_url' => $u] + ($tk !== '' ? ['push_token' => $tk] : [])); $out = []; break;
      case 'make_push_token':
        $t = bin2hex(random_bytes(24)); writeConfig(['push_token_hash' => password_hash($t, PASSWORD_DEFAULT)]); audit('make_push_token', []); $out = ['token' => $t]; break;
      case 'revoke_push_token': writeConfig(['push_token_hash' => '']); audit('revoke_push_token', []); $out = []; break;
      case 'exam_import_zip':
        if (!class_exists('ZipArchive')) throw new Exception('Ekstensi PHP zip belum aktif.');
        $dry = ($_POST['dry_run'] ?? '1') === '1'; $by = (int)($_POST['created_by'] ?? $CFG['default_created_by']); $f = $_FILES['file'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload gagal (kode ' . ($f['error'] ?? '?') . '). Cek upload_max_filesize dan post_max_size di PHP.');
        $z = new ZipArchive; if ($z->open($f['tmp_name']) !== true) throw new Exception('File bukan ZIP yang valid.');
        $b = json_decode((string)$z->getFromName('bundle.json'), true); if (!is_array($b)) throw new Exception('bundle.json tidak ada di dalam paket.');
        $tmp = sys_get_temp_dir() . '/catimp_' . bin2hex(random_bytes(6)); $sd = [];
        try {
          for ($i = 0; $i < $z->numFiles; $i++) {
            $n = $z->getNameIndex($i);
            if (!preg_match('#^files/(gambar|pdf|audio)/([^/\\\\]+)$#', $n, $m) || $m[2][0] === '.') continue;
            @mkdir("$tmp/{$m[1]}", 0700, true); $in = $z->getStream($n); $o = fopen("$tmp/{$m[1]}/{$m[2]}", 'wb');
            stream_copy_to_stream($in, $o); fclose($in); fclose($o); $sd[$m[1]] = "$tmp/{$m[1]}";
          }
          $out = importBundle($db, $b, $dry, $by, (string)($_POST['status'] ?? 'draft'), $sd, ($_POST['files_only'] ?? '0') === '1');
        } finally { rmtree($tmp); }
        audit_if(!$dry, 'import_exam_zip', $out); break;
      case 'upload_pdf':
        $dry = ($_POST['dry_run'] ?? '1') === '1'; $f = $_FILES['file'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload gagal (kode ' . ($f['error'] ?? '?') . '). Cek upload_max_filesize di PHP.');
        if ($f['size'] > $CFG['pdf_max_mb'] * 1048576) throw new Exception('Ukuran melebihi ' . $CFG['pdf_max_mb'] . ' MB.');
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string)($_POST['name'] ?? $f['name'])));
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') throw new Exception("$name bukan file .pdf.");
        if (file_get_contents($f['tmp_name'], false, null, 0, 5) !== '%PDF-' || (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) !== 'application/pdf') throw new Exception("$name bukan PDF yang valid.");
        $dir = rtrim($CFG['pdf_dir'], '/'); $dest = "$dir/$name"; $exists = is_file($dest);
        if ($exists && ($_POST['overwrite'] ?? '0') !== '1') { $out = ['name' => $name, 'status' => 'dilewati (sudah ada)']; break; }
        if (!$dry) {
          if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new Exception('Folder tujuan tidak bisa dibuat: ' . $dir);
          if (!move_uploaded_file($f['tmp_name'], $dest)) throw new Exception('Gagal menyimpan file. Cek izin tulis folder.');
          audit('upload_pdf', ['name' => $name, 'size' => $f['size'], 'overwrite' => $exists]);
        }
        $out = ['name' => $name, 'status' => $dry ? 'simulasi OK' : ($exists ? 'ditimpa' : 'tersimpan')]; break;
      case 'pdf_diff':
        $dir = rtrim($CFG['pdf_dir'], '/'); $files = is_dir($dir) ? array_map('basename', glob("$dir/*.pdf") ?: []) : [];
        $pre = $CFG['pdf_prefix']; $strip = fn($v) => ($pre !== '' && str_starts_with((string)$v, $pre)) ? substr((string)$v, strlen($pre)) : basename((string)$v);
        $refs = [];
        foreach ($db->query("SELECT id,title AS t,exam_pdf AS f FROM exams WHERE exam_pdf IS NOT NULL AND exam_pdf<>''") as $r) $refs[] = ['kind' => 'exam', 'id' => (int)$r['id'], 'label' => $r['t'], 'file' => $strip($r['f'])];
        foreach ($db->query("SELECT id,LEFT(question_text,50) AS t,pdf_file AS f FROM questions WHERE pdf_file IS NOT NULL AND pdf_file<>''") as $r) $refs[] = ['kind' => 'question', 'id' => (int)$r['id'], 'label' => $r['t'], 'file' => $strip($r['f'])];
        $used = array_column($refs, 'file');
        $out = ['dir' => $dir, 'dir_ok' => is_dir($dir) && is_writable($dir), 'total' => count($files),
          'orphans' => array_values(array_diff($files, $used)),
          'missing' => array_values(array_filter($refs, fn($r) => !in_array($r['file'], $files, true)))];
        break;
      case 'link_pdf':
        $fn = basename((string)($d['file'] ?? '')); $id = (int)($d['id'] ?? 0); $kind = $d['kind'] ?? '';
        if (!is_file(rtrim($CFG['pdf_dir'], '/') . "/$fn")) throw new Exception('File tidak ada di folder PDF.');
        if (!in_array($kind, ['exam', 'question'], true) || $id < 1) throw new Exception('Target tidak valid.');
        $db->beginTransaction();
        $st = $db->prepare($kind === 'exam' ? 'UPDATE exams SET exam_pdf=? WHERE id=?' : 'UPDATE questions SET pdf_file=? WHERE id=?');
        $st->execute([$CFG['pdf_prefix'] . $fn, $id]);
        $ex = $db->prepare($kind === 'exam' ? 'SELECT 1 FROM exams WHERE id=?' : 'SELECT 1 FROM questions WHERE id=?'); $ex->execute([$id]);
        if (!$ex->fetch()) { $db->rollBack(); throw new Exception("ID $id tidak ditemukan."); }
        $dry ? $db->rollBack() : $db->commit();
        if (!$dry) audit('link_pdf', ['kind' => $kind, 'id' => $id, 'file' => $fn]);
        $out = ['kind' => $kind, 'id' => $id, 'file' => $fn]; break;
      default: throw new Exception('Aksi tidak dikenal.');
    }
    if (str_starts_with($_GET['api'], 'add_') && !$dry) audit($_GET['api'], ['by' => $by, 'result' => $out]);
    echo json_encode(['ok' => true] + $out, JSON_UNESCAPED_UNICODE);
  } catch (Throwable $e) {
    if (http_response_code() < 400) http_response_code(400);
    $m = $e instanceof PDOException ? 'Database: ' . $e->getMessage() : $e->getMessage();
    echo json_encode(['ok' => false, 'error' => $m], JSON_UNESCAPED_UNICODE);
  }
  exit;
}

/* ---------- halaman login ---------- */
if (!$auth): ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CAT Input Console</title>
<style>
:root{--bg:#eef2f5;--fg:#14212b;--card:#fff;--line:#cfd8df;--ac:#0b6e6e}
@media(prefers-color-scheme:dark){:root{--bg:#10181e;--fg:#e4ecf1;--card:#18232b;--line:#2c3a45;--ac:#4fc3c3}}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);color:var(--fg);font:15px/1.5 system-ui,sans-serif}
form{background:var(--card);border:1px solid var(--line);border-radius:8px;padding:28px;width:min(340px,90vw)}
h1{font-size:18px;margin:0 0 4px}p{margin:0 0 16px;color:#6b7c88;font-size:13px}
input,button{width:100%;box-sizing:border-box;padding:10px;border-radius:6px;border:1px solid var(--line);font:inherit;background:var(--bg);color:var(--fg)}
button{background:var(--ac);color:#fff;border:0;margin-top:12px;cursor:pointer;font-weight:600}.e{color:#c0392b;margin-top:10px;font-size:13px}
</style></head><body>
<form method="post"><h1>CAT Input Console</h1><p>Masuk untuk menginput data ke <?= htmlspecialchars($CFG['name']) ?>.</p>
<?php if ($CFG['pass_hash'] === ''): ?><div class="e">Belum dikonfigurasi: isi <code>pass_hash</code> (atau env <code>CAT_INPUT_PASS_HASH</code>) di bagian atas file ini.</div>
<?php else: ?><input type="password" name="password" placeholder="Password" autofocus required><button name="login" value="1">Masuk</button><?php endif; ?>
<?php if ($err): ?><div class="e"><?= htmlspecialchars($err) ?></div><?php endif; ?></form></body></html>
<?php exit; endif; ?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CAT Input Console</title>
<style>
:root{--bg:#eef2f5;--fg:#14212b;--mut:#5d6f7b;--card:#fff;--line:#cfd8df;--ac:#0b6e6e;--acfg:#fff;--live:#b42318;--livebg:#fdecea;--ok:#1a7f4b;--soft:#e3ecf0}
@media(prefers-color-scheme:dark){:root{--bg:#10181e;--fg:#e4ecf1;--mut:#93a5b1;--card:#18232b;--line:#2c3a45;--ac:#4fc3c3;--acfg:#06201f;--live:#ff8a7a;--livebg:#3a1a17;--ok:#5fd394;--soft:#21303a}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--fg);font:14px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}
header{display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:12px 20px;border-bottom:1px solid var(--line);background:var(--card);position:sticky;top:0;z-index:5}
header h1{font-size:16px;margin:0;font-weight:700}.sp{flex:1}
.chip{padding:3px 10px;border-radius:99px;background:var(--soft);font-size:12px}
.mode{display:flex;align-items:center;gap:8px;padding:5px 12px;border-radius:6px;border:1px solid var(--line);cursor:pointer;font-weight:600}
.mode.live{background:var(--livebg);color:var(--live);border-color:var(--live)}.mode.dry{color:var(--ok)}
nav{display:flex;gap:2px;padding:10px 20px 0;overflow:auto}
nav button{background:none;border:0;border-bottom:2px solid transparent;padding:8px 14px;font:inherit;color:var(--mut);cursor:pointer;white-space:nowrap}
nav button.on{color:var(--fg);border-color:var(--ac);font-weight:600}
main{max-width:1100px;margin:0 auto;padding:16px 20px 80px}
.banner{display:none;background:var(--livebg);color:var(--live);border:1px solid var(--live);padding:8px 12px;border-radius:6px;margin-bottom:14px;font-weight:600}
.banner.show{display:block}
section{display:none}section.on{display:block}
.panel{background:var(--card);border:1px solid var(--line);border-radius:8px;padding:16px;margin-bottom:16px}
.panel h2{font-size:15px;margin:0 0 12px}.grid{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}
label{display:block;font-size:12px;color:var(--mut);margin-bottom:3px}
input,select,textarea{width:100%;padding:8px 10px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--fg);font:inherit}
textarea{min-height:90px;resize:vertical}input:focus,select:focus,textarea:focus,button:focus-visible{outline:2px solid var(--ac);outline-offset:1px}
.row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:12px}
.btn{padding:8px 16px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--fg);font:inherit;cursor:pointer}
.btn.p{background:var(--ac);color:var(--acfg);border-color:var(--ac);font-weight:600}.btn:disabled{opacity:.5;cursor:default}
table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:6px 8px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}th{color:var(--mut);font-weight:600}
.scroll{max-height:340px;overflow:auto;border:1px solid var(--line);border-radius:6px}
.hint{color:var(--mut);font-size:12px;margin:6px 0 0}.opt{display:grid;grid-template-columns:24px 1fr;gap:6px;align-items:center;margin-bottom:6px}
#toast{position:fixed;right:16px;bottom:16px;max-width:380px;display:grid;gap:8px;z-index:9}
.t{padding:10px 14px;border-radius:6px;background:var(--card);border:1px solid var(--line);border-left:4px solid var(--ok);box-shadow:0 4px 16px rgba(0,0,0,.18)}.t.err{border-left-color:var(--live)}
@media(prefers-reduced-motion:no-preference){.t{animation:in .18s ease-out}@keyframes in{from{transform:translateY(8px);opacity:0}}}
</style></head><body>
<header><h1>CAT Input Console</h1><span class="chip" id="dbchip">…</span><span class="chip" id="cnt"></span><span class="sp"></span>
<label class="mode dry" id="mode" style="margin:0"><input type="checkbox" id="dry" checked style="width:auto"><span id="modeTxt">Simulasi (tidak disimpan)</span></label>
<a class="btn" href="?logout=1" style="text-decoration:none">Keluar</a></header>
<nav id="nav"><button data-t="soal" class="on">Soal</button><button data-t="grup">Grup soal</button><button data-t="mapel">Mata pelajaran</button><button data-t="ujian">Ujian</button><button data-t="imp">Import ujian</button><button data-t="push">Kirim ke production</button><button data-t="pdf">File PDF</button><button data-t="set">Pengaturan</button></nav>
<main>
<div class="banner" id="banner">Mode LIVE: data akan ditulis langsung ke database production. Aktifkan simulasi untuk menguji tanpa menyimpan.</div>

<section id="t-soal" class="on">
 <div class="panel"><h2>Tambah soal ke antrian</h2>
  <div class="grid">
   <div><label>Mata pelajaran</label><select id="q_sub"></select></div>
   <div><label>Grup soal (opsional)</label><select id="q_grp"></select></div>
   <div><label>Tipe</label><select id="q_type"><option>multiple_choice</option><option>essay</option><option>short_answer</option><option>short_answer_text</option><option>listening</option><option>tkp</option></select></div>
   <div><label>Poin</label><input id="q_pts" type="number" min="0" value="1"></div>
   <div><label>Level</label><select id="q_diff"><option value="">—</option><option>mudah</option><option>sedang</option><option>sulit</option><option>hots</option></select></div>
   <div><label>Sub topik</label><input id="q_sub_topic"></div>
  </div>
  <div style="margin-top:12px"><label>Teks soal</label><textarea id="q_text"></textarea></div>
  <div class="grid" style="margin-top:12px">
   <div id="opts"></div>
   <div><label>Kunci jawaban (A–E) / jawaban singkat</label><input id="q_ans" maxlength="100">
    <label style="margin-top:10px">Skor TKP (mis. A=5,B=4,C=3,D=2,E=1)</label><input id="q_sc">
    <label style="margin-top:10px">Gambar soal (nama file yang sudah ada di server)</label><input id="q_img"></div>
  </div>
  <div class="row"><button class="btn p" id="addQ">Tambah ke antrian</button></div>
 </div>
 <div class="panel"><h2>Tempel massal</h2>
  <textarea id="bulk" placeholder="Teks soal&#10;A. opsi&#10;B. opsi&#10;C. opsi&#10;D. opsi&#10;E. opsi&#10;Jawaban: C&#10;Poin: 3&#10;Level: sedang&#10;---&#10;(soal berikutnya)"></textarea>
  <p class="hint">Pisahkan soal dengan baris <b>---</b>. Soal TKP: tambahkan baris <b>Skor: A=5,B=4,...</b>. Tanpa opsi dan tanpa jawaban dianggap esai. Mata pelajaran dan grup mengikuti pilihan di atas.</p>
  <div class="row"><button class="btn" id="parseBulk">Masukkan ke antrian</button></div></div>
 <div class="panel"><h2>Antrian (<span id="qn">0</span>)</h2><div class="scroll"><table><thead><tr><th>#</th><th>Mapel</th><th>Tipe</th><th>Soal</th><th>Kunci</th><th>Poin</th><th></th></tr></thead><tbody id="qtb"></tbody></table></div>
  <div class="row"><button class="btn p" id="sendQ">Kirim antrian ke database</button><button class="btn" id="clearQ">Kosongkan</button><span class="hint">Antrian tersimpan di browser ini sampai berhasil dikirim.</span></div></div>
</section>

<section id="t-grup"><div class="panel"><h2>Grup soal baru</h2><div class="grid">
 <div><label>Nama grup</label><input id="g_name"></div><div><label>Mata pelajaran</label><select id="g_sub"></select></div>
 <div><label>Tipe</label><select id="g_type"><option>multiple_choice</option><option>essay</option><option>short_answer</option><option>short_answer_text</option><option>listening</option><option>tkp</option></select></div>
 <div><label>Poin default</label><input id="g_pts" type="number" min="0" value="1"></div><div><label>Sub topik</label><input id="g_st"></div></div>
 <div style="margin-top:12px"><label>Deskripsi</label><textarea id="g_desc" style="min-height:60px"></textarea></div>
 <div class="row"><button class="btn p" id="saveG">Simpan grup</button></div></div></section>

<section id="t-mapel"><div class="panel"><h2>Mata pelajaran baru</h2><div class="grid">
 <div><label>Nama</label><input id="s_name"></div><div><label>Deskripsi</label><input id="s_desc"></div></div>
 <div class="row"><button class="btn p" id="saveS">Simpan mata pelajaran</button></div></div></section>

<section id="t-ujian">
 <div class="panel"><h2>Pengaturan ujian</h2><div class="grid">
  <div><label>Judul</label><input id="e_title"></div>
  <div><label>Kategori</label><select id="e_cat"><option>reguler</option><option>ppdb</option></select></div>
  <div><label>Tipe</label><select id="e_type"><option value="single">single (satu mapel)</option><option value="multi">multi (beberapa mapel)</option></select></div>
  <div><label>Mata pelajaran utama</label><select id="e_sub"></select></div>
  <div><label>Durasi (menit)</label><input id="e_dur" type="number" min="1" value="90"></div>
  <div><label>Mulai</label><input id="e_start" type="datetime-local"></div><div><label>Selesai</label><input id="e_end" type="datetime-local"></div>
  <div><label>Token (kosong = otomatis)</label><input id="e_tok" maxlength="20"></div>
  <div><label>Status</label><select id="e_status"><option>draft</option><option>published</option><option>archived</option></select></div>
  <div><label>Opsi</label><label style="color:var(--fg)"><input id="e_use" type="checkbox" style="width:auto"> Wajib token</label><label style="color:var(--fg)"><input id="e_rand" type="checkbox" style="width:auto"> Acak soal</label></div></div></div>
 <div class="panel"><h2>Pilih soal (<span id="en">0</span> dipilih)</h2><div class="grid"><div><label>Mata pelajaran</label><select id="p_sub"></select></div><div><label>Grup</label><select id="p_grp"></select></div></div>
  <div class="row"><button class="btn" id="loadQ">Muat soal</button><button class="btn" id="allQ">Pilih semua yang tampil</button></div>
  <div class="scroll" style="margin-top:10px"><table><thead><tr><th></th><th>ID</th><th>Tipe</th><th>Soal</th><th>Poin</th></tr></thead><tbody id="ptb"></tbody></table></div>
  <p class="hint">Untuk ujian multi, soal dari beberapa mapel bisa dipilih bergantian: pilihan tetap tersimpan saat Anda ganti mapel.</p></div>
 <div class="panel"><h2>Peserta</h2><div class="grid"><div><label>Cari user (nama atau ID)</label><input id="u_q" placeholder="ketik untuk mencari"></div><div><label>ID peserta (pisah koma)</label><textarea id="e_users" style="min-height:60px"></textarea></div></div>
  <div class="scroll" style="margin-top:10px;max-height:150px"><table><tbody id="utb"></tbody></table></div>
  <div class="row"><button class="btn p" id="saveE">Simpan ujian</button></div></div>
</section>
<section id="t-pdf">
 <div class="panel"><h2>Unggah PDF ke server</h2>
  <p class="hint" id="pdfdir" style="margin:0 0 10px"></p>
  <input type="file" id="pdf_files" accept="application/pdf,.pdf" multiple>
  <div class="row"><label style="color:var(--fg);margin:0"><input id="pdf_ow" type="checkbox" style="width:auto"> Timpa file yang sudah ada</label><button class="btn p" id="pdfUp">Unggah</button></div>
  <div id="pdfres" class="hint"></div></div>
 <div class="panel"><h2>Cek selisih folder dan database</h2><div class="row" style="margin:0 0 10px"><button class="btn" id="pdfDiff">Periksa sekarang</button></div>
  <h2 style="font-size:13px">File di folder yang belum tertaut (<span id="orN">0</span>)</h2>
  <div class="scroll"><table><tbody id="orTb"></tbody></table></div>
  <div class="grid" style="margin-top:10px"><div><label>Tautkan file terpilih ke</label><select id="lk_kind"><option value="exam">Ujian (exams.exam_pdf)</option><option value="question">Soal (questions.pdf_file)</option></select></div><div><label>ID ujian/soal</label><input id="lk_id" type="number" min="1"></div></div>
  <div class="row"><span class="hint" id="lk_sel">Belum ada file dipilih.</span><button class="btn p" id="lkGo">Tautkan</button></div>
  <h2 style="font-size:13px;margin-top:16px">Tercatat di database tapi file tidak ada (<span id="msN">0</span>)</h2>
  <div class="scroll"><table><tbody id="msTb"></tbody></table></div></div>
</section>
<section id="t-imp">
 <div class="panel"><h2>Folder file di server ini</h2><div id="dirs" class="hint"></div>
  <p class="hint">Ubah lokasinya di bagian <code>dirs</code> pada $CFG jika berbeda dengan struktur aplikasi CAT. Nama file di database harus sama dengan nama file di folder ini.</p></div>
 <div class="panel" id="srcPanel"><h2>Import langsung dari database sumber</h2>
  <p class="hint" style="margin:0 0 10px">Ketik nama ujian, pilih hasilnya, lalu import. Soal, mapel, grup, gambar, audio, dan PDF ikut terbawa. Soal yang sudah ada dipakai ulang, bukan digandakan.</p>
  <div class="row" style="margin:0"><input id="i_q" placeholder="Nama ujian" style="max-width:360px"><button class="btn" id="i_find">Cari</button>
   <select id="i_status" style="width:auto"><option value="draft">Status: draft (disarankan)</option><option value="">Status: ikut sumber</option></select></div>
  <div class="scroll" style="margin-top:10px"><table><tbody id="i_tb"></tbody></table></div>
  <div class="row"><button class="btn p" id="i_go">Import ke database ini</button></div></div>
 <div class="panel"><h2>Paket ZIP (jika sumber tidak terhubung)</h2>
  <p class="hint" style="margin:0 0 10px">Di mesin sumber: cari ujian, unduh paket. Di production: unggah paket itu. Paket berisi data ujian dan semua file gambar, audio, PDF-nya.</p>
  <div class="row" style="margin:0"><input id="x_q" placeholder="Nama ujian di database ini" style="max-width:360px"><button class="btn" id="x_find">Cari</button></div>
  <div class="scroll" style="margin-top:10px"><table><tbody id="x_tb"></tbody></table></div>
  <div class="row"><button class="btn" id="x_dl">Unduh paket (.zip)</button></div>
  <div class="row" style="margin-top:18px"><input type="file" id="x_file" accept=".zip,application/zip" style="max-width:360px"><button class="btn p" id="x_go">Import paket ke database ini</button></div></div>
 <div class="panel"><label style="color:var(--fg);margin:0 0 10px"><input id="i_fo" type="checkbox" style="width:auto"> Hanya sinkron file (ujian sudah ada di database ini, mis. untuk melengkapi file yang tadinya belum ada)</label>
  <h2>Hasil</h2><div id="impres" class="hint">Belum ada import.</div></div>
</section>
<section id="t-push">
 <div class="panel"><h2>Kirim ujian ke production</h2>
  <p class="hint" style="margin:0 0 10px">Dipakai di mesin lokal. Ketik nama ujian, pilih, lalu kirim. Ujian, soal, gambar, audio, dan PDF-nya langsung masuk ke database dan folder production. Gunakan mode simulasi dulu untuk uji kirim tanpa menyimpan apa pun di production.</p>
  <div class="row" style="margin:0"><input id="p_q" placeholder="Nama ujian" style="max-width:360px"><button class="btn" id="p_find">Cari</button>
   <select id="p_status" style="width:auto"><option value="draft">Status di production: draft (disarankan)</option><option value="">Status: ikut lokal</option></select></div>
  <div class="scroll" style="margin-top:10px"><table><tbody id="p_tb"></tbody></table></div>
  <div class="row"><label style="color:var(--fg);margin:0"><input id="p_fo" type="checkbox" style="width:auto"> Hanya kirim file (ujian sudah ada di production)</label><button class="btn p" id="p_go">Kirim ke production</button></div>
  <div id="p_res" class="hint" style="margin-top:12px">Belum ada pengiriman.</div></div>
</section>
<section id="t-set">
 <div class="panel"><h2>Status sistem</h2><div id="setinfo" class="hint">Memuat…</div>
  <div class="row"><button class="btn" id="setReload">Periksa ulang</button></div></div>
 <div class="panel"><h2>Koneksi ke production (untuk fitur Kirim)</h2><div class="grid">
  <div><label>Alamat cat_input.php di production (https)</label><input id="ps_url" placeholder="https://cat.binakasihnusantara.sch.id/cat_input.php"></div>
  <div><label>Token dari production (kosongkan jika tidak diganti)</label><input type="password" id="ps_tok" autocomplete="off"></div></div>
  <div class="row"><button class="btn p" id="psSave">Simpan</button><button class="btn" id="psTest">Uji koneksi</button><span class="hint" id="psMsg"></span></div></div>
 <div class="panel"><h2>Terima kiriman (diaktifkan di server production)</h2>
  <p class="hint" style="margin:0 0 10px">Buat token di sini, lalu tempel ke Pengaturan di mesin lokal. Token hanya tampil sekali. Dengan token ini mesin lokal hanya bisa mengirim ujian; tidak bisa membuka halaman lainnya.</p>
  <div class="row" style="margin:0"><button class="btn" id="tkMake">Buat token baru</button><button class="btn" id="tkOff">Nonaktifkan penerimaan</button></div>
  <div id="tkShow" style="margin-top:10px;word-break:break-all"></div></div>
 <div class="panel"><h2>Ganti password halaman ini</h2><div class="grid">
  <div><label>Password lama</label><input type="password" id="pw_old" autocomplete="current-password"></div>
  <div><label>Password baru (min. 10 karakter)</label><input type="password" id="pw_new" autocomplete="new-password"></div></div>
  <div class="row"><button class="btn p" id="pwGo">Ganti password</button></div></div>
</section>
</main><div id="toast"></div>

<script>
const CSRF = <?= json_encode($_SESSION['csrf']) ?>;
const $ = s => document.querySelector(s), esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let META = {subjects: [], groups: []}, QUEUE = [], SEL = new Map(), BY = 1;
try { QUEUE = JSON.parse(localStorage.getItem('cat_queue') || '[]'); } catch (e) {}
const saveQ = () => { try { localStorage.setItem('cat_queue', JSON.stringify(QUEUE)); } catch (e) {} };
const dry = () => $('#dry').checked;

async function api(a, data = {}) {
  const r = await fetch('?api=' + a, {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF': CSRF}, body: JSON.stringify({...data, dry_run: dry(), created_by: BY})});
  const j = await r.json().catch(() => ({ok: false, error: 'Respons server tidak valid.'}));
  if (!j.ok) throw new Error(j.error); return j;
}
function toast(m, err) { const d = document.createElement('div'); d.className = 't' + (err ? ' err' : ''); d.textContent = m; $('#toast').append(d); setTimeout(() => d.remove(), err ? 9000 : 4500); }
const guard = fn => async (...a) => { try { await fn(...a); } catch (e) { toast(e.message, true); } };
const done = m => dry() ? 'Simulasi berhasil (di-rollback): ' + m : 'Tersimpan: ' + m;
function confirmLive(msg) { return dry() || confirm(msg + '\n\nData akan ditulis ke PRODUCTION. Lanjutkan?'); }

function opts(list, withBlank) { return (withBlank ? '<option value="0">—</option>' : '') + list.map(x => `<option value="${x.id}">${esc(x.name)}</option>`).join(''); }
function fillGroups(sel, sid) { const g = META.groups.filter(x => !sid || +x.subject_id === +sid); $(sel).innerHTML = opts(g, true); }
async function loadMeta() {
  const m = await api('meta'); META = m; BY = m.default_by;
  $('#dbchip').textContent = 'DB: ' + m.db; $('#cnt').textContent = `${m.counts.questions} soal · ${m.counts.exams} ujian · ${m.counts.groups} grup`;
  ['#q_sub', '#g_sub', '#e_sub', '#p_sub'].forEach(s => { const v = $(s).value; $(s).innerHTML = opts(m.subjects); if (v) $(s).value = v; });
  fillGroups('#q_grp', $('#q_sub').value); fillGroups('#p_grp', $('#p_sub').value);
}
function setMode() { const d = dry(); $('#mode').className = 'mode ' + (d ? 'dry' : 'live'); $('#modeTxt').textContent = d ? 'Simulasi (tidak disimpan)' : 'LIVE ke production'; $('#banner').classList.toggle('show', !d); }
$('#dry').onchange = setMode;
$('#nav').onclick = e => { const t = e.target.dataset.t; if (!t) return; document.querySelectorAll('nav button').forEach(b => b.classList.toggle('on', b === e.target)); document.querySelectorAll('section').forEach(s => s.classList.toggle('on', s.id === 't-' + t)); };
$('#q_sub').onchange = () => fillGroups('#q_grp', $('#q_sub').value); $('#p_sub').onchange = () => fillGroups('#p_grp', $('#p_sub').value);
$('#opts').innerHTML = ['A', 'B', 'C', 'D', 'E'].map(k => `<div class="opt"><b>${k}</b><input id="o_${k}" placeholder="Opsi ${k}"></div>`).join('');

/* ---- antrian soal ---- */
function renderQ() {
  $('#qn').textContent = QUEUE.length;
  $('#qtb').innerHTML = QUEUE.map((q, i) => `<tr><td>${i + 1}</td><td>${esc((META.subjects.find(s => +s.id === +q.subject_id) || {}).name)}</td><td>${q.type}</td><td>${esc((q.question_text || '(gambar)').slice(0, 90))}</td><td>${esc(q.type === 'tkp' ? 'skor' : q.correct_answer || '')}</td><td>${q.points}</td><td><button class="btn" data-del="${i}">Hapus</button></td></tr>`).join('') || '<tr><td colspan="7" class="hint">Antrian kosong. Tambahkan soal dari form atau tempel massal.</td></tr>';
}
$('#qtb').onclick = e => { if (e.target.dataset.del) { QUEUE.splice(+e.target.dataset.del, 1); saveQ(); renderQ(); } };
const parseScores = s => Object.fromEntries(s.split(',').map(p => p.split('=').map(x => x.trim())).filter(p => p[0] && p[1] !== undefined && !isNaN(+p[1])).map(p => [p[0].toUpperCase(), +p[1]]));
function base() { return {subject_id: +$('#q_sub').value, group_id: +$('#q_grp').value || 0, difficulty_level: $('#q_diff').value, sub_topic: $('#q_sub_topic').value}; }
$('#addQ').onclick = guard(() => {
  const q = {...base(), type: $('#q_type').value, points: +$('#q_pts').value, question_text: $('#q_text').value.trim(), question_image: $('#q_img').value.trim(), correct_answer: $('#q_ans').value.trim()};
  ['A', 'B', 'C', 'D', 'E'].forEach(k => q['option_' + k.toLowerCase()] = $('#o_' + k).value.trim());
  if (q.type === 'tkp') q.option_scores = parseScores($('#q_sc').value);
  if (!q.subject_id) throw new Error('Pilih mata pelajaran dulu.');
  if (!q.question_text && !q.question_image) throw new Error('Isi teks soal atau nama file gambar.');
  QUEUE.push(q); saveQ(); renderQ(); $('#q_text').value = ''; $('#q_ans').value = ''; ['A', 'B', 'C', 'D', 'E'].forEach(k => $('#o_' + k).value = ''); $('#q_text').focus();
});
$('#parseBulk').onclick = guard(() => {
  const blocks = $('#bulk').value.split(/^\s*---+\s*$/m).map(b => b.trim()).filter(Boolean); if (!blocks.length) throw new Error('Area tempel masal masih kosong.');
  const b0 = base(), added = [];
  blocks.forEach((b, i) => {
    const q = {...b0, type: 'multiple_choice', points: +$('#q_pts').value || 1, question_text: ''}, text = [];
    b.split('\n').forEach(l => { let m;
      if (m = l.match(/^\s*([A-Ea-e])[.)]\s+(.*)$/)) q['option_' + m[1].toLowerCase()] = m[2].trim();
      else if (m = l.match(/^\s*(jawaban|kunci)\s*:\s*(.*)$/i)) q.correct_answer = m[2].trim();
      else if (m = l.match(/^\s*poin\s*:\s*(\d+)/i)) q.points = +m[1];
      else if (m = l.match(/^\s*level\s*:\s*(\w+)/i)) q.difficulty_level = m[1].toLowerCase();
      else if (m = l.match(/^\s*skor\s*:\s*(.*)$/i)) q.option_scores = parseScores(m[1]);
      else text.push(l); });
    q.question_text = text.join('\n').trim();
    const hasOpt = ['a', 'b', 'c', 'd', 'e'].some(k => q['option_' + k]);
    q.type = q.option_scores ? 'tkp' : hasOpt ? 'multiple_choice' : q.correct_answer ? 'short_answer_text' : 'essay';
    if (!q.question_text) throw new Error('Blok #' + (i + 1) + ' tidak punya teks soal.');
    added.push(q);
  });
  QUEUE.push(...added); saveQ(); renderQ(); $('#bulk').value = ''; toast(added.length + ' soal masuk antrian.');
});
$('#clearQ').onclick = () => { if (QUEUE.length && confirm('Kosongkan seluruh antrian?')) { QUEUE = []; saveQ(); renderQ(); } };
$('#sendQ').onclick = guard(async () => {
  if (!QUEUE.length) throw new Error('Antrian kosong.'); if (!confirmLive(`Kirim ${QUEUE.length} soal?`)) return;
  const r = await api('add_questions', {items: QUEUE});
  toast(done(r.inserted + ' soal')); if (!dry()) { QUEUE = []; saveQ(); renderQ(); loadMeta(); }
});

/* ---- grup & mapel ---- */
$('#saveG').onclick = guard(async () => {
  if (!confirmLive('Simpan grup soal?')) return;
  const r = await api('add_group', {name: $('#g_name').value, subject_id: +$('#g_sub').value, type: $('#g_type').value, default_points: +$('#g_pts').value, sub_topic: $('#g_st').value, description: $('#g_desc').value});
  toast(done('grup #' + r.id)); if (!dry()) { $('#g_name').value = ''; loadMeta(); }
});
$('#saveS').onclick = guard(async () => {
  if (!confirmLive('Simpan mata pelajaran?')) return;
  const r = await api('add_subject', {name: $('#s_name').value, description: $('#s_desc').value});
  toast(done('mapel #' + r.id)); if (!dry()) { $('#s_name').value = ''; $('#s_desc').value = ''; loadMeta(); }
});

/* ---- ujian ---- */
const upd = () => $('#en').textContent = SEL.size;
$('#loadQ').onclick = guard(async () => {
  const r = await api('questions', {subject_id: +$('#p_sub').value, group_id: +$('#p_grp').value || 0});
  $('#ptb').innerHTML = r.questions.map(q => `<tr><td><input type="checkbox" data-q="${q.id}" ${SEL.has(q.id) ? 'checked' : ''}></td><td>${q.id}</td><td>${q.type}</td><td>${esc(q.t || '(gambar)')}</td><td>${q.points}</td></tr>`).join('') || '<tr><td colspan="5" class="hint">Belum ada soal untuk pilihan ini.</td></tr>';
});
$('#ptb').onchange = e => { const id = +e.target.dataset.q; if (id) { e.target.checked ? SEL.set(id, 1) : SEL.delete(id); upd(); } };
$('#allQ').onclick = () => { document.querySelectorAll('#ptb input[data-q]').forEach(c => { c.checked = true; SEL.set(+c.dataset.q, 1); }); upd(); };
let ut; $('#u_q').oninput = () => { clearTimeout(ut); ut = setTimeout(guard(async () => {
  const q = $('#u_q').value.trim(); if (q.length < 2) { $('#utb').innerHTML = ''; return; }
  const r = await api('users', {q}); $('#utb').innerHTML = r.users.map(u => `<tr><td>${u.id}</td><td>${esc(u.label)}</td><td><button class="btn" data-u="${u.id}">Tambah</button></td></tr>`).join('') || '<tr><td class="hint">Tidak ada hasil (atau tabel users tidak terbaca).</td></tr>';
}), 300); };
$('#utb').onclick = e => { const u = e.target.dataset.u; if (!u) return; const f = $('#e_users'), ids = f.value.split(',').map(x => x.trim()).filter(Boolean); if (!ids.includes(u)) ids.push(u); f.value = ids.join(', '); };
$('#saveE').onclick = guard(async () => {
  if (!SEL.size) throw new Error('Pilih minimal satu soal.'); if (!confirmLive(`Buat ujian "${$('#e_title').value}" dengan ${SEL.size} soal?`)) return;
  const r = await api('add_exam', {title: $('#e_title').value, category: $('#e_cat').value, exam_type: $('#e_type').value, subject_id: +$('#e_sub').value || null, duration: +$('#e_dur').value,
    start_time: $('#e_start').value, end_time: $('#e_end').value, token: $('#e_tok').value, status: $('#e_status').value, use_token: $('#e_use').checked, randomize: $('#e_rand').checked,
    question_ids: [...SEL.keys()], participants: $('#e_users').value.split(',').map(x => +x.trim()).filter(Boolean)});
  toast(done(`ujian${r.exam_id ? ' #' + r.exam_id : ''}, token ${r.token}, ${r.questions} soal, ${r.participants} peserta`)); if (!dry()) { SEL.clear(); upd(); loadMeta(); }
});

/* ---- kirim ke production ---- */
$('#p_find').onclick = guard(async () => examRows('#p_tb', 'pp')(await api('exam_find', {source: 'local', q: $('#p_q').value})));
$('#p_go').onclick = guard(async () => {
  const id = pick('pp'); if (!id) throw new Error('Pilih ujian dari hasil pencarian dulu.');
  if (!META.push) throw new Error('Atur alamat dan token production di tab Pengaturan dulu.');
  if (!confirmLive('Kirim ujian ini beserta semua file-nya ke PRODUCTION?')) return;
  const b = $('#p_go'); b.disabled = true; $('#p_res').textContent = 'Mengirim… (paket besar bisa butuh beberapa menit)';
  try {
    const r = await api('exam_push', {id: +id, status: $('#p_status').value, files_only: $('#p_fo').checked}); showImp(r, '#p_res');
    if (r.missing_local) toast(r.missing_local + ' file tidak ada di folder lokal sehingga tidak ikut terkirim.', true);
  } catch (e) { $('#p_res').textContent = ''; throw e; } finally { b.disabled = false; }
});
$('#psSave').onclick = guard(async () => { await api('save_push', {url: $('#ps_url').value, token: $('#ps_tok').value}); $('#ps_tok').value = ''; toast('Koneksi production disimpan.'); loadMeta(); loadSet(); });
$('#psTest').onclick = guard(async () => { $('#psMsg').textContent = 'Menguji…'; try { const r = await api('push_test'); $('#psMsg').textContent = 'Terhubung ke database production: ' + r.remote_db; } catch (e) { $('#psMsg').textContent = ''; throw e; } });
$('#tkMake').onclick = guard(async () => { if (!confirm('Buat token baru? Token lama berhenti berlaku.')) return; const r = await api('make_push_token'); $('#tkShow').innerHTML = 'Salin token ini sekarang, tidak akan ditampilkan lagi:<br><code>' + esc(r.token) + '</code>'; loadSet(); });
$('#tkOff').onclick = guard(async () => { await api('revoke_push_token'); $('#tkShow').textContent = ''; toast('Penerimaan kiriman dinonaktifkan.'); loadSet(); });

/* ---- pengaturan ---- */
const yn = b => b ? 'siap' : '<b>bermasalah</b>';
const loadSet = guard(async () => {
  const r = await api('settings_info');
  $('#setinfo').innerHTML = [`PHP ${esc(r.php)} · ekstensi zip: ${yn(r.zip)}`, `Batas upload: ${esc(r.upload_max)} per file, ${esc(r.post_max)} per permintaan · memori ${esc(r.memory)} · waktu eksekusi ${esc(r.exec)} dtk`,
    `Database target: <code>${esc(r.db)}</code> (terhubung)`, `Database sumber: ${esc(r.src)}`, `File konfigurasi: ${r.config_file ? 'cat_input.config.php ada' : 'memakai nilai bawaan di file ini'}`,
    ...Object.entries(r.dirs).map(([k, v]) => `Folder ${k}: <code>${esc(v.path)}</code> ${v.ok ? 'siap' : 'belum ada atau tidak bisa ditulis'}`),
    `Kirim ke production: ${r.push_url ? '<code>' + esc(r.push_url) + '</code>' + (r.push_has_token ? ' (token terpasang)' : ' (token belum diisi)') : 'belum diatur'}`, `Menerima kiriman dari lokal: ${r.accepts ? '<b>aktif</b>' : 'nonaktif'}`,
    `Audit log: <code>${esc(r.audit)}</code> ${yn(r.audit_ok)}`].join('<br>'); if (r.push_url) $('#ps_url').value = r.push_url;
});
document.querySelector('[data-t=set]').addEventListener('click', loadSet); $('#setReload').onclick = loadSet;
$('#pwGo').onclick = guard(async () => { await api('change_password', {old: $('#pw_old').value, new: $('#pw_new').value}); $('#pw_old').value = $('#pw_new').value = ''; toast('Password berhasil diganti.'); });

/* ---- import ujian ---- */
const examRows = (tb, name) => r => $(tb).innerHTML = r.exams.map(x => `<tr><td><input type="radio" name="${name}" value="${x.id}"></td><td>${esc(x.title)}</td><td>${x.category}</td><td>${x.status}</td><td>${x.n} soal</td></tr>`).join('') || '<tr><td class="hint">Ujian tidak ditemukan.</td></tr>';
const pick = n => (document.querySelector(`input[name=${n}]:checked`) || {}).value;
function showDirs() {
  $('#dirs').innerHTML = Object.entries(META.dirs || {}).map(([k, v]) => `${k}: <code>${esc(v.path)}</code> ${v.ok ? '(siap)' : '(belum ada atau tidak bisa ditulis; dibuat otomatis saat import jika izin mengizinkan)'}`).join('<br>');
  $('#srcPanel').style.display = META.src ? '' : 'none';
}
$('#i_find').onclick = guard(async () => examRows('#i_tb', 'ip')(await api('exam_find', {source: 'src', q: $('#i_q').value})));
$('#x_find').onclick = guard(async () => examRows('#x_tb', 'xp')(await api('exam_find', {source: 'local', q: $('#x_q').value})));
const ST = {'disalin': 'disalin', 'sudah ada': 'sudah ada di server', 'akan disalin': 'akan disalin', 'sumber tidak ditemukan': '<b>file sumber tidak ditemukan</b>'};
function showImp(r, tgt) {
  const fl = r.files.map(f => `<tr><td>${f.kind}</td><td>${esc(f.name)}</td><td>${ST[f.status] || esc(f.status)}</td></tr>`).join(''), miss = r.files.filter(f => f.status === 'sumber tidak ditemukan').length;
  const head = r.files_only ? `${dry() ? 'Simulasi' : 'Selesai'}: sinkron file untuk “${esc(r.title)}”` : `${dry() ? 'Simulasi (di-rollback)' : 'Tersimpan'}: ujian “${esc(r.title)}” · token ${esc(r.token)} · status ${esc(r.status)}<br>${r.questions_new} soal baru, ${r.questions_reused} soal sudah ada (dipakai ulang), ${r.subjects_new} mapel baru, ${r.groups_new} grup baru. Peserta tidak ikut diimpor (ID user berbeda antar database), tambahkan di tab Ujian.`;
  $(tgt || '#impres').innerHTML = head + `<br>${r.files.length} file: ${miss ? miss + ' tidak ditemukan di sumber. Letakkan file itu lalu jalankan lagi dengan opsi Hanya sinkron file.' : 'semuanya tersedia.'}` + (fl ? `<div class="scroll" style="margin-top:8px"><table><thead><tr><th>Jenis</th><th>File</th><th>Status</th></tr></thead><tbody>${fl}</tbody></table></div>` : '');
  toast(done(r.title)); if (!dry()) loadMeta();
}
$('#i_go').onclick = guard(async () => {
  const id = pick('ip'); if (!id) throw new Error('Pilih ujian dari hasil pencarian dulu.'); if (!confirmLive('Import ujian terpilih beserta file-nya dari database sumber?')) return;
  showImp(await api('exam_import_src', {id: +id, status: $('#i_status').value, files_only: $('#i_fo').checked}));
});
$('#x_dl').onclick = guard(async () => {
  const id = pick('xp'); if (!id) throw new Error('Pilih ujian dulu.'); toast('Menyiapkan paket…');
  const r = await fetch('?api=exam_export_zip', {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF': CSRF}, body: JSON.stringify({id: +id})});
  if (!r.ok) { const j = await r.json().catch(() => ({})); throw new Error(j.error || 'Gagal membuat paket.'); }
  const miss = +r.headers.get('X-Missing') || 0, a = document.createElement('a');
  a.href = URL.createObjectURL(await r.blob()); a.download = 'ujian-' + id + '.cat.zip'; a.click();
  if (miss) toast(miss + ' file tidak ditemukan di folder server ini dan tidak masuk paket (lihat missing.txt di dalam ZIP).', true);
});
$('#x_go').onclick = guard(async () => {
  const f = $('#x_file').files[0]; if (!f) throw new Error('Pilih file paket .zip dulu.'); if (!confirmLive(`Import paket ${f.name} beserta file-nya?`)) return;
  const fd = new FormData(); fd.append('file', f); fd.append('dry_run', dry() ? '1' : '0'); fd.append('status', $('#i_status').value); fd.append('files_only', $('#i_fo').checked ? '1' : '0'); fd.append('created_by', BY);
  const r = await fetch('?api=exam_import_zip', {method: 'POST', headers: {'X-CSRF': CSRF}, body: fd}).then(x => x.json()).catch(() => ({ok: false, error: 'Respons server tidak valid (cek batas ukuran upload PHP).'}));
  if (!r.ok) throw new Error(r.error); showImp(r);
});

/* ---- PDF ---- */
let LK = '';
$('#pdfUp').onclick = guard(async () => {
  const fs = [...$('#pdf_files').files]; if (!fs.length) throw new Error('Pilih file PDF dulu.');
  if (!confirmLive(`Unggah ${fs.length} file PDF?`)) return;
  const res = [];
  for (const f of fs) {
    const fd = new FormData(); fd.append('file', f); fd.append('dry_run', dry() ? '1' : '0'); fd.append('overwrite', $('#pdf_ow').checked ? '1' : '0');
    try { const r = await fetch('?api=upload_pdf', {method: 'POST', headers: {'X-CSRF': CSRF}, body: fd}).then(x => x.json()); res.push(esc(f.name) + ': ' + esc(r.ok ? r.status : r.error)); }
    catch (e) { res.push(esc(f.name) + ': gagal koneksi'); }
  }
  $('#pdfres').innerHTML = res.join('<br>'); if (!dry()) $('#pdf_files').value = '';
});
$('#pdfDiff').onclick = guard(async () => {
  const r = await api('pdf_diff'); LK = '';
  $('#pdfdir').textContent = 'Folder: ' + r.dir + (r.dir_ok ? ' (bisa ditulis)' : ' (belum ada atau tidak bisa ditulis)') + ' · ' + r.total + ' file';
  $('#orN').textContent = r.orphans.length; $('#msN').textContent = r.missing.length;
  $('#orTb').innerHTML = r.orphans.map(f => `<tr><td><input type="radio" name="lk" value="${esc(f)}"></td><td>${esc(f)}</td></tr>`).join('') || '<tr><td class="hint">Semua file sudah tertaut.</td></tr>';
  $('#msTb').innerHTML = r.missing.map(m => `<tr><td>${m.kind} #${m.id}</td><td>${esc(m.label)}</td><td>${esc(m.file)}</td></tr>`).join('') || '<tr><td class="hint">Tidak ada referensi yang rusak.</td></tr>';
});
$('#orTb').onchange = e => { LK = e.target.value; $('#lk_sel').textContent = 'Dipilih: ' + LK; };
$('#lkGo').onclick = guard(async () => {
  if (!LK) throw new Error('Pilih file dari daftar dulu.'); if (!+$('#lk_id').value) throw new Error('Isi ID ujian/soal.');
  if (!confirmLive(`Tautkan ${LK} ke ${$('#lk_kind').value} #${$('#lk_id').value}?`)) return;
  const r = await api('link_pdf', {kind: $('#lk_kind').value, id: +$('#lk_id').value, file: LK}); toast(done(`${r.file} → ${r.kind} #${r.id}`));
});

setMode(); renderQ(); guard(loadMeta)().then(() => { renderQ(); showDirs(); });
</script></body></html>