<?php
/**
 * BUAT UJIAN BARU — SAT MANDARIN KELAS XI GENAP
 * 40 soal persis dari guru, tidak diubah sama sekali
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$subjectId = 7; // Bahasa Mandarin

$guru = $pdo->query("SELECT id FROM users WHERE username LIKE '%wara%' LIMIT 1")->fetch();
if (!$guru) $guru = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
$guruId = $guru['id'];

// Kelas XI
$kelasXI = ['XI-CUTNYAKDIEN','XI-DEWANTARA','XI-KARTINI','XI-SUDIRMAN'];

// ============================================================
// 40 SOAL PERSIS DARI GURU — TIDAK DIUBAH
// ============================================================
$soal = [
[1,
'Find the Pinyin of this Hanzi "这是谁的钱包?"',
'Zhè shì shéi de qiánbāo','Zhě shí shéi de qiān bào','Zhè shì shéi de qiān bào','Zhe shi shei de qian bao','Zhě shí shéi de qiān bào','A'],

[2,
'Kata "Itu" dengan keterangan menunjukkan sesuatu yang jauh dibawah ini adalah….?',
'那是','这个','这是','那个','哪个','D'],

[3,
'"___ 是我的学校" "___Shì wǒ de xuéxiào" (Menjelaskan sekolah itu)',
'那是','这个','这是','那个','哪个','A'],

[4,
'"___ 苹果很好吃" "__ Píngguǒ hěn hào chī." (Menunjuk apel yang dekat)',
'那是','这个','这是','哪个','那个','B'],

[5,
'kata "Ini" dengan keterangan untuk menjelaskan adalah….?',
'那是','这个','这是','那个','哪个','C'],

[6,
'这是我的___。This is my Teacher. Fill the blank of this sentence.',
'老人','佬市','老鼠','佬师','老师','E'],

[7,
'Kata "的" dalam kalimat "这是谁的钱包？" berfungsi untuk …',
'Menunjukkan pertanyaan','menunjukkan waktu','Menunjukkan kepunyaan','Semua benar','Menunjukkan tempat','C'],

[8,
'Susunan kata yang benar dibawah ini adalah …',
'谁这是钱包的?','钱包谁的是这?','这是谁钱包的？','这是谁的钱包？','Semua salah','D'],

[9,
'Mata uang negara cina adalah…',
'远','员','元','园','沅','C'],

[10,
'我看我的好朋友，我____叫他。Wǒ kàn wǒ de hǎo péngyǒu, wǒ____ jiào tā',
'一起','连忙','很少','连芒','已经','B'],

[11,
'A : 这是什么？B : 这是 a CD. Find the hanzi of "a cd"',
'一个光盘','一只光盘','一张光盘','一长光盘','一本光盘','C'],

[12,
'Find the pinyin from this word "idea/plan"',
'Zhǔ yì','Zhǔ yiè','Zhù yì','Zhu yi','Zǔ yì','A'],

[13,
'Find the pinyin from this hanzi "钥匙"',
'Yàoshí','yāoshi','Yuèshi','yaoshi','Yàoshi','E'],

[14,
'Find the meaning from this pinyin "Jǐng chá"….?',
'Doctor','Police officer','Driver','Soldier','Teacher','B'],

[15,
'Find the Pinyin from this sentence "how much the price"',
'Jiāgé duōshǎo','Jiàgé duōshào','Jiàgé duōshǎo','jiege duoshao','Jiàgě duōshǎo','C'],

[16,
'Hanzi dari kata "Rupiah" dalam Bahasa mandarin adalah…?',
'吨','都','盾','者','遁','C'],

[17,
'Hanzi dari kata "uang" adalah…?',
'包','钱','书','浅','水','B'],

[18,
'Pengulangan kata kerja hanya untuk memberikan nuansa tertentu tanpa mengubah arti sebenarnya disebut…?',
'Reduplication of adjective','Reduplication','Reduplication of word','Reduplication of verb','reduplication of verb and word','D'],

[19,
'satuan kata untuk baju adalah...?',
'张','条','件','个','本','C'],

[20,
'她又聪明又漂亮。Tā yòu cōngmíng yòu piàoliang. Berdasarkan kalimat di atas, carilah rumusnya dibawah ini…?',
'S+又+adj+又+verb','S+又+verb1+又+verb2','S+adj1+adj2','S+又+adj1+又+adj2','S+adj1+又+adj2+又','D'],

[21,
'Penggunaan kata "atau" yang digunakan untuk pertanyaan dibawah ini adalah….?',
'或者','或啫','还有','还没','还是','E'],

[22,
'Kata "或者" digunakan untuk….?',
'Menyatakan pilihan dalam kalimat pernyataan','Menyatakan kepemilikan','Menyatakan pertanyaan pilihan','Menyatakan waktu','Menyatakan cinta','A'],

[23,
'我喜欢她……她不喜欢我。Wǒ xǐhuān tā...Tā bù xǐhuān wǒ. isilah titik titik di atas dengan memilih salah satu yang benar…!',
'不杯','可以','可室','担是','但是','E'],

[24,
'A: 哇，很贵呀，可以便宜吗? Wa, hěn guì ya, kěyǐ piányi ma?
B: 对不起，不可以。Duìbuqǐ, bù kěyǐ.
Arti dari kata "贵 guì" pada dialogue adalah…?',
'Mahal','Enak','Murah','Baru','Sedang','A'],

[25,
'Jika turun hujan dan hendak keluar maka kita harus menggunakan…? (payung)',
'帽子','雨伞','雨金','鞋子','雨余','B'],

[26,
'Find the Pinyin from this sentence "这件衣服质量很好看"',
'Zhè jiàn yīfu zhǐliàng hěn hǎokàn','Zhè jiān yīfu zhìliàng hěn hǎokàn','Zhè jiàn yīfú zhìliàng hěn hǎokàn','Zhe jian yifu zhiliang hen haokàn','Zhè jiàn yīfu zhìliàng hěn hǎokàn','E'],

[27,
'B: 好的,你点五杯卡布奇诺对吗？Hǎo de, nǐ diǎn wǔ bēi kǎbùqínuò duì ma?
A: 对，谢谢。Duì, xièxie.
Jenis kopi apakah yang di belinya…?',
'Americano','Espresso','Caffe latte','Macchiato','Cappuccino','E'],

[28,
'我想买手机但是很贵, 价格是左右 Rp 9.586.600. Wǒ xiǎng mǎi shǒujī dànshì hěn guì, jiàgé shì zuǒyòu Rp 9.586.600. Bahasa mandarin dari harga yang di cetak tebal adalah…?',
'九百五八万六千六是六百盾','九十五万八千六百六十盾','九千五百八十六万六千盾','九百五十八万六千六百盾','九百万盾','D'],

[29,
'这是我的好…….。Zhè shì wǒ de hǎo péngyǒu. Carilah aksara dari pinyin péngyǒu …?',
'朋友','朋反','棚友','明反','堋又','A'],

[30,
'Find the meaning of this word "钱包"',
'Book','Wallet','Bag','Change','Money','B'],

[31,
'Find the Hanzi of this Pinyin "Xìn yòng kǎ"',
'信拥卡','新佣咔','信用卡','新用咔','信用垰','C'],

[32,
'Find the meaning of this Hanzi "连忙"',
'Calmly','Together','Slowly','Hurriedly','Unhurriedly','D'],

[33,
'钱包里有多少钱？Qiánbāo Lǐ yǒu duōshǎo qián? Find the meaning from this word "里 Lǐ"…..?',
'Like','Front','Behind','outside','Inside','E'],

[34,
'Perhatikan kalimat ini "我要学习学习一下儿" metode kalimat di atas adalah…',
'AA','AB一AB','A一A','ABAB','Benar semua','D'],

[35,
'Which of the following means "a pair"?',
'双','友','又','戏','欢','A'],

[36,
'A: 你好，这个咖啡价格多少钱? Nǐ hǎo, zhège kāfēi jiàgé duōshao qián?
B: 你好，这一杯咖啡是四万六千盾。Nǐ hǎo, zhè yì bēi kāfēi shì Sì wàn liù qiān dùn.
How much is the price one cup of coffee in the dialogue?',
'Rp 48.000','Rp 46.000','Rp 36.000','Rp 53.000','Rp 30.800','B'],

[37,
'80.000 + 7.200 =…?',
'人方七千两白盾','八万七干俩白盾','八方七千俩百盾','人万七干兩百盾','八万七千两百盾','E'],

[38,
'A: 那有咖啡便宜一点吗？Nà yǒu kāfēi piányi yìdiǎn ma?
B: 有, 卡布奇诺更便宜, 一杯三万七千盾。Yǒu, kǎbùqínuò gèng piányi, yì bēi Sān wàn qī qiān dùn.
The meaning of this word "便宜 piányi" is?',
'Expensive','Cheap','Pricey','Overpriced','Moderate','B'],

[39,
'Zhang Linghe membeli pensil dengan harga Rp.3300 dan membeli roti seharga Rp.6500. Total belanjaannya adalah…?',
'二千五百盾','九干八白盾','九千八百盾','七千八百盾','七万八千盾','C'],

[40,
'A: 那有咖啡便宜一点吗？Nà yǒu kāfēi piányi yìdiǎn ma?
B: 有, 卡布奇诺更便宜, 一杯三万七千盾。Yǒu, kǎbùqínuò gèng piányi, yì bēi Sān wàn qī qiān dùn.
A: 那我点七杯卡布奇诺，给你钱！Nà wǒ diǎn qī bēi kǎbùqínuò, gěi nǐ qián!
B: 好的,你点七杯卡布奇诺对吗？Hǎo de, nǐ diǎn qī bēi kǎbùqínuò duì ma?
A: 对，谢谢。Duì, xièxie.
How much is the total amount of the shopping that must be paid in the sentence above?',
'两十万九千盾','十三万五千盾','二十五万九千盾','二五十万久千盾','三十五万七千盾','C'],
];

$action = $_GET['action'] ?? 'preview';

if ($action === 'buat') {
    $pdo->beginTransaction();
    try {
        // Insert soal ke bank soal
        $ins = $pdo->prepare("
            INSERT INTO questions (subject_id, type, question_text,
                option_a, option_b, option_c, option_d, option_e,
                correct_answer, points, created_by, created_at)
            VALUES (?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ");

        $qIds = [];
        foreach ($soal as $s) {
            $ins->execute([$subjectId, $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $guruId]);
            $qIds[] = $pdo->lastInsertId();
        }

        // Buat ujian baru
        $token = 'SAT-XI-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $pdo->prepare("
            INSERT INTO exams (title, category, subject_id, duration,
                start_time, end_time, token, randomize_questions,
                exam_type, status, created_by, created_at)
            VALUES ('SAT MANDARIN KELAS XI GENAP', 'reguler', ?, 90,
                '2026-06-09 07:30:00', '2026-06-09 09:00:00',
                ?, 0, 'single', 'published', ?, NOW())
        ")->execute([$subjectId, $token, $guruId]);
        $examId = $pdo->lastInsertId();

        // Assign soal ke ujian
        $insEQ = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, order_num) VALUES (?, ?, ?)");
        foreach ($qIds as $i => $qId) {
            $insEQ->execute([$examId, $qId, $i + 1]);
        }

        // Assign peserta kelas XI
        $insP = $pdo->prepare("INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?, ?)");
        $totalPeserta = 0;
        foreach ($kelasXI as $kelas) {
            $siswa = $pdo->prepare("SELECT id FROM users WHERE kelas = ? AND role IN ('siswa','student')");
            $siswa->execute([$kelas]);
            foreach ($siswa->fetchAll() as $s) {
                $insP->execute([$examId, $s['id']]);
                $totalPeserta++;
            }
        }

        $pdo->commit();
        $success = ['examId' => $examId, 'soal' => count($qIds), 'peserta' => $totalPeserta];
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Buat Ujian SAT Mandarin XI Genap</title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Segoe UI',sans-serif; background:#f1f5f9; padding:24px; }
.wrap { max-width:820px; margin:0 auto; }
.card { background:#fff; border-radius:10px; padding:22px 26px; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
.success-box { background:#dcfce7; border:1px solid #86efac; border-radius:8px; padding:18px 22px; color:#166534; line-height:2; margin-bottom:16px; }
.error-box { background:#fee2e2; border:1px solid #fca5a5; border-radius:8px; padding:16px; color:#991b1b; margin-bottom:16px; }
.warn-box { background:#fef9c3; border:1px solid #fde047; border-radius:8px; padding:12px 16px; color:#92400e; font-size:.9rem; margin-bottom:14px; }
table { width:100%; border-collapse:collapse; font-size:.83rem; }
th { background:#f1f5f9; padding:8px 10px; text-align:left; color:#475569; border-bottom:2px solid #e2e8f0; }
td { padding:7px 10px; border-bottom:1px solid #f1f5f9; color:#334155; vertical-align:top; }
tr:nth-child(even) { background:#f8fafc; }
.jwb { font-weight:700; color:#16a34a; background:#dcfce7; border-radius:4px; padding:1px 7px; font-size:.8rem; }
.btn { display:inline-block; padding:11px 26px; border-radius:8px; font-weight:600; font-size:.95rem; text-decoration:none; margin-top:14px; margin-right:8px; }
.btn-red { background:#dc2626; color:white; }
.btn-green { background:#16a34a; color:white; }
.btn-gray { background:#e2e8f0; color:#475569; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  <b style="font-size:1.15rem">✅ Ujian Berhasil Dibuat!</b><br>
  Ujian ID: <b><?= $success['examId'] ?></b> — SAT MANDARIN KELAS XI GENAP<br>
  Total soal: <b><?= $success['soal'] ?> soal</b><br>
  Total peserta: <b><?= $success['peserta'] ?> siswa</b> (kelas XI)<br>
  Status: <b>Published</b>
</div>
<a href="exam/edit/<?= $success['examId'] ?>" class="btn btn-green">→ Edit Tanggal Ujian</a>
<a href="exam" class="btn btn-gray">→ Daftar Ujian</a>

<?php elseif (isset($error)): ?>
<div class="error-box">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview' || isset($error)): ?>
<div class="card">
  <h2 style="margin-bottom:12px">📝 Buat Ujian SAT MANDARIN KELAS XI GENAP</h2>
  <div class="warn-box">
    40 soal persis dari guru akan dimasukkan ke bank soal dan ujian baru.<br>
    Peserta: kelas XI-CUTNYAKDIEN, XI-DEWANTARA, XI-KARTINI, XI-SUDIRMAN
  </div>

  <div style="max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:14px">
  <table>
    <tr><th>#</th><th>Pertanyaan</th><th>A</th><th>B</th><th>Jwb</th></tr>
    <?php foreach ($soal as $s): ?>
    <tr>
      <td><?= $s[0] ?></td>
      <td><?= htmlspecialchars(mb_substr($s[1], 0, 65)) ?>...</td>
      <td><?= htmlspecialchars(mb_substr($s[2], 0, 20)) ?></td>
      <td><?= htmlspecialchars(mb_substr($s[3], 0, 20)) ?></td>
      <td><span class="jwb"><?= $s[7] ?></span></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>

  <a href="?action=buat"
     class="btn btn-red"
     onclick="return confirm('Buat ujian SAT Mandarin XI Genap dengan 40 soal ini?')">
    ✅ BUAT UJIAN SEKARANG
  </a>
  <a href="exam" class="btn btn-gray">Batal</a>
</div>
<?php endif; ?>

</div>
</body>
</html>
