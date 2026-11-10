<?php
/**
 * SETUP SOAL SAT MANDARIN KELAS XI GENAP
 * 40 Soal Pilihan Ganda - Soal Asli dari Guru
 * Dibuat untuk: wara.madina.q.parimora@guru.ubkn.sch.id
 */

// Tampilkan semua error untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use the shared environment and database configuration.
$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
        $config['db_user'], $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    die('<p style="color:red;font-family:sans-serif">Koneksi gagal: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// ============================================
// CARI USER GURU
// Coba beberapa kemungkinan username
// ============================================
$guruCandidates = [
    'wara.madina.q.parimora',
    'wara.madina',
    'waramadina',
    'wara',
];

$guru = null;
foreach ($guruCandidates as $candidate) {
    $stmtGuru = $pdo->prepare("SELECT id, full_name, role FROM users WHERE username = ? LIMIT 1");
    $stmtGuru->execute([$candidate]);
    $guru = $stmtGuru->fetch();
    if ($guru) break;
}

// Coba LIKE jika belum ketemu
if (!$guru) {
    $stmtGuru2 = $pdo->prepare("SELECT id, full_name, role FROM users WHERE username LIKE ? LIMIT 1");
    $stmtGuru2->execute(['%wara%']);
    $guru = $stmtGuru2->fetch();
}

// Coba cari dari full_name
if (!$guru) {
    $stmtGuru3 = $pdo->prepare("SELECT id, full_name, role FROM users WHERE full_name LIKE ? AND role IN ('guru','teacher','admin') LIMIT 1");
    $stmtGuru3->execute(['%Wara%']);
    $guru = $stmtGuru3->fetch();
}

if (!$guru) {
    // Fallback: pakai admin
    $guru = $pdo->query("SELECT id, full_name, role FROM users WHERE role='admin' LIMIT 1")->fetch();
    $fallbackAdmin = true;
} else {
    $fallbackAdmin = false;
}

$guruId   = $guru['id'];
$guruNama = $guru['full_name'];

// ============================================
// CARI ATAU BUAT SUBJECT "Bahasa Mandarin"
// ============================================
$stmtSubj = $pdo->prepare("SELECT id FROM subjects WHERE name LIKE '%Mandarin%' OR name LIKE '%mandarin%' LIMIT 1");
$stmtSubj->execute();
$subject = $stmtSubj->fetch();

if (!$subject) {
    $pdo->prepare("INSERT INTO subjects (name, description, created_at) VALUES (?, ?, NOW())")
        ->execute(['Bahasa Mandarin', 'Mata pelajaran Bahasa Mandarin']);
    $subjectId  = $pdo->lastInsertId();
    $subjectBaru = true;
} else {
    $subjectId  = $subject['id'];
    $subjectBaru = false;
}

// ============================================
// 40 SOAL ASLI - SAT MANDARIN XI GENAP
// Format: [no, pertanyaan, A, B, C, D, E, jawaban]
// ============================================
$soalPG = [

[1,
'Find the Pinyin of this Hanzi "这是谁的钱包?"',
'Zhè shì shéi de qiánbāo',
'Zhě shí shéi de qiān bào',
'Zhè shì shéi de qiān bào',
'Zhe shi shei de qian bao',
'Zhě shí shéi de qiān bào',
'A'],

[2,
'Kata "Itu" dengan keterangan menunjukkan sesuatu yang jauh dibawah ini adalah….?',
'那是',
'这个',
'这是',
'那个',
'哪个',
'D'],

[3,
'"___ 是我的学校" "___ Shì wǒ de xuéxiào" (Menjelaskan sekolah itu)',
'那是',
'这个',
'这是',
'那个',
'哪个',
'A'],

[4,
'"___ 苹果很好吃" "___ Píngguǒ hěn hào chī." (Menunjuk apel yang dekat)',
'那是',
'这个',
'这是',
'哪个',
'那个',
'B'],

[5,
'Kata "Ini" dengan keterangan untuk menjelaskan adalah….?',
'那是',
'这个',
'这是',
'那个',
'哪个',
'C'],

[6,
'这是我的___。This is my Teacher. Fill the blank of this sentence.',
'老人',
'佬市',
'老鼠',
'佬师',
'老师',
'E'],

[7,
'这是我的好朋友。Zhè shì wǒ de hǎo péngyǒu.',
'Ini adalah teman baikku.',
'Ini adalah temanku.',
'Itu adalah teman baikku',
'Semua salah',
'Itu temanku',
'A'],

[8,
'Find the meaning of this word "钱包"',
'Buku',
'Dompet',
'Tas',
'Tukar',
'Uang',
'B'],

[9,
'Kata "的" dalam kalimat "这是谁的钱包？" berfungsi untuk …',
'Menunjukkan pertanyaan',
'Menunjukkan waktu',
'Menunjukkan kepunyaan',
'Semua benar',
'Menunjukkan tempat',
'C'],

[10,
'Susunan kata yang benar dibawah ini adalah …',
'谁这是钱包的?',
'钱包谁的是这?',
'这是谁钱包的？',
'这是谁的钱包？',
'Semua salah',
'D'],

[11,
'Mata uang negara cina adalah…',
'远',
'员',
'元',
'园',
'沅',
'C'],

[12,
'Find the Hanzi of this word "Credit Card"',
'信拥卡',
'新佣咔',
'新用咔',
'信用垰',
'信用卡',
'E'],

[13,
'"连忙" artinya adalah……?',
'Sangat lambat',
'Bersama-sama',
'Segera / Buru-buru',
'Sangat dekat',
'Sangat jauh',
'C'],

[14,
'我看我的好朋友，我____叫他。Wǒ kàn wǒ de hǎo péngyǒu, wǒ____ jiào tā',
'一起',
'连忙',
'很少',
'连芒',
'已经',
'B'],

[15,
'A: 这是什么？B: 这是 a CD. Find the hanzi of "a cd"',
'一个光盘',
'一只光盘',
'一张光盘',
'一长光盘',
'一本光盘',
'C'],

[16,
'Find the pinyin from this word "idea/plan"',
'Zhǔ yì',
'Zhǔ yiè',
'Zhù yì',
'Zhu yi',
'Zǔ yì',
'A'],

[17,
'钱包里有多少钱？Qiánbāo Lǐ yǒu duōshǎo qián? Pada kalimat di atas terdapat kata "里 Lǐ" yang artinya adalah…?',
'Suka',
'Depan',
'Luar',
'Belakang',
'Dalam',
'E'],

[18,
'Find the pinyin from this hanzi "钥匙"',
'Yàoshí',
'Yāoshi',
'Yuèshi',
'Yaoshi',
'Yàoshi',
'E'],

[19,
'Find the meaning from this pinyin "Jǐng chá"….?',
'Doctor',
'Police officer',
'Driver',
'Soldier',
'Teacher',
'B'],

[20,
'Find the Pinyin from this sentence "how much the price"',
'Jiāgé duōshǎo',
'Jiàgé duōshào',
'Jiàgé duōshǎo',
'Jiege duoshao',
'Jiàgě duōshǎo',
'C'],

[21,
'Hanzi dari kata "Rupiah" dalam Bahasa mandarin adalah…?',
'吨',
'都',
'盾',
'者',
'遁',
'C'],

[22,
'Hanzi dari kata "uang" adalah…?',
'包',
'钱',
'书',
'浅',
'水',
'B'],

[23,
'Pengulangan kata kerja hanya untuk memberikan nuansa tertentu tanpa mengubah arti sebenarnya disebut…?',
'Reduplication of adjective',
'Reduplication',
'Reduplication of word',
'Reduplication of verb',
'Reduplication of verb and word',
'D'],

[24,
'Perhatikan kalimat ini "我看看一下儿" metode kalimat di atas adalah…',
'AA',
'AB一AB',
'A一A',
'ABAB',
'Semua salah',
'A'],

[25,
'Satuan kata untuk baju adalah...?',
'张',
'条',
'件',
'个',
'本',
'C'],

[26,
'Dibawah ini yang menyatakan "Sepasang" adalah…?',
'双',
'友',
'又',
'戏',
'欢',
'A'],

[27,
'她又聪明又漂亮。Tā yòu cōngmíng yòu piàoliang. Berdasarkan kalimat di atas, carilah rumusnya dibawah ini…?',
'S+又+adj+又+verb',
'S+又+verb1+又+verb2',
'S+adj1+adj2',
'S+又+adj1+又+adj2',
'S+adj1+又+adj2+又',
'D'],

[28,
'Penggunaan kata "atau" yang digunakan untuk pertanyaan dibawah ini adalah….?',
'或者',
'或啫',
'还有',
'还没',
'还是',
'E'],

[29,
'Kata "或者" digunakan untuk….?',
'Menyatakan pilihan dalam kalimat pernyataan',
'Menyatakan kepemilikan',
'Menyatakan pertanyaan pilihan',
'Menyatakan waktu',
'Menyatakan cinta :)',
'A'],

[30,
'我喜欢她......她不喜欢我。Wǒ xǐhuān tā...Tā bù xǐhuān wǒ. Isilah titik titik di atas dengan memilih salah satu yang benar!',
'不杯',
'可以',
'可室',
'担是',
'但是',
'E'],

[31,
'A: 你好，这个咖啡价格多少钱? Nǐ hǎo, zhège kāfēi jiàgé duōshao qián?
B: 你好，这一杯咖啡是四万六千盾。Nǐ hǎo, zhè yì bēi kāfēi shì Sì wàn liù qiān dùn
Berapa harga 1 gelas kopi pada dialogue tersebut..?',
'Rp 48.000',
'Rp 46.000',
'Rp 36.000',
'Rp 53.000',
'Rp 30.800',
'B'],

[32,
'A: 哇，很贵呀，可以便宜吗? Wa, hěn guì ya, kěyǐ piányi ma?
B: 对不起，不可以。Duìbuqǐ, bù kěyǐ.
Arti dari kata "贵 guì" pada dialogue adalah…?',
'Mahal',
'Enak',
'Murah',
'Baru',
'Sedang',
'A'],

[33,
'A: 那有咖啡便宜一点吗？Nà yǒu kāfēi piányi yìdiǎn ma?
B: 有, 卡布奇诺更便宜, 一杯二万七千盾。Yǒu, kǎbùqínuò gèng piányi, yì bēi èr wàn qī qiān dùn.
Arti dari kata "便宜 piányi" pada dialogue adalah…?',
'Mahal',
'Enak',
'Murah',
'Baru',
'Sedang',
'C'],

[34,
'Jika turun hujan dan hendak keluar maka kita harus menggunakan...? (payung)',
'帽子',
'雨伞',
'雨金',
'鞋子',
'雨余',
'B'],

[35,
'10.000 + 2.000 =…?',
'二万一千',
'一万三千',
'一万两千',
'二万两千',
'一万',
'C'],

[36,
'A: 那有咖啡便宜一点吗？Nà yǒu kāfēi piányi yìdiǎn ma?
B: 有, 卡布奇诺更便宜, 一杯二万七千盾。Yǒu, kǎbùqínuò gèng piányi, yì bēi èr wàn qī qiān dùn.
A: 那我点五杯卡布奇诺，给你钱！Nà wǒ diǎn wǔ bēi kǎbùqínuò, gěi nǐ qián!
B: 好的,你点五杯卡布奇诺对吗？Hǎo de, nǐ diǎn wǔ bēi kǎbùqínuò duì ma?
A: 对，谢谢。Duì, xièxie.
Berapa total belanjaan yang harus di bayarnya pada kalimat di atas…?',
'三十万五千盾',
'十三万五千盾',
'二万七千盾',
'十五万三千盾',
'三十五万七千盾',
'B'],

[37,
'Find the Pinyin from this sentence "这件衣服质量很好看"',
'Zhè jiàn yīfu zhǐliàng hěn hǎokàn',
'Zhè jiān yīfu zhìliàng hěn hǎokàn',
'Zhè jiàn yīfú zhìliàng hěn hǎokàn',
'Zhe jian yifu zhiliang hen haokàn',
'Zhè jiàn yīfu zhìliàng hěn hǎokàn',
'E'],

[38,
'B: 好的,你点五杯卡布奇诺对吗？Hǎo de, nǐ diǎn wǔ bēi kǎbùqínuò duì ma?
A: 对，谢谢。Duì, xièxie.
Jenis kopi apakah yang di belinya…?',
'Americano',
'Espresso',
'Caffe latte',
'Macchiato',
'Cappuccino',
'E'],

[39,
'我想买手机但是很贵, 价格是左右 Rp 9.586.600
Wǒ xiǎng mǎi shǒujī dànshì hěn guì, jiàgé shì zuǒyòu Rp 9.586.600
Bahasa mandarin dari harga yang di cetak tebal (Rp 9.586.600) adalah…?',
'九百五八万六千六是六百盾',
'九十五万八千六百六十盾',
'九千五百八十六万六千盾',
'九百五十八万六千六百盾',
'九百万盾',
'D'],

[40,
'Zhang Linghe membeli pensil dengan harga Rp.2500 dan membeli roti seharga Rp.5300. Total belanjaannya adalah…?',
'二千五百盾',
'五千三百盾',
'六千盾',
'七千八百盾',
'七万八千盾',
'D'],

];

// ============================================
// INSERT SOAL KE DATABASE
// ============================================
$insertQ = $pdo->prepare("
    INSERT INTO questions (
        subject_id, type, question_text,
        option_a, option_b, option_c, option_d, option_e,
        correct_answer, points, created_by, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$pdo->beginTransaction();

$questionIds = [];
$errors      = [];

foreach ($soalPG as $soal) {
    try {
        $insertQ->execute([
            $subjectId,
            'multiple_choice',
            $soal[1],  // pertanyaan
            $soal[2],  // A
            $soal[3],  // B
            $soal[4],  // C
            $soal[5],  // D
            $soal[6],  // E
            $soal[7],  // jawaban benar
            1,         // 1 poin per soal
            $guruId
        ]);
        $questionIds[] = $pdo->lastInsertId();
    } catch (Exception $e) {
        $errors[] = "Soal #{$soal[0]}: " . $e->getMessage();
    }
}

if (!empty($errors)) {
    $pdo->rollBack();
    echo '<div style="font-family:sans-serif;max-width:700px;margin:40px auto;padding:20px;background:#fee2e2;border-radius:8px">';
    echo '<h3 style="color:#991b1b">❌ Gagal insert soal</h3>';
    foreach ($errors as $err) echo '<p>' . htmlspecialchars($err) . '</p>';
    echo '</div>';
    exit;
}

$pdo->commit();

$totalSoal = count($questionIds);

// ============================================
// TAMPILKAN HASIL
// ============================================
$kunciJawaban = ['A','D','A','B','C','E','A','B','C','D','C','E','C','B','C','A','E','E','B','C','C','B','D','A','C','A','D','E','A','E','B','A','C','B','C','B','E','E','D','D'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup SAT Mandarin XI Genap</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 30px; }
.wrap { max-width: 800px; margin: 0 auto; }
.card { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.1); margin-bottom: 20px; }
.header { background: linear-gradient(135deg, #b91c1c, #991b1b); padding: 26px 32px; color: white; }
.header h2 { font-size: 1.4rem; margin-bottom: 5px; }
.header p  { opacity: .85; font-size: .9rem; }
.body  { padding: 26px 32px; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 22px; }
.info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 15px; }
.info-box label { display: block; font-size: .73rem; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3px; }
.info-box span  { font-size: 1rem; font-weight: 600; color: #1e293b; }
.sec-title { font-size: .82rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .07em; margin: 18px 0 9px; }
table { width: 100%; border-collapse: collapse; font-size: .86rem; }
thead th { background: #f1f5f9; padding: 9px 12px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
tbody tr:nth-child(even) { background: #f8fafc; }
tbody td { padding: 8px 12px; color: #334155; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.jwb { font-weight: 700; color: #16a34a; background: #dcfce7; border-radius: 4px; padding: 1px 8px; font-size: .82rem; }
.note-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 15px 18px; margin-top: 18px; }
.note-box h4 { color: #1d4ed8; margin-bottom: 8px; font-size: .92rem; }
.note-box ul { padding-left: 18px; color: #1e40af; font-size: .88rem; line-height: 2; }
.btn { display: inline-block; padding: 10px 22px; border-radius: 8px; font-size: .9rem; font-weight: 600; text-decoration: none; margin-top: 16px; margin-right: 8px; }
.btn-red  { background: #dc2626; color: white; }
.btn-gray { background: #e2e8f0; color: #475569; }
.warn { background: #fefce8; border: 1px solid #fde047; border-radius: 8px; padding: 10px 14px; font-size: .85rem; color: #92400e; margin-bottom: 14px; }
.meta { margin-top: 20px; font-size: .78rem; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 12px; line-height: 1.8; }
</style>
</head>
<body>
<div class="wrap">

<div class="card">
  <div class="header">
    <h2>✅ 40 Soal SAT Mandarin XI Genap Berhasil Disimpan!</h2>
    <p>Soal asli dari guru telah masuk ke Bank Soal sistem CAT</p>
  </div>
  <div class="body">

    <?php if ($fallbackAdmin): ?>
    <div class="warn">
      ⚠️ Akun guru <b>wara.madina.q.parimora@guru.ubkn.sch.id</b> tidak ditemukan.
      Soal disimpan menggunakan akun admin: <b><?= htmlspecialchars($guruNama) ?></b>.
      Silakan hubungi administrator untuk memindahkan kepemilikan soal.
    </div>
    <?php endif; ?>

    <div class="grid2">
      <div class="info-box"><label>Mata Pelajaran</label><span>Bahasa Mandarin</span></div>
      <div class="info-box"><label>Jenis Ujian</label><span>SAT Semester Genap</span></div>
      <div class="info-box"><label>Kelas</label><span>XI (Sebelas)</span></div>
      <div class="info-box"><label>Dibuat Oleh</label><span><?= htmlspecialchars($guruNama) ?></span></div>
      <div class="info-box"><label>Total Soal</label><span><?= $totalSoal ?> soal PG</span></div>
      <div class="info-box"><label>Total Poin</label><span><?= $totalSoal ?> poin (1 poin/soal)</span></div>
      <div class="info-box"><label>Subject ID</label><span><?= $subjectId ?> <?= $subjectBaru ? '🆕 (baru dibuat)' : '(sudah ada)' ?></span></div>
      <div class="info-box"><label>ID Soal Pertama</label><span><?= $questionIds[0] ?? '-' ?></span></div>
    </div>

    <div class="sec-title">Daftar Soal &amp; Kunci Jawaban</div>
    <table>
      <thead>
        <tr>
          <th style="width:40px">No</th>
          <th>Pertanyaan</th>
          <th style="width:60px">Jwb</th>
          <th style="width:60px">DB ID</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($soalPG as $i => $soal):
            $teks = mb_substr(strip_tags($soal[1]), 0, 90);
            if (mb_strlen(strip_tags($soal[1])) > 90) $teks .= '…';
        ?>
        <tr>
          <td><?= $soal[0] ?></td>
          <td><?= htmlspecialchars($teks) ?></td>
          <td><span class="jwb"><?= $soal[7] ?></span></td>
          <td style="color:#94a3b8"><?= $questionIds[$i] ?? '-' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="note-box">
      <h4>📋 Langkah Selanjutnya:</h4>
      <ul>
        <li>Klik <b>"Lihat Bank Soal"</b> untuk melihat semua soal yang sudah masuk</li>
        <li>Buat ujian baru via menu <b>Ujian → Buat Ujian</b></li>
        <li>Pilih subject <b>Bahasa Mandarin</b> lalu assign 40 soal ini</li>
        <li>Atur tanggal ujian, durasi, dan peserta (Kelas XI)</li>
        <li>Publish ujian ketika sudah siap</li>
      </ul>
    </div>

    <div>
      <a href="question" class="btn btn-red">📚 Lihat Bank Soal</a>
      <a href="exam/create" class="btn btn-gray">➕ Buat Ujian SAT</a>
    </div>

    <div class="meta">
      IDs soal: <?= implode(', ', $questionIds) ?><br>
      Subject ID: <?= $subjectId ?> &nbsp;|&nbsp; Guru ID: <?= $guruId ?>
    </div>

  </div>
</div>

</div>
</body>
</html>
