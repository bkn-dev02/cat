<?php
/**
 * REBUILD SAT MANDARIN XI GENAP
 * 1. Hapus ujian lama (ID 338) + soal campuran yang ter-assign
 * 2. Hapus soal lama (ID 4084–4158) dari bank soal
 * 3. Insert ulang 40 soal asli dari guru
 * 4. Buat ujian baru + assign soal + assign peserta kelas XI
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$examIdLama = 338;
$soalLamaIds = range(4084, 4158); // soal lama (tidak semua soal Anda, campuran)

// Ambil info ujian lama
$examLama = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$examLama->execute([$examIdLama]);
$examLamaData = $examLama->fetch();

// Ambil peserta ujian lama
$pesertaLama = $pdo->prepare("SELECT user_id FROM exam_participants WHERE exam_id = ?");
$pesertaLama->execute([$examIdLama]);
$pesertaIds = array_column($pesertaLama->fetchAll(), 'user_id');

// Cari subject Mandarin
$subj = $pdo->query("SELECT id FROM subjects WHERE name LIKE '%Mandarin%' LIMIT 1")->fetch();
$subjectId = $subj['id']; // 7

// Cari user guru/admin untuk created_by
$guru = $pdo->query("SELECT id FROM users WHERE username LIKE '%wara%' LIMIT 1")->fetch();
if (!$guru) $guru = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
$guruId = $guru['id'];

// ============================================
// 40 SOAL ASLI SAT MANDARIN XI GENAP
// ============================================
$soalPG = [
[1,'Find the Pinyin of this Hanzi "这是谁的钱包?"','Zhè shì shéi de qiánbāo','Zhě shí shéi de qiān bào','Zhè shì shéi de qiān bào','Zhe shi shei de qian bao','Zhě shí shéi de qiān bào','A'],
[2,'Kata "Itu" dengan keterangan menunjukkan sesuatu yang jauh dibawah ini adalah….?','那是','这个','这是','那个','哪个','D'],
[3,'"___ 是我的学校" "___ Shì wǒ de xuéxiào" (Menjelaskan sekolah itu)','那是','这个','这是','那个','哪个','A'],
[4,'"___ 苹果很好吃" "___ Píngguǒ hěn hào chī." (Menunjuk apel yang dekat)','那是','这个','这是','哪个','那个','B'],
[5,'Kata "Ini" dengan keterangan untuk menjelaskan adalah….?','那是','这个','这是','那个','哪个','C'],
[6,'这是我的___。This is my Teacher. Fill the blank of this sentence.','老人','佬市','老鼠','佬师','老师','E'],
[7,'这是我的好朋友。Zhè shì wǒ de hǎo péngyǒu.','Ini adalah teman baikku.','Ini adalah temanku.','Itu adalah teman baikku','Semua salah','Itu temanku','A'],
[8,'Find the meaning of this word "钱包"','Buku','Dompet','Tas','Tukar','Uang','B'],
[9,'Kata "的" dalam kalimat "这是谁的钱包？" berfungsi untuk …','Menunjukkan pertanyaan','Menunjukkan waktu','Menunjukkan kepunyaan','Semua benar','Menunjukkan tempat','C'],
[10,'Susunan kata yang benar dibawah ini adalah …','谁这是钱包的?','钱包谁的是这?','这是谁钱包的？','这是谁的钱包？','Semua salah','D'],
[11,'Mata uang negara cina adalah…','远','员','元','园','沅','C'],
[12,'Find the Hanzi of this word "Credit Card"','信拥卡','新佣咔','新用咔','信用垰','信用卡','E'],
[13,'"连忙" artinya adalah……?','Sangat lambat','Bersama-sama','Segera / Buru-buru','Sangat dekat','Sangat jauh','C'],
[14,'我看我的好朋友，我____叫他。Wǒ kàn wǒ de hǎo péngyǒu, wǒ____ jiào tā','一起','连忙','很少','连芒','已经','B'],
[15,'A: 这是什么？B: 这是 a CD. Find the hanzi of "a cd"','一个光盘','一只光盘','一张光盘','一长光盘','一本光盘','C'],
[16,'Find the pinyin from this word "idea/plan"','Zhǔ yì','Zhǔ yiè','Zhù yì','Zhu yi','Zǔ yì','A'],
[17,'钱包里有多少钱？Qiánbāo Lǐ yǒu duōshǎo qián? Pada kalimat di atas terdapat kata "里 Lǐ" yang artinya adalah…?','Suka','Depan','Luar','Belakang','Dalam','E'],
[18,'Find the pinyin from this hanzi "钥匙"','Yàoshí','Yāoshi','Yuèshi','Yaoshi','Yàoshi','E'],
[19,'Find the meaning from this pinyin "Jǐng chá"….?','Doctor','Police officer','Driver','Soldier','Teacher','B'],
[20,'Find the Pinyin from this sentence "how much the price"','Jiāgé duōshǎo','Jiàgé duōshào','Jiàgé duōshǎo','Jiege duoshao','Jiàgě duōshǎo','C'],
[21,'Hanzi dari kata "Rupiah" dalam Bahasa mandarin adalah…?','吨','都','盾','者','遁','C'],
[22,'Hanzi dari kata "uang" adalah…?','包','钱','书','浅','水','B'],
[23,'Pengulangan kata kerja hanya untuk memberikan nuansa tertentu tanpa mengubah arti sebenarnya disebut…?','Reduplication of adjective','Reduplication','Reduplication of word','Reduplication of verb','Reduplication of verb and word','D'],
[24,'Perhatikan kalimat ini "我看看一下儿" metode kalimat di atas adalah…','AA','AB一AB','A一A','ABAB','Semua salah','A'],
[25,'Satuan kata untuk baju adalah...?','张','条','件','个','本','C'],
[26,'Dibawah ini yang menyatakan "Sepasang" adalah…?','双','友','又','戏','欢','A'],
[27,'她又聪明又漂亮。Tā yòu cōngmíng yòu piàoliang. Berdasarkan kalimat di atas, carilah rumusnya dibawah ini…?','S+又+adj+又+verb','S+又+verb1+又+verb2','S+adj1+adj2','S+又+adj1+又+adj2','S+adj1+又+adj2+又','D'],
[28,'Penggunaan kata "atau" yang digunakan untuk pertanyaan dibawah ini adalah….?','或者','或啫','还有','还没','还是','E'],
[29,'Kata "或者" digunakan untuk….?','Menyatakan pilihan dalam kalimat pernyataan','Menyatakan kepemilikan','Menyatakan pertanyaan pilihan','Menyatakan waktu','Menyatakan cinta :)','A'],
[30,'我喜欢她......她不喜欢我。Wǒ xǐhuān tā...Tā bù xǐhuān wǒ. Isilah titik titik di atas dengan memilih salah satu yang benar!','不杯','可以','可室','担是','但是','E'],
[31,'A: 你好，这个咖啡价格多少钱? Nǐ hǎo, zhège kāfēi jiàgé duōshao qián? B: 你好，这一杯咖啡是四万六千盾。Nǐ hǎo, zhè yì bēi kāfēi shì Sì wàn liù qiān dùn. Berapa harga 1 gelas kopi pada dialogue tersebut..?','Rp 48.000','Rp 46.000','Rp 36.000','Rp 53.000','Rp 30.800','B'],
[32,'A: 哇，很贵呀，可以便宜吗? Wa, hěn guì ya, kěyǐ piányi ma? B: 对不起，不可以。Duìbuqǐ, bù kěyǐ. Arti dari kata "贵 guì" pada dialogue adalah…?','Mahal','Enak','Murah','Baru','Sedang','A'],
[33,'A: 那有咖啡便宜一点吗？Nà yǒu kāfēi piányi yìdiǎn ma? B: 有, 卡布奇诺更便宜, 一杯二万七千盾。Yǒu, kǎbùqínuò gèng piányi, yì bēi èr wàn qī qiān dùn. Arti dari kata "便宜 piányi" pada dialogue adalah…?','Mahal','Enak','Murah','Baru','Sedang','C'],
[34,'Jika turun hujan dan hendak keluar maka kita harus menggunakan...? (payung)','帽子','雨伞','雨金','鞋子','雨余','B'],
[35,'10.000 + 2.000 =…?','二万一千','一万三千','一万两千','二万两千','一万','C'],
[36,'A: 那有咖啡便宜一点吗？Nà yǒu kāfēi piányi yìdiǎn ma? B: 有, 卡布奇诺更便宜, 一杯二万七千盾。A: 那我点五杯卡布奇诺，给你钱！A: 对，谢谢。Berapa total belanjaan yang harus di bayarnya pada kalimat di atas…?','三十万五千盾','十三万五千盾','二万七千盾','十五万三千盾','三十五万七千盾','B'],
[37,'Find the Pinyin from this sentence "这件衣服质量很好看"','Zhè jiàn yīfu zhǐliàng hěn hǎokàn','Zhè jiān yīfu zhìliàng hěn hǎokàn','Zhè jiàn yīfú zhìliàng hěn hǎokàn','Zhe jian yifu zhiliang hen haokàn','Zhè jiàn yīfu zhìliàng hěn hǎokàn','E'],
[38,'B: 好的,你点五杯卡布奇诺对吗？Hǎo de, nǐ diǎn wǔ bēi kǎbùqínuò duì ma? A: 对，谢谢。Duì, xièxie. Jenis kopi apakah yang di belinya…?','Americano','Espresso','Caffe latte','Macchiato','Cappuccino','E'],
[39,'我想买手机但是很贵, 价格是左右 Rp 9.586.600. Bahasa mandarin dari harga Rp 9.586.600 adalah…?','九百五八万六千六是六百盾','九十五万八千六百六十盾','九千五百八十六万六千盾','九百五十八万六千六百盾','九百万盾','D'],
[40,'Zhang Linghe membeli pensil dengan harga Rp.2500 dan membeli roti seharga Rp.5300. Total belanjaannya adalah…?','二千五百盾','五千三百盾','六千盾','七千八百盾','七万八千盾','D'],
];

$action = $_GET['action'] ?? 'preview';

if ($action === 'rebuild') {
    $pdo->beginTransaction();
    try {
        // 1. Hapus data ujian lama
        $pdo->exec("DELETE FROM exam_participants WHERE exam_id = $examIdLama");
        $pdo->exec("DELETE FROM exam_questions   WHERE exam_id = $examIdLama");
        $pdo->exec("DELETE FROM exams            WHERE id      = $examIdLama");

        // 2. Hapus soal-soal lama di rentang 4084-4158
        $oldRange = implode(',', $soalLamaIds);
        $pdo->exec("DELETE FROM answers          WHERE question_id IN ($oldRange)");
        $pdo->exec("DELETE FROM exam_questions   WHERE question_id IN ($oldRange)");
        $pdo->exec("DELETE FROM questions        WHERE id          IN ($oldRange)");

        // 3. Insert 40 soal baru
        $ins = $pdo->prepare("
            INSERT INTO questions (
                subject_id, type, question_text,
                option_a, option_b, option_c, option_d, option_e,
                correct_answer, points, created_by, created_at
            ) VALUES (?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        $newIds = [];
        foreach ($soalPG as $s) {
            $ins->execute([$subjectId, $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $guruId]);
            $newIds[] = $pdo->lastInsertId();
        }

        // 4. Buat ujian baru
        $token = 'SAT-MAN-XI-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $pdo->prepare("
            INSERT INTO exams (title, category, subject_id, duration, start_time, end_time,
                               token, randomize_questions, exam_type, status, created_by, created_at)
            VALUES ('SAT MANDARIN KELAS XI GENAP', 'reguler', ?, 90,
                    '2026-06-09 07:30:00', '2026-06-09 09:00:00',
                    ?, 0, 'single', 'published', ?, NOW())
        ")->execute([$subjectId, $token, $guruId]);
        $newExamId = $pdo->lastInsertId();

        // 5. Assign soal ke ujian baru
        $insEQ = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, order_num) VALUES (?, ?, ?)");
        foreach ($newIds as $i => $qId) {
            $insEQ->execute([$newExamId, $qId, $i + 1]);
        }

        // 6. Assign peserta lama ke ujian baru
        $insP = $pdo->prepare("INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?, ?)");
        foreach ($pesertaIds as $uid) {
            $insP->execute([$newExamId, $uid]);
        }

        $pdo->commit();
        $success = ['examId' => $newExamId, 'soalIds' => $newIds, 'peserta' => count($pesertaIds)];

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
<title>Rebuild SAT Mandarin XI</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 820px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 22px 26px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
h2 { color: #1e293b; margin-bottom: 12px; font-size: 1.1rem; }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 18px 22px; margin-bottom: 16px; color: #166534; }
.error-box   { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; margin-bottom: 16px; color: #991b1b; }
.warn-box    { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px; color: #92400e; font-size: .9rem; }
table { width: 100%; border-collapse: collapse; font-size: .84rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
tr:nth-child(even) { background: #f8fafc; }
.jwb { font-weight: 700; color: #16a34a; background: #dcfce7; border-radius: 4px; padding: 1px 7px; font-size: .8rem; }
.btn { display: inline-block; padding: 11px 26px; border-radius: 8px; font-weight: 600; font-size: .95rem; text-decoration: none; margin-top: 14px; margin-right: 8px; }
.btn-red   { background: #dc2626; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
.btn-green { background: #16a34a; color: white; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px; }
.info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 15px; }
.info-box label { display: block; font-size: .73rem; color: #64748b; text-transform: uppercase; margin-bottom: 3px; }
.info-box span  { font-size: 1rem; font-weight: 600; color: #1e293b; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  <b style="font-size:1.15rem">✅ Berhasil! Ujian SAT Mandarin XI Genap sudah dibangun ulang.</b><br><br>
  <div style="line-height:2">
    Ujian lama (ID 338) + soal campuran (ID 4084-4158): <b>dihapus</b><br>
    Ujian baru ID: <b><?= $success['examId'] ?></b><br>
    Total soal baru: <b>40 soal</b> (ID <?= min($success['soalIds']) ?>–<?= max($success['soalIds']) ?>)<br>
    Peserta ter-assign: <b><?= $success['peserta'] ?> siswa</b> (kelas XI)
  </div>
  <br>
  <a href="exam/edit/<?= $success['examId'] ?>" class="btn btn-green">→ Edit Ujian (Set Tanggal)</a>
  <a href="exam" class="btn btn-gray">→ Daftar Ujian</a>
</div>

<?php elseif (isset($error)): ?>
<div class="error-box">❌ Error: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview' || isset($error)): ?>
<div class="card">
  <h2>🔄 Rebuild SAT Mandarin Kelas XI Genap</h2>
  <div class="warn-box">
    Script ini akan:<br>
    1. <b>Hapus</b> ujian lama ID 338 + semua assign soal & pesertanya<br>
    2. <b>Hapus</b> soal ID 4084–4158 dari bank soal (soal campuran/tidak lengkap)<br>
    3. <b>Insert ulang</b> 40 soal asli Anda ke bank soal<br>
    4. <b>Buat ujian baru</b> SAT Mandarin XI Genap + assign soal + assign peserta kelas XI
  </div>

  <div class="grid2">
    <div class="info-box"><label>Ujian Lama (dihapus)</label><span>ID 338 — SAT MANDARIN KELAS XI GENAP</span></div>
    <div class="info-box"><label>Ujian Baru (dibuat)</label><span>SAT MANDARIN KELAS XI GENAP</span></div>
    <div class="info-box"><label>Soal Lama (dihapus)</label><span>ID 4084–4158 (75 soal campuran)</span></div>
    <div class="info-box"><label>Soal Baru (dibuat)</label><span>40 soal asli Anda</span></div>
    <div class="info-box"><label>Peserta</label><span><?= count($pesertaIds) ?> siswa kelas XI (dipindah ke ujian baru)</span></div>
    <div class="info-box"><label>Subject</label><span>Bahasa Mandarin (ID <?= $subjectId ?>)</span></div>
  </div>

  <h2 style="margin-bottom:10px">40 Soal yang Akan Dimasukkan</h2>
  <div style="max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px">
  <table>
    <tr><th>#</th><th>Pertanyaan</th><th>Jwb</th></tr>
    <?php foreach ($soalPG as $s): ?>
    <tr>
      <td><?= $s[0] ?></td>
      <td><?= htmlspecialchars(mb_substr($s[1], 0, 85)) ?>...</td>
      <td><span class="jwb"><?= $s[7] ?></span></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>

  <a href="?action=rebuild"
     class="btn btn-red"
     onclick="return confirm('Yakin rebuild? Ujian 338 + soal 4084-4158 akan dihapus permanen dan dibuat ulang.')">
    🔄 REBUILD SEKARANG
  </a>
  <a href="exam" class="btn btn-gray">Batal</a>
</div>
<?php endif; ?>

</div>
</body>
</html>
